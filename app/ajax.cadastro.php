<?php
require_once "config.php";

if(@$_POST['action'] == 'checkRenasem'){
	// Formato do RENASEM:
	// --> UF/XXXXX/AAAA
	//
	// --> Como lidar com erros?
	// 1. Vamos remover todos os parâmetros
	// 2. Vamos validar os 4 ultimos digitos (AAAA)
	// 3. Vamos validar os 2 primeiros caracteres (UF)
	// 4. Vamos fazer PADDING-LEFT nos numeros restantes
	// 5. Vamos reformatar e buscar no banco de dados.
	//
	// Retorno esperado:
	// --> ret.renasem:  Valor adquirido do banco de dados.
	// --> ret.razao:    Razão social adquirida do banco de dados.
	
	$checkRenasem = dHelper2::formataRenasem($_POST['renasem']);
	$return = Array(
		'renasem'=>false,
		'razao'  =>false,
		'tipo'   =>false,
	);
	if($checkRenasem){
		$line = $db->singleLine("select renasem,nome,cpf_cnpj from c_renasens where renasem='".addslashes($checkRenasem)."' limit 1");
		if($line){
			$cpfCnpj = preg_replace("/[^0-9]/", "", $line['cpf_cnpj']);
			$tipo    = (strlen($cpfCnpj)<=12)?'pf':'pj';
			
			$return['renasem'] = $line['renasem'];
			$return['razao']   = $line['nome'];
			$return['tipo']    = $tipo;
		}
	}
	
	if(dSystem::getGlobal('localHosted')){
		echo "<b>Ajax response:</b><br />";
		dHelper2::dump($return);
	}
	echo "JSON=".json_encode($return);
}
if(@$_POST['action'] == 'doSignup'){
	if(!is_array(@$_POST['theform'])){
		die("Invalid request. x1");
	}
	$rawForm = $_POST['theform'];
	$theForm = Array();
	foreach($rawForm as $dados){
		if(!isset($dados['name']))  die("Invalid request. x1");
		if(!isset($dados['value'])) die("Invalid request. x2");
		$_key = $dados['name'];
		$_val = $dados['value'];
		$theForm[$_key] = $_val;
	}
	
	if(!@$_POST['renasem']){          die("Invalid request. x4"); }
	if(!isset($theForm['renasem'])){  die("Invalid request. x5"); }
	if(!isset($theForm['nome'])){     die("Invalid request. x6"); }
	if(!isset($theForm['telefone'])){ die("Invalid request. x7"); }
	if(!isset($theForm['email'])){    die("Invalid request. x8"); }
	if(!isset($theForm['senha'])){    die("Invalid request. x9"); }
	
	$envRenasem = trim($_POST['renasem']);
	
	if($envRenasem != $theForm['renasem']){
		dSystem::log('LOW', "Inconsistência: EnvRenasem é diferente de theForm[Renasem]",
			"Renasem definido pelo Javascript: {$envRenasem}\n".
			"Renasem informado no formulário: {$theForm['renasem']}\n".
			"Restante do formulário:\n".
			print_r($theForm, true)."\n\n".
			"Vou assumir o RENASEM do Javascript e continuar.<br />"
		);
		$theForm['renasem'] = $envRenasem;
	}
	
	if($theForm['renasem'] && cUsuario::load($theForm['renasem'], 'renasem')){
		die("Seu RENASEM já foi cadastrado.<br /><a href='login.php'>Faça login</a> ou <a href='#' onclick=\"$('#btnChatOnline').click(); return false;\">fale conosco</a>");
	}
	if($theForm['email'] && cUsuario::load($theForm['email'],   'email')){
		die("Seu e-mail já está sendo utilizado.<br /><a href='login.php'>Faça login</a> ou <a href='#' onclick=\"$('#btnChatOnline').click(); return false;\">fale conosco</a>");
	}
	
	$usuarObj = new cUsuario;
	if(!$usuarObj->importFromRenasem($envRenasem)){
		dSystem::notifyAdmin('MED', "Inconsistência: Sistema não encontrou o RENASEM para concluir o cadastro",
			"No ajax.cadastro, o usuário passou pelo Step1 (RENASEM válido), mas o sistema não ".
			"conseguiu importar o RENASEM para efetivar o cadastro.\n".
			"\n".
			"O usuário jamais poderia ir ao passo 2 sem um RENASEM, então isso é uma inconsistência.\n".
			"\n".
			"Renasem: {$envRenasem}\n".
			print_r($theForm, true)."\n".
			"\n".
			"Foi exibida uma mensagem de erro interno para o cliente."
		);
		die("ERR=Um erro interno ocorreu. Já fomos notificados, por favor, tente novamente amanhã.");
	}
	
	$usuarObj->v('responsavel_nome', $theForm['nome']);
	$usuarObj->v('fone1', $theForm['telefone']);
	$usuarObj->v('email', $theForm['email']);
	$usuarObj->v('senha', $theForm['senha']);
	$userId = $usuarObj->save();
	if(!$userId){
		die(implode("<br />", $usuarObj->listErrors(true)));
	}
	
	$usuarObj->notifyNewUser($theForm['senha']);
	cUsuario::setLogged($usuarObj);
	die("OK");
}



return;
