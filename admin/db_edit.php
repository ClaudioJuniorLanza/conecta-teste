<?php
require_once "config.php";
require_once "template.php";

$dbDados = dDatabase::start('dbDados');
$dbDados->setCharset('utf8');
$dbDados->setConfig(
    dSystem::getGlobal('dbHost'),
    'conecta_sementes',
    'j(*Eg98hwe4uoa',
    'conectasementes',
    dSystem::getGlobal('dbEngine')
);

class dDbObject extends dDbRow3
{
    static function buildStruct()
    {
        global $dbDados, $tableName;

        self::structSet('db', $dbDados);
        self::autoStruct($tableName, [
            'allowInProducao' => true,
        ]);
    }
}

$tableName = @$_GET['t'];
$dropDbList = $dbDados->singleColumn("show tables");
if (!in_array($tableName, $dropDbList)) {
    die("Base de dados não encontrada/indisponível.");
}


$a = dDbObject::loadOrNew(@$_GET['id']);
$succMsg = array();

if ($_POST) {
    // Vamos processar o checkbox antes do loadArray para evitar
    // o conflito com agente_pending.
    $a->loadArray($_POST, 'id');

    if (isset($_POST['delete'])) {
        $a->delete();
        $a = new dDbObject;
        $succMsg[] = "Registro excluído com sucesso.";
    } else {
        if ($newid = $a->save()) {
            $succMsg[] = "Registro foi salvo com sucesso!";
        }
    }
}

dAL::layTop(array('bodyTitle' => "Gerenciando {$tableName}"));
if ($a->isLoaded()) {
    dAL::goBack(false, "<a href='db_edit.php?t={$tableName}'>Cadastrar novo item</a>");
}
dAL::boxes($a->listErrors(true), $succMsg);

dALForm::Start();
dALCampo::Start("Editar registro");
$allColumns = array_keys($a->export());
foreach ($allColumns as $item) {
    if ($item == 'id') {
        continue;
    }
    dALCampo::Text($item, $item);
}

if ($a->isLoaded()) {
    dALCampo::Misc("",
        dInput2::checkbox("name='delete'", false, " Excluir este registro? <small>(Ação é irreversível)</small>"));
}

dALCampo::Finish();
dALForm::Finish();

dAL::layBottom();

