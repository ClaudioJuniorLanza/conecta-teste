<?php
/**
	A tabela de configurações tem a seguinte formatação:
	id | key | value | value_text
	
	Se 'key' também existir como chave em "::getConfigList()", então seu valor é 'value'.
	Caso contrário, será utilizado o campo 'value_text', e 'value' sempre será 'SYSTEM'.
**/

class dConfiguracao extends dDbRow3{
	static $cache = false;
	
	static Function buildStruct(){
		
		self::setTable('d_configuracoes');
		self::addField('id,key,value,value_text');
		
		self::addValidation('key',         'required',    false,    'Você precisa preencher o campo key');
		self::addValidation('key',         'singleline',  false,    'O campo key não pode ter mais de uma linha');
		self::addValidation('value',       'singleline',  false,    'O campo value não pode ter mais de uma linha');
		self::addValidation('key',         'strmax',      200,      'O campo key não pode ter mais de 200 caracteres');
		self::addValidation('value',       'strmax',      250,      'O campo value não pode ter mais de 250 caracteres');
		
		self::addModifier('key,value,value_text', 'trim');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
		
		self::structSet('useQuotes', '`');
	}
	
	static Function getConfigList(){
		$listaConfig = Array();
		
		$listaConfig['CORE/TITLE']                   = "Configuraçõs principais do site";
		$listaConfig['CORE/NOME_DO_SITE']            = Array("string", "Nome do projeto");
		$listaConfig['CORE/DOMINIO']                 = Array("string", "Link (oficial, será enviado nos e-mails automáticos)<br /><small>http://www.site.com.br/</small>");
		$listaConfig['CORE/INTRANET_TOKEN']          = Array("string", "Token (Código único, gerado pela intranet IMAGINACOM)");
		$listaConfig['CORE/MAIL_FROM']               = Array("string", "E-mail remetente para os e-mails do sistema (ex: noreply@dominio.com.br)");
		$listaConfig['CORE/MAIL_TO']                 = Array("string", "Destinatário padrão para os formulários de contato (ex: contato@dominio.com.br)");
		$listaConfig['CORE/DEBUGGER_IP']             = Array("string", "IP para debugging (dSystem::debugMsg() e dSystem::isDebugger())");
		$listaConfig['CORE/MODULES']                 = Array("string", "Módulos implementados (<a href='setup.git.php'>Gerenciar</a>)");
		
		$listaConfig['GIT/TITLE']                    = "Integração / suporte GIT";
		$listaConfig['GIT/GIT_REMOTE']               = Array("string", "Remote para sincronizar (recomendado: produ)");
		$listaConfig['GIT/GIT_BRANCH']               = Array("string", "Branch de produção      (ex: master, etc.)");
		$listaConfig['GIT/SSH_SERVER']               = Array("string", "Usuário e servidor SSH  (ex: user@sv201.imaginacom.com)");
		
		$listaConfig['TEMPLATE/TITLE']               = "Preferências e ajustes visuais (quando aplicáveis)";
		$listaConfig['TEMPLATE/BODY_WIDTH']          = Array("string", "Largura máxima do conteúdo, para imagens no editor Rich Text");
		$listaConfig['TEMPLATE/RTE_ONLY_IMAGES']     = Array("cbox",   "No editor Rich Text, permitir APENAS upload de imagens, não de arquivos.");
		
		// To-do:
		//     Garantir a saúde do sistema, caso configurações mudem.
		//     Ex: PRODUTO/RESTRICT_FINAL_CATEGS permite cadastro em 'root', depois desativa isso.
		//     Tem que existir um alerta para que o usuário localize e corriga esses cadastros inválidos.
		//     A largura do site em TEMPLATE/BODY_WIDTH é outro exemplo disso (deve republicar todas as imagens no RTE)
		
		
		return $listaConfig;
	}
	static Function getConfig($key, $onDemand=false){
		// $onDemand admite que a variável pode não estar definida ainda.
		// $onDemand pode ser true ou 'unserialize'. Se existir, será retornado o valor unserialized.
		$list   = self::getConfigList();
		$config = self::loadConfig();
		
		// A configuração desejada não existe na tabela de configurações?
		if(!$onDemand && !array_key_exists($key, ($list+$config))){
			dSystem::notifyAdmin(
				'LOW',
				"Problema na configuração",
				"	O sistema está procurando a configuração '{$key}' (getConfig({$key})), mas ela não existe!\r\n".
				"	Isso não deveria acontecer, tendo em vista que não será possível definir essa variável no sistema.\r\n".
				"	\r\n".
				"	Para resolver:\r\n".
				"	- Identificar onde a chave {$key} está sendo solicitada;\r\n".
				"	- Adicionar a chave {$key} na classe dConfiguracao::getConfigList();\r\n".
				"   \r\n".
				"Por motivo de compatibilidade, vamos retornar FALSE."
			);
			return false;
		}
		
		// A configuração desejada ainda não está definida.
		if(!array_key_exists($key, $config)){
			return $onDemand?false:null;
		}
		
		if($config[$key] && $onDemand === 'unserialize'){
			return unserialize($config[$key]);
		}
		
		return $config[$key];
	}
	static Function setConfig($key, $value){
		$confiObj = dConfiguracao::loadOrNew($key, 'key');
		
		if($value === NULL){
			if($confiObj->isLoaded()){
				$confiObj->delete();
			}
		}
		else{
			$confiObj->v('key',   $key);
			
			if(strlen($value) > 250){
				$confiObj->v('value',      false );
				$confiObj->v('value_text', $value);
			}
			else{
				$confiObj->v('value',      $value);
				$confiObj->v('value_text', false );
			}
			$confiObj->save();
		}
		
		self::loadConfig(true);
		return true;
	}
	static Function loadConfig($reload=false){
		if(self::$cache && !$reload)
			return self::$cache;
		
		// Como saber se o sistema está configurado neste momento?
		if(!dSystem::getGlobal())
			return Array();
		
		$db       = dDatabase::start();
		$list     = self::getConfigList();
		
		$settings = $db->singleIndexV("select `key`,IF(ISNULL(`value_text`), `value`,`value_text`) as `value` from d_configuracoes");
		if(!$settings){
			$settings = Array();
		}
		
		// Vamos somar à $list, as configurações dos módulos (se houver)
		if(array_key_exists('CORE/MODULES', $settings) && $settings['CORE/MODULES']){
			$exModules = explode(",", $settings['CORE/MODULES']);
			foreach($exModules as $module){
				$module::modCreateConfig($list);
			}
		}
		
		// Define valores padrão e converte as configurações conhecidas:
		foreach($list as $key=>$description){
			if(!array_key_exists($key, $settings)){
				// Chave não existe? Padrão é false.
				$settings[$key] = false;
				continue;
			}
			
			if($description[0] == 'cbox'){
				$settings[$key] = ($settings[$key]?true:false);
			}
			if($description[0] == 'number'){
				$settings[$key] = floatval($settings[$key]);
			}
			// if($description[0] == 'string'){
			//  Se for string, mantém.
			// 	$settings[$key] = $settings[$key];
			// }
		}
		
		return self::$cache = $settings;
	}
}


