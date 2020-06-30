<?php
require_once "config.php";
require_once "template.php";

$succMsg = array();
$a = cRenasem::loadOrNew(@$_GET['id']);

if ($_POST) {
    $a->loadFromArray($_POST, 'id');
    // Load checkboxes here.
    if ($newid = $a->save()) {
        $succMsg[] = "O renasem foi salvo com sucesso!";
    }
}

dAL::layTop(array('bodyTitle' => (!$a->getPrimaryValue() ? 'Nov' : 'Editar ') . "o renasem"));
dAL::goBack(true, $a->isLoaded() ? "<a href='raw_renasem_edit.php'>Cadastrar outro</a>" : false);

dAL::boxes($a->listErrors(true), $succMsg);

dALForm::Start();
dALCampo::Start("Insira as informações:");
dALCampo::Text('Uf', 'uf');
dALCampo::Text('Cidade', 'cidade');
dALCampo::Text('Renasem', 'renasem');
dALCampo::Text('Valido status', 'valido_status');
dALCampo::Text('Valido expira', 'valido_expira');
dALCampo::Text('Atividade', 'atividade');
dALCampo::Text('CPF/CNPJ', 'cpf_cnpj');
dALCampo::Text('Nome', 'nome');
dALCampo::Text('Endereco', 'endereco');
dALCampo::Text('Cep', 'cep');
dALCampo::Finish();
dALForm::Finish();

dAL::layBottom();

