<?php
// 2013-10-13: v2.985 - Novos métodos estáticos: start($id), stIsLoaded($id), stSetLoaded($obj), stUnload($obj)
// 2013-08-25: v2.98  - setValue agora return $this, para chained-methods.
// 2013-08-25:        - addExtendedClass: Opção autoDelete agora é FALSE por padrão.
// 2013-08-22: v2.971 - Novo método ->v($aliasName[, $setValue])
// 2013-08-22:        - Nova funcionalidade: ->addExtendedClass()
/**
 * Importante:
 * - Não inserir construções avançadas (private, protected, etc.) nas variáveis.
 * - Isso deverá ser feito no futuro, onde esta e as sub-classes deverão implementar Serializable.
 **/

/** Enable class debug via query string **/
if (isset($_GET["dDbRow-debug"])) {
    setcookie("dDbRow-debug", $_GET["dDbRow-debug"], false, '/');
    header("Location: $_SERVER[PHP_SELF]");
    die;
}

class dDbRow
{
    function getVersion()
    {
        return "2.985";
    }

    var $mainTable;
    var $ffTables;
    var $fieldProps;
    var $fieldValues;
    var $fieldOriginal;
    var $fieldVirtuals;
    var $extClass;
    var $useQuotes;

    var $autoUpdateOn;
    var $autoUpdateAlias;

    var $validations;
    var $modifiers;
    var $errorList;

    var $ignoreMod;
    var $ignoreVal;

    var $debug;
    var $db;

    // Constructor singleTon
    static $stLoaded = array();

    static function start($primaryValue)
    {
        $className = get_called_class();
        if (self::stIsLoaded($primaryValue)) {
            return self::$stLoaded[$className][$primaryValue];
        }

        $obj = new $className;
        if ($obj->loadFromDatabase($primaryValue)) {
            self::stSetLoaded($obj);
            return $obj;
        }

        return false;
    }

    static function stIsLoaded($primaryValue)
    {
        $className = get_called_class();
        return isset(self::$stLoaded[$className][$primaryValue]);
    }

    static function stSetLoaded($obj)
    {
        $className = get_class($obj);
        self::$stLoaded[$className][$obj->getPrimaryValue()] = $obj;
    }

    static function stUnload($obj)
    {
        $className = get_class($obj);
        unset(self::$stLoaded[$className][$obj->getPrimaryValue()]);
    }

    // Constructor
    function dDbRow($db = false)
    {
        if (!$db) {
            $db = dDatabase::start();
        }

        $this->mainTable['primaryValue'] = false;

        $this->ffTables =
        $this->fieldProps =
        $this->fieldValues =
        $this->fieldOriginal =
        $this->fieldVirtuals =
        $this->extClass =
        $this->validations =
        $this->modifiers =
        $this->errorList =
        $this->validations = array();

        $this->ignoreMod =
        $this->ignoreVal =
        $this->autoUpdateOn = false;
        $this->autoUpdateAlias = array();

        $this->setDatabaseClass($db);
    }

    function setDatabaseClass(&$object)
    {
        if (!$object && isset($GLOBALS['db'])) {
            $object = &$GLOBALS['db'];
        }
        if (strtolower(get_class($object)) != 'ddatabase') {
            echo "<font color='#FF0000'>Invalid database class. dDbRow won't work as expected.</font><br>" . get_class($object);
        }
        $this->db = &$object;
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
        if (stripos($tableName, ' as ')) {
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
    }

    function addField($aliasNames, $fieldNames = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $fieldNames = is_string($fieldNames) ? explode(",", $fieldNames) : $fieldNames;
        foreach ($aliasNames as $idx => $aliasName) {
            $fieldName = (isset($fieldNames[$idx]) && $fieldNames[$idx]) ? $fieldNames[$idx] : $aliasName;
            $this->fieldOriginal[$aliasName] = array();
            $this->fieldOriginal[$aliasName]['oldValue'] = false;
            $this->fieldOriginal[$aliasName]['isModified'] = false;
            $this->fieldValues  [$aliasName] = false;
            $this->fieldProps   [$aliasName]['raw'] = false;
            $this->fieldProps   [$aliasName]['isGIS'] = false;
            $this->fieldProps   [$aliasName]['isForeign'] = false;
            $this->fieldProps   [$aliasName]['sqlField'] = "{$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes}.{$this->useQuotes}$fieldName{$this->useQuotes}";
            $this->fieldProps   [$aliasName]['fieldName'] = $fieldName;
            $this->fieldProps   [$aliasName]['loadThis'] = true;
            $this->fieldProps   [$aliasName]['saveThis'] = true;
        }
    }

    function addFField($tableName, $aliasNames, $fieldNames = false, $modes = 'ignore')
    {
        /** Adicionar uma verificação de estrutura, com 'mode' **/
        $aliasNames = explode(",", $aliasNames);
        $fieldNames = is_string($fieldNames) ? explode(",", $fieldNames) : $fieldNames;
        $modes = is_string($modes) ? explode(",", $modes) : $modes;

        foreach ($aliasNames as $idx => $aliasName) {
            $this->ffTables[$tableName]['fieldAliases'][] = $aliasName;
            $fieldName = (isset($fieldNames[$idx]) && $fieldNames[$idx]) ? $fieldNames[$idx] : $aliasName;
            $mode = (isset($modes[$idx]) ? $modes[$idx] : $modes[0]); // If not find, use first 'mode' as default.

            $this->fieldValues[$aliasName] = false;
            $this->fieldProps [$aliasName]['raw'] = false;
            $this->fieldProps [$aliasName]['isGIS'] = false;
            $this->fieldProps [$aliasName]['isForeign'] = true;
            $this->fieldProps [$aliasName]['sqlField'] = "{$this->useQuotes}$tableName{$this->useQuotes}.{$this->useQuotes}$fieldName{$this->useQuotes}";
            $this->fieldProps [$aliasName]['tableName'] = $tableName;
            $this->fieldProps [$aliasName]['fieldName'] = $fieldName;
            $this->fieldProps [$aliasName]['loadThis'] = true;
            $this->fieldProps [$aliasName]['saveThis'] = true;
            $this->fieldProps [$aliasName]['mode'] = $mode;
        }
    }

    function setFFieldMode($aliasName, $mode)
    {
        /** Adicionar uma verificação se é foreign **/
        $this->fieldProps [$aliasName]['mode'] = $mode;
    }

    function setFExtraOn($tableName, $extraOn)
    {
        $this->ffTables[$tableName]['extraOn'] = $extraOn;
    }

    function setJoinMode($tableName, $joinMode)
    {
        $this->ffTables[$tableName]['joinMode'] = $joinMode;
    }

    // Extended classes (1<-->1)
    // Funcionamento básico:
    // --> No construtor, crie uma relação 1-1 com o addExtendedClass
    // --> Sempre que chamar o getExtendedObj, o objeto será carregado e disponibilizado pra uso
    // --> Sempre que o obj principal for salvo, o saveToDatabase() será propagado relacionando os campos
    // --> Sempre que o obj principal for recarregado (loaded), o loadFromDatabase() também será propagado
    function addExtendedClass($className, $aliasSource, $fieldTarget, $options = array())
    {
        $this->extClass[$className] = array(
            'aliasSource' => $aliasSource,
            'fieldTarget' => $fieldTarget,
            'paused' => false,
            'loadedObj' => false,
            'options' => array()
        );
        $this->changeExtendedClass($className, $options);
    }

    function changeExtendedClass($className, $options = array())
    {
        $options += array(
            'autoSave' => true,
            'autoLoad' => true,
            'autoDelete' => false,
        );
        $this->extClass[$className]['options'] += $options;
    }

    function pauseExtendedClass($className, $setPause)
    {
        $this->extClass[$className]['paused'] = $setPause;
    }

    function getExtendedObj($className)
    {
        $classItem = &$this->extClass[$className];
        if (!$classItem['loadedObj']) {
            $classItem['loadedObj'] = new $className;
            if ($this->getPrimaryValue()) {
                $classItem['loadedObj']->loadFromDatabase($this->getValue($classItem['aliasSource']),
                    $classItem['fieldTarget']);
            }
        }
        return $this->extClass[$className]['loadedObj'];
    }

    function unloadExtendedClass($className)
    {
        unset($this->extClass[$className]['loadedObj']);
        $this->extClass[$className]['loadedObj'] = false;
    }

    function _extendedOnLoad()
    {
        // Propague o loadFromDatabase em todos os objetos já carregados.
        foreach ($this->extClass as $className => $classItem) {
            if ($classItem['paused'] || !$classItem['options']['autoLoad'] || !$classItem['loadedObj']) {
                continue;
            }

            $classItem['loadedObj']->loadFromDatabase($this->getValue($classItem['aliasSource']),
                $classItem['fieldTarget']);
        }
    }

    function _extendedOnSave()
    {
        foreach ($this->extClass as $className => $classItem) {
            if ($classItem['paused'] || !$classItem['options']['autoSave'] || !$classItem['loadedObj']) {
                continue;
            }

            $extObj = $classItem['loadedObj'];
            if ($extObj->isModified()) {
                $extObj->setValue($classItem['fieldTarget'], $this->getValue($classItem['aliasSource']));
                $extObj->saveToDatabase();
            }
            $this->appendErrors($extObj);
        }
    }

    function _extendedOnDelete()
    {
        foreach ($this->extClass as $className => $classItem) {
            if ($classItem['paused'] || !$classItem['options']['autoDelete']) {
                continue;
            }

            $extObj = $this->getExtendedObj($className);
            if ($extObj->getPrimaryValue()) {
                $extObj->deleteFromDatabase();
            }
        }
    }

    // Auto-update feature
    function startUpdate($clearPending = false)
    {
        if (!$this->getPrimaryValue()) {
            $this->castMsg(1, "Cannot begin autoUpdate when no primaryValue is loaded!");
            return;
        }

        $this->castMsg("autoUpdate is now started.");
        if ($this->autoUpdateOn) {
            $this->castMsg("autoUpdate was already active. State not changed.");
            return true;
        }

        $this->autoUpdateOn = true;
        if ($clearPending) {
            $this->castMsg("cleaning autoUpdate list.");
            $this->autoUpdateAlias = array();
        }
    }

    function flushUpdate($cancelAll = false)
    {
        $this->castMsg("autoUpdate is now flushing");
        if (!$this->autoUpdateOn) {
            $this->castMsg("autoUpdate is not active, so there's nothing to flush.");
            return false;
        }

        if ($cancelAll) {
            $this->autoUpdateOn = false;
            $this->autoUpdateAlias = array();
            return true;
        }

        if ($this->autoUpdateAlias) {
            $backup = $this->fieldProps;
            $this->saveOnlyFields($this->autoUpdateAlias);
            $ret = $this->saveToDatabase();
            $this->fieldProps = $backup;
            $this->autoUpdateAlias = array();
            return $ret;
        }
        $this->castMsg("autoUpdate had nothing to flush!");
        return false;
    }

    // Field limitation
    function useOnlyFields($fieldsToUse, $invert = false)
    {
        $this->saveOnlyFields($fieldsToUse, $invert);
        $this->loadOnlyFields($fieldsToUse, $invert);
    }

    function saveOnlyFields($fieldsToSave, $invert = false)
    {
        $fieldsToSave = is_string($fieldsToSave) ? explode(",", $fieldsToSave) : $fieldsToSave;
        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            if ($invert) {
                $this->fieldProps[$aliasName]['saveThis'] = !in_array($aliasName, $fieldsToSave);
            } else {
                $this->fieldProps[$aliasName]['saveThis'] = in_array($aliasName, $fieldsToSave);
            }
        }
    }

