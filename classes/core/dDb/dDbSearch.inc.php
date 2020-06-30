<?php
// 02/05/14 (v.2.8)
// + Novo parâmetro setClass(tableName, className, aliasName[, asVirtual]),
//   para tornar a classe compatível com dDbRow3.
// 18/09/12 (v.2.7.8)
// + Novo parametro 'raw' em setOrderBy, para permitir funcoes ali.
// * addWhere foi otimizado para ficar mais permissivo a erros AND e OR.
// * Otimizacao: getPagesTotal() fazia e query duas vezes para retornar.
// 01/09/12 (v.2.7.7)
// * Setfilter teve ser CORE alterado, para ficar mais compativel com addWhere e
//   mais seguro em relação a queries terminando com AND ou OR.
// * Suporte a PHP4 removido (construtor renomeado para __construct()).
// 30/12/11 (v.2.7.6)
// * Bugfix da última atualização, sempre retornava UM resultado e UMA página.
// * Bugfix do setFilter com paginação. O padrão foi alterado de HAVING para WHERE.
// 26/12/11
// * Otimização no select count(*) em conjunto com paginação
// 04/02/11
// * Bug de NOTICE com setFilter, se utilizados campos não definidos previamente.
// * setFilter revisado e comentado
// 11/01/11
// * Bug com setFilter e useQuotes - Duplicando as aspas
// 29/04/10
// + Novo método: performObj(class)
// 07/12/09
// * setFilterWhere agora busca itens dentro do addRawSelect.
// 17/11/09
// ^ Atualizada performance na paginação com group by. Nenhuma diferença visível.
// * setFilter() agora funciona com paginação e group by.
// 28/09/09 (v.2.7.5)
// - setFilter continua passando parâmetros no setHaving
// - Divisão dos filtros:
//   setFilterWhere
//   setFilterHaving
//   Padrão para setFilter é o "having".
// 03/08/09
// - Agora é possível utilizar addFTable com tabelas derivadas. Exemplo:
//   addFTable('(select id,cli_id) as deriv', 'cor_id', 'id');
// 06/07/09
// - Nova função: setHaving()
// - Função activatePagination(0) desativa a paginação
// - setFilter agora passa os parâmetros no Having()
// - Diversos bugfix quando usando grupos (quanto à paginação). Exigirá muitos testes!
// 07/09/08
// - Função getPage() agora funciona mesmo sem a paginação ativada (sempre 1)
// 03/04/08
// - Adicionado parâmetro $match_phrase(true) no método setFilter()
// 23/10/07
// - Novos modifiers: unserialize, unserialize_text
// 10/10/07
// - Novo método: dumpObject(dDbRow $object);
// 29/05/07 (v2.7.4)
// - Novos métodos: getGroupBy, getOrderBy e getLimit()
// 04/05/07
// - Resolvido bug com comentários no debug, visto no firefox.
// 14/04/07
// - writeResultsStr agora tem padrão em PT-BR, e funciona mesmo sem paginação ativada.
// 05/02/07
// - Novo método writeResultsStr($text = 'Showing #-# of #.')
// 29/06/06
// - Corrigido método writePagination(), que estava duplicando o GET da paginação,
//   e escrevendo ?&page= ao invés de ?page=
// 26/06/06 (v2.7.3)
// - Adicionado método setFilter(phrase, fields) para busca em várias tabelas ao mesmo tempo
// 12/03/06
// - Adicionado atributo 'useQuotes'
// 03/01/06
// - Corrigido erro de NOTICE no antigo sistema de addWhere(aliasName, valor)
// 27/12/05
// - Corrigido um erro de level NOTICE: "Use of undefined constant primaryIndex"
// 08/11/05 (v2.7.2)
// - Corrigida diferença entre '' e false/null na query
// 28/11/05
// - Corrigido problema de MODs com valores númericos '0'
// 29/10/05
// - Adicionado método getPage() retornando a página atual
// 10/10/05
// - Adicionado método reset() para realizar várias buscas com a mesma instância
// 15/09/05
// - Corrigido problema de MODs com valores vazios
// 11/09/05
// - Adicionado método: getResultsTotal([refresh?])
// 07/09/05
// - Corrigido bug com Array na QueryString nas funções Write*
// 11/08/05
// - Arrumado (definitivamente?) o problema com addWhere. Liberado addWhere(raw_where)
// 26/07/05
// - Adicionado auto-debug via GET
// 25/07/05
// - Corrigida paginação com FCount (meio gambiarra)
// - Nenhuma query é realizada se não houver nada na paginação
// 16/07/05
// - Adicionado novo str_mod: 'function=[fff]', que chama diretamente a função [fff]

