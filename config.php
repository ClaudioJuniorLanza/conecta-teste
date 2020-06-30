<?php
/**
	Site padrão IMAGINACOM (arquivo compartilhado)
	---------------------------------------------------------------
**/
$_BaseDir       = dirname(__FILE__);
$_EnableSetup   = isset($_EnableSetup )  ?$_EnableSetup   :true ;   // Ativar o redirecionamento para o setup se o sistema não estiver configurado.
$_EnableSession = isset($_EnableSession )?$_EnableSession :true ;   // Ativar sessões (session_init)
$_EnableDB      = isset($_EnableDB      )?$_EnableDB      :true ;   // Cria e pré-configura o objeto dDatabase
$_EnableAudit   = isset($_EnableAudit   )?$_EnableAudit   :false;   // Permite a alimentação automática na auditoria de acessos
$_ForceNotGPC   = isset($_ForceNotGPC   )?$_ForceNotGPC   :true ;   // Garante que a diretiva magic_quotes está desligada
$_EncodeOutput  = isset($_EncodeOutput  )?$_EncodeOutput  :false;   // Permite ativar a compressão de conteúdo (ob_gzhandler)
$_SetCharset    = isset($_SetCharset    )?$_SetCharset    :'utf-8'; // Define qual o charset padrão enviado no content-type
$_NeedNotSSL    = isset($_NeedNotSSL    )?$_NeedNotSSL    :true;   // Se o acesso for via SSL, força redirecionamento para não-SSL
$_NeedSSL       = isset($_NeedSSL       )?$_NeedSSL       :false;    // Se o acesso for via não-SSL, força redirecionamento para SSL
$_DefaultSSL    = isset($_DefaultSSL    )?$_DefaultSSL    :false;   // Utilizado quando !_NeedSSL&&!_NeedNotSSL. Opções: FALSE|'ssl'|'not-ssl'
$_EnableHSTS    = isset($_EnableHSTS    )?$_EnableHSTS    :false;   // Informa os navegadores que o site só pode ser acessado por SSL
$_ForceDomain   = isset($_ForceDomain   )?$_ForceDomain   :true ;   // Garante que o acesso está sendo realizado no domínio principal
$_BaseVersion   = 1.7;



// Configurações default:
$FacebookAppId     = "459103421467338";
$FacebookAppSecret = "5ee7d4ec8bac9e9ad70f0569202931cc";
$GoogleAppId       = "818907870371-q7oq38k8tvdg9hmhf491aililtbejgv0.apps.googleusercontent.com";



// Procedimentos padrão, antes de qualquer código do projeto.
// ------------------------------------------------------------------------
//date_default_timezone_set('America/Sao_Paulo'); removido pois o horário do servidor já está correto
mb_internal_encoding("UTF-8");
setlocale      (LC_CTYPE, "pt_BR", "portuguese");
setlocale      (LC_TIME,  "pt_BR", "portuguese");
error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
spl_autoload_register(function($class) use ($_BaseDir){
	$tryFiles = Array();
	if($class == 'Mobile_Detect'){
		$tryFiles[] = "classes/Mobile_Detect.php";
		$tryFiles[] = "classes/core/Mobile_Detect.php";
	}
	elseif($class == 'dDatabase' || substr($class, 0, 3) == 'dDb'){
		$tryFiles[] = "classes/core/dDb/{$class}.inc.php";
	}
	else{
		$tryFiles[] = "classes/core/{$class}.inc.php";
		$tryFiles[] = "classes/core/class_{$class}.php";
		$tryFiles[] = "classes/{$class}.inc.php";
		$tryFiles[] = "classes/class_{$class}.php";
	}
	
	foreach($tryFiles as $tryFile){
		if(file_exists("{$_BaseDir}/{$tryFile}")){
			require_once "{$_BaseDir}/{$tryFile}";
			return true;
		}
	}
});