    function loadOnlyFields($fieldsToLoad, $invert = false)
    {
        $fieldsToLoad = is_string($fieldsToLoad) ? explode(",", $fieldsToLoad) : $fieldsToLoad;
        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            if ($aliasName != $this->mainTable['primaryIndex']) {
                if ($invert) {
                    $this->fieldProps[$aliasName]['loadThis'] = !in_array($aliasName, $fieldsToLoad);
                } else {
                    $this->fieldProps[$aliasName]['loadThis'] = in_array($aliasName, $fieldsToLoad);
                }
            }
        }
    }

    // Original handling
    function isModified($aliasName = false)
    {
        if (!$aliasName) {
            $aliasNames = array_keys($this->fieldValues);
            foreach ($aliasNames as $aliasName) {
                if ($this->isModified($aliasName)) {
                    return true;
                }
            }
            return false;
        }

        return ($this->fieldOriginal[$aliasName]['isModified']);
    }

    function getOriginal($aliasName)
    {
        return $this->isModified($aliasName) ?
            $this->fieldOriginal[$aliasName]['oldValue'] :
            $this->getValue($aliasName);
    }

    function setOriginal($aliasName, $value, $copyToValue = false)
    {
        $this->fieldOriginal[$aliasName] = array(
            'isModified' => false,
            'oldValue' => $value
        );
        if ($copyToValue) {
            $this->setValue($aliasName, $value);
        }
    }

    // Data handling
    function o($className)
    {
        return $this->getExtendedObj($className);
    }

    function v($aliasName)
    {
        if (func_num_args() < 2) {
            return $this->getValue($aliasName);
        } elseif (func_num_args() == 2) {
            return $this->setValue($aliasName, func_get_arg(1));
        } elseif (func_num_args() == 3) {
            return $this->getValue($aliasName, func_get_arg(2));
        }
        $this->castMsg(1, "Invalid number of arguments.");
        return false;
    }

    function getValue($aliasName, $raw = false)
    {
        if (!isset($this->fieldProps[$aliasName])) {
            trigger_error("dDbRow: Returning empty from '$aliasName', because this alias doesn't exist.",
                E_USER_NOTICE);
            return "";
        }

        // Por padrão, não mostrar o valor de uma string definida como RAW
        if ($raw || !$this->fieldProps[$aliasName]['raw']) {
            return $this->fieldValues[$aliasName];
        }

        return "";
    }

    function setValue($aliasName, $value, $raw = false)
    {
        if (!isset($this->fieldProps[$aliasName])) {
            trigger_error("dDbRow: Cannot set value for '$aliasName', because this alias doesn't exist.",
                E_USER_NOTICE);
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
            return false;
        }

        $plusDebug = "";
        if (($raw || $this->getOriginal($aliasName) !== $value)) {
            $this->fieldOriginal[$aliasName]['isModified'] = true;
            $plusDebug = ", ** IS MODIFIED";
        }

        $this->fieldValues  [$aliasName] = $value;
        $this->fieldProps   [$aliasName]['raw'] = $raw;

        if (is_array($value)) {
            $showNewValue = "<font color='purple'>" . var_export($value, true) . "</font>";
        } elseif (is_bool($value)) {
            $showNewValue = "<font color='#880088'>" . ($value ? '&lt;true&gt;' : '&lt;false&gt;') . "</font>";
        } else {
            $showNewValue = substr($value, 0, 50);
        }
        $this->castMsg("Setting value(<font color='red'>$aliasName</font>): [<font color='black'>$showNewValue</font>]" . ($raw ? " <font color='blue'>*RAW*</font>" : ""));

        if ($this->autoUpdateOn) {
            $this->autoUpdateAlias[] = $aliasName;
        }

        return $this;
    }

    function setPrimaryValue($value, $setValue = false)
    {
        $this->mainTable['primaryValue'] = $value;
        if ($setValue) {
            $this->setValue($this->mainTable['primaryIndex'], $value);
        }
    }

    function getPrimaryValue()
    {
        return $this->mainTable['primaryValue'];
    }

    // Virtual data handling (Virtual values WON'T be stored in Array nor Database)
    function setVirtual($aliasName, $value)
    {
        $this->fieldVirtuals[$aliasName] = $value;
        $this->castMsg("Setting virtual(<font color='purple'>$aliasName</font>): [<font color='black'>" . (!is_string($value) && !is_int($value) ? "*Not String*" : substr($value,
                0, 50)) . "</font>]");
    }

    function getVirtual($aliasName)
    {
        if (isset($this->fieldVirtuals[$aliasName])) {
            return $this->fieldVirtuals[$aliasName];
        }
        return false;
    }

    // Loading
    function loadFromArray($arr, $ignoreFields = array(), $fromDb = false)
    {
        $this->castMsg("Loading variables from Array...");
        if (!is_array($ignoreFields)) {
            $ignoreFields = explode(",", $ignoreFields);
        }

        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            if (!in_array($aliasName, $ignoreFields) && $fieldProps['saveThis']) {
                if (array_key_exists($aliasName, $arr)) {
                    $this->setValue($aliasName, $this->modifyAlias($arr[$aliasName], $aliasName, $fromDb ? 2 : 0));
                    if ($aliasName == $this->mainTable['primaryIndex']) {
                        $this->mainTable['primaryValue'] = $this->modifyAlias($arr[$aliasName], $aliasName,
                            $fromDb ? 2 : 0);
                    }
                } else {
                    $this->castMsg("Couldn't find value for field $aliasName, ignoring...");
                    #$this->setValue($aliasName, '');
                }
            }
            if (array_key_exists($aliasName, $arr)) {
                unset($arr[$aliasName]);
            }
        }
        foreach ($arr as $aliasName => $info) {
            $this->castMsg("Field $aliasName is lefting in the array.");
        }
    }

    function loadFromDatabase($primaryValue, $aliasAsPrimary = false)
    {
        if (is_array($primaryValue)) {
            $this->castMsg(1,
                "<b>loadFromDatabase()</b> cannot receive array. Maybe you're looking for <b>loadFromArray()</b>?");
            return false;
        }
        if ($primaryValue === false) {
            // Não forneceu nenhum valor para carregar.. Não perca tempo processando.
            return false;
        }

        $this->reset(true);

        $db = &$this->db;
        $this->castMsg("Loading from database, primary $primaryValue" . ($aliasAsPrimary ? " (using alias $aliasAsPrimary)" : ""));
        $qSelect = array();
        $qFFTables = array();
        $qFrom = array("{$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes}");

        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            if (!$fieldProps['loadThis']) {
                continue;
            }

            $qSelect[] = ($fieldProps['isGIS'] ? "AsText({$fieldProps['sqlField']}) " : $fieldProps['sqlField']) .
                " as {$this->useQuotes}$aliasName{$this->useQuotes}";

            if ($fieldProps['isForeign'] && !in_array($fieldProps['tableName'], $qFFTables)) {
                $qFFTables[] = $fieldProps['tableName'];
            }
        }

        foreach ($qFFTables as $tableName) {
            $tableProps = &$this->ffTables[$tableName];
            $tmp = "{$tableProps['joinMode']} join ";
            $tmp .= ($tableProps['realTable']) ?
                "{$tableProps['realTable']} as {$tableName}" :
                "{$tableName}";
            $tmp .= " ON ";
            $tmp .= $this->fieldProps[$tableProps['aliasSource']]['sqlField'];
            $tmp .= " = ";
            $tmp .= "{$this->useQuotes}$tableName{$this->useQuotes}.{$this->useQuotes}$tableProps[fieldTarget]{$this->useQuotes} ";
            $tmp .= $tableProps['extraOn'] ? $tableProps['extraOn'] : '';
            $qFrom[] = $tmp;
        }

        if ($aliasAsPrimary) {
            $qWhere = "{$this->fieldProps[$aliasAsPrimary]['sqlField']}";
        } else {
            $qWhere = "{$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes}.{$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes}";
        }
        $qWhere .= " = ";
        $qWhere .= $this->formatToQuery($primaryValue);

        $q = "SELECT\t" . join(", \n\t", $qSelect) . "\n";
        $q .= "FROM  \t" . join("\n\t", $qFrom) . "\n";
        $q .= "WHERE \t$qWhere\n";
        $q .= "LIMIT 1";
        $result = $db->singleLine($q, 'Loading data from database');

        if ($result) {
            foreach ($result as $aliasName => $value) {
                /** Apply modifiers from Database to local class **/
                $this->setValue($aliasName, $this->modifyAlias($value, $aliasName, 2));
                $this->setOriginal($aliasName, $this->getValue($aliasName));
            }
            $this->mainTable['primaryValue'] = $this->getValue($this->mainTable['primaryIndex'], true);
            $this->_extendedOnLoad();
            return true;
        }
        return false;
    }

    function reset($keepUseOnlySettings = false)
    {
        $this->castMsg("Resetting the class...");

        if ($keepUseOnlySettings) {
            $origProps = $this->fieldProps;
        }

        if (method_exists($this, get_class($this))) {
            call_user_func(array($this, get_class($this)));
        } else {
            call_user_func(array($this, '__construct'));
        }

        if ($keepUseOnlySettings) {
            foreach ($origProps as $aliasName => $fieldProps) {
                $this->fieldProps[$aliasName]['loadThis'] = $fieldProps['loadThis'];
                $this->fieldProps[$aliasName]['saveThis'] = $fieldProps['saveThis'];
            }
        }
    }

    // Saving
    function saveToArray($ignoreErrors = false)
    {
        if ((!$this->ignoreVal && !$ignoreErrors) && !$this->checkForErrors()) {
            return false;
        }

        $ret = array();
        foreach ($this->fieldValues as $aliasName => $value) {
            $ret[$aliasName] = $this->modifyAlias($value, $aliasName, 0);
        }
        return $ret;
    }

    function saveToDatabase($primaryValue = false)
    {
        $db = &$this->db;
        $this->castMsg("Saving to primary value: $primaryValue");
        if ($primaryValue) {
            $this->mainTable['primaryValue'] = $primaryValue;
            if (!$this->getValue($this->mainTable['primaryIndex'], true)) {
                $this->setValue($this->mainTable['primaryIndex'], $primaryValue);
            }
        }

        if (!($e1 = $this->checkForErrors()) || !($e2 = $this->validateUnique()) || !($e3 = $this->updateFF())) {
            if ($this->debug) {
                if (isset($e1) && $e1) {
                    $this->castMsg('Halting on: checkForErrors');
                }
                if (isset($e2) && $e2) {
                    $this->castMsg('Halting on: validateUnique');
                }
                if (isset($e3) && $e3) {
                    $this->castMsg('Halting on: updateFF');
                }
            }
            return false;
        }

        if ($this->mainTable['primaryValue']) {
            $q = "select {$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes} ";
            $q .= "from  {$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes} ";
            $q .= "where {$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes} = ";
            $q .= $this->formatToQuery($this->mainTable['primaryValue'], $this->mainTable['primaryIndex']);
            if ($this->mainTable['primaryValue'] && $db->singleResult($q, 'Checking if will CREATE or UPDATE')) {
                /** Already exists, just update **/
                $qFields = array();
                foreach ($this->fieldProps as $aliasName => $fieldProps) {
                    if ($fieldProps['isForeign'] || !$fieldProps['saveThis']) {
                        continue;
                    }
                    if ($fieldProps['fieldName'] == $this->mainTable['primaryIndex']
                        && $this->getValue($aliasName, true) != $this->mainTable['primaryValue']
                        && $this->getValue($aliasName, true)) {
                        $this->castMsg("Updating primary value, from {$this->mainTable['primaryValue']} to " . $this->getValue($aliasName,
                                true));
                    } elseif ($fieldProps['fieldName'] == $this->mainTable['primaryIndex']) {
                        continue;
                    }
                    $qFields[] = "{$this->useQuotes}$fieldProps[fieldName]{$this->useQuotes} = " . $this->formatToQuery($this->getValue($aliasName,
                            true), $aliasName);
                }
                $q = "update {$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes} set ";
                $q .= join(", ", $qFields);
                $q .= " where {$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes} = ";
                $q .= $this->formatToQuery($this->mainTable['primaryValue'], $this->mainTable['primaryIndex']);
                $qOk = $db->query($q, 'Updating existing row');
                if (!$qOk) {
                    $this->addError(false, "Erro interno, Favor contactar o suporte o quanto antes.");
                    return false;
                }

                // Successful update. Flush the original values, because they are now synced again.
                foreach ($this->fieldProps as $aliasName => $fieldProps) {
                    $this->setOriginal($aliasName, $this->getValue($aliasName));
                }

                $this->mainTable['primaryValue'] = $this->getValue($this->mainTable['primaryIndex'], true);
                $this->_extendedOnSave();
                return $this->mainTable['primaryValue'];
            }
        }

        /** Doesn't exist, create it **/
        $qFields =
        $qValues = array();
        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            if ($fieldProps['isForeign'] || !$fieldProps['saveThis']) {
                continue;
            }

            $qFields[] = $fieldProps['fieldName'];
            $qValues[] = $this->formatToQuery($this->getValue($aliasName, true), $aliasName);
        }
        $q = "insert into {$this->useQuotes}" . $this->mainTable['tableName'] . "{$this->useQuotes}\n";
        $q .= " ({$this->useQuotes}" . join("{$this->useQuotes}, {$this->useQuotes}",
                $qFields) . "{$this->useQuotes})\n";
        $q .= " values (" . join(", ", $qValues) . ") ";
        $qOk = $db->query($q, 'Creating new row');

        if (!$qOk) {
            $this->addError(false, "Erro interno, Favor contactar o suporte o quanto antes.");
            return false;
        }

        // Successful save. Flush the original values, because they are now synced again.
        foreach ($this->fieldProps as $aliasName => $fieldProps) {
            $this->setOriginal($aliasName, $this->getValue($aliasName));
        }

        $lastId = $db->lastId();
        if (!$lastId) { // No ID nor SQL error? So the table doesn't have a auto_increment field! Return just the primary value.
            $lastId = $this->getValue($this->mainTable['primaryIndex']);
        }
        $this->mainTable['primaryValue'] = $lastId;
        $this->setValue($this->mainTable['primaryIndex'], $lastId);
        $this->_extendedOnSave();

        return $lastId;
    }

    function updateFF()
    {
        return true;

        $db = &$this->db;
        $foundId = false;
        foreach ($this->ffTables as $tableName => $tableProps) {/* Verificação de erros° ignore° find/error° find/create° create */
            $aliasName = &$tableProps['fieldAliases'][0];
            $fp = &$this->fieldProps [$aliasName];
            $fv = &$this->fieldValues[$aliasName];

            # echo "Doing it here: {$tableName}<br />";
            if ($fp['mode'] == 'ignore' || !$fieldProps['saveThis']) {
                continue;
            }
            if ($fp['mode'] == 'find' || $fp['mode'] == 'find/error' || $fp['mode'] == 'find/create' || $fp['mode'] == 'find/update') {
                $q = "select {$this->useQuotes}$tableProps[fieldTarget]{$this->useQuotes} from {$this->useQuotes}$tableName{$this->useQuotes} ";
                $q .= "where  {$this->useQuotes}{$fp['fieldName']}{$this->useQuotes} = ";
                $q .= $this->formatToQuery($fv, $aliasName);

                $foundId = $db->singleResult($q, 'updateFF - Searching for existing ID');
                if (!$foundId && $fp['mode'] == 'find/error') {
                    $this->castMsg("Casting 'find/error' / table $tableName");
                    foreach ($this->validations['---'] as $valProps) {
                        if ($valProps['strVal'] == 'find/error' && $valProps['param'] == $tableName) {
                            $this->addError('---', $valProps['errorStr']);
                            continue;
                        }
                    }
                }
            }
            if ($fp['mode'] == 'create' || (!$foundId && $fp['mode'] == 'find/create')) {
                $q = "insert into {$this->useQuotes}$tableName{$this->useQuotes} ({$this->useQuotes}$fp[fieldName]{$this->useQuotes}) ";
                $q .= "values (";
                $q .= $this->formatToQuery($fv, $aliasName);
                $q .= ")";
                $db->query($q, 'updateFF - Inserting value in foreign key');
                /** Mais uma verificação de SQL Error **/
                $foundId = $db->lastId();
            }
            if ($fp['mode'] == 'update' || (!$foundId && $fp['mode'] == 'find/update')) {
                if ($tmp = $this->getValue($tableProps['aliasSource'], true)) {
                    $q = "update {$this->useQuotes}$tableName{$this->useQuotes} ";
                    $q .= "set {$this->useQuotes}$fp[fieldName]{$this->useQuotes}=" . $this->formatToQuery($fv,
                            $aliasName) . " ";
                    $q .= "where  {$this->useQuotes}$tableProps[fieldTarget]{$this->useQuotes} = ";
                    $q .= $this->formatToQuery($tmp, $tableProps['fieldTarget']);
                    $db->query($q, "updateFF - Updating field $fv");
                    $foundId = $tmp;
                }
            }
            $this->setValue($tableProps['aliasSource'], $foundId);
        }
        foreach ($this->ffTables as $tableName => $tableProps) {/* Atualização real dos campos */
            for ($x = 1; $x < sizeof($tableProps['fieldAliases']); $x++) {
                $aliasName = &$tableProps['fieldAliases'][$x];
                $fp = &$this->fieldProps [$aliasName];
                $fv = &$this->fieldValues[$aliasName];
                if ($fp['mode'] == 'ignore') {
                    continue;
                }
                if ($fp['mode'] == 'update' && $this->getValue($tableProps['aliasSource'], true)) {
                    $q = "update {$this->useQuotes}$tableName{$this->useQuotes} set {$this->useQuotes}$fp[fieldName]{$this->useQuotes} = ";
                    $q .= $this->formatToQuery($fv, $aliasName);
                    $q .= " where $tableProps[fieldTarget] = ";
                    $q .= $this->formatToQuery($this->getValue($tableProps['aliasSource'], true),
                        $tableProps['aliasSource']);
                    $db->query($q, 'updateFF - Updating existing foreign field');
                }
            }
        }
        return true;
    }

    // Deletting
    function deleteFromDatabase($primaryValue = false)
    {
        $this->_extendedOnDelete();

        $db = &$this->db;
        if ($primaryValue === false && !($primaryValue = $this->mainTable['primaryValue'])) {
            $this->castMsg("Cannot delete without a valid primaryValue.");
            return false;
        }
        $q = "delete from {$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes}";
        $q .= " where {$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes}";
        $q .= " = " . $this->formatToQuery($primaryValue);

        $this->mainTable['primaryValue'] = false;
        $db->query($q, "Deletting from database");

        return true;
    }

    // Modifier
    function addModifier($aliasNames, $strMod, $param = false)
    {
        $aliasNames = explode(",", $aliasNames);
        $mods = explode(",", $strMod);

        foreach ($aliasNames as $aliasName) {
            foreach ($mods as $mod) {
                if (strtolower($mod) == 'gis') {
                    $this->fieldProps[$aliasName]['isGIS'] = true;
                } else {
                    $this->modifiers[$aliasName][] = array(
                        'strMod' => $mod,
                        'param' => $param
                    );
                }
            }
        }
    }

    function modifyString($fv, $strMod, $param, $toDb, $aliasName = false)
    {
        if (is_array($fv) && !in_array($strMod, array('serialize', 'serialize_text', 'callback'))) { // ' // "
            echo "<b>dDbRow critical:</b> Array value given as setValue({$aliasName}, xxx) parameter. Trying modifier {$strMod}<br />";
            echo "<pre style='border: 1px solid #555; padding: 10px'>";
            print_r(debug_backtrace(false));
            echo "</pre>";
            die;
            return false;
        }

        if ($strMod == 'trim') {
            $fv = trim($fv);
        } elseif ($strMod == 'force_int') {
            $fv = (trim($fv) !== '') ? intval($fv) : false;
        } elseif ($strMod == 'force_numbers') {
            $fv = preg_replace("/[^0-9]/", "", $fv);
        } elseif ($strMod == 'force_float') {
            if (trim($fv) === '') {
                $fv = false;
            } else {
                $fv = strtr($fv, ",.", "..");
                $parts = explode(".", $fv);
                if (sizeof($parts) > 1) {
                    $fv = $parts[sizeof($parts) - 1];
                    unset($parts[sizeof($parts) - 1]);
                    $fv = (float)(join("", $parts) . ".{$fv}");
                    if ($param) {
                        $fv = round($fv, $param);
                    }
                } else {
                    $fv = intval($parts[0]);
                }
                unset($parts);
            }
        } elseif ($strMod == 'force_legible') {
            $fv = preg_replace("/[^A-Za-zÀ-ú0-9]/", "-", $fv);
        } elseif ($strMod == 'lower') {
            $fv = strtolower($fv);
        } elseif ($strMod == 'upper') {
            $fv = strtoupper($fv);
        } elseif ($strMod == 'ucfirst') {
            $fv = ucfirst($fv);
        } elseif ($strMod == 'ucwords') {
            $fv = ucwords($fv);
        } elseif ($strMod == 'remove_double_spaces') {
            while (strpos('  ', $fv) !== false) {
                $fv = str_replace('  ', ' ', $fv);
            }
        } elseif ($strMod == 'remove_spaces') {
            if ($param) {
                $fv = str_replace(' ', $param, $fv);
            } else {
                $fv = str_replace(' ', '', $fv);
            }
        } elseif ($strMod == 'remove_accents') {
            $fv = strtr($fv,
                "\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7
				\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
				\xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD\xE0
				\xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB\xEC
				\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8
				\xF9\xFA\xFB\xFD\xFF",
                "!ao?AAAAAC
				EEEEIIIIDN
				OOOOOUUUYa
				aaaaceeeei
				iiidnooooo
				uuuyy");
            $fv = strtr($fv, array(
                "\xC4" => "Ae",
                "\xC6" => "AE",
                "\xD6" => "Oe",
                "\xDC" => "Ue",
                "\xDE" => "TH",
                "\xDF" => "ss",
                "\xE4" => "ae",
                "\xE6" => "ae",
                "\xF6" => "oe",
                "\xFC" => "ue",
                "\xFE" => "th"
            ));
        } elseif ($strMod == 'null_if_empty') {
            if ($fv === '') {
                $this->castMsg(3, "Converting empty alias '$aliasName' to NULL.");
                $fv = false;
            }
        } elseif ($strMod == 'number_mask') {
            if ($toDb == 1) { // To Database
                $fv = preg_replace("/[^0-9]/", "", $fv);
            }
            if ($toDb == 2) { // From Database
                $ApplyThisValidation = true;
                if (strpos($param, "|")) {
                    // Se encontrar a opção de de tamanho fixo no parâmetro (|xx)
                    $parts = explode("|", $param);
                    $param = $parts[0];

                    if (sizeof($parts) > 2) {
                        die("Critical failure: Modifier 'number_mask' cannot contain pipes (|). Trying alias '$aliasName'.");
                    }

                    $workFv = preg_replace("/[^0-9]/", "", $fv);
                    if (strlen($workFv) != intval($parts[1])) {
                        $ApplyThisValidation = false;
                    }
                }

                if ($ApplyThisValidation) {
                    $carrier = 0;
                    for ($x = 0; $x < strlen($param); $x++) {
                        if ($param[$x] == "#") {
                            if (!isset($workFv[$carrier])) {
                                $param = substr($param, 0, $x);
                            }

                            $param[$x] = $workFv[$carrier++];
                            if ($carrier >= strlen($workFv)) {
                                break;
                            }
                        }
                    }
                    $fv = $param;
                }
                unset($ApplyThisValidation);
            }
        } elseif ($strMod == 'date' || $strMod == 'datetime') {
            if (strtoupper($param) == 'BR') {
                $date = explode(" ", $fv);
                if ($toDb == 1) { // To database 16/10/2004 => 2004-10-16
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
                        $fv = false;
                    }
                }
                if ($toDb == 2) { // From database 2004-10-16 => 16/10/2004
                    $parts = explode("-", $date[0]);
                    if (is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                        $date[0] = sprintf('%02d/%02d/%04d', $parts[2], $parts[1], $parts[0]);
                    } else {
                        $fv = false;
                    }
                }
                if ($fv && $strMod == 'datetime') {
                    $fv = join(" ", $date);
                } elseif ($fv) {
                    $fv = $date[0];
                }
            } elseif (strtoupper($param) == 'US') {
                $date = explode(" ", $fv);
                if ($toDb == 1) { // To database 10/16/2004 => 2004-10-16
                    $parts = explode("/", $date[0]);
                    if (is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                        if (strlen($parts[2]) <= 2) {
                            if ($parts[2] <= 25) {
                                $parts[2] = "20" . $parts[2];
                            } else {
                                $parts[2] = "19" . $parts[2];
                            }
                        }
                        $date[0] = sprintf('%04d-%02d-%02d', $parts[2], $parts[0], $parts[1]);
                    } else {
                        $fv = false;
                    }
                }
                if ($toDb == 2) { // From database 2004-10-16 => 10/16/2004
                    $parts = explode("-", $date[0]);
                    if (is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                        $date[0] = sprintf('%02d/%02d/%04d', $parts[1], $parts[2], $parts[0]);
                    } else {
                        $fv = false;
                    }
                }
                if ($fv && $strMod == 'datetime') {
                    $fv = join(" ", $date);
                } else {
                    $fv = $date[0];
                }
            } else {
                $this->castMsg(1, "Cannot convert date '$aliasName' to zone '$param' - Unknown zone.");
            }
        } elseif ($strMod == 'serialize') {
            if (is_array($fv) && $toDb == 1) {
                $fv = serialize($fv);
            } elseif ($fv && $toDb == 2) {
                $fv = unserialize($fv);
            }
        } elseif ($strMod == 'serialize_text') {
            if (is_array($fv) && $toDb == 1) {
                $str = '';
                $first = true;
                foreach ($fv as $key => $val) {
                    if ($first) {
                        $first = false;
                    } else {
                        $str .= "\n";
                    }

                    $val = preg_replace("/\r?\n/", " ", $val);
                    $str .= "{$key}: {$val}";
                }
                $fv = $str;

                unset($first, $str);
            } elseif ($fv && $toDb == 2) {
                $ret = array();
                $lines = explode("\n", $fv);
                foreach ($lines as $line) {
                    $tmp = explode(": ", $line, 2);
                    $ret[$tmp[0]] = rtrim($tmp[1], "\r\n");
                }
                $fv = $ret;
                unset($lines, $line, $tmp);
            }
        } elseif ($strMod == 'url') {
            if (strpos($fv, "://") === false) {
                $fv = "http://" . $fv;
            }
        } elseif ($strMod == 'callback') {
            $fv = call_user_func($param, $this, $fv, $toDb);
        } elseif ($strMod == 'cpf') {
            $fv = $this->modifyString($fv, 'number_mask', '###.###.###-##|11', $toDb, false);
        } elseif ($strMod == 'cnpj') {
            $fv = $this->modifyString($fv, 'number_mask', '##.###.###/####-##|14', $toDb, false);
            $fv = $this->modifyString($fv, 'number_mask', '###.###.###/####-##|15', $toDb, false);
        } elseif ($strMod == 'cpf_cnpj') {
            $fv = $this->modifyString($fv, 'number_mask', '###.###.###-##|11', $toDb, false);
            $fv = $this->modifyString($fv, 'number_mask', '##.###.###/####-##|14', $toDb, false);
            $fv = $this->modifyString($fv, 'number_mask', '###.###.###/####-##|15', $toDb, false);
        } elseif ($strMod == 'from_rte') {
            if ($toDb == 1) {
                $cleaner = new HTMLCleaner();
                $cleaner->html = $fv;
                $fv = $cleaner->cleanUp('latin1');
            }
        }

        return $fv;
    }

    function modifyAlias($fv, $aliasName, $toDb)
    { # $toDb: 0=To/From array / 1=To database / 2=From database
        if ($this->ignoreMod || !isset($this->modifiers[$aliasName]) || $this->fieldProps[$aliasName]['raw']) {
            return $fv;
        }

        if ($toDb == 1 && !$this->fieldProps[$aliasName]['saveThis']
            || $toDb == 2 && !$this->fieldProps[$aliasName]['loadThis']) {
            return $fv;
        }
        $sof = sizeof($this->modifiers[$aliasName]);

        for ($x = 0; $x < $sof; $x++) {
            $strMod = &$this->modifiers[$aliasName][$x]['strMod'];
            $param = &$this->modifiers[$aliasName][$x]['param'];

            $checkWhenNull = array('callback', 'force_int', 'force_float', 'date', 'datetime', 'null_if_empty');
            if (!$fv && !in_array($strMod, $checkWhenNull)) {
                continue;
            }

            $fv = $this->modifyString($fv, $strMod, $param, $toDb, $aliasName);
        }
        return $fv;
    }

    // Validation
    function addValidation($aliasNames, $strVal, $param = false, $errorStr = false)
    {
        $aliasNames = explode(",", $aliasNames);

        foreach ($aliasNames as $aliasName) {
            if (!$aliasName) {
                $aliasName = '---';
            }
            $this->validations[$aliasName][] = array(
                'strVal' => $strVal,
                'param' => $param,
                'errorStr' => $errorStr
            );
        }
    }

    function checkForErrors($ignoreFields = array())
    {
        if ($this->ignoreVal) {
            return true;
        }

        if (is_string($ignoreFields)) {
            $ignoreFields = explode(",", $ignoreFields);
        }

        $valList = reset($this->validations);
        do {
            $aliasName = key($this->validations);

            if (in_array($aliasName, $ignoreFields)) {
                continue;
            }

            if ($valList) {
                foreach ($valList as $valProps) {
                    $hasError = $this->validateAlias($aliasName, $valProps['strVal'], $valProps['param']);
                    if ($hasError) {
                        if ($valProps['strVal'] == 'callback') {
                            $errorMessage = $hasError;
                        } else {
                            $errorMessage = $valProps['errorStr'];
                        }

                        $this->addError($aliasName, $errorMessage);
                        continue;
                    }
                }
            }
        } while (($valList = next($this->validations)) !== false);

        return sizeof($this->errorList) ? false : true;
    }

    function validateUnique()
    {
        $db = &$this->db;

        /** Boa pergunta, retornar quando encontrar ao menos um DUPE, ou verificar todos? Atualmente procurando só 1 **/
        foreach ($this->validations as $aliasName => $validations) {
            foreach ($validations as $valProps) {
                if ($valProps['strVal'] == 'unique') {
                    $aliasesToCompare = (!is_array($valProps['param'])) ? explode(",",
                        $valProps['param']) : $valProps['param'];
                    $dontUsePrimaryKey = false;
                    for ($x = 0; $x < sizeof($aliasesToCompare); $x++) {
                        if ($aliasesToCompare[$x] == $this->mainTable['primaryIndex']) // To avoid "where field = 'a' and field != 'a'"
                        {
                            $dontUsePrimaryKey = true;
                        }

                        $compareValue = $this->getValue($aliasesToCompare[$x]);
                        $aliasesToCompare[$x] = "{$this->useQuotes}{$aliasesToCompare[$x]}{$this->useQuotes} = " . $this->formatToQuery($this->modifyAlias($compareValue,
                                $aliasesToCompare[$x], 1));
                    }
                    $q = "select {$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes} ";
                    $q .= "from  {$this->useQuotes}{$this->mainTable['tableName']}{$this->useQuotes} ";
                    $q .= "where " . join("\n\tand ", $aliasesToCompare);
                    if ($this->mainTable['primaryValue'] && !$dontUsePrimaryKey) {
                        $q .= " and {$this->useQuotes}{$this->mainTable['primaryIndex']}{$this->useQuotes}";
                        $q .= " != " . $this->formatToQuery($this->mainTable['primaryValue']);
                    }
                    $q .= " limit 1";
                    if ($db->singleResult($q, "Checking for dupe result")) {
                        $this->addError($aliasName, $valProps['errorStr']);
                        return false;
                    }
                }
            }
        }
        return true;
    }

    function validateString($fv, $param, $strVal, $aliasName = false)
    {
        // Se passar pela validação, retorna FALSE
        // Se reprovar, retorna TRUE

        if ($strVal == 'required') {
            if ((is_array($fv) && !$fv) || (!is_array($fv) && !strlen($fv))) {
                return true;
            }
        } elseif ($strVal == 'int' && $fv) {
            if (!(is_numeric($fv) ? (intval($fv) == $fv) : false)) {
                return true;
            }
        } elseif ($strVal == 'nummin' && $fv) {
            if ($fv < $param) {
                return true;
            }
        } elseif ($strVal == 'nummax' && $fv) {
            if ($fv > $param) {
                return true;
            }
        } elseif ($strVal == 'email' && $fv) {
            if (!preg_match("/^[a-zA-Z0-9_][\w\.-]*[a-zA-Z0-9_]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/",
                $fv)) {
                return true;
            }
        } elseif ($strVal == 'strmin' && $fv) {
            if (strlen($fv) < $param) {
                return true;
            }
        } elseif ($strVal == 'strmax' && $fv) {
            if (strlen($fv) > $param) {
                return true;
            }
        } elseif ($strVal == 'strexact' && $fv) {
            if (strlen($fv) != $param) {
                return true;
            }
        } elseif ($strVal == 'singleline' && $fv) {
            if (preg_match("/(\n|\r|\r\n)/", $fv)) {
                return true;
            }
        } elseif ($strVal == 'regex' && $fv) {
            if (substr($param, 0, 1) != '/') {
                $param = "/$param/";
            }
            return !preg_match($param, $fv) ? true : false;
        } elseif ($strVal == '!regex' && $fv) {
            if (substr($param, 0, 1) != '/') {
                $param = "/$param/";
            }
            return preg_match($param, $fv) ? true : false;
        } elseif ($strVal == 'date' && $fv) {
            $param = strtoupper($param);
            if ($param == 'BR') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/"; // 31/01/2007
            } elseif ($param == 'US') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/"; // 01/31/2007
            } else {
                $this->castMsg(2, "Date validation param '$param' not recognized. Only BR / US are accept.");
                return false;
            }
            return preg_match($pattern, $fv) ? false : true;
        } elseif ($strVal == 'datetime' && $fv) {
            $param = strtoupper($param);
            if ($param == 'BR') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4} [012]?[0-9]:[0-5]?[0-9](\:[0-5]?[0-9])?$/"; // 31/01/2007
            } elseif ($param == 'US') {
                $pattern = "/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4} [012]?[0-9]:[0-5]?[0-9](\:[0-5]?[0-9])?$/"; // 01/31/2007
            } else {
                $this->castMsg(2, "Date validation param '$param' not recognized. Only BR / US are accept.");
                return false;
            }
            return preg_match($pattern, $fv) ? false : true;
        } elseif ($strVal == 'cpf' && $fv) {
            $RecebeCPF = preg_replace("/[^0-9]/", "", $fv);
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
        } elseif ($strVal == 'cnpj' && $fv) {
            $cnpj = preg_replace("/[^0-9]/", "", $fv);
            if (strlen($cnpj) == 15 && $cnpj[0] == '0') {
                $cnpj = substr($cnpj, 1);
                if ($aliasName) {
                    $this->setValue($aliasName, $cnpj);
                }
            }
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
        } elseif ($strVal == 'cpf_cnpj' && $fv) {
            // True on error.
            $tryCnpj = self::validateString($fv, $param, 'cnpj', $aliasName);
            $tryCpf = self::validateString($fv, $param, 'cpf', $aliasName);
            return ($tryCnpj && $tryCpf);
        }

        // False significa que passou na validação.
        return false;
    }

    function validateAlias($aliasName, $strVal, $param = false)
    { # If error, return string. False otherwise
        // Exception: 'callback'
        if ($strVal == 'callback') {
            // This may have parameter '---', when aliasName is set to 'false'.
            // When this happens, just pass the $value parameter as empty!
            $fv = ($aliasName == '---') ?
                '' :
                $this->fieldValues[$aliasName];

            return call_user_func($param, $this, $fv);
        }

        // Exception: 'unique'
        if ($strVal == 'unique') {
            // This is handled by the validateUnique() method.
            // Always return false (no error).
            return false;
        }

        // Helps user to find errors in their classes - User Friendly error messages.
        if (!array_key_exists($aliasName, $this->fieldValues)) {
            $this->castMsg(1,
                "There's a validation for the alias '$aliasName', but this isn't defined. Trying validation '$strVal'.");
            return false;
        }
        $fv = &$this->fieldValues[$aliasName];

        // Don't validate when *RAW* or not saveable field
        if ($this->fieldProps[$aliasName]['raw'] || !$this->fieldProps[$aliasName]['saveThis']) {
            return false;
        }

        return $this->validateString($fv, $param, $strVal, $aliasName) ? $strVal : false;
    }

    // User error handling
    function addError($groupName, $errorStr)
    {
        if (!$groupName) {
            $groupName = '---';
        }
        $this->errorList[$groupName][] = $errorStr;
        $this->castMsg("Adding error ($groupName): $errorStr");
    }

    function listErrors($groupName = false)
    {
        if ($groupName && $groupName !== true) {
            return isset($this->errorList[$groupName]) ? $this->errorList[$groupName] : false;
        }

        if (!sizeof($this->errorList)) {
            return false;
        }

        $lista = array();
        if (isset($this->errorList['---'])) {
            $lista['---'] = $this->errorList['---'];
        }

        foreach ($this->fieldProps as $aliasName => $valProps) {
            if (isset($this->errorList[$aliasName])) {
                $lista[$aliasName] = $this->errorList[$aliasName];
            }
        }

        if (sizeof($this->errorList)) {
            $lista = array_merge($this->errorList, $lista);
        }

        if ($groupName) {
            foreach ($lista as $fieldName => $errorL) {
                foreach ($errorL as $errorStr) {
                    $errors[] = $errorStr;
                }
            }
            $errors = array_unique($errors);
            return $errors;
        }

        return $lista;
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

    // Private methods
    function formatToQuery($value, $aliasName = false)
    {
        if ($aliasName && isset($this->fieldProps[$aliasName]['raw']) && $this->fieldProps[$aliasName]['raw']) {
            return $value;
        }

        if ($aliasName) {
            $value = $this->modifyAlias($value, $aliasName, 1);
        }

        if (is_array($value)) {
            trigger_error("dDbRow: Returning empty from '$aliasName', because the value is an array (" . print_r($value,
                    true) . ").", E_USER_NOTICE);
            return "NULL";
        }

        if ($aliasName && $this->fieldProps[$aliasName]['isGIS']) {
            if (trim($value)) {
                return "GeomFromText('" . addslashes($value) . "')";
            } else {
                return "NULL";
            }
        }

        if (!isset($value) || $value === false) {
            return 'NULL';
        }
        if (is_float($value)) {
            return $value;
        }
        return "'" . addslashes($value) . "'";
    }

    function castMsg($level, $string = false)
    { // (0=OK  1=Warn  2=Critical  3=Debug)
        if (isset($_COOKIE['dDbRow-debug']) && $_COOKIE['dDbRow-debug'] == date('d')) {
            $this->debug = 1;
        }

        if (!$string) {
            $string = $level;
            $level = 3;
        }
        if ((!$this->debug && $level == 3) || !$string) {
            return;
        }

        switch ($level) {
            case 0:
                $cor = '#080';
                break; // Success
            case 1:
                $cor = '#838';
                break; // Warning
            case 2:
                $cor = '#D00';
                break; // Error
            case 3:
                $cor = '#686; font-style: italic';
                break; // Debug
        }
        $backtrace = debug_backtrace();

        $funcs = array($backtrace[1]['function']);
        for ($x = 2; $x < sizeof($backtrace); $x++) {
            if (@$backtrace[$x]['class'] == 'dDbRow') {
                $funcs = array_merge(array($backtrace[$x]['function']), $funcs);
            }
        }

        $string = implode("->", $funcs) . "(): $string";
        echo "<b style='color: $cor; font: 11px Verdana'>$string</b><br />\r\n";
        //echo "<!--"; print_r($backtrace); echo "-->";
    }

    function __dump($maxDepth = false, $_printedUids = array())
    {
        $vars = $this;
        $className = get_class($vars);
        $dump = get_object_vars($vars);
        $dump['fieldValues'] = '**printed**';
        $dump['fieldOriginal'] = '**printed**';
        $dump['fieldVirtuals'] = '**printed**';
        $dump['fieldProps'] = '**printed**';
        $dump['validations'] = '**printed**';
        $dump['modifiers'] = '**printed**';

        echo "<table style='background: #CCFFCC; border: 1px solid #080; box-shadow: -1px 1px 3px #888888;' cellspacing='0'>";
        echo "	<tr valign='top'>";
        echo "		<td colspan='2' bgcolor='#99FF99' style='position: relative'>";
        if ($vars->getPrimaryValue()) {
            echo "			{$className}::load({$vars->getPrimaryValue()}) <small>dDbRow</small>";
        } else {
            echo "			new {$className}\r\n";
        }
        echo "		</td>";
        echo "	</tr>";
        echo "	<tr>";
        echo "		<td valign='top'>";

        // fieldValues
        echo "<table border='1' style='border-collapse: collapse' cellpadding='1'>";
        { // fieldNames
            echo "	<tr>";
            echo "<td></td>";
            foreach ($vars->fieldValues as $fieldName => $fieldValue) {
                echo "<td title=\"" . print_r($vars->fieldProps[$fieldName], true) . "\">{$fieldName}</td>";
            }
            echo "	</tr>";
        }
        { // getValue
            echo "	<tr valign='top'>";
            echo "<td>getValue()</td>";
            foreach ($vars->fieldValues as $fieldName => $fieldValue) {
                echo "<td>";
                dDbRow3::dump($vars->v($fieldName), $maxDepth - 1);
                echo "</td>";
            }
            echo "	</tr>";
        }
        { // getOriginal
            # if(@$vars->extra['fieldOriginal']){
            echo "	<tr valign='top' style='background: #DFD'>";
            echo "<td>getOriginal()</td>";
            foreach ($vars->fieldValues as $fieldName => $fieldValue) {
                if ($vars->fieldOriginal[$fieldName]['isModified']) {
                    echo "<td style='border: 1px solid #00F; background: #9F9'>";
                    dDbRow3::dump($vars->getOriginal($fieldName), $maxDepth - 1);
                    echo "</td>";
                } else {
                    echo "<td style='border: 0' align='center' valign='center'>";
                    echo "<span style='display: inline-block; color: #000; font-weight: bold'>=</span>";
                    echo "</td>";
                }
            }
            echo "	</tr>";
            # }
        }
        echo "</table>";

        // fieldVirtuals
        if ($vars->fieldVirtuals) {
            echo "<table border='1' style='border-collapse: collapse; margin-top: 3px' cellpadding='2' bgcolor='#CCFFFF'>";
            { // fieldNames
                echo "	<tr>";
                echo "<td></td>";
                foreach ($vars->fieldVirtuals as $fieldName => $fieldValue) {
                    echo "<td>{$fieldName}</td>";
                }
                echo "	</tr>";
            }
            { // getVirtual
                echo "	<tr valign='top'>";
                echo "<td>getVirtual()</td>";
                foreach ($vars->fieldVirtuals as $fieldName => $fieldValue) {
                    echo "<td>";
                    dDbRow3::dump($vars->getVirtual($fieldName), $maxDepth - 1);
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
        dDbRow3::dump($dump, $maxDepth - 1);
        echo "			</div>";
        echo "		</td>";

        echo "	</tr>";
        echo "</table>";
    }

    // Override methods
    function __destruct()
    {
        return true;
    }
    /** EOF: dDbRow **/
}


/*
	HTMLCleaner 1.0 (c) 2007-2008 Lucian Sabo
	HTML source code cleaner (great help for cleaning MS Word content)
	luciansabo@gmail.com

	Licenced under Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported (http://creativecommons.org/licenses/by-nc-sa/3.0/)
	for personal, non-commercial use

	http://www.phpclasses.org/browse/package/4225.html

$cleaner = new HTMLCleaner();
$cleaner->html = $word;
$cleanHTML = $cleaner->cleanUp('latin1');
--------*/
define("TAG_WHITELIST", 0);
define("TAG_BLACKLIST", 1);
define("ATTRIB_WHITELIST", 0);
define("ATTRIB_BLACKLIST", 1);

class HTMLCleaner
{
    var $Options;
    var $Tag_whitelist = '<table><tbody><thead><tfoot><tr><th><td><colgroup><col>
<p><br><hr><blockquote>
<b><i><u><sub><sup><strong><em><tt><var>
<code><xmp><cite><pre><abbr><acronym><address><samp>
<fieldset><legend>
<a><img>
<h1><h2><h3><h4><h4><h5><h6>
<ul><ol><li><dl><dt>
<frame><frameset>
<form><input><select><option><optgroup><button><textarea><font>';
    var $Attrib_blacklist = 'id|on[\w]+';
    var $CleanUpTags = array(
        'a',
        'span',
        'b',
        'i',
        'u',
        'strong',
        'em',
        'big',
        'small',
        'tt',
        'var',
        'code',
        'xmp',
        'cite',
        'pre',
        'abbr',
        'acronym',
        'address',
        'q',
        'samp',
        'sub',
        'sup'
    );//array of inline tags that can be merged
    var $Style_whitelist = array(
        'color',
        'float',
        'margin',
        'clear',
        'background-color',
        'background',
        'text-align',
        'text-indent'
    );
    var $TidyConfig;
    var $Encoding = 'latin1';
    var $Version = '1.0 RC6';

    function HTMLCleaner()
    {
        $this->Options = array(
            'RemoveStyles' => true,    // new: Removes style attribute
            'RemoveClasses' => true,    // new: Removes only class attribute
            'IsWord' => true,    //Microsoft Word flag - specific operations may occur
            'UseTidy' => false,   //uses the tidy engine also to cleanup the source (reccomended)
            'TidyBefore' => false,    //apply Tidy first (not reccomended as tidy messes up sometimes legitimate spaces
            'CleaningMethod' => array(TAG_WHITELIST, ATTRIB_BLACKLIST),    //cleaning methods
            'OutputXHTML' => false,   //converts to XHTML by using TIDY.
            'FillEmptyTableCells' => true,    //fills empty cells with non-breaking spaces
            'DropEmptyParas' => true,    //drops empty paragraphs
            'Optimize' => true,    //Optimize code - merge tags
            'Compress' => false
        );    //trims all spaces (line breaks, tabs) between tags and between words.

        // Specify TIDY configuration
        $this->TidyConfig = array(
            'indent' => true, /*a bit slow*/
            'output-xhtml' => true, //Outputs the data in XHTML format
            'word-2000' => false, //Removes all proprietary data when an MS Word document has been saved as HTML
            //'clean'		=> true, /*too slow*/
            'drop-proprietary-attributes' => true, //Removes all attributes that are not part of a web standard
            'hide-comments' => true, //Strips all comments
            'preserve-entities' => true,    // preserve the well-formed entitites as found in the input
            'quote-ampersand' => true,//output unadorned & characters as &amp;.
            'show-body-only' => true,
            'wrap' => 200
        ); //Sets the number of characters allowed before a line is soft-wrapped
    }

    function RemoveBlacklistedAttributes($attribs)
    {
        if ($attribs != 'style') {
            //the attribute _must_ have a line-break or a space before
            $this->html = preg_replace('/[\s]+(' . $attribs . ')=[\s]*("[^"]*"|\'[^\']*\')/i', "",
                $this->html); //double and single quoted
            $this->html = preg_replace('/[\s]+(' . $attribs . ')=[\s]*[^ |^>]*/i', "", $this->html);    //not quoted
        } else {
            $this->html = preg_replace_callback('/[\s]+(' . $attribs . ')=[\s]*("[^"]*"|\'[^\']*\')/i',
                array($this, 'ParseStyleTag'), $this->html); //double and single quoted
        }
    }

    function ParseStyleTag($dump)
    {
        if ($dump[2][0] == "'" || $dump[2][0] == '"') {
            $dump[2] = substr($dump[2], 1, -1);
        }

        $nstyles = array();
        $styles = explode(";", $dump[2]);
        foreach ($styles as $st) {
            $tmp = explode(":", $st, 2);
            $key = strtolower(trim($tmp[0]));
            if (in_array($key, $this->Style_whitelist)) {
                $nstyles[] = "{$key}: " . trim($tmp[1]);
            }
        }

        if ($nstyles) {
            return " style=\"" . implode("; ", $nstyles) . "\"";
        }
        return "";
    }

    function TidyClean()
    {
        if (!class_exists('tidy')) {
            if (function_exists('tidy_parse_string')) {
                //use procedural style for compatibility with PHP 4.3
                tidy_set_encoding($this->Encoding);
                foreach ($this->TidyConfig as $key => $value) {
                    tidy_setopt($key, $value);
                }
                tidy_parse_string($this->html);
                tidy_clean_repair();
                $this->html = tidy_get_output();
            } else {
                print("<b>No tidy support. Please enable it in your php.ini.\r\nOnly basic cleaning is beeing applied\r\n</b>");
            }
        } else {
            //PHP 5 only !!!
            $tidy = new tidy;
            $tidy->parseString($this->html, $this->TidyConfig, $this->Encoding);
            $tidy->cleanRepair();
            $this->html = $tidy;
        }
    }

    function cleanUp($encoding = 'latin1')
    {
        if (!empty($encoding)) {
            $this->Encoding = $encoding;
        }

        if ($this->Options['IsWord']) {
            $this->TidyConfig['word-2000'] = true;
            $this->TidyConfig['drop-proprietary-attributes'] = true;
        } else {
            $this->TidyConfig['word-2000'] = false;
        }

        if ($this->Options['OutputXHTML']) {
            $this->Options['UseTidy'] = true;
            $this->TidyConfig['output-xhtml'] = true;
        } else {
            $this->TidyConfig['output-xhtml'] = false;
        }

        if ($this->Options['UseTidy']) {
            if ($this->Options['TidyBefore']) {
                $this->TidyClean();
            }
        }

        // remove escape slashes
        $this->html = stripslashes($this->html);

        if ($this->Options['CleaningMethod'][0] == TAG_WHITELIST) {
            // trim everything before the body tag right away, leaving possibility for body attributes
            if (preg_match("/<body/i", "$this->html")) {
                $this->html = stristr($this->html, "<body");
            }

            // strip tags, still leaving attributes, second variable is allowable tags
            $this->html = strip_tags($this->html, $this->Tag_whitelist);
        }

        if ($this->Options['RemoveStyles']) {
            //remove class and style definitions from tidied result
            $this->RemoveBlacklistedAttributes('style');
        }

        if ($this->Options['RemoveClasses']) {
            //remove class and style definitions from tidied result
            $this->RemoveBlacklistedAttributes('class');
        }

        if ($this->Options['IsWord']) {
            $this->RemoveBlacklistedAttributes('lang|[ovwxp]:\w+');
        }

        if ($this->Options['CleaningMethod'][1] == ATTRIB_BLACKLIST) {
            if (!empty($this->Attrib_blacklist)) {
                $this->RemoveBlacklistedAttributes($this->Attrib_blacklist);
            }
        }

        if ($this->Options['Optimize']) {
            //Optimize until nothing can be done
            $count = 0;
            $repl = 1;
            while ($repl) {
                $repl = 0;
                foreach ($this->CleanUpTags as $tag) {
                    $this->html = preg_replace("/(<$tag>|<$tag [^>]*>)[\s]*([(&nbsp;)(<br ?\/?>)]*)[\s]*<\/($tag)>/i",
                        "\\2", $this->html, -1, $count); //strip empty inline tags (must be on top of merge inline tags)
                    $repl += $count;
                    $this->html = preg_replace("/<\/($tag)[^>]*>[\s]*([(&nbsp;)]*)[\s]*<($tag)>/i", "\\2", $this->html,
                        -1, $count); //merge inline tags
                    $repl += $count;
                }
            }

            //drop empty paras after merging tags
            if ($this->Options['DropEmptyParas']) {
                $this->html = preg_replace('/<(p|h[1-6]{1})([^>]*)>[\s]*[(&nbsp;)]*[\s]*<\/(p|h[1-6]{1})>/i', "\r\n",
                    $this->html);
            }

            //trim extra spaces only if tidy is not set to indent
            if (!$this->TidyConfig['indent']) {
                $this->html = preg_replace('/([^<>])[\s]+([^<>])/i', "\\1 \\2", $this->html);//trim spaces between words
                $this->html = preg_replace('/[\n|\r|\r\n|][\n|\r|\r\n|]+</i', "<",
                    $this->html);    //trim excess spaces before tags
            }
        }

        //must be on top of	FillEmptyTableCells, because it can strip nbsp enclosed in paras
        if ($this->Options['DropEmptyParas'] && !$this->Options['Optimize']) {
            $this->html = preg_replace('/<(p|h[1-6]{1})([^>]*)>[\s]*[(&nbsp;)]*[\s]*<\/(p|h[1-6]{1})>/i', "\r\n",
                $this->html);
        }

        if ($this->Options['FillEmptyTableCells']) {
            $this->html = preg_replace("/<td([^>]*)>[\s]*<\/td>/i", "<td\\1>&nbsp;</td>", $this->html);
        }

        if ($this->Options['Compress']) {
            $this->html = preg_replace('/>[\s]+/', ">", $this->html);    //trim spaces after tags
            $this->html = preg_replace('/[\s]+<\//', "</", $this->html);    //trim spaces before end tags
            $this->html = preg_replace('/[\s]+</', "<", $this->html);    //trim spaces before tags
            $this->html = preg_replace('/([^<>])[\s]+([^<>])/', "\\1 \\2", $this->html);//trim spaces between words
        }

        if ($this->Options['UseTidy']) {
            if (!$this->Options['TidyBefore']) {
                $this->TidyClean();
            }
        }

        return $this->html;
    }
}