/****************************************************
 * dDbSearch 2
 *
 * This class was written to provide users who don't like
 * much SQL Syntax, to easily interact with databases,
 * including foreign keys, paginations, and similars.
 *
 * If you want to have full power, use together with the
 * dDbRow class, a class that allows you to easily create,
 * edit and delete a specific row.
 *
 * To do list:
 * - Database error handling
 *
 * Author: Alexandre Tedeschi (d)
 * E-Mail: alexandrebr # gmail.com (MSN as well)
 * ICQ#:   34715587
 * Londrina - PR / Brazil
 *
 * Public attributes:
 * - debug           (false/true)
 *
 * Public methods:
 * - setTable        ($tableName)
 *
 * Str modifiers:
 * - limit=30
 * - date=d/m/Y
 * - upper
 * - lower
 * - function=strip_tags
 ****************************************************/

/** Enable class debug via query string **/
if (isset($_GET["dDbSearch-debug"])) {
    setcookie("dDbSearch-debug", $_GET["dDbSearch-debug"], false, '/');
    header("Location: $_SERVER[PHP_SELF]");
    die;
}

class dDbSearch
{
    function getVersion()
    {
        return "2.8";
    }

    var $debug;

    var $mainTable;
    var $ffTables;
    var $fieldProps;
    var $useQuotes;
    var $searchFilter;

    var $pagination; # dDbSearch only
    var $strings;    # dDbSearch only

    var $db;

    function __construct($db)
    {
        $this->setDatabaseClass($db);
        $this->useQuotes = '';
        $this->queryProps['groupBy'] =
        $this->queryProps['orderBy'] =
        $this->queryProps['having'] =
        $this->queryProps['limit'] = '';
        $this->queryProps['where'] =
        $this->ffTables = array();
        $this->queryProps['orderByRaw'] = false;
        if (isset($_COOKIE['dDbSearch-debug']) && $_COOKIE['dDbSearch-debug'] == date('d')) {
            $this->debug = 1;
        }
    }

    function setDatabaseClass($object)
    {
        if (!$object && isset($GLOBALS['db'])) {
            $object = &$GLOBALS['db'];
        }
        if (strtolower(get_class($object)) != 'ddatabase') {
            echo "<font color='#FF0000'>Invalid database class. dDbSearch won't work as expected.</font><br>" . get_class($object);
        }
        $this->db = &$object;
    }

    function dumpObject($object)
    {
        $this->mainTable = $object->mainTable;
        echo "<pre>";
        echo "<b>Dumping all fields from object " . get_class($object) . ".</b>\r\n";
        echo "\$s->setTable('{$this->mainTable['tableName']}');\r\n";
        if ($object->ffTables) {
            foreach ($object->ffTables as $tableName => $Properties) {
                echo "\$s->addFTable('\$tableName', \'{$Properties['aliasSource']}\', \'{$Properties['fieldTarget']}\');\r\n";
            }
        }
        $a = $b = array();
        foreach ($object->fieldProps as $aliasName => $Properties) {
            $a[] = $aliasName;
            $b[] = $Properties['fieldName'];
        }
        $a = implode(",", $a);
        $b = implode(",", $b);

        if ($a == $b) {
            echo "\$s->addField('$a');\r\n";
        } else {
            echo "\$s->addField('$a', '$b');\r\n";
        }

        echo "</pre>";
        die;
    }

    // Table definition
    function setTable($tableName, $primaryIndex = 'id')
    {
        $this->mainTable['tableName'] = $tableName;
        $this->mainTable['primaryIndex'] = $primaryIndex;
    }

    function addFTable($tableName, $aliasSource, $fieldTarget = 'id', $extraOn = false, $joinMode = 'left')
    {
        $realTable = false;
        // É uma tabela derivada
        if ($tableName[0] == '(') {
            $lastIdx = false;
            for ($k = strlen($tableName) - 1; $k > 1; $k--) {
                if ($tableName[$k] == ')') {
                    $lastIdx = $k;
                    break;
                }
            }
            if (!$lastIdx) {
                die("Falha crítica: Erro na tabela derivada.");
            }

            $realTable = substr($tableName, 0, $lastIdx + 1);
            $tableName = substr($tableName, $lastIdx + 1);
            $tableName = str_ireplace(" as ", "", $tableName);
        } // Possui alias
        elseif (stripos($tableName, ' as ')) {
            $parts = explode(' as ', $tableName);
            $realTable = $parts[0];
            $tableName = $parts[1];
        }

        $this->ffTables[$tableName]['aliasSource'] = $aliasSource;
        $this->ffTables[$tableName]['fieldTarget'] = $fieldTarget;
        $this->ffTables[$tableName]['extraOn'] = $extraOn;
        $this->ffTables[$tableName]['joinMode'] = $joinMode;
        $this->ffTables[$tableName]['fieldAliases'] = array();
        $this->ffTables[$tableName]['realTable'] = $realTable;
        $this->ffTables[$tableName]['className'] = false;
        $this->ffTables[$tableName]['aliasName'] = false;
    }

