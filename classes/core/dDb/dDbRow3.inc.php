<?php
/**
 * dDbRow3
 * ----------------------------------------------------
 * Author:  Alexandre Tedeschi
 * Company: IMAGINACOM Plataformas Tecnologicas Ltda
 * Website: www.imaginacom.com
 * E-mail:  alexandre at imaginacom dot com
 **/

class dDbRow3
{
    static function getVersion($getUpdates = false)
    {
        // Mudar a versão diretamente manualmente no código não é mais aceitável,
        // pois causa conflito caso ocorram duas atualizações simultâneas, mesmo
        // que em partes diferentes do sistema.
        //
        // Vamos congelar a versão da classe em '3.3', e cada atualização deverá
        // ser registrada com um uniqueid na lista de atualizações seguindo o
        // modelo abaixo.
        //
        // Para checar se a classe possui alguma funcionalidade, deverá ser chamado
        // ::getVersion(true), e analisado se a atualização em questão existe na
        // relação de atualizações já aplicadas.
        //
        // Uma versão pode ser qualquer string, a recomendação é a utilização do
        // seguinte formato: "yyyymmdd.smallDescription"
        //
        if (!$getUpdates) {
            return "3.3";
        }

        $allUpdates = array(
            // Classe evoluída para versão 3.3, e novo sistema de controle de versões, com módulos:
            '20150606.newCvs',
            '20150606.sValPassedThirdArgumentIsOptional',
            '20150606.valPassedAllowIgnoreFields',
            '20150606.uniqueRelatedToAliasNames',
            '20150606.bugfixExtObjs',
            '20150615.dSerialize',
            '20150616.beforeSaveHander',
            '20150621.bugfixNumMin',
            '20150621.sValPassed',
            '20150711.bugfixCustomError',
            '20150711.allow3DotsAsLoadParam',
            '20150717.primaryKeyIgnoreDupe',
            '20150718.autoReplaceAddExtOn',
            '20150720.bugfixGlobalVal',
            '20150826.structGetNoParams',
            '20151220.bugfixZeroEqualsToString',
            '20151220.bugfixPartialLoading',
            '20160131.addEventListenerReturnId',
            '20160131.eventListenerExecOrder',
            '20160203.exportMethod',
            '20160212.bugfixExtEarlySave',
            '20161017.bugfixEventListener',
            '20161230.allowOverloading',
        );
        return $allUpdates;
    }

    // Deprecated:
    function loadFromArray($arr, $ignoreFields = false, $fromDb = false)
    {
        return $this->loadArray($arr, array(
            'ignoreFields' => $ignoreFields,
            'format' => ($fromDb ? 'db' : 'raw')
        ));
    }

    function deleteFromDatabase($id = false)
    {
        if ($id) {
            trigger_error("deleteFromDatabase with \$id is deprecated. Use ->setPrimaryValue() and ->setLoaded(true) before deleting.");
            die;
        }
        return $this->delete();
    }

    function o()
    {
        trigger_error("->o() is deprecated. Use ->v() instead.");
        die;
    }

    function __get($what)
    {
        if ($what == 'mainTable') {
            return array('tableName' => self::structGet('tableName'), 'primaryKey' => self::structGet('primaryKey'));
        }
    }

    function castMsg($level, $message)
    {
        // echo "castingMsg: {$level} | {$message}<br />";
    }

    // Estrutura
    // ----------------------------------------------------
    static $struct = array(); // db, mainTable, primaryKey, fieldProps, validations, modifiers
    static $uniqueId = 0;
    static $debug = false;

    static function buildStruct()
    {
        die("dDbRow3: Você não sobrescreveu o método buildStruct() na classe " . get_called_class() . "\r\n");
    }

    static function autoStruct($tableName = false, $options = array())
    {
        // Entenda o autoStruct:
        // --> Utilizar ::addField(...) antes do autoStruct fará o sistema ignorar um campo.
        // --> Utilize options['dump'=>true] para exportar o código final.
        $options += array(
            'ext' => array(), // Classes externas por name. Ex: [pedidObj]=>'ePedido'
            'dump' => false,   // Se true, faz dump no código que pode ser copiado/colado na classe.
            'allowInProducao' => false,
        );

        if (!$tableName) {
            $tableName = self::structGet('tableName');
            if (!$tableName) {
                trigger_error("Não é possível chamar autoStruct(\$tableName) sem informar o nome da tabela.",
                    E_USER_WARNING);
                die;
            }
        }

        $_splitByLength = function ($sourceList, $maxLength = 65) {
            $outGroups = array();
            $_buffer = '';
            foreach ($sourceList as $item) {
                if ((strlen($_buffer) + strlen($item)) > $maxLength) {
                    // Commit.
                    $outGroups[] = rtrim($_buffer, ',');
                    $_buffer = '';
                }
                $_buffer .= "{$item},";
            }
            if ($_buffer) {
                // Commit remaining.
                $outGroups[] = rtrim($_buffer, ',');
            }

            return $outGroups;
        };
        $_getIntLimits = function ($type, $unsigned) {
            if ($type == 'tinyint') {
                return $unsigned ? [0, 255] : [-128, 127];
            }
            if ($type == 'smallint') {
                return $unsigned ? [0, 65535] : [-32768, 32767];
            }
            if ($type == 'mediumint') {
                return $unsigned ? [0, 16777215] : [-8388608, 8388607];
            }
            if ($type == 'int') {
                return $unsigned ? [0, 4294967295] : [-2147483648, 2147483647];
            }
            if ($type == 'bigint') {
                return $unsigned ? [0, 18446744073709551615] : [-9223372036854775808, 9223372036854775807];
            }

            if ($type == 'tinyint') {
                return $unsigned ? [0, 255] : [-128, 127];
            }
            if ($type == 'smallint') {
                return $unsigned ? [0, 65535] : [-32768, 32767];
            }
            if ($type == 'mediumint') {
                return $unsigned ? [0, 16777215] : [-8388608, 8388607];
            }
            if ($type == 'int') {
                return $unsigned ? [0, 4294967295] : [-2147483648, 2147483647];
            }
            if ($type == 'bigint') {
                return $unsigned ? [0, 18446744073709551615] : [-9223372036854775808, 9223372036854775807];
            }

            return ['???', '???'];
        };
        $_prettyName = function ($aliasName) {
            return dHelper2::ucwordsBr(str_replace("_", " ", $aliasName));
        };

        $db = self::getDb();
        $allKeys = $db->singleIndex("show fields from {$tableName}", 'Field');
        if (self::structGet('fieldProps', 'simple')) {
            // Usuário já definiu alguns campos por conta própria, vamos ignorar esses.
            foreach (self::structGet('fieldProps', 'simple') as $aliasName => $props) {
                unset($allKeys[$aliasName]);
            }
        }

        $external = array();
        $rules = [
            'required' => array(),
            'date' => array(),
            'datetime' => array(),
            'timestamp' => array(),
            'email' => array(),
            'int' => array(),
            'nummin' => array(),
            'nummax' => array(),
            'enum' => array(),
            'singleline' => array(),
            'maxchars' => array(),
            'float' => array(),
            'trim' => array(),
            'json' => array(),
            'defaults' => array(),
        ];
        foreach ($allKeys as $aliasName => $info) {
            if ($aliasName == 'id') {
                continue;
            }
            if ($info['Null'] == 'NO') {
                $rules['required'][] = $aliasName;
            }
            if ($info['Default'] !== null) {
                $rules['defaults'][$aliasName] = $info['Default'];
            }
            if ($aliasName) // Especiais:
            {
                if (preg_match("/mail.?$/", $aliasName)) {
                    $rules['email'][] = $aliasName;
                } elseif (preg_match("/^json_/", $aliasName)) {
                    $rules['json'][] = $aliasName;
                    continue; // Não aplicar validações genéricas.
                } elseif (preg_match("/_id([0-9])?$/", $aliasName, $_outN)) {
                    $external[substr($aliasName, 0, 5) . ((@$_outN[1]) ? @$_outN[1] : '') . 'Obj'] = $aliasName;
                }
            }

            // Validações genéricas.
            $_type = $info['Type'];
            if ($_type == 'date' || $_type == 'datetime' || $_type == 'timestamp') {
                $rules[$_type][] = $aliasName;
            } elseif (in_array($_type, ['tinytext', 'text', 'mediumtext', 'longtext'])) {
                //   TINYTEXT |           255 (2 8−1) bytes
                //       TEXT |        65,535 (216−1) bytes = 64 KiB
                // MEDIUMTEXT |    16,777,215 (224−1) bytes = 16 MiB
                //   LONGTEXT | 4,294,967,295 (232−1) bytes =  4 GiB
                if ($_type == 'tinytext') {
                    $rules['strmax'][$aliasName] = 255;
                }
                if ($_type == 'text') {
                    $rules['strmax'][$aliasName] = "64*1024-1";
                }
                if ($_type == 'mediumtext') {
                    $rules['strmax'][$aliasName] = "16*1024*1024-1";
                }
                if ($_type == 'longtext') {
                    $rules['strmax'][$aliasName] = "4*1024*1024*1024-1";
                }
                $rules['trim'][] = $aliasName;
            } else {
                preg_match("/(.+?)\((.+)\)(?: (.+))?$/", $_type,
                    $_outType) or trigger_error("Não sei processar type: {$_type}", E_USER_WARNING);
                $_type = $_outType[1];
                $_params = $_outType[2];
                $_unsign = (@$_outType[3] == 'unsigned');

                if (in_array($_type, ['char', 'varchar'])) {
                    $rules['singleline'][] = $aliasName;
                    $rules['trim'][] = $aliasName;
                    $rules['strmax'][$aliasName] = $_params;
                    continue;
                } elseif (substr($_type, -3) == 'int') {
                    $_limits = $_getIntLimits($_type, $_unsign);
                    $rules['int'][] = $aliasName;
                    $rules['nummin'][$aliasName] = $_limits[0];
                    $rules['nummax'][$aliasName] = $_limits[1];
                } elseif ($_type == 'decimal') {
                    $rules['float'][] = $aliasName;
                    if ($_unsign) {
                        $rules['nummin'][$aliasName] = 0;
                    }
                } elseif ($_type == 'enum') {
                    $rules['enum'][$aliasName] = $_params;
                }
            }
        }
        extract(call_user_func(function () use (&$rules, &$allKeys, &$code, $_prettyName) {
            // Monta uma regra "Validation" contendo [0]=aliasName, [1]=valStr, [2]=Segundo parametro opcional, [3]=Mensagem de erro.
            $validations = array();
            foreach ($rules['required'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'required',
                    false,
                    "Você precisa preencher o campo {$_prettyName($aliasName)}"
                ];
            }
            foreach ($rules['date'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'date',
                    'br',
                    "Preencha {$_prettyName($aliasName)} no formato dd/mm/aaaa"
                ];
            }
            foreach ($rules['datetime'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'datetime',
                    'br',
                    "Preencha {$_prettyName($aliasName)} no formato dd/mm/aaaa hh:mm"
                ];
            }
            foreach ($rules['timestamp'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'datetime',
                    'br',
                    "Preencha {$_prettyName($aliasName)} no formato dd/mm/aaaa hh:mm"
                ];
            }
            foreach ($rules['email'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'email',
                    false,
                    "Por favor, informe um {$_prettyName($aliasName)} válido."
                ];
            }
            foreach ($rules['int'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'int',
                    false,
                    "Preencha o campo {$_prettyName($aliasName)} apenas com números"
                ];
            }
            foreach ($rules['nummin'] as $aliasName => $numMin) {
                $validations[] = [
                    $aliasName,
                    'nummin',
                    $numMin,
                    ($numMin) ?
                        "Informe um número acima de {$numMin} para o campo {$_prettyName($aliasName)}" :
                        "Informe um número positivo para o campo {$_prettyName($aliasName)}"
                ];
            }
            foreach ($rules['nummax'] as $aliasName => $numMax) {
                $validations[] = [
                    $aliasName,
                    'nummax',
                    $numMax,
                    "Informe um número inferior a {$numMax} para {$_prettyName($aliasName)}"
                ];
            }
            foreach ($rules['enum'] as $aliasName => $options) {
                $validations[] = [
                    $aliasName,
                    'regex',
                    "(" . str_replace(",", "|", preg_quote(str_replace("'", "", $options))) . ")",
                    "Opção inválida para {$_prettyName($aliasName)}"
                ];
            }
            foreach ($rules['singleline'] as $aliasName) {
                $validations[] = [
                    $aliasName,
                    'singleline',
                    false,
                    "O campo {$_prettyName($aliasName)} não pode ter mais de uma linha"
                ];
            }
            foreach ($rules['maxchars'] as $aliasName => $maxChars) {
                $validations[] = [
                    $aliasName,
                    'strmax',
                    $maxChars,
                    "O campo {$_prettyName($aliasName)} não pode ter mais de {$maxChars} caracteres."
                ];
            }

            $modifiers = array();
            foreach ($rules['date'] as $aliasName) {
                $modifiers['date'][] = $aliasName;
            }
            foreach ($rules['datetime'] as $aliasName) {
                $modifiers['datetime'][] = $aliasName;
            }
            foreach ($rules['int'] as $aliasName) {
                $modifiers['force_int'][] = $aliasName;
            }
            foreach ($rules['float'] as $aliasName) {
                $modifiers['force_float'][] = $aliasName;
            }
            foreach ($rules['json'] as $aliasName) {
                $modifiers['json'][] = $aliasName;
            }
            foreach ($rules['trim'] as $aliasName) {
                $modifiers['trim'][] = $aliasName;
            }

            return [
                'validations' => $validations,
                'modifiers' => $modifiers,
            ];
        }), EXTR_REFS);

        // Precisamos separar o código entre declaração (addField) e formatação (validação/modifier/etc).
        $code = array();
        if (!self::structGet('tableName')) {
            $code[] = "self::setTable('{$tableName}');";
        }

        // Pretty-print declaração de campos.
        $_standard = array_keys($allKeys);
        $_caches = array();
        foreach ($_standard as $aliasName) {
            if (preg_match("/^.?c_/", $aliasName)) {
                // Provavelmente é cache... Vamos mover eles pra outro canto.
                $_caches[] = $aliasName;
            }
        }
        $_standard = array_diff($_standard, $_caches);
        foreach ($_splitByLength($_standard) as $aliasRow) {
            $code[] = "self::addField('{$aliasRow}');";
        }

        if ($_caches) {
            $code[] = "";
            $code[] = "// Campos de cache (gerados automaticamente):";
            foreach ($_splitByLength($_caches) as $aliasRow) {
                $code[] = "self::addField('{$aliasRow}');";
            }
        }
        $code[] = "";

        if ($external) {
            $_printAny = false;
            foreach ($external as $aliasExt => $aliasName) {
                if (self::structGet('fieldProps', 'external', $aliasExt)) {
                    // Já foi definido antes.
                    continue;
                }
                if (!isset($options['ext'][$aliasExt])) {
                    echo "<pre>";
                    echo "Classe <b>" . get_called_class() . "</b>::buildStruct (autoStruct)<br />";
                    echo "- O autoStruct identificou uma referência externa no campo {$aliasName}, \n";
                    echo "  mas não temos informação de qual classe é responsável por este campo.\n";
                    echo "\n";
                    echo "- Para resolver, você pode passar os seguintes parâmetros para autoStruct:\n";
                    echo str_pad("a) autoStruct(..., ['ext'=>['$aliasExt'=>'NomeDaClasse']]);",
                            60) . " // Para definir a classe.\n";
                    echo str_pad("b) autoStruct(..., ['ext'=>['$aliasExt'=>false]]);", 60) . " // Para ignorar.\n";
                    echo str_pad("c) Antes de autoStruct: addField('{$aliasName}');",
                            60) . " // Para não processar NENHUMA regra para {$aliasName}\n";
                    echo str_pad("d) Antes de autoStruct: addExt('{$aliasExt}', ...);",
                            60) . " // Para definir o addExt manualmente por conta própria.\n";
                    die;
                }

                $_targetClass = $options['ext'][$aliasExt];
                if ($_targetClass !== false) {
                    if (!$_printAny) {
                        $code[] = "// Referencia a classes externas";
                    }
                    $code[] = "self::addExt('{$aliasExt}', '{$_targetClass}::{$aliasName}');";
                    $_printAny = true;
                }
            }
            if ($_printAny) {
                $code[] = "";
            }
        }

        // Prettify Validations:
        $max0 = $max1 = $max2 = 0;
        foreach ($validations as $row) {
            $_L0 = strlen($row[0]) + 2;
            $_L1 = strlen(stripslashes(var_export($row[1], true)));
            $_L2 = strlen(stripslashes(var_export($row[2], true)));

            if ($_L0 > $max0) {
                $max0 = $_L0;
            }
            if ($_L1 > $max1) {
                $max1 = $_L1;
            }

            if ($_L2 > 10) {
                // Deve ser uma lista grande de ENUM, não considere.
            } elseif ($_L2 > $max2) {
                $max2 = $_L2;
            }
        }
        foreach ($validations as $row) {
            $code[] =
                "self::addValidation(" .
                str_pad("'{$row[0]}', ", $max0 + 2) .
                str_pad(stripslashes(var_export($row[1], true)) . ", ", $max1 + 2) .
                str_pad(stripslashes(var_export($row[2], true)) . ", ", $max2 + 2) .
                stripslashes(var_export($row[3], true)) .
                ");";
        };
        $code[] = "";

        // Prettify Modifiers:
        $modifiers = array_map(function ($list) use ($_splitByLength) {
            return $_splitByLength($list, 50);
        }, $modifiers);
        $max0 = 0;
        foreach ($modifiers as $modStr => $modifyList) {
            foreach ($modifyList as $modifyRow) {
                $_L0 = strlen($modifyRow);
                if ($_L0 > $max0) {
                    $max0 = $_L0;
                }
            }
        }
        foreach ($modifiers as $modStr => $modifyList) {
            foreach ($modifyList as $modifyRow) {
                $code[] = "self::addModifier(" .
                    str_pad("'{$modifyRow}', ", $max0 + 4) .
                    "'{$modStr}'" .
                    (($modStr == 'date') ? ",     'br'" : "") .
                    (($modStr == 'datetime') ? ", 'br'" : "") .
                    ");";
            }
        }

        // Set defaults.
        if ($rules['defaults']) {
            $code[] = "";
            $max0 = 0;
            foreach ($rules['defaults'] as $aliasName => $defaultValue) {
                $L0 = strlen($aliasName);
                if ($L0 > $max0) {
                    $max0 = $L0;
                }
            }
            foreach ($rules['defaults'] as $aliasName => $defaultValue) {
                $code[] = "self::setDefaultValue(" .
                    str_pad("'{$aliasName}', ", $max0 + 4) .
                    var_export($defaultValue, true) . ");";
            }
        }

        if ($options['dump']) {
            $backtrace = debug_backtrace(null, 1);
            $filename = basename($backtrace[0]['file']);

            echo "<div style='font-size: 12px; margin: 40px; padding: 16px; background: #EEE'>";
            echo "<b>Este código deverá ser colocado no arquivo {$filename}</b><br />";
            echo "<hr size='1' />";
            highlight_string(
                "<?php // Código gerado em " . date('d/m/Y H:i:s') . "\r\n" .
                "class " . get_called_class() . " extends dDbRow3{\r\n" .
                "\tstatic Function buildStruct(){\r\n" .
                "\t\t" . implode("\r\n\t\t", $code) . "\r\n" .
                "\t}\r\n" .
                "}\r\n"
            );
            echo "</div>";
            die;
        } else {
            // Executa!
            if (!$options['allowInProducao'] && !dSystem::getGlobal('localHosted')) {
                $className = get_called_class();
                $message = "Não é permitido chamar {$className}::autoStruct() em produção.";
                dSystem::notifyAdmin('CRITICAL', "Classe {$className} não construída corretamente", $message, true);
                die;
            }
            // if(get_called_class() == 'eLocal'){
            // 	echo "<pre>";
            // 	echo implode("\r\n", $code);
            // 	echo "</pre>";
            // }
            eval(implode("\r\n", $code));
        }
    }

