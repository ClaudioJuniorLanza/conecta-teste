<?php

/**
 * Usage example:
 * $s = new dDbSearch3('eCliente', Array(
 * 'onlyFields' =>'',
 * 'loadExt'    =>'',
 * '...'        =>"Filtros aplicáveis.", (sinônimos: 'callback', 'cbMakeQuery' ou '...')
 * ));
 * $s->activateSorting();
 * $s->activatePagination();
 * $s->addWhere(...)
 * $s->addFilter(...)
 * $s->perform();
 **/
class dDbSearch3
{
    public $className;
    public $mqSettings;
    public $cache;

    function __construct($className, $makeQuerySettings = array())
    {
        $this->className = $className;
        $this->mqSettings = dHelper2::addDefaultToArray($makeQuerySettings, array(
            'onlyFields' => false,
            'callback' => false,
        ));
        $this->where =
        $this->cache = array();

        if (!$this->mqSettings['callback']) {
            if (isset($this->mqSettings['cbMakeQuery'])) {
                $this->mqSettings['callback'] = $this->mqSettings['cbMakeQuery'];
                unset($this->mqSettings['cbMakeQuery']);
            } elseif (isset($this->mqSettings['...'])) {
                $this->mqSettings['callback'] = $this->mqSettings['...'];
                unset($this->mqSettings['...']);
            }
        }

        if (!$className::structExists()) {
            $className::buildStruct();
        }
    }

    // Como as queryes são geradas e processadas por dDbSearch3?
    // a) A query original será gerada pelo dDbRow3
    // b) O método $this->loadQuery  vai separar os elementos da query original
    // c) O método $this->perform()  vai aplicar as modificações nesses elementos
    // d) O método $this->buildQuery vai juntar os elementos novamente, inserindo
    //    as modificações, tal como setFilter, setOrdem, setPage, etc..
    function loadQuery()
    {
        // Resultado esperado para parseSql:
        // Vamos tentar obter esse resultado diretamente do makeQuery.
        // $split = Array('select','from','where','group by','order by','having','limit');
        if (isset($this->cache['loadSql'])) {
            return $this->cache['loadSql'];
        }

        $className = $this->className;
        $mapPrefix = array();
        $theQuery = $className::makeQuery(array('getParts' => 2) + $this->mqSettings, $mapPrefix);

        // Split "append" into 'where', 'group by', 'order by', 'having' and 'limit', if needed.
        $theQuery['where'] = '';
        $theQuery['group by'] = '';
        $theQuery['having'] = '';
        $theQuery['order by'] = '';
        $theQuery['limit'] = '';
        if ($theQuery['append']) {
            $expected = array('limit', 'having', 'order by', 'group by', 'where');
            foreach ($expected as $expect) {
                $parts = dHelper2::explode_outside_quotes($expect, $theQuery['append']);
                if (sizeof($parts) < 2) {
                    continue;
                }

                $theQuery[$expect] = $parts[1];
                $theQuery['append'] = $parts[0];
            }
            unset($theQuery['append']);
        }

        $this->cache['mapPrefix'] = $mapPrefix;
        return $this->cache['loadSql'] = $theQuery;
    }

    function buildQuery($query = false)
    {
        if (!$query) {
            $query = $this->loadQuery();
        }

        $finalSql = array();
        foreach (array_keys($query) as $sqlKey) {
            $sqlPart = trim($query[$sqlKey]);
            if ($sqlPart) {
                $finalSql[] = str_pad(strtoupper($sqlKey), 6) . "\t" . $sqlPart;
            }
        }
        return implode("\r\n", $finalSql);
    }

    function perform()
    {
        $query = $this->loadQuery();
        // select, from, where, group by, order by, having, limit

        if (@$this->pagination['perPage']) {
            $query['limit'] =
                (($this->pagination['page'] - 1) * $this->pagination['perPage']) . "," .
                $this->pagination['perPage'];
        }

        $this->_applyWhere($query);
        $sqlQuery = $this->buildQuery($query);

        # echo "<pre>";
        # echo $sqlQuery;
        # echo "</pre>";

        // Execute query and get objects...
        // ** Muito parecido com dDbRow3::load()
        $className = $this->className;
        $db = $className::getDb();
        $objList = array();
        $qh = $db->query($sqlQuery);
        while ($item = $db->fetch($qh)) {
            $primaryKey = $className::structGet('primaryKey');
            if ($primaryKey) {
                $obj = $className::newReusable($item[$primaryKey], $this->mqSettings['onlyFields'], false);
            } else {
                $className = get_called_class();
                $obj = new $className(false, false);
            }
            $obj->loadArray($item, array(
                'format' => 'db',
                'setLoaded' => true,
                'overwriteLoaded' => false,
                'noChecks' => true,
                'ignoreKeys' => false,
                'mapPrefix' => $this->cache['mapPrefix'],
            ));
            $objList[] = $obj;
        }
        return $objList;
    }

