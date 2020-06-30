<?php
require_once "config.php";

$anuncObj = cAnuncio::load($_POST['id']);

// Ações referentes ao anúncio:
if (@$_POST['action'] == 'aceitarAnuncio') {
    if ($anuncObj->enableCotacao()) {
        die("OK");
    }
    die("Erro interno.");
}
if (@$_POST['action'] == 'encerrarAnuncio') {
    if ($anuncObj->encerrarAnuncio(true)) {
        die("OK");
    }
    die("Erro interno.");
}
if (@$_POST['action'] == 'reativarAnuncio') {
    if ($anuncObj->reativarAnuncio()) {
        die("OK");
    }
    die("Erro interno.");
}
if (@$_POST['action'] == 'rejeitarAnuncio') {
    if ($anuncObj->rejeitarAnuncio(trim(@$_POST['motivo']))) {
        die("OK");
    }
    die("Erro interno.");
}

// Ações referente a cada proposta:
if (@$_POST['propoId']) {
    $propoObj = cProposta::load($_POST['propoId']) or die("Proposta não encontrada.");
    if ($propoObj->v('anunc_id') != $anuncObj->v('id')) {
        die("Inconsistência - Proposta não pertence a este anúncio.");
    }

    if (@$_POST['action'] == 'propoEncaminhar') {
        // Administrador deu um OK para a proposta, vamos liberar e notificar o anunciante.
        if ($anuncObj->propostaRevisada($propoObj, intval(@$_POST['notify']) ? true : false)) {
            die("OK");
        }
        die("Erro interno.");
    }
    if (@$_POST['action'] == 'propoRejeitar') {
        // Administrador deu um OK para a proposta, vamos liberar e notificar o anunciante.
        $motivo = trim($_POST['motivo']);
        $allowRedo = ($_POST['allowRedo']);

        if ($anuncObj->propostaAdminRejeitou($propoObj, $motivo, $allowRedo)) {
            die("OK");
        }
        die("Erro interno.");
    }
    if (@$_POST['action'] == 'propoChangeStatus') {
        $newStatus = @$_POST['newStatus'];
        if ($anuncObj->propostaChangeStatus($propoObj, $newStatus)) {
            die("OK={$propoObj->v('status')}");
        }
        die("Erro interno.");
    }
}

dHelper2::dump($_POST);