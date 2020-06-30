<?php
/**
	dSystem:
		Classe responsável por centralizar os parâmetros do sistema:
		- Variáveis globais (senha do banco de dados, etc.)
		- Configurações do sistema (preferências, etc.).
		
	Modo de uso:
		dSystem::getGlobal()      - Retorna todas as variáveis globais do sistema
		dSystem::getGlobal(var)   - Retorna apenas a variável global desejada
		
	Entenda - Variáveis globals (getGlobal) - Estão armazenadas no /config/settings.ini.php.
		São variáveis vitais do sistema, tal como usuário e senha do banco de dados.
		Sem essas variáveis, o sistema não pode funcionar direito.. nem errado... não funciona.
	
	Features:
		(To do) Detectar se o arquivo settings.ini.php foi alterado pelo FTP, e recuperar um backup se necessário.
		Re-utilizar dados (carregar settings.ini.php e database apenas uma vez, e re-utilizar sempre)
	
	Relação completa das Variáveis Globais e Configurações do Sistema.
		Variáveis globais:
			localHosted: Ativa exibição de erros na tela, dentre outras depurações;
			notifyEmail: Erros no banco de dados e alertas do sistema serão encaminhados para este e-mail;
			hashkey:     Chave utilizada para criptografar as senhas do banco de dados;
			dbHost, dbUser, dbPassword, dbDatabase, dbEngine: Configurações do banco de dados.
**/
// [global]
// currentVersion=#.###
// localHosted=1 (ou ZERO)
// baseUrl=http://www.site.com.br/
// hashkey=xxxxxxx
// notifyEmail=xxxxxxx
// dbHost    =xxxxxxxxx
// dbUser    =xxxxxxxxxx
// dbPassword=xxxxxxxx
// dbDatabase=xxxxxxxx
// dbEngine  =mysql

class dSystem{
	static $globalKeys = 'currentVersion,localHosted,baseUrl,hashkey,notifyEmail,dbHost,dbUser,dbPassword,dbDatabase,dbEngine';
	static $globalFile = 'config/settings.ini.php';
	static $global = false;
	static $config = false;
	static $env    = false;
	
