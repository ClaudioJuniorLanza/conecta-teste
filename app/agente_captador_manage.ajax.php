<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if(!$usuarObj->v('agente_captador')){
	// Sem permissões.
	dHelper2::redirectTo("agente_central.php");
	die;
}

if(@$_POST['action'] == 'delete'){
	$anuncObj = cAnuncio::load($_POST['anunc_id'], 'usuarObj') or die("Anuncio não encontrado");
	if(!$usuarObj->isAgenteOf($anuncObj->v('usuarObj'))){
		die("Sem permissões");
	}
	
	$anuncObj->setCancelado("Por determinação do agente responsável");
	die("OK");
}