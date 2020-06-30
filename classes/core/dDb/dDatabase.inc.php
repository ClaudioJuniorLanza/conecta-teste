<?php
// 21/07/2017 (v2.0)
// * Classe migrou para PDO;
// * Debug melhorado/reformulado
// 

/*************************************************
 * dDatabase
 *
 * Author: Alexandre Tedeschi (d)
 * E-Mail: alexandrebr AT gmail DOT com
 *
 * Documentação rápida:
 * ::start()
 * ::onSqlErrorMailTo      (...)
 * ::onSqlErrorShowOnScreen(true|false)
 *
 * ->setConfig    ($host, $user, $pass, $dbname);
 * ->setCharset   ('utf8')
 * ->singleQuery  ($query)
 * ->singleLine   ($query)
 * ->singleColumn ($query)
 * ->singleResult ($query)
 * ->singleIndex  ($query, $indexC=1)                       (select id¹,nome² from tabela)
 * ->singleIndexV ($query, $indexC=1, $valueC=2)            (select id¹,nome² from tabela)
 * ->singleIndexG ($query, $groupC=1, $indexC=2)            (select grupo¹,id²,nome from tabela)
 * ->singleIndexGV($query, $groupC=1, $indexC=2, $valueC=3) (select grupo¹,id²,nome³ from tabela)
 *************************************************/
class dDatabase
{
    function getVersion()
    {
        return "2.0";
    }

    // Static:
    static $isFatalDead = false;
    static $instance = array();
    static $onErrorMail = "sistema_notifica@imaginacom.com";
    static $onErrorShow = false;
    static $uniqueInstanceId = 1; // Cada nova conexão, este número mudará.
    static $debugId = 1;

    // Private:
    var $h;       // Connection handler
    var $mycolor; // Debug color
    var $config;  // Default connection data (host, user, pass, database, server)
    var $charset; // Default charset (input queries must be in this charset)

    // Public:
    var $instanceId; // Instance id used with ::start()
    var $debug;      // Debug [true or false]
    var $time;       // Cumulative time
    var $queries;    // Queryes counter

    // Public: (Config.)
    var $fetch_method;         // Fetch method to be used: 'array', 'row', 'assoc'.  (Default: 'assoc')
    var $connect_fatal = true; // Cannot connect is a fatal error

    // Private:
    function connect()
    { // Initialize the connection
        if ($this->h) {
            // Already connected, ignore request.
            return true;
        }
        if (!is_array($this->config)) {
            die("dDatabase: Configuration is not set.");
        }
        if ($this->config['server'] != 'mysql') {
            die("dDatabase: Can't handle any engine other than 'mysql'.");
        }

        // PDO or MySQLi? --> PDO.
        $dsn = "mysql:host={$this->config['host']}";
        if ($this->config['database']) {
            $dsn .= ";dbname={$this->config['database']}";
        }
        if ($this->charset) {
            $dsn .= ";charset={$this->charset}";
        }

        try {
            $this->h = new PDO($dsn, $this->config['user'], $this->config['pass']);
        } catch (PDOException $e) {
            if ($this->connect_fatal) {
                dDatabase::$isFatalDead = true;
                echo "<div align='center'>";
                echo "<div style='font: 14px Arial; width: 80%; border: 1px dotted #800; background: #FFC; padding: 15px; text-align: left'>\n";
                echo "<h2>Em manutenção</h2>\n";
                echo "Caro visitante,<br />\n";
                echo "<br />";
                echo "Ocorreu um problema interno em nosso servidor, e seu acesso não pôde continuar.<br />\n";
                echo "Já estamos trabalhando para normalizar a situação, pedimos sua compreensão para o ocorrido.<br />\n";
                echo "<br />";
                echo "Por favor, tente novamente mais tarde.<br />\n";
                echo "<small style='font: 9px Verdana; font-style: italic'>Se o problema persistir por muito tempo, contate um administrador.</small><br />\n";
                if (@$_GET['why'] == 'error') {
                    echo "<br />\n";
                    echo "<b>Error code:</b> {$e->getCode()}.<br />";
                    echo "<b>Message:</b> {$e->getMessage()}<br />\n";
                    echo "<br />\n";
                } else {
                    echo "<!-- why=error -->\r\n";
                }
                echo "</div>";
                echo "</div>";
                die;
            }
        }

        if (dDatabase::$uniqueInstanceId++ == 1) {
            $this->mycolor = "#6a1";
        } elseif (dDatabase::$uniqueInstanceId++ == 2) {
            $this->mycolor = "#17e";
        } else {
            $this->mycolor = "#" . dechex(rand(0, 15)) . dechex(rand(0, 15)) . dechex(rand(0, 15));
        }
        return (bool)$this->h;
    }