// Efetivando as configurações explicitadas no início do config.
// ------------------------------------------------------------------------
if($_SetCharset){
	header("Content-Type: text/html; charset={$_SetCharset}");
}
if($_EnableHSTS){
	// Força o sistema a jamais receber acessos não-ssl.
	// Leia mais: https://support.google.com/webmasters/answer/6073543
	header("Strict-Transport-Security: max-age=604800");
}
if($_EnableSetup){
	// Tenta carregar as configurações globais do sistema.
	// Se não conseguir, redirecione para o setup.
	if(!dSystem::getGlobal()){
		dHelper2::redirectTo("admin/setup.php");
		die;
	}
	
	// Força o error_reporting para mostrar DEPRECATED e STRICT quando trabalhando localmente.
	// Isso forçará o desenvolvedor a analisar os erros e evoluir o sistema.
	if(dSystem::getGlobal('localHosted')){
		error_reporting(E_ALL | E_STRICT);
	}
}
if(($_NeedNotSSL || $_NeedSSL || $_DefaultSSL || $_ForceDomain) && dSystem::getGlobal() && !dSystem::getGlobal('localHosted')){
	if($_DefaultSSL == 'ssl' && !$_NeedNotSSL){
		$_NeedSSL = true;
	}
	elseif($_DefaultSSL == 'not-ssl' && !$_NeedSSL){
		$_NeedNotSSL = true;
	}
	
	call_user_func(function() use ($_NeedNotSSL, $_NeedSSL, $_ForceDomain){
		if(!isset($_SERVER['HTTP_HOST'])){
			return false;
		}
		
		$redirectTo = false;
		$baseUrl    = dSystem::getGlobal('baseUrl');
	//	$baseUrlSSL = dSystem::getGlobal('baseUrlSSL');
$baseUrlSSL = false;
		if(!$baseUrlSSL){
			// Não vou redirecionar.
			return false;
		}
		
		$_IsSSL     = $baseUrlSSL?dSystem::getEnv('isSSL'):false;
		if( $_IsSSL && $_NeedNotSSL){
			// É SSL, mas não deveria.
			$redirectTo = $baseUrl.substr($_SERVER['REQUEST_URI'], 1);
		}
		if(!$_IsSSL && $_NeedSSL){
			// Não é SSL, mas deveria.
			$redirectTo = $baseUrlSSL.substr($_SERVER['REQUEST_URI'], 1);
		}
		
		if(!$redirectTo && $_ForceDomain){
			$_curr = ($_IsSSL?"https":"http")."://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
			$_base = ($_IsSSL?$baseUrlSSL:$baseUrl);
			if(0 !== stripos($_curr, $_base)){
				$_cu = parse_url($_curr);
				$_ba = parse_url($_base);
				
				if(0 === stripos($_cu['path'], $_ba['path'])){
					$_cu['host'] = $_ba['host'];
					$redirectTo  = dHelper2::unparseUrl($_cu);
				}
				else{
					dSystem::log('CRITICAL', "Não é possível direcionar usuário para site oficial",
						"A URL acessada pelo cliente difere da URL informada como sendo a base do site.\n".
						"URL acessada:  {$_curr}\n".
						"Base esperada: {$_base}\n".
						"\n".
						"Path acessado: {$_cu['path']}\n".
						"Path base:     {$_ba['path']}\n".
						"\n".
						"Para o visitante, vamos apenas ignorar o redirecionamento.\n".
						"Mas isso deve ser verificado.".
						"\n".
						"(Resultado da diretiva \$_ForceDomain em config.php)"
					);
				}
			}
		}
		
		if($redirectTo){
			if(sizeof($_POST)){
				dSystem::notifyAdmin('HIGH', "Inconsistência no redirecionamento",
					"O sistema identificou a necessidade de um redirecionamento ".
					"diretamente no config.php. As variáveis atuais são:\r\n".
					"- _SERVER[HOST]={$_SERVER['HTTP_HOST']}\r\n".
					"- _isSSL=".($_IsSSL?"Sim":"Nao")."\r\n".
					"- _NeedSSL=".($_NeedSSL?"Sim":"Nao")."\r\n".
					"- _NeedNotSSL=".($_NeedNotSSL?"Sim":"Nao")."\r\n".
					"- _ForceDomain=".($_ForceDomain?"Sim":"Nao")."\r\n".
					"- Config:baseUrl={$baseUrl}\r\n".
					"- Config:baseUrlSSL={$baseUrlSSL}\r\n".
					"- redirectTo={$redirectTo}\r\n".
					"- _POST: ".print_r($_POST, true)."\r\n".
					"\r\n".
					"Estamos liberando o acesso sem redirecionar, para não perder a informação, ".
					"mas o caso deve ser analisado cuidadosamente. No futuro, este alerta ".
					"deve ser removido e o acesso totalmente bloqueado.\r\n".
					"\r\n",
					false
				);
			}
			else{
				dHelper2::redirectTo($redirectTo, "HTTP/1.1 301 Moved Permanently");
				die;
			}
		}
	});
}
if($_EnableDB){
	$db = dDatabase::start();
	$db->setCharset('utf8');
	$db->setConfig(
		dSystem::getGlobal('dbHost'),
		dSystem::getGlobal('dbUser'),
		dSystem::getGlobal('dbPassword'),
		dSystem::getGlobal('dbDatabase'),
		dSystem::getGlobal('dbEngine')
	);
	
	if(dSystem::getGlobal('localHosted')){
		dDatabase::onSqlErrorMailTo      (false);
		dDatabase::onSqlErrorShowOnScreen(true);
	}
	else{
		dDatabase::onSqlErrorMailTo      (dSystem::getGlobal('notifyEmail'));
		dDatabase::onSqlErrorShowOnScreen(false);
	}
}
if($_EnableSession){
	session_start();
}
if($_EnableAudit){
	dAuditAcesso::autoLog();
}
if($_EncodeOutput){
	ob_start("ob_gzhandler");
}
if($_ForceNotGPC){
	if(get_magic_quotes_gpc()){
		if(!function_exists("stripslashes_deep")){
			function stripslashes_deep($value){
				$value = is_array($value) ?
					array_map('stripslashes_deep', $value) :
					stripslashes($value);
				return $value;
			}
		}
		$_GET    = array_map('stripslashes_deep', $_GET);
		$_POST   = array_map('stripslashes_deep', $_POST);
		$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	}
}
if(file_exists("config/_maintenance")){
	// Desativa o acesso ao site, considerando que estamos em manutenção.
	if(!dUsuario::isLogged() || !dUsuario::isLogged()->checkPerms('MASTER_ACCOUNT')){
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 300');
		echo "<div style='padding: 25px; background: #DDD; font: 12px Arial'>";
		echo "<b>Atualização em andamento!</b><br />";
		echo "<br />";
		echo "Sentimos muito pelo incômodo, mas tentaremos ser breves.<br />";
		echo "Estamos aplicando algumas atualizações, e em instantes estaremos de volta.<br />";
		echo "<br />";
		echo "<a href='{$_SERVER['PHP_SELF']}'>Clique aqui para tentar acessar novamente.</a><br />";
		echo "<!--";
		echo "<br />";
		echo "- Última tentativa às: ".date('H\hi\ms\s')."<br />";
		echo "-->";
		echo "</div>";
		echo "<br />";
		die;
	}
}


