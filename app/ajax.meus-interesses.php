<?php
require_once "config.php";

if(isset($includeContext)){
	// Veio incluído via dHelper2::includePage.
	$_POST['action'] = 'displayList';
	$_POST['group']  = $params['group'];
	$_POST['type']   = $params['type'];
}

$usuarObj = cUsuario::isLogged() or die("Sua sessão expirou.<br />Faça login novamente.");

if(@$_POST['action'] == 'toggle'){
	$parts = explode("[", str_replace("]", "", @$_POST['setting']));
	if($parts[0] != 'interesses'){
		die("Interesse inválido: {$_POST['setting']}");
	}
	array_shift($parts);
	
	$group    = $parts[0];
	$curValue = @$_POST['value'];
	$setValue = @$_POST['setAs'];
	
	if($parts[0] != 'compra' && $parts[0] != 'venda' && $parts[0] != 'troca'){
		die("Grupo inválido: {$parts[0]}");
	}
	if(sizeof($parts) == 2){
		if($parts[1] == 'ativo'){
			$usuarObj->setInteresse($group, (bool)$setValue);
		}
		if($parts[1] == 'tudo'){
			$usuarObj->setInteresse($group, 'tudo', (bool)$setValue);
		}
	}
	if(sizeof($parts) == 3){
		if($parts[1] != 'regiao' && $parts[1] != 'embalagem'){
			die("Propriedade desconhecida: {$parts[1]}");
		}
		$usuarObj->setInteresse($group, $parts[1], $curValue, (bool)$setValue);
	}
	
	die("OK");
}
if(@$_POST['action'] == 'addOnly'){
	$group     = @$_POST['group'];     // 'comprar' ou 'vender'
	$interesse = @$_POST['interesse']; // String.
	if($group != 'comprar' && $group != 'vender'){
		die("Unknown: {$group}");
	}
	
	$useGroup = ($group=='comprar')?"compra":"venda";
	if($addedInfo = $usuarObj->addOnly($useGroup, $interesse, true)){
		// IF OK:
		// > type:  ID  | STR
		// > value: 123 | "xxxx"
		$varieObj = ($addedInfo['type'] == 'ID')?
			cRefVariedade::load($addedInfo['value']):
			false;
		
		echo "OK:";
		echo "{$addedInfo['type']}|{$addedInfo['value']}|";
		if($varieObj){
			echo "{$varieObj->v('cultura')}|{$varieObj->v('variedade')}";
		}
		else{
			echo "|";
			echo mb_convert_case($interesse, MB_CASE_TITLE, "UTF-8");
		}
		die;
	}
	else{
		die(implode("<br />", $usuarObj->listErrors(true)));
	}
}
if(@$_POST['action'] == 'deleteRow'){
	$group = @$_POST['group'];  // 'comprar' ou 'vender'
	$type  = @$_POST['type'];   // only | exceto
	$what  = @$_POST['what'];   // STR:string | ID:1234
	$what  = explode(":", $what, 2);
	if(sizeof($what) != 2){
		die("Invalid params (1).");
	}
	if($what[0] != 'STR' && $what[0] != 'ID'){
		die("Invalid params (2).");
	}
	
	$isString = ($what[0] == 'STR');
	$what     = $what[1];
	
	$useGroup = ($group=='comprar')?"compra":"venda";
	if($type == 'exceto'){
		$usuarObj->removeException($useGroup, $what, $isString);
	}
	elseif($type == 'only'){
		$usuarObj->removeOnly($useGroup, $what, $isString);
	}
	
	die("OK");
}

// "inc.anuncio-v2.php" e "anunc-v2.js":
if(@$_POST['action'] == 'notInterested'){
	$usuarObj = cUsuario::isLogged() or die("Sua sessão expirou");
	$group = @$_POST['group'];  // Venda | Compra
	$what  = @$_POST['what'];   // cultura, variedade, embalagem, regiao
	$value = @$_POST['target']; // soja | 123 | Saco de 50kg | PR Norte
	
	if($group != 'Venda' && $group != 'Compra'){
		die("Erro: Grupo inválido.");
	}
	if(!in_array($what, ['cultura', 'variedade', 'embalagem', 'regiao'])){
		die("Erro: Filtro inválido.");
	}
	if(!$value){
		die("Erro: Sem valor para filtrar.");
	}
	
	// Vamos inverter o grupo, pois o 'group" que vem é o tipo de anúncio,
	// que é o oposto do que o usuário autenticado está vendo. (ex: Anuncio de venda / Preferência de compra)
	$useGroup = ($group=='Venda')?'compra':'venda';
	
	if($what == 'cultura'){
		$usuarObj->addException($useGroup, $value, true);
	}
	if($what == 'variedade'){
		$varieId = intval($value);
		$usuarObj->addException($useGroup, $value, false);
	}
	if($what == 'embalagem' || $what == 'regiao'){
		$usuarObj->setInteresse($useGroup, $what, $value, false);
	}
	if(!$usuarObj->listErrors(true)){
		die("OK");
	}
	die(implode("<br />", $usuarObj->listErrors(true)));
}

dHelper2::dump($_POST);