    // Static:

    /**
     * @param string $id
     * @return self
     */
    static function start($id = '_default_')
    {
        if (isset(self::$instance[$id])) {
            return self::$instance[$id];
        }

        self::$instance[$id] = new dDatabase;
        self::$instance[$id]->instanceId = $id;
        return self::$instance[$id];
    }

    static function onSqlErrorMailTo($emailAddr)
    {
        dDatabase::$onErrorMail = $emailAddr;
    }

    static function onSqlErrorShowOnScreen($tf)
    {
        dDatabase::$onErrorShow = $tf;
    }

    // Public: (Methods)
    function __construct($id = '_default_')
    {
        if (!isset(self::$instance[$id])) {
            self::$instance[$id] = $this;
        }
    }

    function setConfig($host, $user, $pass, $database = false, $server = 'mysql')
    {
        $this->config = array();
        $this->config['host'] = $host;
        $this->config['user'] = $user;
        $this->config['pass'] = $pass;
        $this->config['database'] = $database;
        $this->config['server'] = $server;
    }

    function setCharset($charset)
    {
        // Supported so far:
        // utf8, latin1
        if (!in_array($charset, array('utf8', 'latin1'))) {
            die("dDatabase: setCharset() - Charset {$charset} not supported. Only utf8 or latin1");
        }
        $this->charset = $charset;
        if ($this->h) {
            // Já conectado, vamos alterar o charset para o solicitado.
            $this->h->query("SET NAMES {$charset}");
        }
    }