    function addField($aliasNames, $fieldNames = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $fieldNames = explode(",", $fieldNames);
        foreach ($aliasNames as $idx => $aliasName) {
            $fieldName = (isset($fieldNames[$idx]) && $fieldNames[$idx]) ? $fieldNames[$idx] : $aliasName;
            $this->fieldProps [$aliasName]['isForeign'] = false;
            $this->fieldProps [$aliasName]['sqlField'] = "{$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes}.{$this->useQuotes}$fieldName{$this->useQuotes}";
            $this->fieldProps [$aliasName]['fieldName'] = $fieldName;
        }
    }

    function addFField($tableName, $aliasNames, $fieldNames = false)
    {
        /** Adicionar uma verificação de estrutura, com 'mode' **/
        $aliasNames = explode(",", $aliasNames);
        $fieldNames = explode(",", $fieldNames);

        foreach ($aliasNames as $idx => $aliasName) {
            $fieldName = (isset($fieldNames[$idx]) && $fieldNames[$idx]) ? $fieldNames[$idx] : $aliasName;
            if ($this->ffTables[$tableName]['className']) {
                $aliasName = "{$tableName}_{$aliasName}";
            }

            $this->ffTables[$tableName]['fieldAliases'][] = $aliasName;
            $this->fieldProps [$aliasName]['isForeign'] = true;
            $this->fieldProps [$aliasName]['sqlField'] = "{$this->useQuotes}{$tableName}{$this->useQuotes}.{$this->useQuotes}{$fieldName}{$this->useQuotes}";
            $this->fieldProps [$aliasName]['tableName'] = $tableName;
            $this->fieldProps [$aliasName]['fieldName'] = $fieldName;
        }
    }

    function setFExtraOn($tableName, $extraOn)
    {
        $this->ffTables[$tableName]['extraOn'] = $extraOn;
    }

    function setJoinMode($tableName, $joinMode)
    {
        $this->ffTables[$tableName]['joinMode'] = $joinMode;
    }

    function setClass($tableName, $className, $aliasName, $setVirtual = false)
    {
        // ->setClass('ec_categorias', 'dEcCategoria', 'categObj')
        $this->ffTables[$tableName]['className'] = $className;
        $this->ffTables[$tableName]['aliasName'] = array($aliasName, $setVirtual);
    }

    /** dDbSearch only **/
    function reset()
    {
        $this->queryProps['groupBy'] =
        $this->queryProps['orderBy'] =
        $this->queryProps['limit'] = '';
        $this->queryProps['where'] =
        $this->mainTable =
        $this->fieldProps =
        $this->pagination =
        $this->strings =
        $this->ffTables = array();
        $this->searchFilter = false;
    }

    function addRawSelect($aliasName, $expression = false)
    {
        if (!$expression) {
            $expression = $aliasName;
            $aliasName = microtime();
            $this->fieldProps[$aliasName]['ignoreAlias'] = true;
        }
        $this->fieldProps[$aliasName]['isForeign'] = false;
        $this->fieldProps[$aliasName]['sqlField'] = $expression;
        $this->fieldProps[$aliasName]['fieldName'] = '*****';
    }

    function addFCount($aliasName, $fTable, $fField, $primaryIndex = false)
    {
        if (!$primaryIndex) {
            $primaryIndex = $this->mainTable['primaryIndex'];
        }
        if (!isset($this->ffTables[$fTable])) {
            $this->addFTable($fTable, $this->mainTable['tableName'] . ".$primaryIndex", $fField);
        }
        $this->addRawSelect($aliasName, "count($fTable.$fField)");
        $this->setGroupBy($this->mainTable['tableName'] . ".$primaryIndex");
    }

    // Class features
    function activateSorting($queryString = 'sortby')
    {
        $this->strings['orderby'] = $queryString;
        if (isset($_GET[$queryString])) {
            $this->setOrderBy($_GET[$queryString]);
        }
    }

    function activatePagination($perPage = 15, $queryString = 'page')
    {
        if (!$perPage) {
            $this->pagination = array();
        } else {
            $this->strings['page'] = $queryString;
            if ($perPage != $this->pagination['perPage']) {
                $this->pagination['results'] = array();
            }
            $this->pagination['perPage'] = $perPage;
            if (isset($_GET[$queryString])) {
                $this->setPage(intval($_GET[$queryString]));
            } else {
                $this->setPage(1);
            }
        }
    }