    // Add Where and String Filtering:
    public $where;

    function addWhere($aliasName, $value = null, $eq = "=", $andOr = 'and')
    {
        if ($value == null) { // If only one argument, use it as RAW
            $this->where[] = array(
                'sqlWhere' => $aliasName,
                'andOr' => $andOr
            );
        } else {
            // Let's generate a short Where code...
            $className = $this->className;
            if (!$className::structExists()) {
                $className::buildStruct();
            }
            $parts = explode(".", $aliasName);
            if (sizeof($parts) == 1) {
                $_aliasName = "{$className::structGet('tableName')}.{$aliasName}";
            } else {
                while (sizeof($parts) > 1) {
                    $className = $className::structGet('fieldProps', 'external', array_shift($parts), 'className');
                    if (!$className::structExists()) {
                        $className::buildStruct();
                    }
                }

                $_aliasName = $aliasName;
            }
            $useValue = $className::sModApply('raw2sql', $_aliasName, $value);
            $this->where[] = array(
                'sqlWhere' => "{$_aliasName} {$eq} {$useValue}",
                'andOr' => $andOr,
            );
        }

        if (isset($this->cache['nResults'])) {
            // Limpa o cache de resultados.
            unset($this->cache['nResults']);
        }

        return sizeof($this->where) - 1;
    }

    function addFilter($phrase, $fields, $settings = array())
    {
        // setFilter($phrase, $fields[, $match_phrase=true])
        // setFilter($phrase, $fields[, $settings])
        // Settings:
        //   match_phrase: true     // Only full phrases (ex: 'john doe' != 'john is doe')
        //   andOr:        'and'    // Will be used in addWhere(....., $andOr)
        //
        if (!trim($phrase)) {
            return;
        }
        if (is_bool($settings)) {
            $settings = array('match_phrase' => $settings);
        }
        if (!is_array($fields)) {
            $fields = explode(",", $fields);
        }
        $settings += array(
            'match_phrase' => true,
            'andOr' => 'and',
        );

        // Utilizamos $className::sModApply('raw2sql', ...) para ver qual valor utilizar.
        // Utilizamos $className::(validação)               para ver se vamos utilizar o item em questão
        //

        $matchPhrase = $settings['match_phrase'];
        $phrases = $matchPhrase ?
            array($phrase) :
            explode(" ", preg_replace("/ +/", " ", $phrase));

        // 1º Passo:
        // --> Obter o valor em SQL de todos os campos utilizados, em $sqlValues.
        // --> Exemplo: Array('tabela.titulo'=>Array("'string'"))
        // --> Exemplo: Array('extObj.nome'  =>Array("'sem'", "'match'", "'phrase'"))
        $sqlValues = array();
        foreach ($fields as $aliasName) {
            $parts = explode(".", $aliasName);
            $className = $this->className;
            if (!$className::structExists()) {
                $className::buildStruct();
            }
            if (sizeof($parts) == 1) {
                $aliasName = "{$className::structGet('tableName')}.{$aliasName}";
            }
            while (sizeof($parts) > 1) {
                $className = $className::structGet('fieldProps', 'external', array_shift($parts), 'className');
                if (!$className::structExists()) {
                    $className::buildStruct();
                }
            }

            $_aliasName = array_pop($parts);
            foreach ($phrases as $phrase) {
                $_tryStr = $className::sModApply('raw2db', $_aliasName, $phrase);
                if ($_tryStr) {
                    $sqlValues[$phrase][$aliasName] = $className::sModApply('db2sql', $_aliasName, $_tryStr);
                }
            }
        }

        // Exemplo de $sqlValues:
        //   ['colar drusa'][d_ec_produtos.titulo] = "'colar drusa'"
        //   ['colar drusa'][d_ec_produtos.codigo] = "'colar drusa'"
        //   ['colar drusa'][variaObj.varia_str]   = "'colar drusa'"
        //
        // * O mesmo valor pode aparecer várias vezes, pois tem que ser
        //   considerado o modificador de cada uma dessas classes, tal como:
        //
        //   ['25/15/2015'][d_ec_produtos.titulo]        = "'25/12/2015'"
        //   ['25/15/2015'][d_ec_produtos.data_cadastro] = "'2015-12-25'";
        //

        // 2º Passo:
        // --> Montar a sql where...
        $andList = array();
        foreach ($sqlValues as $phrase => $_searchWhere) {
            $orList = array();
            foreach ($_searchWhere as $_searchAliasName => $_searchValue) {
                $_searchValue = (substr($_searchValue, 0, 1) == "'" && substr($_searchValue, -1) == "'") ?
                    ("like '%" . substr(str_replace(array("%", "_"), array("\\%", "\\_"), $_searchValue), 1,
                            -1) . "%'") :
                    "= {$_searchValue}";

                $orList[] = "{$_searchAliasName} {$_searchValue}";
            }
            $andList[] = "(" . implode(" OR ", $orList) . ")";
        }
        $strWhere = "(" . implode(" AND\r\n     \t ", $andList) . ")";
        $this->addWhere($strWhere, null, false, $settings['andOr']);
    }