// Termina de configurar o ambiente e deixá-lo pronto para execução segura.
// ------------------------------------------------------------------------
if(ini_get('register_globals')){
	if(false){
		// Para permitir register_globals ON, altere para TRUE.
		// Não recomendável, mas por questões de compatibilidade...
		if (is_array($_REQUEST)) foreach(array_keys($_REQUEST) as $var_to_kill) unset($$var_to_kill);
		if (is_array($_SESSION)) foreach(array_keys($_SESSION) as $var_to_kill) unset($$var_to_kill);
		if (is_array($_SERVER))  foreach(array_keys($_SERVER)  as $var_to_kill) unset($$var_to_kill);
		unset($var_to_kill);
	}
	else{
		dSystem::notifyAdmin('HIGH', 'Servidor mal configurado!',
			"A diretiva register_globals está ativada no php.ini.\r\n".
			"Essa diretiva deve estar DESLIGADA, para sua segurança.\r\n".
			"-- Ou ativar a compatibilidade editando o config.php --\r\n",
			true
		);
		die;
	}
}
if(phpversion() < 5.3){
	dSystem::notifyAdmin('HIGH', "Servidor mal configurado",
		"  Este software só funciona a partir da versão 5.3 do PHP.\r\n".
		"  O servidor está executando a versão ".phpversion().".\r\n",
		true
	);
	die;
}

//setcookie("loadJs-debug","1");