    // Query properties
    function setFilter($values, $aliasNames, $match_phrase = true, $inHaving = true)
    {
        if (is_string($aliasNames)) {
            $aliasNames = explode(",", $aliasNames);
        }

        $values = $match_phrase ?
            array($values) :
            explode(" ", $values);

        // Bugfix:
        //   Se tiver paginação, não pode ser $inHaving, tem que ser via Where.
        if (isset($this->pagination['perPage'])) {
            $inHaving = false;
        }

        $this->searchFilter = array(
            'values' => $values,
            'aliasNames' => $aliasNames,
            'inHaving' => $inHaving
        );
    }

    function setFilterHaving($values, $aliasNames, $match_phrase = true)
    {
        return $this->setFilter($values, $aliasNames, $match_phrase, true);
    }

    function setFilterWhere($values, $aliasNames, $match_phrase = true)
    {
        return $this->setFilter($values, $aliasNames, $match_phrase, false);
    }

    function _applyFilter()
    {
        if (!$this->searchFilter) {
            return false;
        }
        extract($this->searchFilter);

        $orStack = array();
        $andStack = array();
        foreach ($values as $idx => $value) {
            foreach ($aliasNames as $aliasName) {
                $field = false;
                if (!isset($this->fieldProps[$aliasName])) {
                    // Alias was not defined by addField, addFField, addFCount nor addRawSelect.
                    // So, let's just pass it in RAW mode to SQL.

                    // Ex: select ... where (name like '%doe%')
                    $field = "{$this->useQuotes}{$aliasName}{$this->useQuotes}";
                } elseif ($this->fieldProps[$aliasName]['fieldName'] == '*****') {
                    if ($inHaving) {
                        // If in 'Having' clause, just ignore it, as it would result in a invalid query.
                        // Ex: select ... having(now() like '%foo%') is invalid.
                    } else {
                        // If in 'Where'  clause, pass it directly to query string.
                        // Ex: select ... where (now() like '%foo%')
                        $orStack[] = "{$this->fieldProps[$aliasName]['sqlField']} like '%" . addslashes($value) . "%'";
                    }
                    continue;
                } else {
                    if ($inHaving) {
                        // Use the raw aliasName
                        // Ex: select ... having (name like '%doe%')
                        $field = "{$this->useQuotes}{$aliasName}{$this->useQuotes}";
                    } else {
                        // Use the internal fieldName. (ex: tablename.title)
                        // Ex: select ... where (table.name like '%doe%')
                        $field = $this->fieldProps[$aliasName]['sqlField'];
                    }
                }

                if (!$field) {
                    die("dDbSearch - Internal failure. setFilter(). Cannot detect FIELD to compare. Please review class code.");
                }
                $orStack[] = "{$field} like '%" . addslashes($value) . "%'";
            }
            $andStack[] = "  (" . implode(" OR ", $orStack) . ")";
            $orStack = array();
        }
        $sql = implode(" AND ", $andStack);

        if ($inHaving) {
            if ($this->queryProps['having']) {
                if (!preg_match("/(and|or)$/i", trim($this->queryProps['having']))) {
                    $this->queryProps['having'] .= " AND";
                } else {
                    $this->queryProps['having'] .= " /* Ignoring 'AND' for _applyFilter, because it was already there. */ ";
                }
            }
            $this->queryProps['having'] .= $sql;
        } else {
            $this->addWhere($sql);
        }
    }

    function addWhere($aliasName, $value = null, $eq = "=", $and_or = 'and')
    {
        if ($value == null) { // If only one argument, use it as RAW
            $this->queryProps['where'][] = $aliasName;
        } else {

            $this->queryProps['where'][] = array(
                'field' => $aliasName,
                'eq' => $eq,
                'value' => $value,
                'and_or' => $and_or
            );
        }
    }

    function setHaving($having_expr)
    {
        $this->queryProps['having'] = $having_expr;
    }

    function setGroupBy($fieldName)
    {
        $this->queryProps['groupBy'] = $fieldName;
    }

    function setOrderBy($field, $raw = false)
    {
        $this->queryProps['orderBy'] = $field;
        $this->queryProps['orderByRaw'] = $raw;
    }

    function setLimit($limit)
    {
        $this->queryProps['limit'] = $limit;
    }

    function getGroupBy()
    {
        if (isset($this->queryProps['groupBy']) && $this->queryProps['groupBy']) {
            return $this->queryProps['groupBy'];
        }
        return false;
    }

    function getOrderBy()
    {
        if (isset($this->queryProps['orderBy']) && $this->queryProps['orderBy']) {
            return $this->queryProps['orderBy'];
        }
        return false;
    }

    function getLimit()
    {
        if (isset($this->queryProps['limit']) && $this->queryProps['limit']) {
            return $this->queryProps['limit'];
        }
        return false;
    }