    function query($queryStr, $description = false)
    {     // Envia uma query
        $this->connect();

        $queryStr = trim($queryStr);
        $timerStart = $this->realTime();

        $res = $this->h->query($queryStr);
        $timerEnd = $this->realTime() - $timerStart;
        $this->time += $timerEnd;

        $this->queries++;
        if ($this->debug || (!$res && (dDatabase::$onErrorShow || dDatabase::$onErrorMail))) {
            self::$debugId++;
            $debugId = self::$debugId;

            { // Gerar $debugMsg:
                $debugMsg = "";
                $debugMsg .= "<!-- dDbDatabase -->";
                $debugMsg .= "<div style='font: 12px Verdana; margin: 5px 0px; width: 100%'>";
                $debugMsg .= "<div style='background: " . ($res ? '#7E7' : '#E77') . "; color: #003; border: 1px solid #000; border-left: 8px solid {$this->mycolor}; box-sizing: border-box; display: inline-block; max-width: 100%'>";
                $_debugId = "__dDb" . self::$debugId;
                $_writeButton = function ($id, $text) use ($_debugId) {
                    $setA = "var _a=document.getElementById('{$_debugId}-{$id}')";
                    $str = "[<a href='#' style='text-decoration: none; color: #000' ";
                    $str .= "onclick=\"{$setA}; ";
                    $str .= "	var _shouldOpen = (_a.dataset.isOpen !== 'y'); ";
                    $str .= "	_a.dataset.isOpen=_shouldOpen?'y':'n'; ";
                    $str .= "	_a.style.display = (_shouldOpen?'block':'none'); ";
                    $str .= "	_a.style.position='inherit'; ";
                    $str .= "	return false;";
                    $str .= "\"";
                    $str .= "onmouseover=\"{$setA}; if(_a.dataset.isOpen!='y'){ _a.style.display = 'block'; _a.style.position = 'absolute';} \"";
                    $str .= "onmouseout =\"{$setA}; if(_a.dataset.isOpen!='y'){ _a.style.display = 'none';  _a.style.position = 'inherit';} \"";
                    $str .= ">{$text}</a>] ";
                    return $str;
                };

                $_displayStyle = ($res) ? "; display: none" : "; display: block;' data-is-open='y";

                // Speed analisis:
                $_speedStr = "<span style='font-size: 11px' title=\"" . number_format($timerEnd,
                        5) . "s (cumulativo: " . number_format($this->time, 5) . "s)\">";
                if ($timerEnd < 0.0001) {
                    $_speedStr .= "⚡5";
                } elseif ($timerEnd < 0.001) {
                    $_speedStr .= "⚡4";
                } elseif ($timerEnd < 0.01) {
                    $_speedStr .= "⚡3";
                } elseif ($timerEnd < 0.1) {
                    $_speedStr .= "<b style='color: #900'>⚡2</b>";
                } elseif ($timerEnd < 1) {
                    $_speedStr .= "<b style='color: #B00; display: inline-block' title='Slow Query'>⚡1 (" . number_format($timerEnd,
                            3) . "s)</b>";
                } else {
                    $_speedStr .= "<b style='color: #B00; display: inline-block' title='Very Slow Query'>Slow (" . number_format($timerEnd,
                            2) . "s)</b>";
                }
                $_speedStr .= "</span>";

                // Buttons
                $debugMsg .= "<div style='white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 3px'>";
                $debugMsg .= "Query #{$this->queries} | ";
                $debugMsg .= $_writeButton(1, "Query");
                $debugMsg .= $_writeButton(2, "Result");
                $debugMsg .= $_writeButton(3, "Trace");
                $debugMsg .= " {$_speedStr}";
                if ($description) {
                    $debugMsg .= " | <b style='color: #000'>{$description}</b>";
                } else {
                    // Vamos analisar essa query por conta própria, para buscar um padrão.
                    // 1. Tiramos todos os comentários.
                    // 2. Convertemos todas as quebras de linha e tabulações em ESPAÇO
                    // 3. Remove os espaços sobressalentes
                    // 4. Tiramos todos os espaços iniciais e finais
                    $_tmpQuery = $queryStr;
                    $_tmpQuery = preg_replace("/#.+\r?[\n$]/", " ", $_tmpQuery);
                    $_tmpQuery = preg_replace("/[\r\n\t]/", " ", $_tmpQuery);
                    $_tmpQuery = preg_replace("/  +/", " ", $_tmpQuery);
                    $_tmpQuery = trim($_tmpQuery);

                    // Padroniza "select *** from.."
                    $_tmpQuery = preg_replace("/^(select|delete)(.+)(from)/i", "\\1 ... \\3", $_tmpQuery);
                    $_tmpQuery = substr($_tmpQuery, 0, 400);
                    $debugMsg .= " | <b>" . htmlspecialchars($_tmpQuery) . "</b>";
                }
                $debugMsg .= "</div>";

                // Query string
                $_showQS = htmlspecialchars($queryStr);
                $_showQS = preg_replace("/(#.+?(\r?\n|$))/", "<i style='color: #090'>\\1</i>", $_showQS);
                $debugMsg .= "<div id='__dDb" . (self::$debugId) . "-1' style='background: #FFF; padding: 3px; border: 1px solid #888; {$_displayStyle}'>";
                $debugMsg .= "<pre style='display: inline-box; overflow: auto; margin: 0'>{$_showQS}</pre>";
                $debugMsg .= "</div>";

                // Response from server (list OR error)
                $debugMsg .= "<div id='__dDb" . (self::$debugId) . "-2' style='background: #DDA; padding: 3px; {$_displayStyle}'>";
                if (!$res) {
                    $error = $this->error();
                    $error = preg_replace("/near '(.+)' at line/s",
                        "near <span style='background: yellow; display: inline'>&nbsp;\\1&nbsp;</span> at line",
                        $error);
                    $debugMsg .= "<b style='color: #F00'>{$error}</b>\r\n";
                } else {
                    $allData = $res->fetchAll(PDO::FETCH_ASSOC);
                    ob_start();
                    dHelper2::dump($allData);
                    $debugMsg .= ob_get_clean();

                    if ($allData) {
                        // Vamos resetar o cursor/refazer a query apenas se tiver algum retorno (ou seja, resultado de um select, show, etc..).
                        // Caso contrário, poderíamos acabar duplicando inserts.
                        $res->closeCursor();
                        $res = $this->h->query($queryStr);
                    }
                }
                $debugMsg .= "</div>";

                // Debug traceback
                $debugMsg .= "<div id='__dDb" . (self::$debugId) . "-3' style='background: #EEE; padding: 3px; {$_displayStyle}'>";
                $debugMsg .= "<code>";
                $debug = debug_backtrace();
                foreach ($debug as $idx => $info) {
                    $class = isset($info['class']) ? $info['class'] : '<i>(sem classe)</i>';
                    $type = isset($info['type']) ? $info['type'] : '<i>-</i>';
                    $funct = isset($info['function']) ? $info['function'] : '<i>(sem função)</i>';

                    $debugMsg .= "<font color='#669900'>#{$idx}</font> ";
                    $debugMsg .= "<font color='#CC6600'><b>{$class}</b>{$type}{$funct}</font>";
                    $debugMsg .= "<font color='#000099'>(";
                    $first = true;
                    if (isset($info['args'])) {
                        foreach ($info['args'] as $arg) {
                            if (!$first) {
                                $debugMsg .= ", ";
                            } else {
                                $first = false;
                            }

                            if (is_object($arg)) {
                                $arg = "<i>object <b>" . get_class($arg) . "</b></i>";
                            } elseif (is_array($arg)) {
                                $arg = '(' . print_r($arg, true) . ')';
                            } elseif ($arg === null) {
                                $arg = '(NULL)';
                            } elseif ($arg === false) {
                                $arg = '(FALSE)';
                            } elseif ($arg === true) {
                                $arg = '(TRUE)';
                            } elseif (is_array($arg)) {
                                $arg = '(' . print_r($arg, true) . ')';
                            }

                            $debugMsg .= "<span title=\"$arg\" style='background: #ddd'>...</span>";
                        }
                    }
                    $debugMsg .= ")</font> ";
                    if (!isset($info['file'])) {
                        $info['file'] = "*função anonima*";
                    }
                    if (!isset($info['line'])) {
                        $info['line'] = 'n/a';
                    }
                    $debugMsg .= "(<span title='{$info['file']}' style='background: #E8E8E8'>" . basename($info['file']) . "</span>:{$info['line']})";
                    $debugMsg .= "<br />";
                }

                if ($info['file'] && $info['line'] && is_readable($info['file'])) {
                    $file = file($info['file']);
                    $line = highlight_string("<?pHp " . trim($file[$info['line'] - 1]), true);
                    $line = str_replace("&lt;?pHp&nbsp;", "", $line);
                    $debugMsg .= "&nbsp; &nbsp; {$line}\r\n";
                }

                $debugMsg .= "</code>";
                $debugMsg .= "</div>";
                $debugMsg .= "</div>";
                $debugMsg .= "</div>";
                $debugMsg .= "<!-- End database debug code -->";
            }

            $error = $this->error();
            if ($this->debug || ($error && dDatabase::$onErrorShow)) {
                echo $debugMsg;
            } elseif ($error && dDatabase::$onErrorMail) {
                $useDomain = @$_SERVER['SERVER_NAME'];
                if (!$useDomain) {
                    $useDomain = @$_SERVER['HTTP_HOST'];
                }
                if (!$useDomain) {
                    $useDomain = preg_replace("/.+@/", "", @$_SERVER['SERVER_ADMIN']);
                }
                if (!$useDomain) {
                    $useDomain = "imaginacom.com";
                }
                $useDomain = preg_replace("/^www./i", "", $useDomain);

                mail(dDatabase::$onErrorMail, "[HIGH] MySQL Error Warning",
                    "{$debugMsg}<hr />" .
                    "<pre>" . print_r($_SERVER, true) . "</pre>",
                    "From: noreply@{$useDomain}\r\n" .
                    "Content-Type: text/html; charset=iso-8859-1"
                );
            }
        }
        return $res;
    }

