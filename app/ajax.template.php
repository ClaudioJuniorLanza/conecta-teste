<?php
require_once "config.php";

if(@$_POST['acao'] == 'simularLogin'){
	// Tem que ser administrador!
	$usuarObj = dUsuario::isLogged() or die("Você precisa estar logado como administrador.");
	$clienObj = cUsuario::load($_POST['userId']) or die("Usuário não encontrado.");
	cUsuario::setLogged($clienObj);
	die("OK");
}