    // Pagination
    function setPage($page_n)
    {
        $this->pagination['page'] = ($page_n > 0) ? $page_n : 1;
    }

    function writePagination(
        $before = 5,
        $after = 5,
        $not_selected = array('[', ']'),
        $selected = array('(', ')'),
        $minus_plus = array("&laquo;&laquo;", "&raquo;&raquo;"),
        $between = '&nbsp;'
    ) {
        if (!isset($this->pagination['perPage'])) {
            return $this->debug ?
                "<b>Pagination is not enabled</b>" :
                "";
        }

        $page = &$this->pagination['page'];
        $page = ($page < 1) ? 1 : $page;
        $page = ($page > $this->getPagesTotal()) ? $this->getPagesTotal() : $page;
        $pagesTotal = $this->getPagesTotal();

        // Prepare to write the links
        $link = ($_SERVER["QUERY_STRING"] ? "$_SERVER[QUERY_STRING]" : "");
        $link = str_replace(array(
            $this->strings['page'] . "=$page&",
            "&" . $this->strings['page'] . "=$page",
            $this->strings['page'] . "=$page",
        ), "", $link);

        if ($link) {
            $link = "?$link&{$this->strings['page']}=";
        } else {
            $link = "?{$this->strings['page']}=";
        }

        $link = $_SERVER['PHP_SELF'] . $link;
        // End of preparation

        if ($page - $before < 1) {
            $after += ($before - $page + 1);
            $before -= ($before - $page + 1);
        }
        if ($page + $after > $pagesTotal) {
            $before += ($page + $after) - $pagesTotal;
            $after -= ($page + $after) - $pagesTotal;
        }

        $ret = "";
        if ($page - $before > 1) {
            $ret .= "<a href='$link" . "1'>$minus_plus[0]</a>$between";
        }
        for ($x = ($page - $before > 0 ? ($page - $before) : 1); $x < ($page); $x++) {
            $ret .= "<a href='$link$x'>$not_selected[0]$x$not_selected[1]</a>$between";
        }
        $ret .= $selected[0] . $page . $selected[1];
        for ($x = $page + 1; $x <= ($page + $after); $x++) {
            $ret .= "$between<a href='$link$x'>$not_selected[0]$x$not_selected[1]</a>";
        }
        if ($page + $after < $pagesTotal) {
            $ret .= "$between<a href='$link$pagesTotal'>$minus_plus[1]</a>";
        }

        return $ret;
    }

    function getPage()
    {
        return isset($this->pagination['page']) ?
            $this->pagination['page'] :
            1;
    }

    function getPagesTotal($refresh = false)
    {
        if (!isset($this->pagination['perPage']) || !$this->pagination['perPage']) {
            // Paginação desativada.
            return intval($this->getResultsTotal($refresh)) ?
                1 : // Se houver resultados, uma página.
                0; // Caso contrário, nenhuma página.
        }

        // A paginação não está desativada.
        return ceil($this->getResultsTotal($refresh) / $this->pagination['perPage']);
    }

    function getResultsTotal($refresh = false)
    {
        $db = &$this->db;

        // Há algo em cache?
        if (!$refresh && isset($this->pagination['totalResults'])) {
            return $this->pagination['totalResults'];
        }

        // Não havia nada em cache, vamos re-calcular.
        $qFrom = array($this->mainTable['tableName']);
        foreach ($this->ffTables as $tableName => $tableProps) {
            $tmp = "{$tableProps['joinMode']} join ";
            $tmp .= ($tableProps['realTable']) ?
                "{$tableProps['realTable']} as {$tableName}" :
                "{$tableName}";
            $tmp .= " ON ";
            $tmp .= (isset($this->fieldProps[$tableProps['aliasSource']]) ?
                $this->fieldProps[$tableProps['aliasSource']]['sqlField'] :
                $tableProps['aliasSource']);
            $tmp .= " = ";
            $tmp .= "$tableName.$tableProps[fieldTarget] ";
            $tmp .= $tableProps['extraOn'] ? $tableProps['extraOn'] : '';
            $qFrom[] = $tmp;
        }
        $qWhere = $this->getWhereList();;

        $q = "SELECT\tsum(co) FROM (\r\n";
        $q .= "\tSELECT\tcount(*) as co\n";
        $q .= "\tFROM \r\n\t\t" . join("\n\t\t", $qFrom) . "\n";
        if (sizeof($qWhere)) {
            $q .= "\tWHERE \r\n\t" . join("\n\t\t", $qWhere) . "\n";
        }

        if ($this->queryProps['groupBy']) {
            $q .= "GROUP BY " . addslashes($this->queryProps['groupBy']) . "\n";
        }
        if ($this->queryProps['having']) {
            $q .= "HAVING( " . $this->queryProps['having'] . " )\n";
        }
        $q .= ") as sub_table";

        $this->pagination['totalResults'] = $db->singleResult($q);
        return $this->pagination['totalResults'];
    }

