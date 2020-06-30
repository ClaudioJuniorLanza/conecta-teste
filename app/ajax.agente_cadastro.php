<?php
require_once "config.php";

if(@$_POST['action'] == 'doSignup'){
	if(!is_array(@$_POST['theform'])){
		die("Invalid request. x1");
	}
	$rawForm = $_POST['theform'];
	// nome, telefone, email, senha, google_token, facebook_token, senha
	
	$theForm = Array(
		'facebook_id'=>'',
		'googleacc_id'=>'',
	);
	foreach($rawForm as $dados){
		if(!isset($dados['name']))  die("Invalid request. x1");
		if(!isset($dados['value'])) die("Invalid request. x2");
		
		$_key = $dados['name'];
		$_val = $dados['value'];
		$theForm[$_key] = $_val;
	}
	
	if(!isset($theForm['nome'])){     die("Invalid request. x6"); }
	if(!isset($theForm['telefone'])){ die("Invalid request. x7"); }
	if(!isset($theForm['email'])){    die("Invalid request. x8"); }
	
	
	// Como vamos fazer com a senha!?
	if(@$theForm['facebook_token']){
		$accessToken       = $theForm['facebook_token'];
		
		$fb = new dFacebook;
		$fb->setConfig($FacebookAppId, $FacebookAppSecret);
		$tokenData = $fb->debugToken($accessToken);
		$tokenData = $tokenData['data'];
		
		if(@$tokenData['app_id'] && @$tokenData['app_id'] != $FacebookAppId){
			$tokenData = Array(
				'error'   =>Array('code'=>999, 'message'=>'Token pertence a outro AppId'),
				'is_valid'=>false,
				'scopes'  =>Array(),
			);
		}
		else{
			$theForm['facebook_id'] = @$tokenData['user_id'];
		}
	}
	if(@$theForm['google_token']){
		$b = new dBrowser2;
		$b->go("https://www.googleapis.com/oauth2/v3/tokeninfo?id_token={$theForm['google_token']}");
		$response = json_decode($b->getBody(), true);
		
		if(@$response && @$response['aud'] && @$response['aud'] != $GoogleAppId){
			$response = Array(
				"error_description"=>"Token pertence a outro AppId",
			);
		}
		else{
			$theForm['googleacc_id'] = @$response['sub'];
		}
	}
	
	$usuarObj = new cUsuario;
	$usuarObj->v('nome',  $theForm['nome']);
	$usuarObj->v('fone1', $theForm['telefone']);
	$usuarObj->v('email', $theForm['email']);
	$usuarObj->v('senha', $theForm['senha']);
	$usuarObj->v('facebook_id',  $theForm['facebook_id']);
	$usuarObj->v('googleacc_id', $theForm['googleacc_id']);
	$usuarObj->v('agente_pending', '1');
	
	$userId = $usuarObj->save();
	if(!$userId){
		die(implode("<br />", $usuarObj->listErrors(true)));
	}
	
	$usuarObj->notifyNewUser($theForm['senha']);
	cUsuario::setLogged($usuarObj);
	die("OK");
}
