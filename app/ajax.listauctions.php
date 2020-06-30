<?php
require_once "config.php";

$usuarObj = cUsuario::isLogged() or die("Sua sessão expirou. Acesse novamente.");

// V2:
if (@$_POST['action'] == 'markAsRead') {
    $anuncObj = cAnuncio::load($_POST['anuncCod'], 'codigo');
    if (!$anuncObj) {
        die("Invalido(x1).");
    }
    if ($anuncObj->v('status') != 'Ag. Propostas') {
        die("Invalido(x2)");
    }

    $anuncObj->markAsRead($usuarObj);

    header("Content-Type: application/json");
    die(json_encode(cAnuncio::loadCounts($usuarObj)));
}
if (@$_POST['action'] == 'notInterested') {
    $anuncObj = cAnuncio::load($_POST['anuncCod'], 'codigo');
    if (!$anuncObj) {
        die("Invalido(x1).");
    }
    if ($anuncObj->v('status') != 'Ag. Propostas') {
        die("Invalido(x2)");
    }

    $propoObj = $anuncObj->getPropoObj($usuarObj);
    if (!$propoObj->v('status')) {
        $propoObj->v('status', 'Sem Interesse')->save();
    }
    die("OK");
}
if (@$_POST['action'] == 'setProposta') {
    $anuncCod = trim($_POST['anuncCod']);
    $anuncObj = cAnuncio::load($anuncCod, 'codigo') or die("Anuncio não encontrado.");

    setcookie('last-regiao', @$_POST['regiao']);

    $isOK = $anuncObj->sendProposta($usuarObj, @$_POST['valor'], @$_POST['regiao'], @$_POST['justificativa']);
    if ($isOK) {
        die("OK");
    }
    die(implode("\r\n", $anuncObj->listErrors(true)));
}
if (@$_POST['action'] == 'encerrarAnuncio') {
    $anuncCod = trim($_POST['anuncCod']);
    $anuncObj = cAnuncio::load($anuncCod, 'codigo') or die("Anuncio não encontrado.");

    if ($anuncObj->v('usuar_id') != $usuarObj->v('id')) {
        die("Faça login com o usuário correto para poder excluir este anúncio.");
    }

    if ($anuncObj->userEncerrarAnuncio()) {
        die("OK");
    }
    die(implode("\r\n", $anuncObj->listErrors(true)));
}
if (@$_POST['action'] == 'acceptProposta') {
    $anuncObj = cAnuncio::load($_POST['anuncCod'], 'codigo') or die("Anúncio não encontrado.");
    if ($anuncObj->v('usuar_id') != $usuarObj->v('id')) {
        die("Você não tem permissão para gerenciar essa proposta.");
    }

    $propoObj = cProposta::load(@$_POST['propoId']) or die("Proposta não encontrada.");
    if ($propoObj->v('anunc_id') != $anuncObj->v('id')) {
        die("Proposta não relacionada com o anúncio.");
    }

    if ($anuncObj->setAceite($propoObj)) {
        die("OK");
    }
    die(implode("\r\n", $anuncObj->listErrors(true)));
}
if (@$_POST['action'] == 'rejectProposta') {
    $anuncObj = cAnuncio::load($_POST['anuncCod'], 'codigo') or die("Anúncio não encontrado.");
    if ($anuncObj->v('usuar_id') != $usuarObj->v('id')) {
        die("Você não tem permissão para gerenciar essa proposta.");
    }

    $propoObj = cProposta::load(@$_POST['propoId']) or die("Proposta não encontrada.");
    if ($propoObj->v('anunc_id') != $anuncObj->v('id')) {
        die("Proposta não relacionada com o anúncio.");
    }

    if ($anuncObj->setRejected($propoObj)) {
        die("OK");
    }
    die(implode("\r\n", $anuncObj->listErrors(true)));
}
dHelper2::dump($_POST);