    function writeSortLink($orderBy)
    {
        if (!isset($this->strings['orderby'])) {
            return "#";
        }

        // Prepare to write the links
        $link = "$_SERVER[PHP_SELF]?" . ($_SERVER["QUERY_STRING"] ? "$_SERVER[QUERY_STRING]" : "");
        $link = preg_replace("/{$this->strings['orderby']}=.+?\&/", "", $link);
        $link = preg_replace("/\&{$this->strings['orderby']}=.+/", "", $link);
        $link .= "&{$this->strings['orderby']}=";
        // End of preparation

        return ($this->queryProps['orderBy'] == $orderBy) ?
            $link . $orderBy . " desc" :
            $link . $orderBy;
    }

    function writeResultsStr($text = 'Exibindo #-# de #')
    {
        $str = explode("#", $text);

        if ($this->getResultsTotal()) {
            $total = $this->getResultsTotal();
            if (!isset($this->pagination['perPage'])) {
                $start = 1;
                $end = $total;
            } else {
                $start = ($this->pagination['perPage'] * ($this->getPage() - 1)) + 1;
                $end = $start + $this->pagination['perPage'] - 1;
            }

            if ($end > $total) {
                $end = $total;
            }
        } else {
            $start = '0';
            $end = '0';
            $total = '0';
        }

        return $str[0] . $start . $str[1] . $end . $str[2] . $total . $str[3];
    }

    // Main Methods
    function perform($class = false)
    {
        $db = &$this->db;
        $this->_applyFilter();

        $qSelect = array();
        $qFrom = array($this->mainTable['tableName']);
        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            if (!isset($fieldProps['sqlField'])) {
                continue;
            }

            $q = $fieldProps['sqlField'];
            if (!isset($this->fieldProps[$aliasName]['ignoreAlias'])) {
                $q .= " as {$this->useQuotes}$aliasName{$this->useQuotes}";
            }

            $qSelect[] = $q;
        }
        foreach ($this->ffTables as $tableName => $tableProps) {
            $tmp = "{$tableProps['joinMode']} join ";
            $tmp .= ($tableProps['realTable']) ?
                "{$tableProps['realTable']} as {$tableName}" :
                "{$tableName}";
            $tmp .= " ON ";

            if (isset($this->fieldProps[$tableProps['aliasSource']])) {
                $tmp .= $this->fieldProps[$tableProps['aliasSource']]['sqlField'];
            } else {
                $tmp .= $tableProps['aliasSource'];
            }
            $tmp .= " = ";
            $tmp .= "$tableName.$tableProps[fieldTarget] ";
            $tmp .= $tableProps['extraOn'] ? $tableProps['extraOn'] : '';
            $qFrom[] = $tmp;
        }
        $qWhere = $this->getWhereList();

        // Pagination code:
        $limit = '';
        if ($this->queryProps['limit']) {
            $limit = $this->queryProps['limit'];
        }

        if (!empty($this->pagination['page'])) {
            $pt = $this->getPagesTotal();
            if ($this->pagination['page'] > $pt) {
                $this->pagination['page'] = $pt;
            }

            if (!$this->pagination['page']) {
                return false;
            }

            $limit = intval($this->pagination['page'] - 1) * intval($this->pagination['perPage']);
            $limit .= ", " . $this->pagination['perPage'];
        }

        $q = "SELECT\t" . join(", \n\t", $qSelect) . "\n";
        $q .= "FROM  \t" . join("\n\t", $qFrom) . "\n";
        if (sizeof($qWhere)) {
            $q .= "WHERE \t" . join("\n\t", $qWhere) . "\n";
        }
        if ($this->queryProps['groupBy']) {
            $q .= "GROUP BY " . addslashes($this->queryProps['groupBy']) . "\n";
        }
        if ($this->queryProps['having']) {
            $q .= "HAVING( " . $this->queryProps['having'] . " )\n";
        }
        if ($this->queryProps['orderBy']) {
            $q .= "ORDER BY " . ($this->queryProps['orderByRaw'] ? $this->queryProps['orderBy'] : addslashes($this->queryProps['orderBy'])) . "\n";
        }
        if ($limit) {
            $q .= "LIMIT $limit\n";
        }

