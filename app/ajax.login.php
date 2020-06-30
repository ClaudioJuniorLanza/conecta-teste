<?php
require_once "config.php";

$adminObj = dUsuario::isLogged();
dAuditAcesso::blockPost(true);

if(@$_POST['provider'] && @$_POST['token']){
	$usuarObj = false;
	if(@$_POST['provider'] == 'facebook'){
		$accessToken = $_POST['token'];
		
		$fb = new dFacebook;
		$fb->setConfig($FacebookAppId, $FacebookAppSecret);
		$tokenData = $fb->debugToken($accessToken);
		$tokenData = $tokenData['data'];
		if(@$tokenData['app_id'] && @$tokenData['app_id'] != $FacebookAppId){
			die("Token de autenticação é inválido. Entre em contato com o suporte.");
		}
		else{
			$usuarObj = cUsuario::loginWithFacebook(@$tokenData['user_id']);
		}
	}
	elseif($_POST['provider'] == 'google'){
		$b = new dBrowser2;
		$b->go("https://www.googleapis.com/oauth2/v3/tokeninfo?id_token={$_POST['token']}");
		$response = json_decode($b->getBody(), true);
		
		if(@$response && @$response['aud'] && @$response['aud'] != $GoogleAppId){
			die("Token de autenticação é inválido. Entre em contato com o suporte.");
		}
		else{
			$usuarObj = cUsuario::loginWithGoogle(@$response['sub']);
		}

	}
	
	if($usuarObj){
		// Login bem sucedido!
		if(@$_POST['remember'] == '1'){
			cUsuario::rememberMeSaveCookie($usuarObj);
		}
		
		$goto = @$_SESSION['ClienteAfterLoginGoTo'];
		unset($_SESSION['ClienteAfterLoginGoTo']);
		
		if($goto){
			die("OK={$goto}");
		}
		else{
			die("OK=index.php");
		}
	}
	else{
		die("Nenhuma conta associada a sua rede social.<br /><a href='#' onclick=\"$('#btnChatOnline').click(); return false;\">Esqueceu sua senha?</a>");
	}
}
elseif(@$_POST['login'] && @$_POST['senha']){
	$usuarObj = false;
	if($adminObj && $_POST['senha'] == 'conecta'){
		// Tenta buscar por Email:
		$renasem  = dHelper2::formataRenasem($_POST['login']);
		$usuarObj = @cUsuario::load($renasem, 'renasem');
		if(!$usuarObj){
			$usuarObj = @cUsuario::load($_POST['login'], 'email');
		}
		if(!$usuarObj){
			$usuarObj = @cUsuario::load($_POST['login'], 'cpf_cnpj');
		}
		
		if($usuarObj){
			cUsuario::setLogged($usuarObj);
		}
	}
	if(!$usuarObj){
		$usuarObj = cUsuario::loginWithPassword($_POST['login'], $_POST['senha']);
	}
	
	if($usuarObj){
		// Login bem sucedido!
		if(@$_POST['remember'] == '1'){
			cUsuario::rememberMeSaveCookie($usuarObj);
		}
		
		$goto = @$_SESSION['ClienteAfterLoginGoTo'];
		unset($_SESSION['ClienteAfterLoginGoTo']);
		
		if($goto){
			die("OK={$goto}");
		}
		else{
			die("OK=index.php");
		}
	}
	else{
		die("Usuário/senha inválidos.<br /><a href='#' onclick=\"$('#btnChatOnline').click(); return false;\">Esqueceu sua senha?</a>");
	}
}
else{
	die("Informe seu e-mail ou renasem e senha antes de prosseguir.");
}