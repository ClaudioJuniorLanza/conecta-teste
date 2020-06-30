<?php
require_once "config.php";

$usuarObj = cUsuario::isLogged();
if(!$usuarObj){
	die("Sua sessão expirou. Faça login novamente.");
}

if(@$_POST['action'] == 'searchVariedade'){
	$searchStr = trim(@$_POST['variedade']);
	$return    = Array();
	
	if(!$searchStr){
		$return['error'] = "Nada encontrado.";
	}
	else{
		$sqlWhere = "REPLACE(variedade, ' ', '') = REPLACE('".addslashes($searchStr)."', ' ', '')";
		$list     = cRefVariedade::multiLoad("where {$sqlWhere} limit 1");
		if(!sizeof($list)){
			$return['error'] = "Nada encontrado.";
		}
		elseif(sizeof($list) > 1){
			$return['error'] = "Mais de um resultado encontrado.";
		}
		else{
			$foundObj             = $list[0];
			$return['error']      = false;
			$return['id']         = $foundObj->v('id');
			$return['variedade']  = $foundObj->v('variedade');
			$return['cultura']    = $foundObj->v('cultura');
			$return['tecnologia'] = $foundObj->v('tecnologia');
		}
	}
	
	die("JSON=".json_encode($return, true));
}
if(@$_POST['action'] == 'doCreate'){
	$rawForm = @$_POST['theform'];
	$theForm = Array();
	foreach($rawForm as $dados){
		if(!isset($dados['name']))  die("Invalid request. x1");
		if(!isset($dados['value'])) die("Invalid request. x2");
		$_key = $dados['name'];
		$_val = $dados['value'];
		$theForm[$_key] = $_val;
	}
	
	$varieObj = cRefVariedade::load(@$_POST['varie_id']) or die("Variedade informada não foi localizada.");
	if($varieObj->v('variedade') != $theForm['inputCultivar']){
		die("Formulário inconsistente - Você selecionou uma variedade, e o sistema reconheceu outra. Tente novamente.");
	}
	
	$setCodigo = substr(time(), -6);
	while($db->singleResult("select 1 from c_anuncios where codigo='{$setCodigo}'")){
		$setCodigo++;
	}
	
	$anuncObj = new cAnuncio;
	$anuncObj->loadArray($theForm);
	$anuncObj->v('usuar_id',      $usuarObj->v('id'));
	$anuncObj->v('codigo',        $setCodigo);
	$anuncObj->v('status',        'Em Análise');
	$anuncObj->v('varie_id',       @$_POST['varie_id']);
	$anuncObj->v('troca_varie_id', @$_POST['troca_id']);
	$anuncObj->v('data_anuncio',  date('d/m/Y H:i:s'));
	$anuncObj->v('negocio',       ucfirst(@$theForm['negocio']));
	
	if(!$anuncObj->save()){
		die(implode("<br />", $anuncObj->listErrors(true)));
	}
	
	$anuncObj->afterCreate();
	
	// dHelper2::dump($theForm);
	// dHelper2::dump($anuncObj);
	
	die("OK");
}

die("Nothing to do.");