        if ($class && is_subclass_of($class, 'dDbRow3')) {
            if (!$class::structExists()) {
                $class::buildStruct();
            }

            $ret = array();
            $qh = $db->query($q);
            while ($entry = $db->fetch($qh)) {
                // Vamos carregar (ou re-utilizar) os campos do item principal
                // Vamos carregar (ou re-utilizar) os itens secundários
                // Vamos adicionar os virtuais necessários
                $mainData = array();
                $extData = array();
                $virtuals = array();

                foreach ($this->fieldProps as $aliasName => $item) {
                    if (!$item['isForeign'] && array_key_exists($item['fieldName'],
                            $class::structGet('fieldProps', 'simple'))) {
                        $mainData[$item['fieldName']] = $entry[$aliasName];
                        continue;
                    } elseif ($item['isForeign']) {
                        $extData[$item['tableName']][$item['fieldName']] = $entry[$aliasName];
                        continue;
                    }
                    $virtuals[$aliasName] = $entry[$aliasName];
                }

                $mainId = $mainData[$class::structGet('primaryKey')];
                $useOnly = implode(",", array_keys($mainData));
                $mainObj = $class::newReusable($mainId, array_keys($mainData));
                $mainObj->loadArray($mainData,
                    array('format' => 'db', 'overwriteLoaded' => false, 'setLoaded' => true));
                foreach ($extData as $extTableName => $extItem) {
                    $tableProps = $this->ffTables[$extTableName];
                    $extClassName = $tableProps['className'];
                    $extAlias = $tableProps['aliasName'];
                    if ($extClassName) {
                        if (!$extClassName::structExists()) {
                            $extClassName::buildStruct();
                        }

                        $subId = $extItem[$extClassName::structGet('primaryKey')];
                        if ($subId) {
                            $subUseOnly = array_keys($extItem);
                            $extObj = $extClassName::newReusable($subId, $subUseOnly);
                            $extObj->loadArray($extItem, array('overwriteLoaded' => false, 'setLoaded' => true));
                            $extAlias[1] ?
                                $mainObj->setVirtual($extAlias[0], $extObj) :
                                $mainObj->setValue($extAlias[0], $extObj);
                        } elseif ($extAlias[1]) {
                            $mainObj->setVirtual($extAlias[0], false);
                        }
                    } else {
                        foreach ($extItem as $extAliasName => $extValue) {
                            $mainObj->setVirtual($extAliasName, $extValue);
                        }
                    }
                }
                foreach ($virtuals as $aliasName => $value) {
                    $mainObj->setVirtual($aliasName, $value);
                }

                $ret[] = $mainObj;
            }
            return $ret;
        } elseif ($class) {
            // Retorna objetos instanciados ao invés
            return $this->db->singleObjects($q, $class);
        }