    static function madeForVersion($version)
    {
        self::structSet('madeForVersion', $version);
    }

    static function setTable($tableName, $primaryKey = 'id')
    {
        self::structSet('tableName', $tableName);
        self::structSet('primaryKey', $primaryKey);
        self::structSet('fieldProps', array());
        self::addValidation($primaryKey, 'int', false, "{$primaryKey} should be an integer value");
        self::structSet('allowReusable', 15);
    }

    static function addField($aliasNames, $fieldNames = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $fieldNames = $fieldNames ? explode(",", $fieldNames) : false;

        if (!$fieldNames) {
            foreach ($aliasNames as $aliasName) {
                self::structSet('fieldProps', 'simple', $aliasName, false);
            }
        } else {
            foreach ($aliasNames as $idx => $aliasName) {
                $fieldName = $fieldNames[$idx];
                self::structSet('fieldProps', 'simple', $aliasName, $fieldName);
            }
        }

        self::addModifier($aliasNames, 'null_if_empty');
    }

    /**
     * Indica que ->v($aliasName) é um objeto relacionado, e não um campo no banco de dados.
     *
     *      $extItem = Array:
     *          ['className']  string Classe relacionada
     *          ['thisKey']    string Key nesta classe
     *          ['targetKey']  string Key na classe de destino
     *          ['reverseKey'] false | string A key que faz referência a esta classe, na classe de destino.
     *          ['className']  string Classe relacionada
     *          ['joinMode']   string 'left'|'inner' (default: 'left')
     *          ['extraOn']    false | string (default: false)
     *
     * @param string $aliasName
     * @param string $extItem "className::thisKey"
     * @param string $extItem "className::thisKey::reverseKey"
     * @param array $extItem Veja $extItem acima.
     */
    static function addExt($aliasName, $extItem)
    {
        // Parâmetros:
        //   addExt($aliasName, Array(....))
        //   addExt($aliasName, "className::thisKey");
        //   addExt($aliasName, "className::thisKey::reverseKey");
        if (is_string($extItem)) {
            $temp = explode("::", $extItem);
            $extItem = array();
            $extItem['className'] = trim($temp[0]);
            $extItem['thisKey'] = trim($temp[1]);
            if (sizeof($temp) == 3) {
                $extItem['reverseKey'] = trim($temp[2]);
            }
        }

        $extItem = array('type' => 'extObj') + $extItem + array(
                'className' => false,  // Required, se for FALSE haverá um erro crítico.
                'thisKey' => false,  // Required, se for FALSE haverá um erro crítico.
                'targetKey' => false,  // Opcional, padrão: false -> thisTable.thisKey = targetTable.targetKey
                'reverseKey' => false,  // Opcional, padrão: false -> relatedObj
                'joinMode' => 'left', // Opcional, padrão: 'left'
                'extraOn' => false,  // Opcional, padrão: false
                'autoLoad' => false,  // Opcional, padrão: false
            );
        self::structSet('fieldProps', 'external', $aliasName, $extItem);
        self::addModifier($extItem['thisKey'], 'check_foreign', $aliasName, 'raw2basic');
    }

    static function addModifier($aliasNames, $strMods, $param = false, $whens = false)
    {
        $aliasNames = is_string($aliasNames) ? explode(",", $aliasNames) : $aliasNames;
        $strMods = is_string($strMods) ? explode(",", $strMods) : $strMods;
        $whens = ($whens ? explode(",", $whens) : false); // raw2basic, basic2db, db2sql, db2basic

        foreach ($aliasNames as $aliasName) {
            foreach ($strMods as $strMod) {
                if (!$whens) {
                    // When é automático.
                    // Por padrão, tudo será adicionado em raw2basic.
                    //
                    // Exceções abaixo podem rodar em outros momentos.
                    if (in_array($strMod, array('date', 'datetime', 'serialize', 'serialize_text', 'json'))) {
                        $useWhens = array('basic2db', 'db2basic');
                    } elseif (in_array($strMod, array('dSerialize'))) {
                        $useWhens = array('raw2basic', 'basic2db', 'db2basic');
                    } else {
                        $useWhens = array('raw2basic');
                    }
                } else {
                    // When foi definindo manualmente.
                    $useWhens = $whens;
                }

                foreach ($useWhens as $useWhen) {
                    // Exceto para o callback, não faz nenhum sentido termos um mesmo modifier inserido duas vezes.
                    // To-do: Verificar modifiers duplicados.
                    self::structAppend('modifiers', $useWhen, $aliasName, array(
                        'strMod' => $strMod,
                        'param' => $param
                    ));
                }
            }
        }
    }

    static function addValidation($aliasNames, $strVal, $param = false, $errorStr = false, $whens = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $whens = ($whens ? explode(",", $whens) : false); // final (ou definido pelo usuário)

        // Callback pré-definido
        if ($strVal == 'unique') {
            $aliasesToCompare = ($param) ?
                ((!is_array($param)) ? explode(",", $param) : $param) :
                $aliasNames;

            $aliasName = (sizeof($aliasesToCompare) > 1) ? false : $aliasesToCompare[0];

            self::addValidation($aliasName, 'callback', function ($obj) use ($errorStr, $aliasesToCompare) {
                // Se estiver carregado, e os campos envolvidos não tiverem sido modificados,
                // então é uma query inútil. Em outras palavras, se ela já estava no banco
                // de dados, então ela não está duplicada... Se está, o erro ocorreu antes, e o
                // usuário não pode ser culpado por isso. Ou seja, se foi cadastrado errado, o
                // cadastro errôneo será mantido até que haja interesse real em modificar o campo
                // que deve ser único.

                if ($obj->isLoaded()) {
                    $_stop = true;
                    foreach ($aliasesToCompare as $aliasName) {
                        if ($obj->getOriginal($aliasName) !== $obj->getValue($aliasName)) {
                            // Ops, alguma coisa mudou nos campos importantes...
                            $_stop = false;
                            break;
                        }
                    }
                    if ($_stop) {
                        // Passou pela validação sem query.
                        return true;
                    }
                }

                // Se não houver valor nesses campos únicos, vamos ignorá-los.
                // Por exemplo, se o unique for o CPF, mas não houver CPF, vamos aceitar que podem existir dois CPFs vazios no sistema.
                // Já se estivermos verificando CPF+EMAIL, se AMBOS forem vazios, vamos aceitar. Senão, vamos fazer a comparação.
                $_stop = true;
                foreach ($aliasesToCompare as $aliasName) {
                    if ($obj->getValue($aliasName)) {
                        $_stop = false;
                        break;
                    }
                }
                if ($_stop) {
                    // Não tem valores para ver se é único ou não.
                    return true;
                }


                $tableName = $obj->structGet('tableName');
                $primaryKey = $obj->structGet('primaryKey');
                $useQuotes = $obj->structGet('useQuotes');
                $fieldNames = $obj->getDbAliasNames(true);

                $where = array();
                foreach ($aliasesToCompare as $aliasName) {
                    if ($obj->v($aliasName) === false || $obj->v($aliasName) === null) {
                        $where[] = "ISNULL({$useQuotes}{$aliasName}{$useQuotes})";
                    } else {
                        $where[] = "{$useQuotes}{$fieldNames[$aliasName]}{$useQuotes} = " . $obj->getModValue($aliasName,
                                'sql');
                    }
                }
                if ($obj->isLoaded()) {
                    $where[] = "{$useQuotes}{$fieldNames[$primaryKey]}{$useQuotes} != " . $obj->modApply('basic2sql',
                            $primaryKey, $obj->getPrimaryValue());
                }

                $q = "SELECT {$primaryKey} FROM  {$tableName}\r\n";
                $q .= "WHERE\r\n";
                $q .= "    " . implode("\n\t    AND ", $where) . "\r\n";
                $q .= "LIMIT 1";

                $db = $obj->getDb();
                if ($db->singleResult($q, "Checking for dupe result")) {
                    if (!is_string($errorStr) && is_callable($errorStr)) {
                        $errorStr = $valItem['errorStr']($this);
                    } else {
                        $allValues = array();
                        foreach ($aliasesToCompare as $aliasName) {
                            $allValues[$aliasName] = $obj->v($aliasName);
                        }
                        $errorStr = str_replace("{value}", implode("/", $allValues), $errorStr);
                    }

                    $obj->addError($aliasName, $errorStr);
                    return false;
                }

                return true;
            });
            return;
        }

        foreach ($aliasNames as $aliasName) {
            if (!$aliasName) {
                $aliasName = '---';
            }

            if (!$whens) {
                // When é automático.
                // Por padrão, tudo será adicionado em 'final'.
                $useWhens = array('final');
            } else {
                if (is_string($whens)) {
                    $useWhens = array($whens);
                } else {
                    // When foi fornecido de forma forçada.
                    $useWhens = &$whens;
                }
            }

            foreach ($useWhens as $useWhenItem) {
                self::structAppend('validations', $useWhenItem, $aliasName, array(
                    'strVal' => $strVal,
                    'param' => $param,
                    'errorStr' => $errorStr,
                ));
            }
        }
    }

    static function setDefaultValue($aliasNames, $value, $format = 'raw')
    {
        // Define o valor padrão para os campos em um novo objeto (que será salvo pela primeira vez).
        // - $value pode ser uma string (fácil)
        // - $value pode ser um callback ($obj, $aliasName)
        $aliasNames = is_array($aliasNames) ? $aliasNames : explode(",", $aliasNames);
        foreach ($aliasNames as $aliasName) {
            self::structSet('fieldDefaults', $aliasName, array('format' => $format, 'value' => &$value));
        }
    }

    static function setAuditing($cbAuditing)
    {
        self::structSet('cbAuditing', $cbAuditing);
    }

    static function removeModifier($aliasNames, $strMods, $whens = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $strMods = explode(",", $strMods);
        $whens = ($whens ? explode(",", $whens) : array('raw2basic', 'basic2db', 'db2sql', 'db2basic'));
        foreach ($aliasNames as $aliasName) {
            foreach ($strMods as $strMod) {
                foreach ($whens as $when) {
                    $_hasChanged = false;
                    $allModifiers = self::structGet('modifiers', $when, $aliasName);
                    if (!$allModifiers) {
                        continue;
                    }

                    foreach ($allModifiers as $idx => $modItem) {
                        if ($modItem['strMod'] == $strMod) {
                            // Found, remove it.
                            unset($allModifiers[$idx]);
                            $_hasChanged = true;
                        }
                    }

                    if ($_hasChanged) {
                        self::structSet('modifiers', $when, $aliasName, $allModifiers);
                    }
                }
            }
        }
    }

    static function removeValidation($aliasNames, $strVal, $whens = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $strVals = explode(",", $strVals);
        $whens = ($whens ? explode(",", $whens) : array('final'));
        foreach ($aliasNames as $aliasName) {
            foreach ($strVals as $strMod) {
                foreach ($whens as $when) {
                    $_hasChanged = false;
                    $allValidations = self::structGet('validations', $when, $aliasName);
                    if (!$allValidations) {
                        continue;
                    }

                    foreach ($allValidations as $idx => $valItem) {
                        if ($valItem['strVal'] == $strVal) {
                            // Found, remove it.
                            unset($allValidations[$idx]);
                            $_hasChanged = true;
                        }
                    }
                    if ($_hasChanged) {
                        self::structSet('validations', $when, $aliasName, $allValidations);
                    }
                }
            }
        }
    }

    static function addToConstructor($callback)
    {
        $constructorCbs = self::structGet('constructorCbs');
        if (!$constructorCbs) {
            $constructorCbs = array();
        }
        $constructorCbs[] = $callback;
        self::structSet('constructorCbs', $constructorCbs);
    }

    static function setCbMakeQuery($callback)
    {
        self::structSet('cbMakeQuery', $callback);
    }

    /**
     * @param $when 'afterLoad', 'beforeSave', 'afterSave', 'afterSaveFail'   --> $callback($obj)
     * @param $when '[afterC|c]reate', '[afterU|u]pdate', '[afterD|d]elete' --> $callback($obj, $id, $event, $data)
     * @param $callback
     * @return $eventId
     * @link https://docs.google.com/document/d/1HxhmPcCx4exl98hk6tx9vzufqb12n1_VEI_UiMVjtRc/edit#heading=h.rvoiaifbd51e Documentação oficial
     */
    static function addEventListener($when, $callback)
    {
        $when = explode(",", $when);
        $exEvents = self::structGet('eventListeners');
        if (!$exEvents) {
            $exEvents = array();
            $eventId = 1;
        } else {
            $aKeys = array_keys($exEvents);
            $eventId = array_pop($aKeys) + 1;
        }

        $exEvents[$eventId] = array(
            'callback' => $callback,
            'when' => $when,
        );
        self::structSet('eventListeners', $exEvents);
        return $eventId;
    }

    static function removeEventListener($eventId)
    {
        $exEvents = self::structGet('eventListeners');
        if (array_key_exists($eventId, $exEvents)) {
            unset($exEvents[$eventId]);
        }

        self::structSet('eventListeners', $exEvents);
    }

    // Helpers:
    static function structExists()
    {
        return isset(self::$struct[get_called_class()]['tableName']);
    }