	static Function getGlobal($onlyKey=false){
		global $_BaseDir;
		
		if(self::$global){
			// Informação em cache, já foi carregada e validada corretamente.
			// Re-utilize a informação existente.
			return $onlyKey?
				isset(self::$global[$onlyKey])?self::$global[$onlyKey]:false:
				self::$global;
		}
		
		// Informação não está em cache, ainda não foi carregada.
		$globalFile = "{$_BaseDir}/".self::$globalFile;
		if(!file_exists($globalFile)){
			// Variáveis globais do sistema não existem.
			return false;
		}
		
		// Settings.ini.php:
		// -----------------
		// [global]
		// localHosted=1 (ou ZERO)
		// baseUrl=http://www.site.com.br/
		// baseUrlSSL=https://secure.site.com.br/
		// hashkey=xxxxxxx
		// notifyEmail=xxxxxxx
		// dbHost    =xxxxxxxxx
		// dbUser    =xxxxxxxxxx
		// dbPassword=xxxxxxxx
		// dbDatabase=xxxxxxxx
		// dbEngine  =mysql
		
		// Sobre o settings.ini.php:
		//   Tudo até o /* será ignorado.
		//   Os 4 caracteres finais devem ser * / ? >
		//   Tudo dentro disso será considerado um arquivo INI padrão
		$globalString = self::getGlobalString();
		$globalObject = parse_ini_string($globalString);
		$expectedKeys = explode(',', self::$globalKeys);
		$existingKeys = array_keys($globalObject);
		foreach($expectedKeys as $key){
			if(!in_array($key, $existingKeys)){
				// Erro crítico:
				//   Há variáveis faltando no arquivo de variáveis globais.
				echo "<b>Erro crítico:</b><br />\r\n";
				echo "- Um erro crítico ocorreu, desculpe-nos o incômodo.<br />\r\n";
				echo "<small>Se você é o desenvolvedor, veja o código fonte.</small><br />\r\n";
				echo "<hr size='1' />";
				echo "<!--\r\n";
				echo "  dSystem::getGlobal() - Falha ao carregar key={$key}.\r\n";
				echo "  Solução: Editar manualmente o arquivo 'settings.ini.php'.\r\n";
				echo "-->";
				die;
				$globalObject[$key] = '';
			}
			
			if(is_numeric($globalObject[$key]))
				$globalObject[$key] = floatval($globalObject[$key]);
		}
		
		// Gera o baseDir
		$globalObject['baseDir'] = $_BaseDir;
		
		// Salva as informações em cache (para não ter que trata o arquivo toda hora)
		self::$global = $globalObject;
		
		return $onlyKey?
			$globalObject[$onlyKey]:
			$globalObject;
	}
	static Function getGlobalString(){
		global $_BaseDir;
		$globalFile   = "{$_BaseDir}/".self::$globalFile;
		$globalString = trim(file_get_contents($globalFile));
		$globalString = substr($globalString, strpos($globalString, "/*")+2, -4);
		return $globalString;
	}
	static Function setGlobal($onlyKey, $value){
		$global          = self::getGlobal();
		$global[$onlyKey] = $value;
		unset($global['baseDir']);
		
		$newString  = "[global]\r\n";
		foreach($global as $key=>$value){
			$newString .= "{$key}={$value}\r\n";
		}
		return self::setGlobalString($newString);
	}
	static Function setGlobalString($iniString){
		global $_BaseDir;
		$globalFile = "{$_BaseDir}/".self::$globalFile;
		
		$outputStr    = "<?php\r\nheader('Location: ../');\r\ndie;\r\n/*\r\n";
		$outputStr   .= trim($iniString)."\r\n";
		$outputStr   .= "*/?>";
		
		if(!file_exists(dirname($globalFile))){
			mkdir(dirname($globalFile)) or die("A pasta ".dirname($globalFile)." não existe e não pode ser criada automaticamente.");
		}
		if(!file_put_contents($globalFile, $outputStr)){
			die("Não foi possível gravar o arquivo {$globalFile}, verifique se a pasta existe e se há permissões para isso.");
		}
		
		// Remove tudo que estava em cache, e recarrega.
		self::$global = false;
		self::getGlobal();
		
		return true;
	}
	
	static Function setDefaultEnv(){
		if(self::$env === false){
			$_sdParts    = explode(".", strtolower($_SERVER['HTTP_HOST']), 2);
			$_IsSSL      = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS'] == 'on'));
			$_SubDomain  = array_shift($_sdParts);
			self::$env = Array(
				'subDomain'=>($_SubDomain && $_SubDomain != 'www')?$_SubDomain:false,
				'isSSL'    =>$_IsSSL,
			);
		}
	}
	static Function setEnv($key, $value){
		// Variáveis ambientes conhecidas:
		// --> (bool) subDomain
		// --> (bool) isSSL
		self::setDefaultEnv();
		self::$env[$key] = $value;
		return true;
	}
	static Function getEnv($env){
		self::setDefaultEnv();
		return array_key_exists($env, self::$env)?
			self::$env[$env]:
			false;
	}
	