        $starttime = $db->time;
        if ($ret = $this->db->singleQuery($q)) {
            foreach ($ret as $idx => $row) {
                foreach ($row as $col => $value) {
                    $ret[$idx][$col] = $this->applyModifiers($value, $col);
                }
            }

            if ($this->debug) {
                $first = true;
                $cor = array(false, 'CF8', 'AC3', '0F0', '030');
                echo "\n<!-- (DDbSearch: Begin Debug Code) -->\n";
                echo "<table style='background: #$cor[4]; font: 10px Arial' padding='1' cellspacing='1'>";
                foreach ($ret as $row) {
                    if ($first) {
                        echo "<tr style='background: #000; color: #FFF'>";
                        echo "<td colspan='" . sizeof($row) . "'>dDbSearch - Debug</td>";
                        echo "</tr>";
                        echo "<tr style='background: #$cor[3]'>";
                        foreach ($row as $col => $value) {
                            echo "<td><a href='" . $this->writeSortLink($col) . "'>$col</a></td>";
                        }
                        $first = false;
                        echo '</tr>';
                    }

                    echo "<tr style='background: #" . ($cor[0] = $cor[0] == $cor[1] ? $cor[2] : $cor[1]) . "'>";
                    foreach ($row as $col => $value) {
                        echo "<td>$value</td>";
                    }
                    echo "</tr>";
                }
                echo "<tr style='background: #$cor[3]'>";
                echo "<td colspan='" . sizeof($row) . "' align='right'>";
                echo "Query time: " . number_format($this->db->time - $starttime, 5) . "s";
                if ($this->pagination['page']) {
                    echo " | Page: " . $this->writePagination(5, 5,
                            array('<span style="text-decoration: none; background: #FFC">&nbsp;', '&nbsp;</span>'),
                            array('<b style="font-size: 12px">', '</b>'));
                }
                echo '</td>';
                echo '</tr>';
                echo "</table>";
                echo "\n<!-- (DDbSearch: EOF Debug Code) -->\n";
            }

            return $ret;
        }
        return false;
    }

    function performObj($class)
    {
        return $this->perform($class);
    }

    // Support Methods
    function addModifier($aliasNames, $strMod)
    {
        $aliasNames = explode(",", $aliasNames);
        foreach ($aliasNames as $aliasName) {
            $this->fieldProps[trim($aliasName)]['strMod'] = $strMod;
        }
    }

    function formatToQuery($value, $fieldName = false)
    {
        if (is_null($value) || $value === false) {
            return 'NULL';
        }
        if (is_float($value)) {
            return $value;
        }
        return "'" . addslashes($value) . "'";
    }

    function applyModifiers($value, $fieldName = false, $toDb = false)
    {
        if (!isset($this->fieldProps[$fieldName]['strMod'])) {
            return $value;
        }

        if (is_array($this->fieldProps[$fieldName]['strMod'])) {
            $callback = $this->fieldProps[$fieldName]['strMod'][1];
            $this->fieldProps[$fieldName]['strMod'] = $this->fieldProps[$fieldName]['strMod'][0];
        }
        if ($value === null) {
            return "";
        }

        $toMod = explode(",", str_replace("=", ",", $this->fieldProps[$fieldName]['strMod']));

        // String Mods
        if (in_array('upper', $toMod)) {
            $value = strtoupper($value);
        }
        if (in_array('lower', $toMod)) {
            $value = strtolower($value);
        }
        if (in_array('trim', $toMod)) {
            $value = trim($value);
        }
        if (in_array('date', $toMod)) {
            $param = $toMod[array_search('date', $toMod) + 1];
            if ($tmp = @strtotime($value)) {
                $value = date($param, $tmp);
            }
        }
        if (in_array('function', $toMod)) {
            $param = $toMod[array_search('function', $toMod) + 1];
            eval("\$value = $param(\$value);");
        }
        if (in_array('unserialize', $toMod)) {
            $value = unserialize($value);
        }
        if (in_array('unserialize_text', $toMod)) {
            $ret = array();
            $lines = explode("\n", $value);
            foreach ($lines as $line) {
                $tmp = explode(": ", $line, 2);
                $ret[$tmp[0]] = rtrim($tmp[1], "\r\n");
            }
            $value = $ret;
            unset($lines, $line, $tmp);
        }
        if (in_array('limit', $toMod)) {
            $param = $toMod[array_search('limit', $toMod) + 1];
            $words = explode(chr(0), wordwrap($value, $param, chr(0)));
            $value = $words[0];

            if (strlen($value) > $param) {
                $value = substr($value, 0, $param) . "...";
            } elseif (isset($words[1])) {
                $value .= "...";
            }
        }
        if (isset($callback)) {
            $value = call_user_func($callback, $value, $toDb);
        }
        return $value;
    }

    function getWhereList()
    {
        $qWhere = array();
        $nw = '';
        foreach ($this->queryProps['where'] as $idx => $w) {
            $lastNw = $nw;
            if (is_string($w)) {
                $towrite = $w;
                $nw = 'AND';
            } else {
                $towrite = '';
                $towrite .= isset($this->fieldProps[$w['field']]) ?
                    $this->fieldProps[$w['field']]['sqlField'] :
                    $w['field'];
                $towrite .= " " . $w['eq'] . " " . $this->formatToQuery($w['value'], $w['field']);
                $towrite;
                $nw = $w['and_or'];
            }

            // Make permissive error checking:
            $last = ($idx) ? $qWhere[$idx - 1] : false;
            $lastHadCondition = ($last ? preg_match("/ (and|or)$/i", trim($last)) : false);
            $startCondition = preg_match("/^(and|or) /i", trim($towrite));

            if (!$last && $startCondition) {
                // Error will occur here.
                // Cannot start a WHERE statement with a condition.
                // --> Replace condition with nothing.
                $towrite = preg_replace("/(^and|or) /i", "/*\$1*/ ", trim($towrite));
            }
            if ($last && $lastHadCondition && $startCondition) {
                // Error will occur here.
                // Double condition will occur.
                // --> Replace the last condition with nothing.
                $qWhere[$idx - 1] = preg_replace("/(^and|or) /i", "(old-\$1)", trim($last));
            }
            if ($last && !$lastHadCondition && !$startCondition) {
                // Error will occur here.
                // No condition between statements will occur.
                // --> Prepend $nw to the current statement.
                $towrite = "{$lastNw} {$towrite}";
            }
            $qWhere[] = $towrite;
        }

        $sof = sizeof($qWhere);
        if ($sof) {
            if ($sof > 1) {
                // Let's check if the final line is ending with a condition, which
                // would throw a SQL error. If it happens, let's remove it.
                $qWhere[$sof - 1] = preg_replace("/(and|or)$/i", "/*\$1*/", trim($qWhere[$sof - 1]));
            }
        }

        return $qWhere;
    }
}