    function easySelect($select, $from, $where = false, $limit = false, $orderby = false, $description = false)
    {           // Formata uma query utilizando os parâmetros enviados
        $query = "SELECT\r\n\t" . implode(",\r\n\t", $select) . "\r\n";
        $query .= "FROM\r\n\t" . implode(",\r\n\t", $from) . "\r\n";
        if ($where) {
            $query .= "WHERE\r\n\t(" . implode(") and\r\n\t(", $where) . ")\r\n";
        }
        if ($orderby) {
            $query .= "ORDER BY $orderby\r\n";
        }
        if ($limit) {
            $query .= "LIMIT $limit";
        }

        return $this->singleQuery($query, $description);
    }

    function singleResult($query, $description = false)
    { // Realiza uma query, e retorna a primeira coluna do primeiro valor
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        $ret = $res->rowCount() ?
            $res->fetchColumn() :
            false;

        $res->closeCursor();
        return $ret;
    }

    function singleLine($query, $description = false)
    { // Realiza uma query, e retorna o primeiro resultado
        // Faça suas queryes com 'limit 1' no final
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        $ret = ($res->rowCount()) ?
            $this->fetch($res) :
            false;

        $res->closeCursor();
        return $ret;
    }

    function singleColumn($query, $description = false)
    { // Realiza uma query, e retorna a primeira coluna
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }
        if (!$res->rowCount()) {
            $res->closeCursor();
            return false;
        }