	static Function notifyAdmin($level, $subject, $message, $userDie=false){
		// Message deve ser em Plain/Text
		// Level deve ser:
		//   NOTICE, LOW, MED, HIGH, CRITICAL
		// 
		// A partir de Fev/2015, apenas alertas HIGH e CRITICAL são enviados por 
		// e-mail. Os demais ficarão apenas registrados no banco de dados (dSystemLog)
		$from    = "noreply@{$_SERVER['HTTP_HOST']}";
		
		$mailSubject = ($userDie)?
			"[CRITICAL] [{$level}] {$subject}":
			"[{$level}] {$subject}";
		
		// Por que utilizar debug_print_backtrace ao invés de debug_backtrace()?
		// --> Porque o var_dump e o debug_backtrace entram em loop infinito.
		ob_start();
		debug_print_backtrace();
		$backtrace = ob_get_clean();
		
		$usuarObj = class_exists('dUsuario')?
			dUsuario::isLogged():
			false;
		
		$messageToMail =
			"{$message}\r\n".
			"-------------------------------------\r\n".
			"O erro foi gerado ".
			($usuarObj?"pelo usuário logado id={$usuarObj->v('id')}":"por um visitante não-logado").", ".
			"com o seguinte código de sessão: ".session_id()."\r\n".
			"-------------------------------------\r\n".
			"Mensagem fatal exibida p/ usuário: ".($userDie?'Sim':'Não')."\r\n".
			"-------------------------------------\r\n".
			"Debug backtrace:\r\n".
			"{$backtrace}\r\n".
			"-------------------------------------\r\n".
			"Server:  ".print_r($_SERVER,  true)."\r\n".
			"Request: ".print_r($_REQUEST, true)."\r\n".
			"Data e hora no servidor: ".date('d/m/Y H:i:s');
		
		if(self::getGlobal('localHosted')){
			$tmpId = uniqid();
			echo "<div style='font: 12px Courier New; background: #FCC; padding: 10px; margin: 10px'>\r\n";
			echo "<b style='color: #F00'>Alerta do sistema:</b><br />\r\n";
			echo "From: {$from}<br />\r\n";
			echo "Assunto: [Level={$level}] {$subject}<br />\r\n";
			echo "<hr size='1' />\r\n";
			echo nl2br($message)."\r\n";
			echo "<small><a href='#' onclick=\"document.getElementById('{$tmpId}').style.display=(document.getElementById('{$tmpId}').style.display=='none')?'block':'none'; return false;\">(mais)</a></small><br />\r\n";
			echo "<pre id='{$tmpId}' style='display: none; border: 1px solid #888; padding: 5px; background: #FDD'>";
			debug_print_backtrace();
			echo "</pre>";
			echo "</div>";
		}
		elseif(self::getGlobal('notifyEmail') && in_array($level, Array('HIGH', 'CRITICAL'))){
			mail(self::getGlobal('notifyEmail'), $mailSubject, $messageToMail, "From: {$from}");
		}
		
		self::log($level, $subject, $messageToMail);
		
		if($userDie){
			echo "<b>Falha crítica</b><br />";
			echo "<hr size='1' />";
			echo "Uma falha interna ocorreu no sistema.<br/>\r\n";
			echo "Um administrador já foi notificado, e o problema será solucionado o quanto antes.<br/>\r\n";
			echo "<br />\r\n";
			echo "Pedimos desculpas pelo transtorno.\r\n";
			echo "Nos colocamos a disposição para atendimento através de nossa <a href='contato.php'>página de contato</a>.<br />\r\n";
			echo "<hr size='1' />\r\n";
			echo "Novamente, pedimos perdão e agradecemos a sua compreensão.<br />\r\n";
			echo "<a href='index.php'>Continuar navegando...</a><br />\r\n";
			echo "\r\n";
			echo "<!-- Erro gerado por dSystem::".(__LINE__)." -->";
			die;
		}
	}
	static Function log        ($level, $subject, $message=false){
		return dSystemLog::log($level, $subject, $message);
	}
	