    function _applyWhere(&$query)
    {
        if (!$this->where && $query['where']) {
            // Where foi definido no parâmetro '...' na construção da classe.
            // Se houvesse ->addWhere, o original seria sobrescrito, mas este
            // não é o caso.
            return;
        }

        $qWhere = array();
        $nw = '';
        foreach ($this->where as $idx => $w) {
            $lastNw = $nw;
            $towrite = $w['sqlWhere'];
            $nw = $w['andOr'];

            // Make permissive error checking:
            $last = ($idx) ? $qWhere[$idx - 1] : false;
            $lastHadCondition = ($last ? preg_match("/ (and|or)$/i", trim($last)) : false);
            $startCondition = preg_match("/^(and|or) /i", trim($towrite));
            $startCP = (substr(trim($towrite), 0, 1) == ')'); // Start with CloseParenthesis
            $lastOP = (substr(trim($last), -1) == '('); // Last ends with OpenParenthesis

            if (!$last && $startCondition) {
                // É a primeira query da lista (não tem anterior), mas começou
                // com and/or. Vamos remover o and/or da lista.
                $towrite = preg_replace("/^(and|or) /i", "/*\$1Rule1*/ ", trim($towrite));
            }
            if ($last && $lastHadCondition && $startCondition) {
                // Tem query anterior, a anterior terminou com and/or e esta
                // também começa com and/or. Ou seja, temos 'and and' ou 'or or'.
                // Para resolver, vamos remover o que estava no statement anterior.
                $qWhere[$idx - 1] = preg_replace("/(and|or)$/i", "/*($1)Rule2*/", trim($last));
            }
            if ($last && !$lastHadCondition && !$startCondition) {
                // Tem query anterior, a anterior não terminou com and/or
                // e esta não começou com and/or. Dessa forma, vamos
                // adicionar and/or no início.
                if (!$startCP && !$lastOP) {
                    // Se o anterior terminou com '(', não tem como adicionar "and/or" no início.
                    // Se este começar com ')',        não tem como adicionar "and/or" no início.
                    $towrite = "{$lastNw} {$towrite}";
                }
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

        $strWhere = implode("\r\n     \t", $qWhere);

        // Replace simples de prefixos.
        // Converte nomes simples de entender para o correspondente na tabela.
        // Ex: 'produObj.variaObj.varia_str = null' ==> 'variaObj.varia_str = null'
        $mapPrefix = $this->cache['mapPrefix'];
        if (is_array($mapPrefix)) {
            foreach (array_reverse($mapPrefix) as $tablePrefix => $map) {
                if (is_string($map)) {
                    $strWhere = dHelper2::str_replace_outside_quotes("{$map}.", $tablePrefix, $strWhere);
                }
            }
        }

        $query['where'] = $strWhere;
    }

    // Sorting:
    public $sorting;

    function activateSorting($queryString = 'sortby')
    {
        $this->sorting['q_str'] = $queryString;
        if (isset($_GET[$queryString])) {
            $orderBy = trim($_GET[$queryString]);
            $className = $this->className;
            $knownKeys = array_keys($className::structGet('fieldProps', 'simple'));
            $parts = explode(" ", $orderBy);
            $isDesc = (sizeof($parts) > 1 && $parts[1] == 'desc') ? true : false;
            if (!in_array($parts[0], $knownKeys)) {
                return;
            }

            $this->setOrderBy($parts[0] . ($isDesc ? " desc" : ""));
        }
    }

    function writeSortLink($orderBy)
    {
        if (!isset($this->sorting['q_str'])) {
            return "#";
        }

        // Prepare to write the links
        $link = "{$_SERVER['PHP_SELF']}?" . ($_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : "");
        $link = preg_replace("/{$this->sorting['q_str']}=.+?\&/", "", $link);
        $link = preg_replace("/\&{$this->sorting['q_str']}=.+/", "", $link);
        $link .= "&{$this->sorting['q_str']}=";
        // End of preparation

        $query = $this->loadQuery();
        return ($query['order by'] == $orderBy) ?
            $link . $orderBy . " desc" :
            $link . $orderBy;
    }

    // Pagination
    public $pagination = array();

    function activatePagination($perPage = 15, $queryString = 'page')
    {
        if (!$this->pagination) {
            $this->pagination = array();
        }
        if ($perPage) {
            $this->pagination['q_str'] = $queryString;
            if (@$this->pagination['perPage'] && $perPage != $this->pagination['perPage']) {
                // Se mudar o perPage, zera o cache.
                $this->cache['nResults'] = 0;
            }

            $this->pagination['perPage'] = $perPage;
            if (isset($_GET[$queryString])) {
                $this->setPage(intval($_GET[$queryString]));
            } else {
                $this->setPage(1);
            }
        }
    }

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
            return "{$selected[0]}1{$selected[1]}";
        }

        $page = &$this->pagination['page'];
        $page = ($page < 1) ? 1 : $page;
        $page = ($page > $this->getPagesTotal()) ? $this->getPagesTotal() : $page;
        $pagesTotal = $this->getPagesTotal();

        // Prepare to write the links
        $link = ($_SERVER["QUERY_STRING"] ? "$_SERVER[QUERY_STRING]" : "");
        $link = str_replace(array(
            $this->pagination['q_str'] . "=$page&",
            "&" . $this->pagination['q_str'] . "=$page",
            $this->pagination['q_str'] . "=$page",
        ), "", $link);

        if ($link) {
            $link = "?$link&{$this->pagination['q_str']}=";
        } else {
            $link = "?{$this->pagination['q_str']}=";
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
        // Há algo em cache?
        if (!$refresh && isset($this->cache['nResults'])) {
            return $this->cache['nResults'];
        }

        // Contabiliza o total de linhas
        $_doWrap = function ($before, $after, &$q) {
            $q =
                $before . "\r\n" .
                "  " . str_replace("\n", "\n  ", trim($q)) . "\r\n" .
                $after;
        };

        $query = $this->loadQuery();
        $this->_applyWhere($query);
        $query['limit'] = false; // Get whole match
        $query['order by'] = false; // Optimize query

        $q = $this->buildQuery($query);
        $_doWrap("SELECT count(*) as co FROM (", ") as s2", $q);
        $_doWrap("SELECT sum(co) FROM (", ") as s1", $q);

        $className = $this->className;
        return $this->cache['nResults'] = $className::getDb()->singleResult($q);
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

    // Others:
    function setOrderBy($orderBy)
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        $this->cache['loadSql']['order by'] = $orderBy;
    }

    function setGroupBy($groupBy)
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        $this->cache['loadSql']['group by'] = $groupBy;
    }

    function setLimit($limit)
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        $this->cache['loadSql']['limit'] = $limit;
    }

    function setHaving($having)
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        $this->cache['loadSql']['having'] = $having;
    }

    function getOrderBy()
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        return $this->cache['loadSql']['order by'];
    }

    function getGroupBy()
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        return $this->cache['loadSql']['group by'];
    }

    function getLimit()
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        return $this->cache['loadSql']['limit'];
    }

    function getHaving()
    {
        if (!isset($this->cache['loadSql'])) {
            $this->loadQuery();
        }
        return $this->cache['loadSql']['having'];
    }
}