        $ret = array();
        foreach ($res as $row) {
            $ret[] = current($row);
        }
        return $ret;
    }

    function singleQuery($query, $description = false)
    { // Realiza uma query, e retorna um array com todos os resultados
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        if ($this->fetch_method == "array") {
            $ret = $res->fetchAll(PDO::FETCH_NAMED);
        } elseif ($this->fetch_method == "row") {
            $ret = $res->fetchAll(PDO::FETCH_NUM);
        } else {
            $ret = $res->fetchAll(PDO::FETCH_ASSOC);
        }

        $res->closeCursor();

        return $ret;
    }

    function singleIndex($query, $index = false, $description = false)
    { // Realiza uma query, e retorna um array cujos índice são automáticos
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        $ret = array();
        while ($entry = $this->fetch($res)) {
            if ($index === false) {
                $index = array_keys($entry);
                $index = $index[0];
            }
            if (!isset($entry[$index])) {
                echo "<b>dDatabase-debug:</b> Índice '<b>$index</b>' não foi encontrado - retornando vazio.<br />";
                break;
            }
            $ret[$entry[$index]] = $entry;
        }
        if ($res->rowCount()) {
            $res->closeCursor();
        }

        return $ret;
    }

    function singleIndexV($query, $index = false, $value = false, $description = false)
    { // Realiza uma query, e retorna o valor cujo índice é automático
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        $ret = array();
        while ($entry = $this->fetch($res)) {
            if ($index === false) {
                $index = array_keys($entry);
                $index = $index[0];
            }
            if ($value === false) {
                $value = array_keys($entry);
                $value = $value[1];
            }
            if (!isset($entry[$index])) {
                echo "<b>dDatabase-debug:</b> Índice '<b>$index</b>' não foi encontrado - retornando vazio.<br />";
                break;
            }
            $ret[$entry[$index]] = $entry[$value];
        }
        if ($res->rowCount()) {
            $res->closeCursor();
        }

        return $ret;
    }

    function singleIndexG($query, $group = false, $index = false, $description = false)
    { // Retorna um array multi-assoc agrupado.
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        $ret = array();
        while ($entry = $this->fetch($res)) {
            if ($group === false) {
                $group = array_keys($entry);
                $group = $group[1];
            }
            if ($index === false) {
                $index = array_keys($entry);
                $index = $index[0];
            }

            if (!isset($entry[$group])) {
                echo "<b>dDatabase-debug:</b> Coluna <b>$group</b>' não foi encontrada para agrupar - retornando vazio.<br />";
                break;
            }
            if (!isset($entry[$index])) {
                echo "<b>dDatabase-debug:</b> Índice '<b>$index</b>' não foi encontrado - retornando vazio.<br />";
                break;
            }

            $ret[$entry[$group]][$entry[$index]] = $entry;
        }
        if ($res->rowCount()) {
            $res->closeCursor();
        }

        return $ret;
    }

    function singleIndexGV($query, $group = false, $index = false, $value = false, $description = false)
    { // Retorna um array multi-assoc agrupado, mas com valor único
        $res = $this->query($query, $description);
        if (!$res) {
            return false;
        }

        $ret = array();
        while ($entry = $this->fetch($res)) {
            if ($group === false) {
                $group = array_keys($entry);
                $group = $group[1];
            }
            if ($index === false) {
                $index = array_keys($entry);
                $index = $index[0];
            }
            if ($value === false) {
                $value = array_keys($entry);
                $value = $value[2];
            }

            if (!isset($entry[$group])) {
                echo "<b>dDatabase-debug:</b> Coluna <b>$group</b>' não foi encontrada para agrupar - retornando vazio.<br />";
                break;
            }
            if (!isset($entry[$index])) {
                echo "<b>dDatabase-debug:</b> Índice '<b>$index</b>' não foi encontrado - retornando vazio.<br />";
                break;
            }

            $ret[$entry[$group]][$entry[$index]] = $entry[$value];
        }
        if ($res->rowCount()) {
            $res->closeCursor();
        }

        return $ret;
    }

    function singleObjects($query, $class, $description = false)
    {
        trigger_error("Metodo descontinuado. Para continuar usando, edite dDatabase.inc.php.");
        return false;

        $db = dDatabase::start();
        $dDbRow3 = is_subclass_of($class, 'dDbRow3');

        $ret = array();
        $ids = $db->singleColumn($query, $description);
        if ($ids) {
            foreach ($ids as $id) {
                if ($dDbRow3) {
                    $tmp = $class::load($id);
                } else {
                    $tmp = new $class;
                    $tmp->loadFromDatabase($id);
                    $ret[] = $tmp;
                }
            }
        }
        return $ret;
    }

    function singleIndexObjects($query, $class, $description = false)
    {
        trigger_error("Metodo descontinuado. Para continuar usando, edite dDatabase.inc.php.");
        return false;
        $db = dDatabase::start();
        $dDbRow3 = is_subclass_of($class, 'dDbRow3');

        $ret = array();
        $ids = $db->singleColumn($query, $description);
        if ($ids) {
            foreach ($ids as $id) {
                if ($dDbRow3) {
                    $ret[$id] = $class::load($id);
                } else {
                    $ret[$id] = new $class;
                    $ret[$id]->loadFromDatabase($id);
                }
            }
        }
        return $ret;
    }

    function close()
    {
        if ($this->h) {
            $this->h = null;
        }
        return true;
    }

    function lastId()
    {             // Retorna o último ID adicionado
        return $this->h->lastInsertId();
    }

    function affected()
    {           // Retorna myddb_affected_rows()
        return $this->h->rowCount();
    }

    function fetch($res)
    {          // Recupera as colunas da linha atual (utiliza o $this->fetch_method)
        if ($this->fetch_method == "assoc") {
            return $res->fetch(PDO::FETCH_ASSOC);
        }

        if ($this->fetch_method == "array") {
            return $res->fetch(PDO::FETCH_NAMED);
        }

        if ($this->fetch_method == "row") {
            return $res->fetch(PDO::FETCH_NUM);
        }

        return $res->fetch(PDO::FETCH_ASSOC);
    }

    function realTime()
    {           // Retorna o time atual em float, com precisão de microsegundos
        return microtime(true);
    }

    function error()
    {              // Retorna a string do último erro ocorrido, se houver. Senão, retorna FALSE.
        if (!$this->h) {
            return "->h não foi iniciado.";
        }

        $error = $this->h->errorInfo();
        return $error ? $error[2] : false;
    }

    function errorNo()
    {
        if (!$this->h) {
            return -999;
        }
        $error = $this->h->errorInfo();
        return $error ? $error[1] : false;
    }

    function numRows($res)
    {
        return $res ? $res->rowCount() : 0;
    }

    function freeResult($res)
    {
        return $res->closeCursor();
    }

    function __dump($maxDepth = false, $_printedUids = array())
    {
        echo "<table style='border: 1px solid #080; margin: -2px' cellspacing='0'>";
        echo "	<tr valign='top'>";
        echo "		<td colspan='2' bgcolor='#FFFF99' style='position: relative'>";
        echo "			<b>dDatabase</b>";
        if ($this->instanceId) {
            echo "<i>::start('{$this->instanceId}')</i>";
        }
        echo "		</td>";
        echo "	</tr>";
        echo "</table>";
    }
}