	static Function isDebugger(){
		if(dSystem::getGlobal('localHosted')){
			return true;
		}
		return (dConfiguracao::getConfig('CORE/DEBUGGER_IP') && dConfiguracao::getConfig('CORE/DEBUGGER_IP') == $_SERVER['REMOTE_ADDR']);
	}
	static Function debugMsg  ($string, $dumpVars=NULL){
		if(self::isDebugger()){
			$tmpId = uniqid();
			echo "<div style='border: 1px solid #885; padding: 5px; margin-bottom: 5px; font: 11px Courier New; background: #FFF; color: #000'>";
			echo nl2br($string);
			echo " <small><a href='#' onclick=\"document.getElementById('{$tmpId}').style.display=(document.getElementById('{$tmpId}').style.display=='none')?'block':'none'; return false;\">(mais)</a></small><br />\r\n";
			echo "<pre id='{$tmpId}' style='display: none'>";
			if($dumpVars !== NULL){
				echo self::dumpVars($dumpVars);
			}
			echo "<hr size='1' />";
			debug_print_backtrace();
			echo "</pre>";
			echo "</div>";
		}
	}
	static Function dumpVars  ($vars, $deep=1, &$dumpedHash=false){
		$bgColors   = Array();
		$bgColors[] = Array('#EEEEEE', '#FFFFFF');
		$bgColors[] = Array('#CCCCCC', '#DDDDDD');
		$bgColors[] = Array('#AAAAAA', '#BBBBBB');
		$bgColors[] = Array('#BBBBAA', '#AAAA99');
		$bgColors[] = Array('#DDDDCC', '#CCCCBB');
		$bgColors[] = Array('#FFFFDD', '#EEEEDD');
		
		if(!$dumpedHash){
			$dumpedHash = Array();
		}
		
		$ret = "";
		if(is_array($vars)){
			if($vars){
				$myHash = md5(print_r($vars, true));
				if(in_array($myHash, $dumpedHash)){
					return "** <a href='#{$myHash}'>Recursion (click to go)</a> **";
				}
				$dumpedHash[] = $myHash;
				
				$ret .= "<a name='{$myHash}'></a>";
				$ret .= "<table cellpadding='3' cellspacing='1' style='border: 1px solid #080; font: 11px Arial'>";
				$idx = 0;
				foreach($vars as $key=>$value){
					$ret .= "<tr bgcolor='".($bgColors[$deep%(sizeof($bgColors)-1)][$idx++%2])."' valign='top'>";
					$ret .= "	<td><b>{$key}</b></td>";
					$ret .= "	<td>";
					$ret .= 		self::dumpVars($value, $deep+1, $dumpedHash);
					$ret .= "	</td>";
					$ret .= "</tr>";
				}
				$ret .= "</table>";
			}
			else{
				$ret .= "Array()";
			}
		}
		elseif(is_object($vars)){
			$myHash = md5(print_r($vars, true));
			if(in_array($myHash, $dumpedHash)){
				return "** <a href='#{$myHash}'>Já exibido, possível recursão (click to go)</a> **";
			}
			$dumpedHash[] = $myHash;
			
			$ret .= "<a name='{$myHash}'></a>";
			
			$isdDbRow    = is_subclass_of($vars, 'dDbRow')    || get_class($vars) == 'dDbRow';
			$isdDbSearch = is_subclass_of($vars, 'dDbSearch') || get_class($vars) == 'dDbSearch';
			
			$ret .= "<div style='padding: 3px; background: #FFF; border: 1px solid #080; font: 11px Arial'>object ".get_class($vars).($isdDbRow?($vars->getPrimaryValue()?" ID={$vars->getPrimaryValue()}":" unloaded"):"")."</div>";
			$ret .= "<div style='height: 150px; overflow-y: scroll'>";
			$ret .= "<table cellpadding='3' cellspacing='1' style='font: 11px Arial'>";
			$idx = 0;
			foreach($vars as $key=>$value){
				if($isdDbRow){
					if(in_array($key, Array('ffTables', 'mainTable', 'fieldProps', 'fieldOriginal', 'useQuotes', 'autoUpdateOn', 'autoUpdateAlias', 'validations', 'modifiers', 'debug', 'db', 'ignoreVal', 'ignoreMod', 'errorList')))
						continue;
				}
				if($isdDbSearch){
					if(in_array($key, Array('debug', 'mainTable', 'ffTable', 'fieldProps', 'ffTables', 'useQuotes', 'strings', 'db')))
						continue;
				}
				$ret .= "<tr bgcolor='".($bgColors[$deep%(sizeof($bgColors)-1)][$idx++%2])."' valign='top'>";
				$ret .= "	<td>{$key}</td>";
				$ret .= "	<td>";
				$ret .= 		self::dumpVars($value, $deep+1, $dumpedHash);
				$ret .= "	</td>";
				$ret .= "</tr>";
			}
			$ret .= "</table>";
			$ret .= "</div>";
		}
		else{
			$ret .= var_export($vars, true);
		}
		return $ret;
	}
}