    static function structSet($setProp /*, ... */, $setValue)
    {
        // structSet($struct)
        // structSet($setProp, $setValue)
        // structSet($setProp, $setPropKey, $setValue)
        // structSet($setProp, $setPropKey, $setPropSubKey, $setValue)
        // structSet($setProp, $setPropKey, $setPropSubKey, $setPropSubSubKey, $setValue)
        $funcN = func_num_args();
        $className = get_called_class();
        if ($funcN == 1) {
            self::$struct[$className] = func_get_arg(0);
        } elseif ($funcN == 2) {
            self::$struct[$className][func_get_arg(0)] = func_get_arg(1);
        } elseif ($funcN == 3) {
            if (!isset(self::$struct[$className][func_get_arg(0)])) {
                self::$struct[$className][func_get_arg(0)] = array();
            }
            self::$struct[$className][func_get_arg(0)][func_get_arg(1)] = func_get_arg(2);
        } elseif ($funcN == 4) {
            if (!isset(self::$struct[$className][func_get_arg(0)])) {
                self::$struct[$className][func_get_arg(0)] = array();
            }
            if (!isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)])) {
                self::$struct[$className][func_get_arg(0)][func_get_arg(1)] = array();
            }
            self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)] = func_get_arg(3);
        } elseif ($funcN == 5) {
            if (!isset(self::$struct[$className][func_get_arg(0)])) {
                self::$struct[$className][func_get_arg(0)] = array();
            }
            if (!isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)])) {
                self::$struct[$className][func_get_arg(0)][func_get_arg(1)] = array();
            }
            if (!isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)])) {
                self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)] = array();
            }
            self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)][func_get_arg(3)] = func_get_arg(4);
        }
    }

    static function structAppend($setProp /*, ... */, $setValue)
    {
        // structAppend($setProp, $setValue)
        // structAppend($setProp, $setPropKey, $setValue)
        // structAppend($setProp, $setPropKey, $setPropSubKey, $setValue)
        // structAppend($setProp, $setPropKey, $setPropSubKey, $setPropSubSubKey, $setValue)
        $funcN = func_num_args();
        $className = get_called_class();
        if ($funcN == 2) {
            self::$struct[$className][func_get_arg(0)][] = func_get_arg(1);
        } elseif ($funcN == 3) {
            if (!isset(self::$struct[$className][func_get_arg(0)])) {
                self::$struct[$className][func_get_arg(0)] = array();
            }
            self::$struct[$className][func_get_arg(0)][func_get_arg(1)][] = func_get_arg(2);
        } elseif ($funcN == 4) {
            if (!isset(self::$struct[$className][func_get_arg(0)])) {
                self::$struct[$className][func_get_arg(0)] = array();
            }
            if (!isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)])) {
                self::$struct[$className][func_get_arg(0)][func_get_arg(1)] = array();
            }
            self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)][] = func_get_arg(3);
        } elseif ($funcN == 5) {
            if (!isset(self::$struct[$className][func_get_arg(0)])) {
                self::$struct[$className][func_get_arg(0)] = array();
            }
            if (!isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)])) {
                self::$struct[$className][func_get_arg(0)][func_get_arg(1)] = array();
            }
            if (!isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)])) {
                self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)] = array();
            }
            self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)][func_get_arg(3)][] = func_get_arg(4);
        }
    }

    static function structGet(/* $getProp, ... */)
    {
        // structGet()
        // structGet($getProp)
        // structGet($getProp, $getPropKey)
        // structGet($getProp, $getPropKey, $getPropSubKey)
        // structGet($getProp, $getPropKey, $getPropSubKey, $getPropSubSubKey)
        $funcN = func_num_args();
        $className = get_called_class();

        if ($funcN == 0) {
            return isset(self::$struct[$className]) ?
                self::$struct         [$className] :
                false;
        } elseif ($funcN == 1) {
            return isset(self::$struct[$className][func_get_arg(0)]) ?
                self::$struct         [$className][func_get_arg(0)] :
                false;
        } elseif ($funcN == 2) {
            return isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)]) ?
                self::$struct         [$className][func_get_arg(0)][func_get_arg(1)] :
                false;
        } elseif ($funcN == 3) {
            return isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)]) ?
                self::$struct         [$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)] :
                false;
        } elseif ($funcN == 4) {
            return isset(self::$struct[$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)][func_get_arg(3)]) ?
                self::$struct         [$className][func_get_arg(0)][func_get_arg(1)][func_get_arg(2)][func_get_arg(3)] :
                false;
        }
    }

    static function structClear()
    {
        unset(self::$struct[get_called_class()]);
    }

    // Atributos de cada objeto instanciado:
    // ----------------------------------------------------
    public $isLoaded = false;   // Is loaded/synced with database
    public $fieldValues = array(); // O aliasName não existirá até que seja alterado, e será excluído após ser salvo (movido para fieldOriginal)
    public $extObjects = array(); // Tal como fieldValues, mas conterá objetos carregados
    public $extra = array(); // Container para dados adicionais, apenas quando existirem. Sobrevive à sessão. (fieldVirtuals, fieldOriginal)
    public $temp = array(); // Container para dados temporários, enquanto existirem.     Não sobrevive à sessão. (errorList, outros, enquanto necessários)
    public $uid;

    // Handling and manipulating object data
    function __construct($useOnly = false, $applyDefaults = true)
    {
        $className = get_called_class();
        if (!self::structExists()) {
            $className::buildStruct();
        }

        $useOnly = ($useOnly) ?
            (is_array($useOnly) ? $useOnly : explode(",", $useOnly)) :
            array_keys(self::structGet('fieldProps', 'simple'));

        foreach ($useOnly as $aliasName) {
            $this->fieldValues[$aliasName] = false;
        }

        $this->_extConstruct($useOnly);
        $this->uid = ++dDbRow3::$uniqueId;

        $_extraOnConstruct = self::structGet('constructorCbs');
        if ($_extraOnConstruct) {
            foreach ($_extraOnConstruct as $callback) {
                $callback($this);
            }
        }

        if ($applyDefaults && ($_defValues = self::structGet('fieldDefaults'))) {
            foreach ($_defValues as $aliasName => $item) {
                if (array_key_exists($aliasName, $this->fieldValues)) {
                    $value = &$item['value'];
                    $isCallback = (!is_string($value) && !is_array($value) && is_callable($value));
                    $this->setValue($aliasName, $isCallback ? $value($this, $aliasName) : $value, $item['format']);
                }
            }
        }

        # self::log("__construct() - new ".get_class($this).". uid={$this->uid}.".($useOnly?" with useOnly=".(is_array($useOnly)?implode(", ", $useOnly):$useOnly):""));
    }

    function setVirtual($aliasName, $value)
    {
        if (!isset($this->extra['fieldVirtuals'])) {
            $this->extra['fieldVirtuals'] = array();
        }
        $this->extra['fieldVirtuals'][$aliasName] = $value;
    }

    function getVirtual($aliasName)
    {
        return $this->hasVirtual($aliasName) ?
            $this->extra['fieldVirtuals'][$aliasName] :
            null;
    }

    function hasVirtual($aliasName)
    {
        return isset($this->extra['fieldVirtuals'][$aliasName]);
    }

    function removeVirtual($aliasName)
    {
        unset($this->extra['fieldVirtuals'][$aliasName]);
        if (isset($this->extra['fieldVirtuals']) && !$this->extra['fieldVirtuals']) {
            unset($this->extra['fieldVirtuals']);
        }
    }

    function setValue($aliasName, $value, $format = 'raw')
    {
        if (isset($this->extObjects[$aliasName])) {
            $this->extObjects[$aliasName] = $value;
            return $this;
        }

        if (!$this->isAliasEnabled($aliasName)) {
            array_key_exists($aliasName, self::structGet('fieldProps', 'simple')) ?
                trigger_error("setValue({$aliasName}, '{$value}'): aliasName was disabled in the constructor.") :
                trigger_error("setValue({$aliasName}, '{$value}'): aliasName was not defined in the buildStruct.");

            return false;
        }

        $newValue = $this->modApply("{$format}2basic", $aliasName, $value);
        $oldValue = &$this->fieldValues[$aliasName];

        if ((!is_bool($newValue) && !is_bool($oldValue) && $newValue == $this->fieldValues[$aliasName]) || $newValue === $this->fieldValues[$aliasName]) {
            # echo "setValue($aliasName, $value, 'basic'): É igual ao getOriginal($aliasName), ignorando chamado.<br />\r\n";
            return $this;
        }
        if ($this->isLoaded()) {
            // Devo salvar o valor original?
            if (!isset($this->extra['fieldOriginal'][$aliasName])) {
                $this->extra['fieldOriginal'][$aliasName] = $this->fieldValues[$aliasName];
            } else {
                $cmpOriginal = $this->extra['fieldOriginal'][$aliasName];
                $cmpNewValue = $newValue;

                if (is_numeric($cmpOriginal)) {
                    $cmpOriginal = (string)$cmpOriginal;
                }
                if (is_numeric($cmpNewValue)) {
                    $cmpNewValue = (string)$cmpNewValue;
                }
                if (!is_string($cmpOriginal) || !is_string($cmpNewValue)) {
                    $cmpOriginal = serialize($cmpOriginal);
                    $cmpNewValue = serialize($cmpNewValue);
                }

                if ($cmpOriginal === $cmpNewValue) {
                    // O novo valor é igual ao valor original, então na verdade, não temos um novo valor
                    // e um valor antigo, apenas um único valor, que é o que está carregado.
                    # echo "-- Excluindo original {$aliasName}<br />";
                    unset($this->extra['fieldOriginal'][$aliasName]);
                } else {
                    # echo "Não fazendo nada com o original do {$aliasName}<br />";
                }
            }
        }

        # echo "setValue($aliasName, {$value}, 'basic')<br />";
        $this->fieldValues[$aliasName] = $newValue;
        return $this;
    }

    function getValue($aliasName, $autoNew = false)
    {
        if (isset($this->extObjects[$aliasName])) {
            return $this->_extGetValue($aliasName, $autoNew);
        }
        if (!$this->isAliasEnabled($aliasName)) {
            array_key_exists($aliasName, self::structGet('fieldProps', 'simple')) ?
                trigger_error(get_called_class() . "::getValue({$aliasName}): aliasName exists, but was not loaded due to constructor parameters.") :
                trigger_error(get_called_class() . "::getValue({$aliasName}): aliasName does not exist (not specified in the ::buildStruct).");
            return false;
        }

        return $this->fieldValues[$aliasName];
    }

    function getModValue($aliasName, $format)
    {
        return $this->modApply("basic2{$format}", $aliasName, $this->v($aliasName));
    }

    function v()
    {
        $aliasName = func_get_arg(0);
        switch (func_num_args()) {
            case 1:
                return $this->getValue($aliasName);

            case 2:
                if (func_get_arg(1) === true && array_key_exists($aliasName, $this->extObjects)) {
                    // Se for um aliasName externo, é um alias para getValue($aliasName, true).
                    // Ou seja, sempre vai retornar um objeto instanciado (ou instanciar automaticamente).
                    return $this->getValue($aliasName, true);
                }
                return $this->setValue($aliasName, func_get_arg(1));

            case 3:
                return $this->setValue($aliasName, func_get_arg(1), func_get_arg(2));
        }
    }

    function getOriginal($aliasName)
    {
        // Se existir o getOriginal, então certamente já foi alterado.
        // Nesse caso retorne o original.
        //
        // Caso contrário, se estiver carregado, retorne ele mesmo.
        // Se não estiver carregado, então não existe original, então é sempre FALSE.
        //
        if ($this->isLoaded()) {
            if (isset($this->extra['fieldOriginal']) && array_key_exists($aliasName, $this->extra['fieldOriginal'])) {
                return $this->extra['fieldOriginal'][$aliasName];
            }

            return $this->getValue($aliasName);
        }

        return false;
    }

    function getPrimaryValue()
    {
        if (!self::structGet('primaryKey')) {
            return false;
        }

        if (!$this->isLoaded()) {
            return false;
        }

        return $this->getOriginal(self::structGet('primaryKey'));
    }

    function setLoaded($yesno)
    {
        // Se não estiver loaded, então não tem original (não está no database)
        // Se estiver loaded, então o ponto atual é snapshot, o original será
        // criado apenas quando modificado.
        $this->isLoaded = $yesno;
        unset($this->extra['fieldOriginal']);
    }

    // Handling modifiers:
    function modApplyAll($when, &$array)
    {
        foreach ($array as $aliasName => $value) {
            if (is_null($value) && $when == 'db2basic') {
                $value = false;
            }

            $array[$aliasName] = self::sModApply($when, $aliasName, $value, $this, $array);
        }
        return $this;
    }

    function modApply($when, $aliasName, $value)
    {
        return self::sModApply($when, $aliasName, $value, $this);
    }

    static function sModApply($when, $aliasName, $value, $useObj = false, &$array = false)
    {
        if ($when == 'raw2db') {
            $value = self::sModApply('raw2basic', $aliasName, $value, $useObj);
            $value = self::sModApply('basic2db', $aliasName, $value, $useObj);
            return $value;
        } elseif ($when == 'raw2sql') {
            $value = self::sModApply('raw2basic', $aliasName, $value, $useObj);
            $value = self::sModApply('basic2db', $aliasName, $value, $useObj);
            $value = self::sModApply('db2sql', $aliasName, $value, $useObj);
            return $value;
        } elseif ($when == 'basic2sql') {
            $value = self::sModApply('basic2db', $aliasName, $value, $useObj);
            $value = self::sModApply('db2sql', $aliasName, $value, $useObj);
            return $value;
        } elseif ($when == 'raw2raw') {
            return $value;
        } elseif ($when == 'basic2basic') {
            return $value;
        } elseif ($when == 'db2db') {
            return $value;
        } elseif ($when == 'sql2sql') {
            return $value;
        }

        $applyMods = self::structGet('modifiers', $when, $aliasName);
        if (!$applyMods && $when == 'db2sql') {
            // Exceção:
            // --> db2sql será chamado para todos os campos em $array.
            // --> Se existir um modifier específico (callback provavelmente), este será utilizado.
            // --> Caso contrário, o modifier 'formatToQuery' será utilizado.
            $applyMods = array(array('strMod' => 'formatToQuery', 'param' => false, 'when' => $when));
        }
        if ($applyMods) {
            $_applyAfter = array();
            foreach ($applyMods as $modItem) {
                // Exceções:
                //   'callback'      é processado diretamente por aqui
                //   'null_if_empty' é sempre processado após os demais modifiers.
                if ($modItem['strMod'] == 'callback') {
                    $value = is_array($modItem['param']) ?
                        (is_object($modItem['param'][0]) ?
                            $modItem['param'][0]->$modItem['param'][1]($useObj, $value, $when, $aliasName, $array) :
                            $modItem['param'][0]::$modItem['param'][1]($useObj, $value, $when, $aliasName, $array)) :
                        $modItem['param']($useObj, $value, $when, $aliasName, $array);
                    continue;
                } elseif ($modItem['strMod'] == 'null_if_empty') {
                    $_applyAfter[] = $modItem;
                    continue;
                } else {
                    $value = self::sModifyStr($value, $modItem['strMod'], $modItem['param'], $when, $useObj);
                }
            }
            if ($_applyAfter) {
                foreach ($_applyAfter as $modItem) {
                    $value = self::sModifyStr($value, $modItem['strMod'], $modItem['param'], $when, $useObj);
                }
            }
        }

        return (!is_string($value) && is_numeric($value)) ?
            (string)$value :
            $value;
    }

    static function sModifyStr($value, $strMod, $param = false, $when = 'raw2basic', $useObj = false)
    {
        // Os mods são aplicados conforme definido na estrutura da classe.
        //
        // Por exemplo, 'date' por estar registrado em basic2db e não o contrário.
        // Dessa forma, mesmo tratado aqui, o 'date' não seria chamado em db2basic.
        // Essa regra automática deve ser feita no construtor addModifier()
        //
        // Importante: Se não for possível executar uma modificação no raw2basic,
        // retorne o valor incorreto informado pelo usuário, e deixe a validação cuidar do assunto.
        //
        // To-do:
        //   Este modificador também será chamado pelo ::load, e nesse caso ficaria complicado
        //   retornar valores que não puderam passar corretamente pelos modificadores. No entanto,
        //   esses valores também não passam pela validação. E agora!? Talvez o ideal seja fazer
        //   esses valores passarem pela validação.
        //
        // No entanto, se não for possível executar uma modificação basic2db, então
        // cabe a você decidir se a informação será armazenada erroneamente ou o valor será
        // salvo como FALSE. Se isso acontecer, é recomendável um setValue(aliasName, false, 'basic')
        // para manter o objeto ativo sincronizado com o que consta no banco de dados.
        //
        // A exceção é a key db2sql, que é chamada para todos os campos.
        //
        if ($strMod == 'check_foreign') {
            // Se o usuário modificou manualmente um campo que é necessário para
            // algo externo, devemos limpar o objeto em questão (tal como o 'id'):
            //
            // Por exemplo, o objeto categObj tem id=1 e está carregado ($obj->v('categObj'))
            // Na sequencia, o usuário inocentemente faz $obj->v('categ_id', 1).
            //
            // Assumindo que já estava carregado com o id=1, vamos mantê-lo carregado sem fazer nada.
            // Se estava carregado, mas com outro ID, então apague o categObj (volte-o para o status de on-demand)
            // Se já tentou carregar anteriormente, e gravou '**not_found**' (cache), então é a hora de limpar o cache.
            //
            // Lembrando que as vezes, podemos chamar isso sem $obj, para uma verificação simples de string.
            // Se isso acontecer, não faremos nada neste modificador.
            //
            // --> useObj é o objeto principal cujo 'id' mudou, por exemplo.
            // --> param  é o aliasName do objeto externo
            // --> extObj é o objeto relacionado, tal como em this->v($param)
            //
            if (!$useObj) {
                // É uma validação de string simples, não há nada a fazer.
                return $value;
            }

            $aliasName = $param;
            $extInfo = self::structGet('fieldProps', 'external', $aliasName);
            if ($useObj->getValue($extInfo['thisKey']) === $value) {
                // Nada foi alterado, então não há nada a fazer.
                return $value;
            }

            $extObj = $useObj->extObjects[$aliasName];
            if (!$extObj) {
                // Não havia um extObj carregado, então não há nada a fazer.
                return $value;
            }
            if (is_string($extObj) && $extObj == '**not_found**') {
                // O extObj era uma informação de not_found em cache.
                // Vamos limpar a informação de cache (ex: categObj) e prosseguir normalmente.
                $useObj->extObjects[$aliasName] = false;
                return $value;
            }

            // Já estava carregado anteriormente.
            // Vamos comparar o primaryKey do objeto que já estava carregado, com o
            // novo valor fornecido. Se for o mesmo, podemos ignorar. Caso contrário,
            // então vamos limpar o objeto anterior e preparar caminho para um novo objeto.
            $value = self::sModApply('raw2basic', $extInfo['thisKey'], $value);
            $className = get_class($extObj);
            $targetKey = $className::structGet('primaryKey');
            if ($extObj->v($targetKey) != $value) {
                $useObj->extObjects[$aliasName] = false;
            }

            return $value;
        } elseif ($strMod == 'formatToQuery') {
            if (!isset($value) || $value === false) {
                return 'NULL';
            }
            if (is_float($value)) {
                return $value;
            }

            return "'" . addslashes($value) . "'";
        } elseif ($strMod == 'trim') {
            $value = trim($value);
        } elseif ($strMod == 'force_int') {
            $value = (trim($value) !== '') ? intval($value) : false;
        } elseif ($strMod == 'force_numbers') {
            if (!empty($value)) {
                $value = preg_replace("/[^0-9]/", "", $value);
            }
        } elseif ($strMod == 'force_float') {
            if (trim($value) === '') {
                $value = false;
            } else {
                $value = strtr($value, ",.", "..");
                $parts = explode(".", $value);
                if (sizeof($parts) > 1) {
                    $value = $parts[sizeof($parts) - 1];
                    unset($parts[sizeof($parts) - 1]);
                    $value = (float)(join("", $parts) . ".{$value}");
                    if ($param) {
                        $value = round($value, $param);
                    }
                } else {
                    $value = intval($parts[0]);
                }
                unset($parts);
            }
        } elseif ($strMod == 'force_legible') {
            $value = preg_replace("/[^A-Za-zÀ-ú0-9]/u", "-", $value);
        } elseif ($strMod == 'lower') {
            $value = mb_strtolower($value);
        } elseif ($strMod == 'upper') {
            $value = mb_strtoupper($value);
        } elseif ($strMod == 'ucfirst') {
            $value =
                mb_strtoupper(mb_substr($value, 0, 1)) .
                mb_substr($value, 1);
        } elseif ($strMod == 'ucwords') {
            $value = mb_convert_case($str, MB_CASE_TITLE);
        } elseif ($strMod == 'remove_double_spaces') {
            $value = preg_replace("/  +/", "  ", $value);
        } elseif ($strMod == 'remove_accents') {
            $value = dHelper2::removeAccents($value);
        } elseif ($strMod == 'null_if_empty') {
            if ($value === '') {
                $value = false;
            }
            if ($value === null) {
                $value = false;
            }
        } elseif ($strMod == 'number_mask') {
            if ($when == 'basic2db') { // To database
                // A validação deixou chegar até aqui, então devemos ter uma informação correta.
                $value = preg_replace("/[^0-9]/", "", $value);
            } else {
                // Ou seja, db2basic ou raw2basic, que podem ser tratados da mesma forma.
                // Tenta aplicar a máscara, independente do que o usuário digitou.
                // Se não conseguir, retorna o valor informado pelo usuário e deixa a validação cuidar do assunto.
            }
        } elseif ($strMod == 'date' || $strMod == 'datetime') {
            if (strtoupper($param) == 'BR') {
                $date = explode(" ", $value, 2);
                if ($when == 'basic2db') { // To database 16/10/2004 => 2004-10-16
                    $parts = explode("/", $date[0]);
                    if (is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                        if (strlen($parts[2]) <= 2) {
                            if ($parts[2] <= 25) {
                                $parts[2] = "20" . $parts[2];
                            } else {
                                $parts[2] = "19" . $parts[2];
                            }
                        }
                        $date[0] = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
                    } else {
                        $value = false;
                    }
                } elseif ($when == 'db2basic') { // From database 2004-10-16 => 16/10/2004
                    $parts = explode("-", $date[0]);
                    if (is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                        $date[0] = sprintf('%02d/%02d/%04d', $parts[2], $parts[1], $parts[0]);
                    } else {
                        $value = false;
                    }
                }

                if ($value && $strMod == 'datetime') {
                    $value = join(" ", $date);
                } elseif ($value) {
                    $value = $date[0];
                }
            }
        } elseif ($strMod == 'serialize') {
            if ($value !== false) {
                if ($when == 'basic2db') {
                    $value = serialize($value);
                } elseif ($when == 'db2basic') {
                    $value = unserialize($value);
                }
            }
        } elseif ($strMod == 'serialize_text') {
            if (is_array($value) && $when == 'basic2db') {
                $str = '';
                $first = true;
                foreach ($value as $key => $val) {
                    if ($first) {
                        $first = false;
                    } else {
                        $str .= "\n";
                    }

                    $val = preg_replace("/\r?\n/", " ", $val);
                    $str .= "{$key}: {$val}";
                }
                $value = $str;

                unset($first, $str);
            } elseif ($value && $when == 'db2basic') {
                $ret = array();
                $lines = explode("\n", $value);
                foreach ($lines as $line) {
                    $tmp = explode(": ", $line, 2);
                    $ret[$tmp[0]] = rtrim($tmp[1], "\r\n");
                }
                $value = $ret;
                unset($lines, $line, $tmp);
            }
        } elseif ($strMod == 'dSerialize') {
            if ($when == 'raw2basic') {
                $value = is_null($value) ? false : $value;
            } elseif ($when == 'basic2db') {
                $value = (is_bool($value) && $value === false) ?
                    false :
                    dHelper2::dSerialize($value);
            } elseif ($when == 'db2basic') {
                $value = (is_bool($value) && $value === false) ?
                    false :
                    dHelper2::dUnserialize($value);
            }
        } elseif ($strMod == 'url') {
            if ($value && strpos($value, "://") === false) {
                $value = "http://" . $value;
            }
        } elseif ($strMod == 'cpf' || $strMod == 'cnpj' || $strMod == 'cpf_cnpj') {
            return dHelper2::formataCpfCnpj($value);
        } elseif ($strMod == 'json') {
            if ($when == 'basic2db') {
                if (is_bool($value) && !$value) {
                    $value = false;
                } else {
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            } elseif ($when == 'db2basic' && $value) {
                $value = json_decode($value, true);
            }
            return $value;
        }

        return $value;
    }

    static function formatToQuery($value, $aliasName = false, $from = 'raw')
    {
        return ($aliasName) ?
            self::sModApply($from . '2sql', $aliasName, $value) :
            self::sModifyStr($value, 'formatToQuery', false, 'db2sql');
    }

    // Handling validations:
    function valPassed($when = 'final', $aliasNames = true, $applyGlobal = true)
    {
        // Tipos conhecidos de validação:
        // No momento, apenas 'final'. Usuário pode criar outros sob medida.
        //
        // $aliasNames são os campos a serem validados, ou TRUE, que indica
        // uma validação de todos os campos.
        //
        // O parâmetro applyGlobal resulta na verificação das validações '---',
        // sendo inicialmente, callbacks (e validações do tipo UNIQUE).
        //
        // Se $aliasName for fornecido apenas com itens começando com "!", então
        // serão considerados todos os aliasNames exceto os marcados. Se houver "!"
        // e também aliasNames normais, os "!" serão totalmente ignorados.
        //
        $this->clearErrors();

        if (!self::structGet('validations', $when)) {
            // Nenhuma validação a ser aplicada neste momento.
            // Ou seja, passou.
            return true;
        }

        if ($aliasNames === true) {
            $aliasNames = $this->getDbAliasNames();
        } elseif (is_string($aliasNames) || is_array($aliasNames)) {
            if (!is_array($aliasNames)) {
                $aliasNames = explode(",", $aliasNames);
            }
            $_tmpExclude = array();
            $_tmpInclude = array();
            foreach ($aliasNames as $aliasName) {
                if ($aliasName[0] == "!") {
                    $_tmpExclude[] = substr($aliasName, 1);
                    continue;
                }
                $_tmpInclude[] = $aliasName;
            }

            if ($_tmpInclude) {
                $aliasNames = $_tmpInclude;
            } elseif ($_tmpExclude) {
                $aliasNames = array_diff($this->getDbAliasNames(), $_tmpExclude);
            }
        }

        $passed = true;
        if ($applyGlobal && self::structGet('validations', $when, '---')) {
            // Tem algumas validações globais para verificar também.
            $aliasNames[] = '---';
        }

        foreach ($aliasNames as $aliasName) {
            if ($aliasName != '---' && !array_key_exists($aliasName, $this->fieldValues)) {
                // Skipping validation as the aliasName is not being used in this object.
                // (Result of a load using 'onlyFields'=>'...')
                continue;
            }

            $value = ($aliasName != '---') ?
                $this->fieldValues[$aliasName] :
                false;

            $_valPassed = self::sValPassed($when, $aliasName, $value, $this);
            if (!$_valPassed) {
                $passed = false;
            }
        }
        # echo $passed?"<font color='#009900'>&gt;&gt; Passed</font>":"<font color='red' title='".implode(", ", $this->listErrors(true))."'><b>&gt;&gt; Global failed...</b></font>";
        # echo "<br />";

        if (!$passed && !$this->listErrors(true)) {
            $this->addError(false,
                "Não foi possível salvar, mas não existem detalhes do erro. Já fomos notificados e estamos investigando o problema.");
            dSystem::notifyAdmin('HIGH', "Não passou pela validação e não tem erros.",
                "dDbRow3 - Passamos pela validação, mas nada foi adicionado utilizando ->addError().\r\n" .
                "Provavelmente culpa de algum callback, que retornou FALSE e não execitou o addError().\r\n" .
                "\r\n" .
                "Para o cliente, foi mostrada a seguinte de erro: <b>Não foi possível salvar, mas não existem detalhes do erro. Já fomos notificados e estamos investigando o problema.</b>" .
                "Classe: " . get_class($this),
                false
            );
        }

        // Em caso de erro, aciona addError(aliasName, mensagem)
        // A mensagem terá {value} substituído pelo texto informado pelo usuário (com htmlspecialchars)
        return $passed;
    }

    static function sValPassed($when, $aliasName, $value, $useObj = false)
    {
        // Importante:
        //   Até segunda ordem, se não houver $useObj, callbacks não serão validados.
        //   Isso porque a documentação solicita que sejam inseridos $this->addError() quando o
        //   retorno é falso, e se não há um objeto, isso se torna impossível.
        //
        //   Quando aplicável, vamos ver a viabilidade de adicionar um $when especial, tal como
        //   'sFinal', que fará com que aquele callback seja executado também nessas situações.
        //
        $passed = true;
        $valList = self::structGet('validations', $when, $aliasName);
        if ($valList) {
            foreach ($valList as $valItem) {
                $strVal = $valItem['strVal'];
                $param = $valItem['param'];
                $errorStr = $valItem['errorStr'];

                if ($valItem['strVal'] == 'callback') {
                    if (!$useObj) {
                        continue;
                    }

                    $callbackRet = is_array($valItem['param']) ?
                        (is_object($valItem['param'][0]) ?
                            $valItem['param'][0]->$valItem['param'][1]($useObj, $value, $aliasName, $when) :
                            $valItem['param'][0]::$valItem['param'][1]($useObj, $value, $aliasName, $when)) :
                        $valItem['param']($useObj, $value, $aliasName, $when);

                    // Se for FALSE, então fracassou, e teve addError adicionado manualmente.
                    // Se for TRUE,  então passou com sucesso
                    if (!$callbackRet) {
                        # echo " <font color='#FF0000' title='_erro_nao_retornado_'>({$valItem['strVal']})</font> ";
                        $passed = false;
                    }
                    # else{
                    # echo " <font color='#006600'>({$valItem['strVal']})</font> ";
                    # }
                } elseif (self::sValFailed($value, $strVal, $param)) {
                    // Já as validações pré-definidas tem comportamento diferente,
                    // Elas retornam TRUE se falharem, e o addError é chamado por aqui mesmo.
                    if (!is_string($valItem['errorStr']) && is_callable($valItem['errorStr'])) {
                        $errorStr = $valItem['errorStr']($useObj);
                    } else {
                        $errorStr = (is_string($value) || is_numeric($value)) ?
                            str_replace("{value}", $value, $valItem['errorStr']) :
                            $valItem['errorStr'];
                    }

                    if ($useObj) {
                        $useObj->addError($aliasName, $errorStr);
                    }
                    $passed = false;
                    # echo " <font color='#FF0000' title='{$errorStr}'>({$valItem['strVal']})</font> ";
                }
                # else{
                # echo " <font color='#006600'>({$valItem['strVal']})</font> ";
                # }
            }
            # echo "<br />";
        }
        return $passed;
    }

    static function sValFailed($value, $strVal, $param = false)
    {
        if ($strVal == 'required') {
            if ((is_array($value) && !$value) || (!is_array($value) && !strlen($value))) {
                return true;
            }
        } elseif ($strVal == 'int' && $value) {
            if ((int)$value == $value) {
                return false;
            }

            if (preg_replace("/[^0-9]/", "", $value) == $value) {
                return false;
            }

            return true;
        } elseif ($strVal == 'nummin' && $value !== false) {
            if ($value < $param) {
                return true;
            }
        } elseif ($strVal == 'nummax' && $value !== false) {
            if ($value > $param) {
                return true;
            }
        } elseif ($strVal == 'email' && $value) {
            if (!preg_match("/^[a-zA-Z0-9_][\w\.-]*[a-zA-Z0-9_]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/",
                $value)) {
                return true;
            }
        } elseif ($strVal == 'strmin' && $value) {
            if (strlen($value) < $param) {
                return true;
            }
        } elseif ($strVal == 'strmax' && $value) {
            if (strlen($value) > $param) {
                return true;
            }
        } elseif ($strVal == 'strexact' && $value) {
            if (strlen($value) != $param) {
                return true;
            }
        } elseif ($strVal == 'singleline' && $value) {
            if (preg_match("/(\n|\r|\r\n)/", $value)) {
                return true;
            }
        } elseif ($strVal == 'regex' && $value) {
            if (substr($param, 0, 1) != '/') {
                $param = "/$param/";
            }
            return !preg_match($param, $value) ? true : false;
        } elseif ($strVal == '!regex' && $value) {
            if (substr($param, 0, 1) != '/') {
                $param = "/$param/";
            }
            return preg_match($param, $value) ? true : false;
        } elseif ($strVal == 'date' && $value) {
            $param = strtoupper($param);
            if ($param == 'BR') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/"; // 31/01/2007
            } elseif ($param == 'US') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/"; // 01/31/2007
            } else {
                $this->castMsg(2, "Date validation param '$param' not recognized. Only BR / US are accept.");
                return false;
            }
            return preg_match($pattern, $value) ? false : true;
        } elseif ($strVal == 'datetime' && $value) {
            $param = strtoupper($param);
            if ($param == 'BR') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4} [012]?[0-9]:[0-5]?[0-9](\:[0-5]?[0-9])?$/"; // 31/01/2007
            } elseif ($param == 'US') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4} [012]?[0-9]:[0-5]?[0-9](\:[0-5]?[0-9])?$/"; // 01/31/2007
            } else {
                $this->castMsg(2, "Date validation param '$param' not recognized. Only BR / US are accept.");
                return false;
            }
            return preg_match($pattern, $value) ? false : true;
        } elseif ($strVal == 'cpf' && $value) {
            $RecebeCPF = preg_replace("/[^0-9]/", "", $value);
            if (strlen($RecebeCPF) != 11) {
                return true;
            } else {
                for ($x = 0; $x <= 9; $x++) {
                    $tmp[] = str_repeat($x, 11);
                }

                if (in_array($RecebeCPF, $tmp)) {
                    return true;
                } else {
                    $Numero[1] = intval(substr($RecebeCPF, 1 - 1, 1));
                    $Numero[2] = intval(substr($RecebeCPF, 2 - 1, 1));
                    $Numero[3] = intval(substr($RecebeCPF, 3 - 1, 1));
                    $Numero[4] = intval(substr($RecebeCPF, 4 - 1, 1));
                    $Numero[5] = intval(substr($RecebeCPF, 5 - 1, 1));
                    $Numero[6] = intval(substr($RecebeCPF, 6 - 1, 1));
                    $Numero[7] = intval(substr($RecebeCPF, 7 - 1, 1));
                    $Numero[8] = intval(substr($RecebeCPF, 8 - 1, 1));
                    $Numero[9] = intval(substr($RecebeCPF, 9 - 1, 1));
                    $Numero[10] = intval(substr($RecebeCPF, 10 - 1, 1));
                    $Numero[11] = intval(substr($RecebeCPF, 11 - 1, 1));

                    $soma = 10 * $Numero[1] + 9 * $Numero[2] + 8 * $Numero[3] + 7 * $Numero[4] + 6 * $Numero[5] + 5 * $Numero[6] + 4 * $Numero[7] + 3 * $Numero[8] + 2 * $Numero[9];
                    $soma = $soma - (11 * (intval($soma / 11)));
                    $resultado1 = ($soma == 0 || $soma == 1) ? 0 : (11 - $soma);

                    if ($resultado1 == $Numero[10]) {
                        $soma = $Numero[1] * 11 + $Numero[2] * 10 + $Numero[3] * 9 + $Numero[4] * 8 + $Numero[5] * 7 + $Numero[6] * 6 + $Numero[7] * 5 + $Numero[8] * 4 + $Numero[9] * 3 + $Numero[10] * 2;
                        $soma = $soma - (11 * (intval($soma / 11)));
                        $resultado2 = ($soma == 0 || $soma == 1) ? 0 : ($resultado2 = 11 - $soma);

                        if ($resultado2 == $Numero[11]) {
                            // Tudo correto!
                            return false;
                        } else {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        } elseif ($strVal == 'cnpj' && $value) {
            $cnpj = preg_replace("/[^0-9]/", "", $value);
            if (strlen($cnpj) <> 14) {
                // CNPJ Tem que ter 14 numeros
                return true;
            }

            $soma1 = ($cnpj[0] * 5) + ($cnpj[1] * 4) + ($cnpj[2] * 3) + ($cnpj[3] * 2) +
                ($cnpj[4] * 9) + ($cnpj[5] * 8) + ($cnpj[6] * 7) + ($cnpj[7] * 6) +
                ($cnpj[8] * 5) + ($cnpj[9] * 4) + ($cnpj[10] * 3) + ($cnpj[11] * 2);
            $soma2 = ($cnpj[0] * 6) + ($cnpj[1] * 5) + ($cnpj[2] * 4) + ($cnpj[3] * 3) +
                ($cnpj[4] * 2) + ($cnpj[5] * 9) + ($cnpj[6] * 8) + ($cnpj[7] * 7) + ($cnpj[8] * 6) +
                ($cnpj[9] * 5) + ($cnpj[10] * 4) + ($cnpj[11] * 3) + ($cnpj[12] * 2);
            $resto = $soma1 % 11;
            $digito1 = $resto < 2 ? 0 : 11 - $resto;
            $resto = $soma2 % 11;
            $digito2 = $resto < 2 ? 0 : 11 - $resto;
            $isCnpjValid = (($cnpj[12] == $digito1) && ($cnpj[13] == $digito2));
            return !$isCnpjValid;
        } elseif ($strVal == 'cpf_cnpj' && $value) {
            // True on error.
            $tryCnpj = self::sValFailed($value, 'cnpj', $param);
            $tryCpf = self::sValFailed($value, 'cpf', $param);
            return ($tryCnpj && $tryCpf);
        }
    }

    // Handlig errors
    function addError($aliasName, $errorStr)
    {
        if (!$aliasName) {
            $aliasName = '---';
        }

        if (!isset($this->temp['errorList'])) {
            $this->temp['errorList'] = array();
        }

        if (!isset($this->temp['errorList'][$aliasName])) {
            $this->temp['errorList'][$aliasName] = array();
        }

        $this->temp['errorList'][$aliasName][] = $errorStr;
    }

    function listErrors($aliasName = false)
    {
        // Se $aliasName for FALSE, retorna Array('aliasName'=>Array[] errors)
        // Se $aliasName for TRUE,  retorna Array[] errors
        // Caso contrário, apenas os erros relacionados ao $aliasName informado são retornados
        // * Para pegar os erros globais (não associados a um aliasName), utilizar '---'

        // Não há erros.
        if (!@$this->temp['errorList']) {
            return false;
        }

        if ($aliasName === false) {
            return $this->temp['errorList'];
        }
        if ($aliasName === true) {
            $errorList = array();
            $aliasNames = array_keys($this->temp['errorList']);
            foreach ($aliasNames as $aliasName) {
                $errorList = array_merge($errorList, $this->listErrors($aliasName));
            }

            return array_unique($errorList);
        }

        return isset($this->temp['errorList'][$aliasName]) ?
            $this->temp['errorList'][$aliasName] :
            false;
    }

    function appendErrors($object)
    {
        if (!$object) {
            return false;
        }

        $errors = $object->listErrors();
        if (!$errors) {
            return false;
        }

        foreach ($errors as $fieldName => $errorList) {
            foreach ($errorList as $errorStr) {
                $this->addError($fieldName, $errorStr);
            }
        }
        return true;
    }

    function clearErrors()
    {
        unset($this->temp['errorList']);
    }

    // Handling table structure, partial loading and alias modified
    static function sGetDbAliasNames($getFieldName = false)
    {
        // Retorna a lista de aliasNames existentes na estrutura da tabela.
        // Não inclui addExt ou campos virtuais, apenas os que estão no banco de dados.
        // Se getFieldName=true, então o retorno será [aliasName]=fieldName.
        $aliasNames = array();
        $fieldProps = self::structGet('fieldProps', 'simple');
        foreach ($fieldProps as $aliasName => $fieldName) {
            if ($getFieldName) {
                $aliasNames[$aliasName] = $fieldName ? $fieldName : $aliasName;
            } else {
                $aliasNames[] = $aliasName;
            }
        }

        return $aliasNames;
    }

    function getDbAliasNames($getFieldName = false)
    {
        return self::sGetDbAliasNames($getFieldName);
    }

    function getAliasEnabled()
    {          // Se o construtor utilizar onlyFields
        return array_keys($this->fieldValues);
    }

    function isAliasEnabled($aliasName)
    { // Se o construtor utilizar onlyFields
        return array_key_exists($aliasName, $this->fieldValues);
    }

    function hasChanges()
    {
        return array_key_exists('fieldOriginal', $this->extra);
    }

    function hasAliasChanged($aliasName)
    {// Retorna se $aliasName foi modificado, ou seja, se possui getOriginal() diferente
        return array_key_exists('fieldOriginal', $this->extra) && array_key_exists($aliasName,
                $this->extra['fieldOriginal']);
    }

    function getAliasChanged()
    {          // Retorna aliasNames que foram alterados (se estiver loaded)
        // What has changed?
        //   TRUE  = Everything
        //   FALSE = Nothing
        //   Array(aliasName, ...)
        if (!$this->isLoaded()) {
            return true;
        }
        if (!array_key_exists('fieldOriginal', $this->extra)) {
            return false;
        }
        return array_keys($this->extra['fieldOriginal']);
    }

    // Interação com o banco de dados:
    static function getDb()
    {
        $db = self::structGet('db');
        if (!$db) {
            $db = dDatabase::start();
            self::structSet('db', $db);
        }
        return $db;
    }

    static function _handleEventListeners($evWhen, $obj, $evUseId = false, $saveData = false)
    {
        // Processa os eventos relacionados.
        if (!self::structGet('eventListeners')) {
            return;
        }

        if ($evWhen == 'beforeSave' || $evWhen == 'afterSaveFail') {
            // callback($obj)
            foreach (self::structGet('eventListeners') as $eventListener) {
                if (in_array($evWhen, $eventListener['when'])) {
                    $cbListener = $eventListener['callback'];
                    is_array($cbListener) ?
                        call_user_func($cbListener, $obj) :
                        $cbListener                ($obj);
                }
            }
            return;
        }

        foreach (self::structGet('eventListeners') as $eventListener) {
            // callback($obj, $id, $when, $saveData)
            if (in_array($evWhen, $eventListener['when'])) {
                $cbListener = $eventListener['callback'];
                is_array($cbListener) ?
                    call_user_func($cbListener, $obj, $evUseId, $evWhen, $saveData) :
                    $cbListener                ($obj, $evUseId, $evWhen, $saveData);
            }
        }
    }

    function save()
    {
        $this->clearErrors();
        $className = get_called_class();
        $tableName = self::structGet('tableName');
        $primaryKey = self::structGet('primaryKey');
        $useQuotes = self::structGet('useQuotes');
        $primaryVal = $this->getPrimaryValue();

        self::_handleEventListeners('beforeSave', $this);

        // O que mudou:
        $aliasToSave = $this->getAliasChanged();
        if (!$aliasToSave) {
            # echo "Nenhuma alteracao, não vou salvar.<br />";
            # self::log("save() - Retornando SUCESSO ({$primaryKey}={$this->getPrimaryValue()}) pois não houve nenhuma modificação a ser salva.</div>");
            return $this->getPrimaryValue();
        }

        if (!$this->valPassed('final', $aliasToSave, true)) {
            # echo "Não passei pela validação:<br />";
            # self::dump($this->listErrors());

            self::_handleEventListeners('afterSaveFail', $this);

            # self::log("save() - Retornando FALSE, pois não passei na validação.</div>");
            return false;
        }

        // O que temos que fazer:
        // 1. Converter todos os campos que precisam ser salvos para 'db'
        // 2. Aplicar possíveis modifiers globais
        // 3. Converter todos os campos que preicsam ser salvos para 'sql'
        // 4. Aplicar possíveis modifiers globais novamente
        // 5. Enviar a query
        $saveData = array();
        $fieldsToSave = $this->getDbAliasNames(true);
        foreach ($fieldsToSave as $aliasName => $fieldName) {
            if (isset($this->fieldValues[$aliasName]) && ($aliasToSave === true || in_array($aliasName,
                        $aliasToSave))) {
                $saveData[$aliasName] = $this->fieldValues[$aliasName];
            }
        }

        $sql = array();
        foreach ($saveData as $aliasName => $value) {
            $sql[] = "\t{$useQuotes}{$fieldsToSave[$aliasName]}{$useQuotes} = " . $this->modApply('basic2sql',
                    $aliasName, $value);
        }

        // Quase sempre, o $primaryKey será definido como PRIMARY KEY() ou UNIQUE() no banco de dados.
        // Dessa forma, dá pra utilizar o REPLACE quando necessário, e deixar o próprio MySQL
        // decidir se é pra inserir ou atualizar.
        //
        // No entanto, vamos deixar uma válvula de escape (settings) para substituir esse comportamento.
        // Ou seja, se a setting primaryKeyNotUnique for TRUE, então não dá pra confiar no REPLACE.
        // Nesse caso, sempre que for pra inserir OU atualizar, uma query subsequente deverá ser realizada.
        $db = self::getDb();

        if ($this->isLoaded() && $primaryVal) {
            // O objeto já está carregado, sabemos que ele existe no banco de dados.
            // Vamos apena atualizá-lo.
            $queryDesc = "{$className}: Atualizando item já carregado anteriormente";
            $query = "UPDATE {$tableName} SET\r\n    ";
            $query .= implode(",\r\n    ", $sql) . "\r\n";
            $query .= "WHERE {$useQuotes}{$primaryKey}{$useQuotes} = " . $this->modApply('basic2sql', $primaryKey,
                    $primaryVal);
        } else {
            // Estamos cadastrando um objeto pela primeira vez.
            // Se o usuário rodou $obj->setValue($primaryKey, ...), entraremos em algumas situações:
            // - Padrão: O sistema vai criar um objeto com o id informado pelo usuário *ou* vai substituir um registro pré-existente.
            // - primaryKeyIgnoreDupe: O sistema vai tentar criar o objeto, sem verificar se já existe outro igual. Isso pode dar erro.
            // - primaryKeyNotUnique:  O sistema vai verificar no banco de dados se já existe aquele valor, antes de optar por insert ou update.

            // Se ->setValue($primaryKey, ...) existir, então é visível o desejo de
            // substituir o item existente pelo novo (replace).
            if (self::structGet('primaryKeyIgnoreDupe')) {
                $queryDesc = "{$className}: Item novo, devo inserir (ignore dupe)";
                $query = "INSERT INTO {$tableName} SET\r\n";
                $query .= "    " . implode(",\r\n    ", $sql);
            } elseif (self::structGet('primaryKeyNotUnique')) {
                if ($this->v($primaryKey) && $db->singleResult("SELECT {$primaryKey} FROM {$tableName} WHERE {$useQuotes}{$primaryKey}{$useQuotes} = " . $this->getModValue($primaryKey,
                            'sql') . " LIMIT 1",
                        "Verificando se devo inserir ou atualizar (setting 'primaryKeyNotUnique')")) {
                    // Já existe, então devo atualizar.
                    $queryDesc = "{$className}: Item já existia, devo atualizar";
                    $query = "UPDATE {$tableName} SET\r\n    ";
                    $query .= implode(",\r\n    ", $sql) . "\r\n";
                    $query .= "WHERE {$useQuotes}{$primaryKey}{$useQuotes} = " . $this->getModValue($primaryKey, 'sql');
                } else {
                    $queryDesc = "{$className}: Item novo, devo inserir";
                    $query = "INSERT INTO {$tableName} SET\r\n";
                    $query .= "    " . implode(",\r\n    ", $sql);
                }
            } else {
                $queryDesc = "{$className}: Inserindo um item completo, novo ou sobrescrevendo.";
                $query = "INSERT INTO {$tableName} SET\r\n";
                $query .= "    " . implode(",\r\n    ", $sql) . "\r\n";
                if ($this->getValue($primaryKey)) {
                    $query .= "ON DUPLICATE KEY UPDATE {$useQuotes}{$primaryKey}{$useQuotes} = " . $this->getModValue($primaryKey,
                            'sql');
                }
            }
        }

        // Salvando, efetivamente.
        $db->query($query, $queryDesc);

        if ($db->error()) {
            // SQL Error. Abort!
            $this->addError(false,
                "Falha crítica no sistema. Um administrador acaba de ser notificado. Desculpe o incômodo.");
            dSystem::notifyAdmin('HIGH', "dDbRow3 - SQL Error on Save",
                "Um SQL Error jamais deveria ser exibido no método ->save() do dDbRow3.\r\n" .
                "No entanto, isso acabou de acontecer.\r\n" .
                "\r\n" .
                "A query original era a seguinte:\r\n" .
                "{$query}\r\n" .
                "\r\n" .
                "A mensagem de erro do banco de dados foi:\r\n" .
                "{$db->error()}\r\n" .
                "\r\n" .
                "Para o usuário, a classe dDbRow3 está retornando 'Falha crítica, desculpe o incômodo."
            );
            self::_handleEventListeners('afterSaveFail', $this);
            # self::log("save() - Retornando FALSE porque houve um SQL Error grave.", 3);
            return false;
        }

        // Sucesso, tudo o que precisava ser feito foi feito.
        // Vamos atualizar o id, se for auto_increment.
        if (!$this->getValue($primaryKey)) {
            # self::log("save() - Definindo {$primaryKey} como {$db->lastId()} (auto_increment).");
            $this->setValue($primaryKey, $db->lastId(), 'basic');
        }

        // Sistema de reusables:
        // --> Importante ser chamado antes de ->setLoaded().
        self::_reuseAfterSave($this);

        // Vamos passar essas informações tabém para a auditoria:
        // --> Sobre os callbacks:
        //     O ideal seria utilizarmos sempre callbacks anônimos (function(...){}), mas
        //     em algumas situações podemos definir um método estático dentro de uma classe
        //     para fazer o trabalho. Essa situação só funciona com o call_user_func.
        if (self::structGet('cbAuditing')) {
            $cbAuditing = self::structGet('cbAuditing');
            if (is_array($cbAuditing)) {
                $this->isLoaded() ?
                    call_user_func($cbAuditing, $this, $this->getPrimaryValue(), 'update', $saveData) :
                    call_user_func($cbAuditing, $this, $this->getValue($primaryKey), 'create', $saveData);
            } else {
                $this->isLoaded() ?
                    $cbAuditing($this, $this->getPrimaryValue(), 'update', $saveData) :
                    $cbAuditing($this, $this->getValue($primaryKey), 'create', $saveData);
            }
        }

        // Vamos processar eventListeners: create,update
        $evWhen = $this->isLoaded() ? 'update' : 'create';
        $evUseId = $this->isLoaded() ? $this->getPrimaryValue() : $this->getValue($primaryKey);
        self::_handleEventListeners($evWhen, $this, $evUseId, $saveData); // update ou create

        // Vamos marcar o objeto como loaded (remover 'getOriginal);
        // Vamos processar eventListener:afterCreate,afterUpdate,afterSave
        $this->setLoaded(true);

        self::_handleEventListeners("after" . ucfirst($evWhen), $this, $evUseId,
            $saveData); // afterCreate ou afterUpdate
        self::_handleEventListeners('afterSave', $this, $evUseId, $saveData); // afterSave é chamado sempre

        # self::log("save() - Retornando SUCESSO ({$primaryKey}={$this->getPrimaryValue()}), pois pude atualizar o banco de dados.</div>");
        return $this->getValue($primaryKey);
    }

    function export($settings = array())
    {
        // Parâmetros especiais:
        //   '!id,nome,sobrenome' = Array('ignoreFields'=>'id,nome,sobrenome')
        //   'id,nome,sobrenome'  = Array('onlyFields'  =>'id,nome,sobrenome')
        if (is_string($settings)) {
            $settings = ($settings[0] == '!') ?
                array('ignoreFields' => substr($settings, 1)) :
                array('onlyFields' => $settings);
        }
        $settings += array(
            'loadExt' => false, // Not implemented.
            'onlyFields' => false, // Array ou lista separada por vírgula
            'ignoreFields' => false, // Array ou lista separada por vírgula
        );

        if ($settings['loadExt']) {
            trigger_error("export->loadExt not implemented yet.");
            return false;
        }
        if ($_of = $settings['onlyFields']) {
            if (!is_array($_of)) {
                $_of = explode(",", $_of);
            }

            foreach ($_of as $_field) {
                $data[$_field] = $this->fieldValues[$_field];
            }
        } else {
            $data = $this->fieldValues;
        }

        if ($_if = $settings['ignoreFields']) {
            if (!is_array($_if)) {
                $_if = explode(",", $_if);
            }

            foreach ($_if as $_field) {
                unset($data[$_field]);
            }
        }
        return $data;
        /*
			Base para desenvolvimento futuro (suporte a loadExt):

		$externalList = self::structGet('fieldProps', 'external');
		if($externalList){
			$_exportedList[] = $this->uid;
			foreach($externalList as $aliasName=>$extItem){
				if($extItem['type'] != 'extObj'){
					continue;
				}
				if(!$this->isAliasEnabled($aliasName)){
					continue;
				}

				if($subObj = $this->getValue($aliasName)){ // Está carregado (pode não estar loaded, mas está carregado)
					if(in_array($subObj->uid, $_exportedList)){
						# self::log("export() - Não vou exportar ".get_class($this)."->v({$aliasName}), pois detectei uma recursividade.");
						continue;
					}
					$extData = $subObj->export($_exportedList);
					foreach($extData as $subAliasName=>$subValue){
						$data["{$aliasName}.{$subAliasName}"] = $subValue;
					}
				}
			}
		}

		return $data;
		*/
    }

    function reload($loadExt = false)
    {
        // Carrega novamente, sincronizando com as informações do banco de dados.
        if (!$this->isLoaded()) {
            return false;
        }

        $primaryKey = self::structGet('primaryKey');
        $useQuotes = self::structGet('useQuotes');
        $sqlWhere = "{$useQuotes}" . self::structGet('tableName') . "{$useQuotes}.{$useQuotes}{$primaryKey}{$useQuotes} = " . $this->getModValue($primaryKey,
                'db');

        $mapPrefix = array();
        $loadSql = self::makeQuery(array(
            'onlyFields' => $this->getAliasEnabled(),
            'loadExt' => $loadExt,
            'callback' => function ($select, $from, $mapPrefix) use ($sqlWhere) {
                return array('where' => $sqlWhere);
            }
        ), $mapPrefix);
        $newData = self::getDb()->singleLine($loadSql, 'Reloading object...');
        if (!$newData) {
            $this->setLoaded(false);
            return $this;
        }

        $this->loadArray($newData, array(
            'format' => 'db',
            'setLoaded' => true,
            'noChecks' => true,
            'overwriteLoaded' => true,
            'mapPrefix' => $mapPrefix,
        ));
        self::_handleEventListeners('afterLoad', $this);
        return $this;
    }

    private function _delete()
    { // Can be overloaded (redeclared with other parameters)
        if (!$this->isLoaded()) {
            return false;
        }

        $className = get_called_class();
        $db = self::getDb();
        $useValue = self::sModifyStr($this->getPrimaryValue(), 'formatToQuery');
        $db->query("delete from " . self::structGet('tableName') . " where " . self::structGet('primaryKey') . " = {$useValue}",
            "{$className}->_delete()");
        if ($db->error()) {
            return false;
        }

        if (self::structGet('cbAuditing')) {
            $cbAuditing = self::structGet('cbAuditing');
            if (is_array($cbAuditing)) {
                call_user_func($cbAuditing, $this, $this->getPrimaryValue(), 'delete', false);
            } else {
                $cbAuditing($this, $this->getPrimaryValue(), 'delete', false);
            }
        }

        // Vamos processar eventListener:delete
        self::_handleEventListeners('delete', $this, $this->getPrimaryValue(), false);

        // Vamos marcar o objeto como !loaded
        $this->setLoaded(false);

        // Vamos processar eventListener:afterDelete
        self::_handleEventListeners('afterDelete', $this, $this->getPrimaryValue(), false);

        return true;
    }

    // Which methods could be overloaded (remember to also check dDbRow3Plus)
    function __call($method, $params)
    {
        if ($method == 'delete') {
            return $this->_delete();
        }

        trigger_error("Call to undefined method " . get_class($this) . "::{$method}()", E_USER_ERROR);
        die;
    }

    /**
     * Converte os parâmetros utilizados em ::load para SQL Query.
     *
     *      $settings = Array:
     *          'onlyFields'=>false       (Array ou String separada por vírgulas)
     *          'loadExt'   =>'**auto**', (Veja mais abaixo 'loadExt')
     *          'callback'  =>false,      (Veja mais abaixo em 'callback')
     *          'getParts'  =>false,      (Não converte para SQL, retorna em partes [select, from, where, append])
     *          'tableAlias'=>false,      (Informa o nome da tabela ou tableAlias a ser chamado antes dos campos)
     *          'prefix'    =>false,      (To-doc)
     *          'mapPrefix' =>false,      (Veja mais abaixo 'mapPrefix')
     *
     * **'loadExt':**
     *          Carrega os aliasName externos, definidos em addExt().
     *          Alias a serem carregados em recursividade, separados por ponto e vírgula (;).
     *          Se for '**auto**', a propriedade 'autoLoad' será respeitada (deprecated).
     *          Se for false, os campos definidos em addExt
     *          Ex: 'categObj;categObj.fotoObj;marcaObj;marcaObj.sedeObj'
     *
     * **'callback':**
     *          Permite modificar a query antes de sua execução.
     *          callback(&$select, &$from, &$mapPrefix, &$where, &$append);
     *              Você pode modificar os parâmetros enviados por referência, ou
     *              Você pode retornar Array('where'=>(string), 'append'=>string) para substituir o original, ou
     *              Você pode retornar string, que é sinônimo para Array('append'=>string)
     *
     * **'mapPrefix':**
     *          Define callbacks especiais para processar o conteúdo que é carregado do banco de dados.
     *          Ex: Digamos que você usou "callback" para retornar um campo chamado "extraDados.agora = now()".
     *          $mapPrefix['extraDados.'] = function($object, $array){
     *              $object->setVirtual('now', $array['agora']);
     *          }
     *
     * @param array $settings Veja descrição acima.
     * @param bool $mapPrefix
     * @return array|string
     * @see load
     */
    static function makeQuery($settings, &$mapPrefix = false)
    {
        if (!self::structExists()) {
            $className = get_called_class();
            $className::buildStruct();
        }

        # echo "<div style='padding-left: 15px; margin-left: 5px; border-left: 2px solid #F00; background: #FCC; margin-top: 5px; width: 100%'>";
        # echo get_called_class()."::makeQuery(\$settings)...<br />";
        # dDbRow3::dump($settings);
        $settings += array(
            'onlyFields' => false,
            'loadExt' => '**auto**',
            'callback' => false,
            'getParts' => false,
            'tableAlias' => false,
            'prefix' => false,
            'mapPrefix' => false,
        );

        if ($settings['mapPrefix']) {
            $mapPrefix = $settings['mapPrefix'];
        }
        if ($settings['loadExt'] == '**auto**') {
            $settings['loadExt'] = array();
            if (self::structGet('fieldProps', 'external')) {
                foreach (self::structGet('fieldProps', 'external') as $aliasName => $extInfo) {
                    if ($extInfo['autoLoad']) {
                        $settings['loadExt'][] = $aliasName;
                    }
                }
                $settings['loadExt'] = implode(";", $settings['loadExt']);
            }
        }

        $useQuotes = self::structGet('useQuotes');
        $tableName = self::structGet('tableName');
        $onlyFields = $settings['onlyFields'];
        $prefix = $settings['prefix'];

        if (!$settings['tableAlias']) {
            $settings['tableAlias'] = $tableName;
        }

        // Adiciona os dados básicos (fields to load):
        $allFields = self::sGetDbAliasNames(true);
        $loadFields = array();
        if ($onlyFields) {
            $primaryKey = self::structGet('primaryKey');
            if ($primaryKey) {
                $loadFields = array(self::structGet('primaryKey') => $allFields[self::structGet('primaryKey')]);
            }

            // Se onlyFields começar com '!', então carregue tudo exceto o que estiver nessa lista.
            if (is_string($onlyFields) && $onlyFields[0] == '!') {
                $_except = array_map('trim', explode(',', substr($onlyFields, 1)));
                $onlyFields = array_diff($allFields, $_except);
            }

            $onlyFields = is_array($onlyFields) ?
                $onlyFields :
                array_map('trim', explode(',', $onlyFields));

            foreach ($onlyFields as $aliasName) {
                $loadFields[$aliasName] = &$allFields[$aliasName];
            }
        } else {
            $loadFields = $allFields;
        }

        $select = $from = $where = $append = array();
        foreach ($loadFields as $aliasName => $fieldName) {
            $select[] = array(
                'tableName' => $settings['tableAlias'],
                'aliasName' => $aliasName,
                'fieldName' => ($fieldName != $aliasName) ? $fieldName : false,
                'prefix' => $prefix
            );
        }
        $from[] = array(
            'tableName' => $tableName,
            'tableAlias' => $settings['tableAlias'],
            'joinMode' => false,
            'on' => false
        );

        // Processa o loadExt:
        if ($settings['loadExt']) {
            $loadExt = $settings['loadExt'];
            $_prefixList = array();
            $_makePrefix = function ($extPath) use (&$_prefixList, &$mapPrefix, &$settings) {
                // Por padrão, tentaremos utilizar o nome do último objeto em $path.
                // Se ele já estiver em uso, adicionaremos um contador nele.
                // Ex: lojaObj; lojaObj2; lojaObj3...
                if ($settings['mapPrefix']) {
                    $mapPrefixR = array_flip($settings['mapPrefix']);
                    if (isset($mapPrefixR[$extPath])) {
                        # echo "_makePrefix($extPath): Re-utilizando como {$mapPrefixR[$extPath]}<br />";
                        return $mapPrefixR[$extPath];
                    }
                }
                # echo "_makePrefix($extPath): Gerando novo<br />";

                $_tmpPath = explode(".", $extPath);   // array_pop exige pass-by-ref,
                $tryName = array_pop($_tmpPath) . '.'; // por isso precisamos do _tmpPath.

                if (array_key_exists($tryName, $_prefixList)) {
                    $tryName = (rtrim($tryName, '.') . (++$_prefixList[$tryName])) . '.';
                }
                $_prefixList[$tryName] = 1;
                $mapPrefix[$tryName] = $extPath;

                return $tryName;
            };

            // 1. Convertendo $loadExt para $extItems:
            //   A saída $extItems vai conter os parâmetros necessários para a recursividade
            //   neste próprio método. Por exemplo:
            //   [categObj] = Array('onlyFields'=>false, 'prefix'=>xxxx, 'loadExt'=>'lojaObj;categObj', 'mapPrefix'=>....)
            //
            // 1.1. Vamos aceitar 'categObj,lojaObj'.
            if (strpos($loadExt, ',') !== false && strpos($loadExt, '(') === false) {
                // Utilizou vírgula ao invés de ponto e vírgula.
                $loadExt = str_replace(",", ";", $loadExt);
            }

            // 1.3. Vamos processar a string em loadExt e montar $loadExt
            $loadExt = array_map('trim', explode(";", $loadExt));
            foreach ($loadExt as $extItem) {
                $onlyFields = preg_match("/\((.+)\)$/", $extItem, $out) ? $out[1] : false;
                $extPath = preg_replace("/\((.+)\)$/", "", $extItem);
                $extParts = explode(".", $extPath, 2);
                $usePrefix = $_makePrefix($extPath);
                $useTableName = rtrim($usePrefix, '.');

                if (sizeof($extParts) == 1) {
                    // Item principal, sem sub-itens
                    $extItems[$extPath]['onlyFields'] = $onlyFields;
                    $extItems[$extPath]['tableAlias'] = $useTableName;
                    $extItems[$extPath]['prefix'] = $usePrefix;
                    $extItems[$extPath]['loadExt'] = array();
                    $extItems[$extPath]['mapPrefix'] = array();
                } else {
                    // Sub-item:
                    $extPath = $extParts[0];
                    $subPath = $extParts[1];
                    $extItems[$extPath]['loadExt'][] = "{$subPath}" . ($onlyFields ? "({$onlyFields})" : "");
                }
            }
            foreach ($extItems as $extPath => $extInfo) {
                $extItems[$extPath]['loadExt'] = implode(";", $extInfo['loadExt']);
                foreach ($mapPrefix as $_prefix => $_extPath) {
                    $_extParts = explode(".", $_extPath, 2);
                    if (sizeof($_extParts) > 1 && $extPath == $_extParts[0]) {
                        $extItems[$extPath]['mapPrefix'][$_prefix] = $_extParts[1];
                    }
                }
            }

            // 2. Vamos aplicar a recursividade e montar a query:
            foreach ($extItems as $extPath => $extItem) {
                # echo "<div style='margin: 5px; padding: 10px; background: #EEE; border: 1px solid #888; font: 12px Courier New'>";
                # echo "Recursividade para: {$extPath}\r\n";
                $extInfo = self::structGet('fieldProps', 'external', $extPath);
                if (!$extInfo) {
                    throw new Exception(get_called_class() . "::load() - Could not loadExt '{$extPath}', because it was not defined.");
                }

                $className = $extInfo['className'];
                if (!class_exists($className)) {
                    throw new Exception("Trying to load '{$className}::buildStruct()', which doesn't exist.");
                }

                if (!$className::structExists()) {
                    $className::buildStruct();
                }

                $targetKey = $extInfo['targetKey'] ?
                    $extInfo['targetKey'] :
                    $className::structGet('primaryKey');

                $extSql = $className::makeQuery(array(
                    'onlyFields' => $extItem['onlyFields'],
                    'loadExt' => $extItem['loadExt'],
                    'tableAlias' => $extItem['tableAlias'],
                    'prefix' => $extItem['prefix'],
                    'mapPrefix' => $extItem['mapPrefix'],
                    'getParts' => true,
                ));

                // Replace simples de prefixos, também para os parâmetros informados
                // pelo usuário em ::addExt(Array('extraOn'=>...)).
                $useExtraOn = $extInfo['extraOn'];
                if ($useExtraOn && is_array($mapPrefix)) {
                    foreach (array_reverse($mapPrefix) as $_tablePrefix => $_map) {
                        if (is_string($_map)) {
                            $useExtraOn = dHelper2::str_replace_outside_quotes("{$_map}.", $_tablePrefix, $useExtraOn);
                        }
                    }
                }

                $extSql['from'][0]['joinMode'] = $extInfo['joinMode'];
                $extSql['from'][0]['on'] =
                    "{$useQuotes}{$settings['tableAlias']}{$useQuotes}.{$useQuotes}{$extInfo['thisKey']}{$useQuotes} = " .
                    "{$useQuotes}{$extItem['tableAlias']}{$useQuotes}.{$useQuotes}{$targetKey}{$useQuotes} " .
                    "{$useExtraOn}";


                $select = array_merge($select, $extSql['select']);
                $from = array_merge($from, $extSql['from']);
                # echo "</div>";
            }
        }
        if (self::structGet('cbMakeQuery')) {
            $useCb = self::structGet('cbMakeQuery');
            $useCb($select, $from, $mapPrefix);
            if (is_string($afterCb)) {
                $append = array($afterCb);
            } else {
                if (is_array($afterCb) && isset($afterCb['where'])) {
                    $where = array_merge($where,
                        (!is_array($afterCb['where']) ? array($afterCb['where']) : $afterCb['where']));
                }
                if (is_array($afterCb) && isset($afterCb['append'])) {
                    $append = array_merge($append,
                        (!is_array($afterCb['append']) ? array($afterCb['append']) : $afterCb['append']));
                }
            }
        }
        if ($settings['callback']) {
            if (is_callable($settings['callback'])) {
                $afterCb = $settings['callback']($select, $from, $mapPrefix, $where, $append);
                if (is_string($afterCb)) {
                    $append = array($afterCb);
                } else {
                    if (is_array($afterCb) && isset($afterCb['where'])) {
                        $where = array_merge($where,
                            (!is_array($afterCb['where']) ? array($afterCb['where']) : $afterCb['where']));
                    }
                    if (is_array($afterCb) && isset($afterCb['append'])) {
                        $append = array_merge($append,
                            (!is_array($afterCb['append']) ? array($afterCb['append']) : $afterCb['append']));
                    }
                }
            } elseif (is_string($settings['callback'])) {
                $append = array($settings['callback']);
                $settings['callback'] = false;
            }

            // Replace simples de prefixos. Por exemplo, se o parâmetro for
            // multiLoad("produObj.categObj.id = 1"), o conteúdo seria substituído
            // pela tabela equivalente (produObj.categObj.id ==> categObj.id).
            //
            // Note que isso não tem como acontecer no caso de mapas com callbacks,
            // tal como prefixos que não estão relacionados a tabelas (por ex, que são
            // inseridos em ->setVirtual()).
            if (is_array($mapPrefix)) {
                $append = array_map(function ($item) use ($mapPrefix) {
                    foreach (array_reverse($mapPrefix) as $tablePrefix => $map) {
                        if (is_string($map)) {
                            $item = dHelper2::str_replace_outside_quotes("{$map}.", $tablePrefix, $item);
                        }
                    }
                    return $item;
                }, $append);
            }
        }
        if ($settings['getParts'] && $settings['getParts'] !== 2) {
            $ret = array(
                'select' => $select,
                'from' => $from,
            );
            # echo "--> Retornando com getParts=TRUE<br />";
            # dDbRow3::dump($ret);
            # echo "</div>";
            return $ret;
        }

        $select = array_map(function ($item) use ($useQuotes) {
            if (is_array($item)) {
                return
                    ($item['tableName'] ? "{$useQuotes}{$item['tableName']}{$useQuotes}." : "") .
                    ($item['fieldName'] ? "{$useQuotes}{$item['fieldName']}{$useQuotes}" : "{$useQuotes}{$item['aliasName']}{$useQuotes}") .
                    (($item['fieldName'] || $item['prefix']) ? " as '{$item['prefix']}{$item['aliasName']}'" : "");
            }
            return $item;
        }, $select);
        $from = array_map(function ($item) use ($useQuotes) {
            if (is_array($item)) {
                return
                    ((($item['joinMode'] || $item['on']) ? "{$item['joinMode']} join " : "") . $useQuotes . $item['tableName']) . $useQuotes .
                    (($item['tableAlias'] != $item['tableName']) ? " as {$useQuotes}{$item['tableAlias']}{$useQuotes}" : "") .
                    ($item['on'] ? " on {$item['on']}" : "");
            }
            return $item;
        }, $from);

        $select = implode(",\r\n      \t", $select) . "\r\n";
        $from = implode("\r\n      \t", $from) . "\r\n";
        $where = ($where ? ("WHERE \t" . implode("\r\n       \t", $where) . "\r\n") : "");
        $append = ($append ? (implode("\r\n", $append)) : "");

        if ($settings['getParts'] === 2) {
            $ret = array(
                'select' => $select,
                'from' => $from,
                'where' => $where,
                'append' => $append,
            );
            return $ret;
        }

        $ret = "SELECT\t{$select}\r\nFROM\t{$from}" . $where . $append;

        return $ret;
    }

    /**
     * @return $this;
     * @see load()
     */
    static function loadOrNew($value, $loadParam = false, $settings = array())
    {
        $ret = $value ?
            self::load($value, $loadParam, $settings) :
            false;

        if (!$ret) {
            $className = get_called_class();
            return new $className(isset($settings['onlyFields']) ? $settings['onlyFields'] : false);
        }
        return $ret;
    }

    /**
     * Carrega o objeto com base em seu $value (normalmente o id), ou $settings.
     *
     * Pode ser utilizado de várias formas diferentes, sendo:
     *      ::load($value[, $settings])
     *      ::load($value, $useAsPrimaryKey[, $settings])
     *      ::load($value, $loadExt[, $settings])
     *      ::load($settings)
     *
     * Settings permitidas são:
     *      onlyFields      --> Será encaminhado para ::makeQuery
     *      loadExt         --> Será encaminhado para ::makeQuery
     *      primaryKeyAsKey --> Apenas para 'multiLoad', retorna como índice a chave primária (ex: $lista[id] = $produObj)
     *      multiLoad       --> true/false. Se for "true", $value será ignorado e será retornado um array com todos os resultados.
     *      useAsPrimaryKey --> Utiliza uma coluna diferente de $primaryKey para buscar por $value.
     *      dDbSearch       --> Retorna "new dDbSearch3($className, $settings)"
     *      cbMakeQuery     --> Será encaminhado como 'callback' para ::makeQuery
     *      ...             --> Sinônimo para cbMakeQuery
     *
     * @return $this Retorna o objeto carregado, dDbSearch3 ou false.
     * @see makeQuery
     */
    static function load($value, $loadParam = false, $settings = array())
    {
        if (!self::structExists()) {
            $className = get_called_class();
            $className::buildStruct();
        }

        // Permitir sintaxes alternativas:
        // 1. load(string $value,    array  $settings)
        // 2. load(string $value,    string $useAsPrimaryKey, array  $settings)
        // 3. load(string $value,    string $loadExt,         array  $settings)
        // 5. load(array  $settings)
        if (is_array($value) && !$loadParam && !$settings) {
            $settings = $value;
            $value = false;
        } elseif (is_array($loadParam) && !$settings) {
            # echo "Syntax auto-detected as (value, settings)<br />";
            $settings = $loadParam;
        } elseif (!$loadParam || array_key_exists($loadParam, self::structGet('fieldProps', 'simple'))) {
            # echo "Syntax auto-detected as (value, useAsPrimaryKey, settings)<br />";
            $settings['useAsPrimaryKey'] = $loadParam;
        } else {
            # echo "Syntax auto-detected as (value, loadExt, settings)<br />";
            $settings['loadExt'] = $loadParam;
        }

        $settings += array(
            'onlyFields' => false,
            // --> Será encaminhado para ::makeQuery
            'loadExt' => '**auto**',
            // --> Será encaminhado para ::makeQuery
            'cbMakeQuery' => false,
            // --> Será encaminhado como 'callback' para ::makeQuery
            'primaryKeyAsKey' => false,
            // --> Apenas para 'multiLoad', retorna o primaryKey (ex: [id] => produObj)
            'multiLoad' => false,
            // * Exclusiva para ::load - Ignora $value e retorna um Array() com todos os resultados
            'useAsPrimaryKey' => false,
            // * Exclusiva para ::load - Utiliza esta coluna para carregar $value
            'dDbSearch' => false,
            // * Exclusiva para ::load - Retorna "new dDbSearch3($className, $settings)"
        );
        if (!$settings['cbMakeQuery'] && isset($settings['...'])) {
            $settings['cbMakeQuery'] = $settings['...'];
        }

        // Super-fast reusable:
        if ((!$settings['useAsPrimaryKey'] || $settings['useAsPrimaryKey'] == self::structGet('primaryKey'))
            && (!$settings['loadExt'] || $settings['loadExt'] == '**auto**')
            && !$settings['multiLoad']
            && !$settings['cbMakeQuery']
            && !$settings['dDbSearch']) {
            $useAsPrimaryKey = self::structGet('primaryKey');
            $primaryValue = self::sModApply('raw2basic', $useAsPrimaryKey, $value);
            if (self::reuseExists($primaryValue, $settings['onlyFields'])) {
                # self::log("load() - Utilizando Super-fast reusable");
                return self::newReusable($value, $settings['onlyFields'], false);
            } else {
                # self::log("load() - Super fast reusable disse que o objeto não estava carregado.");
            }
        }

        // Interrompe, se precisar retornar um objeto de busca extendida
        if ($settings['dDbSearch']) {
            // Só encaminhe as settings relacionadas ao makeQuery.
            $settings['callback'] = &$settings['cbMakeQuery'];
            unset($settings['dDbSearch']);
            unset($settings['multiLoad']);
            unset($settings['useAsPrimaryKey']);
            unset($settings['cbMakeQuery']);
            return new dDbSearch3($className, $settings);
        }

        // Entendendo simpleFilter:
        // --> ::load pode ser chamado para carregar um único resultado, ocasião onde
        //      será adicionado automaticamente o WHERE e o LIMIT 1. Se esse for o caso,
        //      então teremos simpleFilter=true. Caso contrário, o WHERE e o APPEND não
        //      serão gerados, e o programador terá que lidar sozinho com o cbMakeQuery e
        //      o multiLoad.
        $simpleFilter = (!$settings['cbMakeQuery'] && !$settings['multiLoad']);
        if ($simpleFilter) {
            $useAsPrimaryKey = $settings['useAsPrimaryKey'] ?
                $settings['useAsPrimaryKey'] :
                self::structGet('primaryKey');

            if (!array_key_exists($useAsPrimaryKey, self::structGet('fieldProps', 'simple'))) {
                trigger_error("Cannot load with useAsPrimaryKey={$useAsPrimaryKey}, because this alias is NOT part of table structure.");
                return false;
            }

            $value = self::sModApply('raw2basic', $useAsPrimaryKey, $value);
            if (!$value) {
                trigger_error("::load() - O valor {$value} retornou FALSE após passar pelos modificadores, então não tem o que carregar.");
                return false;
            }
        }

        $mapPrefix = array();
        $className = get_called_class();
        $makeQueryParams = array(
            'onlyFields' => $settings['onlyFields'],
            'loadExt' => $settings['loadExt'],
            'callback' => !$simpleFilter ?
                $settings['cbMakeQuery'] ? $settings['cbMakeQuery'] : false :
                function ($select, $from, $mapPrefix) use (&$settings, $className, $useAsPrimaryKey, $value) {
                    $useQuotes = $className::structGet('useQuotes');
                    $loadValue = $className::sModApply('basic2sql', $useAsPrimaryKey, $value);
                    $sqlWhere = ($settings['loadExt']) ?
                        $useQuotes . $className::structGet('tableName') . $useQuotes . "." . $useQuotes . $useAsPrimaryKey . $useQuotes . " = " . $loadValue :
                        $useQuotes . $useAsPrimaryKey . $useQuotes . " = " . $loadValue;

                    return array('where' => $sqlWhere, 'append' => "LIMIT 1");
                },
        );
        $sqlQuery = self::makeQuery($makeQueryParams, $mapPrefix);

        # echo "<b>Pronto para executar a seguinte query:</b><br />";
        # echo "<pre style='padding: 5px; border: 1px solid #CCC; background: #EEE'>";
        # echo $sqlQuery;
        # echo "</pre>";
        $db = self::getDb();
        $primaryKey = self::structGet('primaryKey');
        if ($simpleFilter || !$settings['multiLoad']) {
            $item = $db->singleLine($sqlQuery);
            if (!$item) {
                return false;
            }

            # self::log("load() - Found result, trying newReusable({$item[$primaryKey]})...");
            $obj = self::newReusable($item[$primaryKey], $settings['onlyFields'], false);
            $obj->loadArray($item, array(
                'format' => 'db',
                'setLoaded' => true,
                'overwriteLoaded' => false,
                'noChecks' => true,
                'ignoreKeys' => false,
                'mapPrefix' => $mapPrefix,
            ));
            self::_handleEventListeners('afterLoad', $obj);
            return $obj;
        }

        $objList = array();
        $qh = $db->query($sqlQuery, "{$className}::load(...)");
        if (!$qh) {
            // SQL Error.
            // Vamos tratar como se não houvesse nada na lista.
            return array();
        }

        while ($item = $db->fetch($qh)) {
            if ($primaryKey) {
                $obj = self::newReusable($item[$primaryKey], $settings['onlyFields'], false);
            } else {
                $className = get_called_class();
                $obj = new $className($settings['onlyFields'], false);
            }
            $obj->loadArray($item, array(
                'format' => 'db',
                'setLoaded' => true,
                'overwriteLoaded' => false,
                'noChecks' => true,
                'ignoreKeys' => false,
                'mapPrefix' => $mapPrefix,
            ));
            self::_handleEventListeners('afterLoad', $obj);

            if ($settings['primaryKeyAsKey']) {
                $objList[$obj->getPrimaryValue()] = $obj;
            } else {
                $objList[] = $obj;
            }
        }

        return $objList;
    }

    /**
     * @return $this[]
     * @see load
     */
    static function multiLoad($param1 = false, $param2 = false, $settings = array())
    {
        // Sintaxes permitidas:
        // 1. multiLoad(array  $settings)
        // 2. multiLoad(string $cbMakeQuery, array $settings)
        // 3. multiLoad(string $cbMakeQuery, string $loadExt,     array $settings);
        // 4. multiLoad(string $loadExt,     array $settings)
        // 5. multiLoad(string $loadExt,     string $cbMakeQuery, array $settings);
        if (is_array($param1)) {                                        // 1
            $settings = $param1;
        } elseif (is_string($param1) || ($param1 && is_callable($param1))) {
            if (is_array($param2)) {
                $settings = $param2;
            }

            if (is_callable($param1) || strpos($param1, " ") !== false) {
                $settings['cbMakeQuery'] = $param1;
                if (is_string($param2)) {
                    $settings['loadExt'] = $param2;
                }
            } else {
                $settings['loadExt'] = $param1;
                if (is_string($param2) || is_callable($param2)) {
                    $settings['cbMakeQuery'] = $param2;
                }
            }
        }

        $settings = array('multiLoad' => true) + $settings;
        return self::load($settings);
    }

    /** @return $this */
    function loadArray($allData, $settings = array())
    {
        if (is_string($settings)) {
            $settings = array('ignoreKeys' => $settings);
        }

        $settings += array(
            'format' => 'raw',
            'setLoaded' => false,
            'noChecks' => false,
            'overwriteLoaded' => true,
            'onlyKeys' => array(),
            'ignoreKeys' => array(),
            'mapPrefix' => false,
        );

        if (!$allData) {
            return false;
        }
        if (!$settings['mapPrefix']) {
            // Carregamento de um item final, sem external objects.
            // ----------------------------------------------------
            if (!$settings['overwriteLoaded'] && $this->isLoaded()) {
                # echo "Está carregado e pediu pra não sobrescrever...<br />";
                return $this;
            }
            if ($settings['noChecks']) {
                // Carregamento super rápido, assumindo que todos os dados foram
                // revisados antes de serem passados para esta função.
                //
                // Por exemplo, o resultado de um $db->singleLine sem loadExt poderia receber o noChecks.
                // É obrigatório o envio do 'format' também.
                //
                // Lembrando que é possível que um 'left join' tenha sido realizado.
                // Se for esse o caso, o primaryKey assim como todos os demais itens serão FALSE,
                // o setLoaded causaria uma informação inconsistente.
                //
                if ($settings['format'] != 'basic') {
                    $this->modApplyAll($settings['format'] . '2basic', $allData);
                }
                $this->fieldValues = &$allData;
            } else {
                // Carregamento normal (sem noChecks)
                $this->clearErrors();
                if ($settings['onlyKeys'] && !is_array($settings['onlyKeys'])) {
                    $settings['onlyKeys'] = array_map('trim', explode(",", $settings['onlyKeys']));
                }
                if ($settings['ignoreKeys'] && !is_array($settings['ignoreKeys'])) {
                    $settings['ignoreKeys'] = array_map('trim', explode(",", $settings['ignoreKeys']));
                }

                $myFields = $this->getDbAliasNames();
                if ($settings['onlyKeys']) {
                    $myFields = array_intersect($myFields, $settings['onlyKeys']);
                }
                if ($settings['ignoreKeys']) {
                    $myFields = array_diff($myFields, $settings['ignoreKeys']);
                }

                foreach ($myFields as $aliasName) {
                    if (array_key_exists($aliasName, $allData)) {
                        $this->setValue($aliasName, $allData[$aliasName], $settings['format']);
                    }
                }
            }

            if ($settings['setLoaded']) {
                $this->setLoaded(true);
            }

            return $this;
        }

        $mapPrefix = $settings['mapPrefix'];
        $_extractPrefixes = function () use (&$allData, &$mapPrefix) {
            $allDataPrefixed = array();
            foreach ($allData as $aliasName => $value) {
                foreach ($mapPrefix as $prefixStr => $target) {
                    $sPrefix = strlen($prefixStr);
                    if (substr($aliasName, 0, $sPrefix) == $prefixStr) {
                        // Move para allDataPrefixed e remove de allData.
                        $allDataPrefixed[$prefixStr][substr($aliasName, $sPrefix)] = &$allData[$aliasName];
                        unset($allData[$aliasName]);
                    }
                }
            }
            return $allDataPrefixed;
        };
        $allDataPrefixed = $_extractPrefixes($allData);

        $subSettings = $settings;
        $subSettings['mapPrefix'] = false;
        $this->loadArray($allData, $subSettings);

        foreach ($mapPrefix as $prefixStr => $target) {
            $subData = $allDataPrefixed[$prefixStr];
            if (is_string($target)) {
                $target = explode(".", $target);
                $sTarget = sizeof($target);

                if ($sTarget == 1) {
                    $className1 = self::structGet('fieldProps', 'external', $target[0], 'className');
                    $primaryKey = $className1::structGet('primaryKey');
                    if (!$subData[$primaryKey]) {
                        # echo "Não existem dados para o sub-item '".implode('.', $target)."', vou armazenar a informação de not_found em cache.<br />";
                        $this->setValue($target[0], '**not_found**');
                        continue;
                    }
                    $subObject = $className1::newReusable($subData[$primaryKey], array_keys($subData), false);
                    $subObject->loadArray($subData, $subSettings);
                    $this->v($target[0], $subObject);
                    $reverseKey = self::structGet('fieldProps', 'external', $target[0], 'reverseKey');
                    if ($reverseKey) {
                        $subObject->v($reverseKey, $this);
                    }
                } elseif ($sTarget == 2) {
                    $className1 = self::structGet('fieldProps', 'external', $target[0], 'className');
                    $className2 = $className1::structGet('fieldProps', 'external', $target[1], 'className');
                    $primaryKey = $className2::structGet('primaryKey');
                    if (!$subData[$primaryKey]) {
                        # echo "Não existem dados para o sub-item '".implode('.', $target)."', vou armazenar a informação de not_found em cache.<br />";
                        if (!$this->v($target[0])) {
                            continue;
                        }
                        $this->v($target[0])->v($target[1], '**not_found**');
                        continue;
                    }
                    $subObject = $className2::newReusable($subData[$primaryKey], array_keys($subData), false);
                    $subObject->loadArray($subData, $subSettings);
                    $this
                        ->v($target[0])
                        ->v($target[1], $subObject);
                    $reverseKey = $className2::structGet('fieldProps', 'external', $target[1], 'reverseKey');
                    if ($reverseKey) {
                        $subObject->v($reverseKey, $this);
                    }
                } elseif ($sTarget == 3) {
                    $className1 = self::structGet('fieldProps', 'external', $target[0], 'className');
                    $className2 = $className1::structGet('fieldProps', 'external', $target[1], 'className');
                    $className3 = $className2::structGet('fieldProps', 'external', $target[2], 'className');
                    $primaryKey = $className3::structGet('primaryKey');
                    if (!$subData[$primaryKey]) {
                        # echo "Não existem dados para o sub-item '".implode('.', $target)."', vou armazenar a informação de not_found em cache.<br />";
                        if (!$this->v($target[0])) {
                            continue;
                        }
                        if (!$this->v($target[0])->v($target[1])) {
                            continue;
                        }
                        $this->v($target[0])->v($target[1])->v($target[2], '**not_found**');
                        continue;
                    }
                    $subObject = $className3::newReusable($subData[$primaryKey], array_keys($subData), false);
                    $subObject->loadArray($subData, $subSettings);
                    $this
                        ->v($target[0])
                        ->v($target[1])
                        ->v($target[2], $subObject);
                    $reverseKey = $className3::structGet('fieldProps', 'external', $target[2], 'reverseKey');
                    if ($reverseKey) {
                        $subObject->v($reverseKey, $this);
                    }
                } elseif ($sTarget == 4) {
                    $className1 = self::structGet('fieldProps', 'external', $target[0], 'className');
                    $className2 = $className1::structGet('fieldProps', 'external', $target[1], 'className');
                    $className3 = $className2::structGet('fieldProps', 'external', $target[2], 'className');
                    $className4 = $className3::structGet('fieldProps', 'external', $target[3], 'className');
                    $primaryKey = $className4::structGet('primaryKey');
                    if (!$subData[$primaryKey]) {
                        # echo "Não existem dados para o sub-item '".implode('.', $target)."', vou armazenar a informação de not_found em cache.<br />";
                        if (!$this->v($target[0])) {
                            continue;
                        }
                        if (!$this->v($target[0])->v($target[1])) {
                            continue;
                        }
                        if (!$this->v($target[0])->v($target[1])->v($target[2])) {
                            continue;
                        }
                        $this->v($target[0])->v($target[1])->v($target[2])->v($target[3], '**not_found**');
                        continue;
                    }
                    $subObject = $className4::newReusable($subData[$primaryKey], array_keys($subData), false);
                    $subObject->loadArray($subData, $subSettings);
                    $this
                        ->v($target[0])
                        ->v($target[1])
                        ->v($target[2])
                        ->v($target[3], $subObject);
                    $reverseKey = $className4::structGet('fieldProps', 'external', $target[3], 'reverseKey');
                    if ($reverseKey) {
                        $subObject->v($reverseKey, $this);
                    }
                } elseif ($sTarget >= 5) {
                    dSystem::notifyAdmin('HIGH', 'dDbRow3 -- mapPrefix excedeu o limite de recursividade (6)',
                        "O método loadArray não consegue mapear os dados com prefixo '{$prefixStr}' para " .
                        "o target " . implode('.',
                            $target) . ", que contém {$sTarget} partes. O máximo que o sistema " .
                        "é capaz de lidar são 5 partes. Para exceder este limite, modifique diretamente o código.",
                        true
                    );
                }
            } elseif (is_callable($target)) {
                $target($this, $subData);
            }
        }

        return $this;
    }

    function isLoaded($aliasName = false)
    {
        // 1. Se aliasName estiver em branco, diz se o objeto está ou não carregado.
        // 2. Se aliasName estiver definido por addField, retorna isAliasEnabled(...)
        //
        // 3. Se aliasName estiver definido por addExt:
        // 3.1. Se for straight (aliasName='categId' e target='id'),  por exemplo, categObj.
        //        a) categ_id por existir,   e categObj não.                    Nesse caso, retorna TRUE.
        //        b) categ_id pode ser NULL, e categObj existir, loaded ou não. Nesse caso, retorna ->isLoaded().
        //
        // 3.2. Se for reverso  (aliasName='id' e target='produ_id'), por exemplo, variaObj.
        //        a) Se variaObj não existir (for NULL),            tentamos carregá-lo.  Se falhar, modifique-o para '**not_found**'.
        //        b) Se variaObj não exsitir (for '**not_found**'), apenas retorne FALSE.
        //        c) Se existir, retorne ->isLoaded().

        // 1.
        if (!$aliasName) {
            return $this->isLoaded;
        }

        // 2.
        if (!array_key_exists($aliasName, $this->extObjects)) {
            return $this->isAliasEnabled($aliasName);
        }

        // 3.
        $thisKey = self::structGet('fieldProps', 'external', $aliasName, 'thisKey');
        $extObj = $this->extObjects[$aliasName];
        if (is_object($extObj)) {
            # self::log("isLoaded() - Objeto está carregado, retornando o seu extObj->isLoaded(): ".($extObj->isLoaded()?"True":"False"));
            # dDbRow3::dump($extObj);
            return $extObj->isLoaded();
        }
        if ($thisKey == self::structGet('primaryKey')) {
            $extObj = $this->getValue($aliasName);
            if (!$extObj) {
                # self::log("isLoaded() - Detectei ext reverso, mas o ->getValue($aliasName) retornou false mesmo assim.");
                return false;
            }

            # self::log("isLoaded() - Objeto foi carregado em tempo real, retornando o seu ->isLoaded(): ".($extObj->isLoaded()?"True":"False"));
            return $extObj->isLoaded();
        }

        if ($this->getValue($thisKey)) {
            # self::log("isLoaded() - Existe o valor {$thisKey}={$this->v($thisKey)}, vou retornar TRUE sem carregar o objeto externo.");
            return true;
        }

        # self::log("isLoaded() - Retornando FALSE pois é um external straight e não existe '{$thisKey}'");
        return false;
    }

    // Dealing with re-utilization of objects.
    static $reusable = array();

    static public function newReusable($id, $onlyFields, $applyDefaults = true)
    {
        $className = get_called_class();
        $maxSlots = self::structGet('allowReusable');
        if (!$maxSlots) {
            # self::log("newReusable() - Não tenho slots para {$className}, retornando um novo objeto.", 2);
            return new $className($onlyFields, $applyDefaults);
        }

        if (!is_array($onlyFields) && !is_bool($onlyFields)) {
            if (is_string($onlyFields)) {
                $onlyFields = array_map('trim', explode(",", $onlyFields));
            } else {
                trigger_error("onlyFields deve ser fornecido como Array() ou TRUE para newReusable.");
                return false;
            }
        }
        if (is_array($onlyFields) && !array_diff(array_keys($className::structGet('fieldProps', 'simple')),
                $onlyFields)) {
            $onlyFields = false;
            # self::log("newReusable() - Forçando 'all' como tryKey.");
        }

        $onlyFields = is_array($onlyFields) ?
            implode(",", $onlyFields) :
            false;

        $tryKey = "{$id}(" . ($onlyFields ? $onlyFields : 'all') . ")";
        if (isset(self::$reusable[$className][$tryKey])) {
            self::$reusable[$className][$tryKey]['inUse']++;
            # self::log("newReusable() - Retornando em cache {$className}({$tryKey})");
            return self::$reusable[$className][$tryKey]['objRef'];
        }
        if (!isset(self::$reusable[$className])) {
            self::$reusable[$className] = array();
        }

        $ret = new $className($onlyFields, $applyDefaults);
        if (sizeof(self::$reusable[$className]) < $maxSlots) {
            // Só vamos alocar se houverem slots disponíveis.
            # self::log("newReusable() - Alocando novo objeto em cache {$className}({$tryKey})");
            self::$reusable[$className][$tryKey] = array('objRef' => $ret, 'inUse' => 1);
        }

        return $ret;
    }

    static public function reuseFreeAll()
    {
        $className = get_called_class();
        if ($className == 'dDbRow3') {
            self::$reusable = array();
            return;
        }
        if (isset(self::$reusable[$className])) {
            unset(self::$reusable[$className]);
        }
    }

    static private function reuseExists($id, $onlyFields)
    {
        $className = get_called_class();

        if (!is_array($onlyFields) && !is_bool($onlyFields)) {
            if (is_string($onlyFields)) {
                $onlyFields = array_map('trim', explode(",", $onlyFields));
            } else {
                trigger_error("onlyFields deve ser fornecido como Array() ou TRUE para reuseExists.");
                return false;
            }
        }
        if (is_array($onlyFields) && !array_diff(array_keys($className::structGet('fieldProps', 'simple')),
                $onlyFields)) {
            $onlyFields = false;
            # self::log("newReusable() - Forçando 'all' como tryKey.");
        }

        $onlyFields = is_array($onlyFields) ?
            implode(",", $onlyFields) :
            false;

        $tryKey = "{$id}(" . ($onlyFields ? $onlyFields : 'all') . ")";

        # self::log("reuseExists() - Verificando a existência de {$className}[{$tryKey}]");
        return isset(self::$reusable[$className][$tryKey]);
    }

    static private function _reuseAfterSave(dDbRow3 $object, $onlyFields = false)
    {
        // Neste momento, temos um objeto logo antes de ->setLoaded(true).
        // Isso significa que temos os originais e o isLoaded() antigo.
        $className = get_called_class();
        $maxSlots = self::structGet('allowReusable');
        if (!$maxSlots) {
            return new $className($onlyFields);
        }

        // --> Se mudou o primaryKey, então tenho que des-carregar tudo o que fazia referência ao ID antigo.
        // --> Se o objeto não estava no sistema de reusables, então tenho que adicioná-lo
        // --> Se o objeto não estava alocado, mas outro estava com o mesmo id/onlyFields, mantenha-o desalocado
        // --> Se o objeto não estava alocado, mas nenhum outro também estava, aloque-o
        $primaryKey = self::structGet('primaryKey');
        $primaryVal = $object->getValue($primaryKey);
        if ($object->isLoaded()) {
            $primaryOri = $object->getOriginal($primaryKey);

            if ($primaryVal != $primaryOri) {
                $compareKey = "{$primaryOri}(";
                $compareLen = strlen($compareKey);
                foreach (self::$reusable[$className] as $reuseKey => $reuseItem) {
                    if (substr($reuseKey, 0, $compareLen) == $compareKey) {
                        unset(self::$reusable[$className][$reuseKey]);
                    }
                }
            }
        }

        if (!isset(self::$reusable[$className])) {
            self::$reusable[$className] = array();
        }

        $onlyFields = (sizeof($object->fieldValues) == sizeof(self::structGet('fieldProps',
                'simple'))) ? false : implode(",", array_keys($object->fieldValues));
        if (self::reuseExists($primaryVal, $onlyFields)) {
            return false;
        }

        self::_reuseAllocate(1);
        if (sizeof(self::$reusable[$className]) < $maxSlots) {
            // Só vamos alocar se houverem slots disponíveis.
            $tryKey = $primaryVal . "(" . ($onlyFields ? $onlyFields : 'all') . ")";
            self::$reusable[$className][$tryKey] = array('objRef' => $object, 'inUse' => 1);
        }
        return true;
    }

    public function reuseFree()
    {
        return self::_reuseFree($this);
    }

    static private function _reuseFree(dDbRow3 $object)
    {
        $className = get_called_class();
        $onlyFields = (sizeof($object->fieldValues) == sizeof(self::structGet('fieldProps', 'simple'))) ?
            false :
            implode(",", $object->fieldValues);

        $tryKey = $object->getPrimaryValue() . "(" . ($onlyFields ? $onlyFields : 'all') . ")";
        unset(self::$reusable[$className][$tryKey]);
    }

    static private function _reuseAllocate($nResults)
    {
        $maxSlots = self::structGet('allowReusable');
        if (!$maxSlots) {
            // O sistema de reusable está desativado.
            return false;
        }

        $className = get_called_class();
        $inUse = isset(self::$reusable[$className]) ? sizeof(self::$reusable[$className]) : 0;
        $setFree = ($inUse + $nResults) - $maxSlots;

        if ($setFree < 0) {
            // Não precisa liberar slots.
            return false;
        } elseif ($setFree > $maxSlots) {
            // Precisa liberar todos os slots
            unset(self::$reusable[$className]);
            return true;
        }

        $mostUsed = array();
        foreach (self::$reusable[$className] as $key => $reuseItem) {
            $mostUsed[$reuseItem['inUse']] = $key;
        }

        // Organiza do menos usado para o mais usado:
        ksort($mostUsed, SORT_NUMERIC);
        while ($setFree--) {
            unset(self::$reusable[$className][array_shift($mostUsed)]);
        }
    }

    public function setDebug($minLevel)
    {
        dSystem::log('CRITICAL',
            "A classe '" . get_called_class() . "' chamou o método dDbRow3::setDebug(), que foi descontinuado.",
            "A sequencia lógica será esta classe chamando por ::log(), que não existe mais, o que vai resultar num " .
            "erro fatal. Por isso, trata-se de um erro crítico que deve ser revisado e solucionado.",
            false
        );
    }

    // Dealing with External Tables:
    private function _extGetValue($aliasName, $autoNew = false)
    {
        // Como funciona:
        //   Usuário chamou ->getValue(categObj)   (aliasName definido anteriormente em addExt)
        //   O retorno será um objeto, carregado ou não, desse elemento
        //
        //   Se o objeto não estiver carregado e categ_id estiver em branco, um novo objeto será
        //   criado e disponibilizado para uso.
        //
        //   Se o objeto não estiver criado, mas existir categ_id, então o sistema tentará executar
        //   um ::load no ID em questão. Em caso de sucesso, um objeto carregado com isLoaded() será
        //   retornado. Se o ::load falhar e $autoNew existir, um novo objeto em branco será retornado,
        //   e o categ_id será totalmente ignorado (será substituído pelo id deste novo objeto, se o mesmo for salvo)
        //
        //   Se o objeto já estiver criado, este objeto será retornado, mantendo sempre o mesmo.
        //
        //   Vale lembrar que, se o valor de categ_id for alterado com setValue(categ_id, ...), o objeto
        //   criado será eliminado,  e dados podem ser perdidos.
        //
        $className = get_called_class();
        $extItem = self::structGet('fieldProps', 'external', $aliasName);
        $extClassName = is_string($extItem['className']) ? $extItem['className'] : $extItem['className']($this);
        $type = $extItem['type'];

        // Atualmente, só existe extObj. Talvez um extList no futuro.
        if ($type == 'extObj') {
            // 1. Se existir um objeto instanciado, retorne-o.
            // 2. Se existir um 'not_found' em cache, retorne false*.
            // 3. Se existir um 'id' a ser carregado, tente carregá-lo e retorne-o*.
            // 4. Em qualquer caso, se o retorno for false, verifique a existência do $autoNew.

            $itemObj = &$this->extObjects[$aliasName];
            // Valores possível para $itemObj:
            // - (bool)  false           --> O sistema nunca tentou carregar este objeto.
            // - (string)'**not_found**' --> O sistema já tentou carregar e gravou que ele não existe.
            // - (object)                --> O sistema já carregou (ou instanciou um novo) este objeto

            if (!$itemObj) {
                // Tente carregar o objeto e retorná-lo, se possível.
                // Se não for possível, grava o cache **not_found** e segue a execução.
                //
                // IMPORTANTE!!!!
                // - $extItem[autoLoad] não se aplica a este momento.
                // - Aqui, o carregamento é feito on-demand. O autoLoad é referente ao método ::load e ::multiLoad.
                //

                // Situação a ser analisada/melhorada:
                // 1. Quando dEcProduto::load(1, 'fotoObj') é chamado, podemos utilizar 'extraOn'
                // 2. No entanto, ao carregar sob demanda fotoObj::load(), o campo 'extraOn' simplesmente não faz sentido.
                //
                // Temporariamente, estamos resolvendo da seguinte forma:
                $thisKey = $extItem['thisKey'];
                $targetKey = $extItem['targetKey'] ? $extItem['targetKey'] : $extItem['className']::structGet('primaryKey');
                if (!$this->isAliasEnabled($thisKey)) {
                    trigger_error(get_class($this) . "->_extGetValue({$aliasName}): We are relying on alias <b>{$thisKey}</b>, which has been <b>disabled</b> in the constructor.");
                    return false;
                }

                if ($extItem['extraOn']) {
                    if (!$this->isLoaded()) {
                        if (!$autoNew) {
                            trigger_error(get_class($this) . "->_extGetValue({$aliasName}: This extObj has 'extraOn' setting, but is not loaded yet! So, it cannot be autoloaded.");
                            return false;
                        }
                        $itemObj = new $extClassName;
                    } else {
                        $searchSelf = self::load($this->v('id'), array(
                            'onlyFields' => array('id'),
                            'loadExt' => $aliasName,
                        ));
                        $itemObj = $searchSelf->extObjects[$aliasName];
                    }
                } else {
                    if ($this->v($thisKey)) {
                        // self::log("_extGetValue({$aliasName}) - Tentando on-demand {$extClassName}::load({$thisKey}->v({$thisKey}))");
                        $itemObj = $extClassName::load($this->v($thisKey), $targetKey);
                        if (!$itemObj && !$autoNew) {
                            // Se não for instanciar um novo, então aproveita pra salvar
                            // essa informação em cache.
                            $itemObj = '**not_found**';
                        }
                    }
                }
            }
            if (!is_object($itemObj) && $autoNew) {
                $itemObj = new $extClassName;
            }
            if (is_object($itemObj)) {
                if ($extItem['reverseKey']) {
                    $itemObj->v($extItem['reverseKey'], $this);
                }
                return $itemObj;
            }

            return false;
        }

        trigger_error("Tipo de external '{$type}' não é conhecido.");
        return false;
    }

    private function _extConstruct($settings)
    {
        $extObjects = self::structGet('fieldProps', 'external');
        if ($extObjects) {
            foreach ($extObjects as $aliasName => $propItem) {
                if ($propItem['type'] == 'extObj') {
                    $this->extObjects[$aliasName] = false;
                }
            }
        }
    }

    static function dump(&$vars, $maxDepth = 10, $_printedUids = array())
    {
        dHelper2::dump($vars, $maxDepth, $_printedUids);
    }

    function __dump($maxDepth = false, $_printedUids = array())
    {
        $vars = $this;
        $className = get_class($vars);
        $dump = get_object_vars($vars);
        $dump['fieldValues'] = '**printed**';
        $dump['extObjects'] = '**printed**';
        if (isset($dump['extra']['fieldOriginal'])) {
            $dump['extra']['fieldOriginal'] = '**printed**';
        }
        if (isset($dump['extra']['fieldVirtuals'])) {
            $dump['extra']['fieldVirtuals'] = '**printed**';
        }

        $_mem = memory_get_usage();
        $_tmp = clone $vars;
        $_mem = memory_get_usage() - $_mem;
        unset($_tmp);

        echo "<table style='background: #CCFFCC; border: 1px solid #080; box-shadow: -1px 1px 3px #888888;' cellspacing='0' rel='dDbRow3-{$vars->uid}'>";
        echo "	<tr valign='top'>";
        echo "		<td colspan='2' bgcolor='#99FF99' style='position: relative'>";
        echo "			<small style='float: right'>uid={$vars->uid} | {$_mem} bytes</small>";
        if ($vars->isLoaded()) {
            echo "			{$className}::load({$vars->getPrimaryValue()})";
        } else {
            echo "			new {$className}\r\n";
        }
        echo "		</td>";
        echo "	</tr>";
        echo "	<tr>";
        if (method_exists($vars, 'getFoto') && method_exists($vars, 'getFotoSize')) {
            $_tmpAllSizes = $vars->getFotoSize(false);
            if (isset($_tmpAllSizes['fnt'])) {
                echo "		<td valign='top'>";
                echo "			<img src='{$vars->getFoto('', 'fnt')}' height='58' />";
                echo "		</td>";
            }
        }
        echo "		<td valign='top'>";

        echo "<table border='1' style='border-collapse: collapse' cellpadding='1'>";
        { // fieldNames
            echo "	<tr>";
            echo "<td></td>";
            foreach ($vars->fieldValues as $fieldName => $fieldValue) {
                echo "<td>{$fieldName}</td>";
            }
            $extKeys = $className::structGet('fieldProps', 'external');
            if ($extKeys) {
                array_map(function ($aliasName) use (&$extKeys) {
                    echo "<td style='background: #F99' title='Related to: {$extKeys[$aliasName]['thisKey']}'>{$aliasName}</td>";
                }, array_keys($extKeys));
            }
            echo "	</tr>";
        }
        { // getValue
            echo "	<tr valign='top'>";
            echo "<td>getValue()</td>";
            foreach ($vars->fieldValues as $fieldName => $fieldValue) {
                echo "<td>";
                $_toDump = $vars->v($fieldName);
                self::dump($_toDump, $maxDepth - 1);
                echo "</td>";
            }
            $extKeys = $className::structGet('fieldProps', 'external');
            if ($extKeys) {
                array_map(function ($aliasName) use ($vars, $maxDepth, &$_printedUids) {
                    echo "<td rowspan='2' style='background: #FDD' align='center'>";
                    if (!$vars->extObjects[$aliasName] || is_string($vars->extObjects[$aliasName]) && $vars->extObjects[$aliasName] == '**not_found**') {
                        echo "<i><small style='white-space: nowrap'>";
                        if ($vars->extObjects[$aliasName] == '**not_found**') {
                            echo "false<br />(cached)<br />";
                        } else {
                            $extClassName = get_class($vars);
                            $thisKey = $extClassName::structGet('fieldProps', 'external', $aliasName, 'thisKey');
                            if (!$vars->isLoaded($thisKey)) {
                                echo "false<br />(!{$thisKey})<br />";
                            } elseif ($vars->v($thisKey)) {
                                echo "(on-demand)<br />";
                            } else {
                                echo "false<br />(!{$thisKey})<br />";
                            }
                        }
                        echo "</small></i>";
                    } elseif (in_array($vars->v($aliasName)->uid, $_printedUids)) {
                        echo "<div";
                        echo " onmouseover=\"$(this).closest('table[rel=dDbRow3-{$vars->v($aliasName)->uid}]').css('box-shadow', '0px 0px 25px 0px #F00');\"";
                        echo " onmouseout =\"$(this).closest('table[rel=dDbRow3-{$vars->v($aliasName)->uid}]').css('box-shadow', '-1px 1px 3px #888888');\"";
                        echo ">";
                        echo "*recursive*<br />";
                        echo "<small>";
                        echo "uid={$vars->v($aliasName)->uid}<br />";
                        echo "</small>";
                        echo "</div><br />";
                    } else {
                        $_printedUids[] = $vars->uid;
                        echo "	<div class='expand_obj' style='display: none'>";
                        $_dump = $vars->v($aliasName);
                        dDbRow3::dump($_dump, $maxDepth - 1, $_printedUids);
                        echo "	</div>";
                        echo "	<a href='#' onclick=\"dDbRow3DumpToggle($(this).closest('td').find('.expand_obj').first()); return false;\">(show)</a>";
                    }
                    echo "</td>";
                }, array_keys($extKeys));
            }
            echo "	</tr>";
        }
        { // getOriginal
            echo "	<tr valign='top' style='background: #DFD'>";
            echo "<td>getOriginal()</td>";
            if (!$vars->isLoaded()) {
                echo "<td style='border: 0' colspan='" . sizeof($vars->fieldValues) . "'>";
                echo "	<i>Unloaded, always false.</i>";
                echo "</td>";
            } elseif (@!$vars->extra['fieldOriginal']) {
                echo "<td style='border: 0' colspan='" . sizeof($vars->fieldValues) . "'>";
                echo "	<i>No changes, always equal to getValue().</i>";
                echo "</td>";
            } else {
                foreach ($vars->fieldValues as $fieldName => $fieldValue) {
                    if (isset($vars->extra['fieldOriginal']) && array_key_exists($fieldName,
                            $vars->extra['fieldOriginal'])) {
                        echo "<td style='border: 1px solid #00F; background: #9F9'>";
                        $_dump = $vars->getOriginal($fieldName);
                        self::dump($_dump, $maxDepth - 1);
                        echo "</td>";
                    } else {
                        echo "<td style='border: 0' align='center' valign='center'>";
                        echo "<span style='display: inline-block; color: #000; font-weight: bold'>=</span>";
                        echo "</td>";
                    }
                }
            }
            echo "	</tr>";
        }
        echo "</table>";

        // fieldVirtuals
        if (isset($vars->extra['fieldVirtuals'])) {
            echo "<table border='1' style='border-collapse: collapse; margin-top: 3px' cellpadding='2' bgcolor='#CCFFFF'>";
            { // fieldNames
                echo "	<tr>";
                echo "<td></td>";
                foreach ($vars->extra['fieldVirtuals'] as $fieldName => $fieldValue) {
                    echo "<td>{$fieldName}</td>";
                }
                echo "	</tr>";
            }
            { // getVirtual
                echo "	<tr valign='top'>";
                echo "<td>getVirtual()</td>";
                foreach ($vars->extra['fieldVirtuals'] as $fieldName => $fieldValue) {
                    echo "<td>";
                    $_toDump = $vars->getVirtual($fieldName);
                    self::dump($_toDump, $maxDepth - 1);
                    echo "</td>";
                }
                echo "	</tr>";
            }
            echo "</table>";
        }

        echo "		</td>";

        echo "		<td style='padding: 3px; border-left: 1px solid #000; background: #FFF' valign='top'>";
        echo "			<a href='#' onclick=\"$(this).closest('td').find('.expand').first().animate({ width: 'toggle', height: 'toggle'}); return false;\">(+)</a><br />";
        echo "			<div class='expand' style='display: none'>";
        self::dump($dump, $maxDepth - 1);
        echo "			</div>";
        echo "		</td>";

        echo "	</tr>";
        echo "</table>";
    }
}

