<?php
require_once "config.php";
require_once "template.php";

$succMsg = array();
$a = cRefVariedade::loadOrNew(@$_GET['id']);

if ($_POST) {
    $a->loadFromArray($_POST, 'id');
    // Load checkboxes here.
    if ($newid = $a->save()) {
        $succMsg[] = "A variedade foi salvo com sucesso!";
    }
}

dAL::layTop(array('bodyTitle' => (!$a->getPrimaryValue() ? 'Nov' : 'Editar ') . "a variedade"));
dAL::goBack(true, $a->isLoaded() ? "<a href='ref_variedades_edit.php'>Cadastrar outra</a>" : false);

dAL::boxes($a->listErrors(true), $succMsg);

dALForm::Start();
dALCampo::Start("Insira as informações:");
dALCampo::Text('Variedade', 'variedade');
dALCampo::Text('Cultura', 'cultura');
dALCampo::Text('Tecnologia', 'tecnologia');
dALCampo::Finish();
dALForm::Finish();

dAL::layBottom();

