<?php
/**
	dAuditAcesso:
	--> No config.php, a variável $_EnableAudit define se o autoLog estará ativado por padrão.
	--> Para desativar o autoLog num script específico (ex: keep-alive), adicione $_EnableAudit=false antes de incluir o config.
	--> Para desativar o autoLog no meio da execução, utilize ::autoLog(false).
	--> Para adicionar informações, ou registrar novos logs, utilize ::log($acao, $acao_id, $explicacao)
	--> Ao executar o método ::log(), o evento será gravado imediatamente.
	--> Se você não utilizar o método ::log(), o evento será gravado apenas no final do script.
	--> Se o método ::blockPost(true) for chamado, então o conteúdo de $_POST será bloqueado.
	--> Se o método ::getLastId() for chamado e autoLog estiver ativo, então ele será consolidado imediatamente.
**/

class dAuditAcesso extends dDbRow3{
	static Function buildStruct(){
		self::structSet('primaryKeyNotUnique', true);
		self::setTable('d_audit_acessos');
		self::addField('id,usuar_id,agent_id,clien_id,data_hora,acao,acao_id,explicacao,filename,reque_uri');
		self::addField('post_data,ip,session_id');
		
		self::addExt('clienObj', 'cUsuario::clien_id');
		self::addExt('agentObj', 'cUsuario::agent_id');
		
		self::addValidation('data_hora',   'required',    false,    'Você precisa preencher o campo data hora');
		self::addValidation('ip',          'required',    false,    'Você precisa preencher o campo ip');
		self::addValidation('data_hora',   'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('usuar_id',    'int',         false,    'Preencha o campo usuar id apenas com números');
		self::addValidation('usuar_id',    'nummin',      0,        'Por favor, digite um numero positivo para usuar id');
		self::addValidation('agent_id',    'int',         false,    'Preencha o campo usuar id apenas com números');
		self::addValidation('agent_id',    'nummin',      0,        'Por favor, digite um numero positivo para usuar id');
		self::addValidation('clien_id',    'int',         false,    'Preencha o campo clien_id apenas com números');
		self::addValidation('clien_id',    'nummin',      0,        'Por favor, digite um numero positivo para clien_id');
		self::addValidation('acao',        'singleline',  false,    'O campo acao não pode ter mais de uma linha');
		self::addValidation('acao_id',     'singleline',  false,    'O campo acao id não pode ter mais de uma linha');
		self::addValidation('filename',    'singleline',  false,    'O campo filename não pode ter mais de uma linha');
		self::addValidation('reque_uri',   'singleline',  false,    'O campo reque uri não pode ter mais de uma linha');
		self::addValidation('ip',          'singleline',  false,    'O campo ip não pode ter mais de uma linha');
		self::addValidation('session_id',  'singleline',  false,    'O campo session id não pode ter mais de uma linha');
		self::addValidation('acao',        'strmax',      250,      'O campo acao não pode ter mais de 250 caracteres');
		self::addValidation('acao_id',     'strmax',      250,      'O campo acao id não pode ter mais de 250 caracteres');
		self::addValidation('filename',    'strmax',      120,      'O campo filename não pode ter mais de 120 caracteres');
		self::addValidation('reque_uri',   'strmax',      1500,     'O campo reque uri não pode ter mais de 1500 caracteres');
		self::addValidation('ip',          'strmax',      39,       'O campo ip não pode ter mais de 39 caracteres');
		self::addValidation('session_id',  'strmax',      80,       'O campo session id não pode ter mais de 80 caracteres');
		
		self::addModifier('usuar_id,clien_id',          'force_int');
		self::addModifier('data_hora',   'datetime',    'br');
		self::addModifier('acao,acao_id,explicacao,filename,reque_uri,post_data,ip', 'trim');
		self::addModifier('session_id',  'trim');
	}
	
	static $blockPost = false;
	static $autoLog   = false;
	static $lastId    = false;
	static Function blockPost($yesno=true){
		self::$blockPost = $yesno;
	}
	static Function autoLog($enable=true){
		if(dSystem::getGlobal('currentVersion') < 1.2){
			// Evita erros fatais durante o processo de atualização.
			return false;
		}
		
		if(!$enable){
			if(self::$autoLog)
				self::$autoLog->removeVirtual('autoLog');
			
			self::$autoLog = false;
			return true;
		}
		if(self::$autoLog){
			dSystem::notifyAdmin('MED', "Chamando autoLog duas vezes",
				"O método dAuditAcesso::autoLog só deveria ser chamado ".
				"uma única vez a cada requisição, automaticamente, pelo ".
				"config.php. No entanto, ao ser chamado, o autoLog já constava ".
				"ativado por alguma razão do destino.\r\n".
				"\r\n".
				"Deve-se investigar a origem desse chamado em duplicidade."
			);
			return false;
		}
		
		self::$autoLog = new dAuditAcesso;
		self::$autoLog->setVirtual('autoLog', true);
	}
	static Function log($acao=false, $acao_id=false, $explicacao=false){
		// acao:       Opcional: de preferência, apenas uma única string com até 250 caracteres. Ex: REMOVENDO_FOTO
		// acao_id:    Opcional: o ID do objeto relevante, para busca posterior.
		// explicacao: Opcional: string no formato TEXT, explicando o evento.
		// 
		$auditObj = (self::$autoLog)?
			self::$autoLog:
			new dAuditAcesso;
		
		if(self::$autoLog){
			$auditObj->removeVirtual('autoLog');
			self::$autoLog = false;
		}
		
		$usePost = false;
		if($_POST){
			if(self::$blockPost){
				if(is_bool(self::$blockPost) && self::$blockPost === true){
					$usePost = dHelper2::dSerialize(array_map(function(){ return "*******"; }, $_POST));
				}
				else{
					$usePost = self::$blockPost;
				}
			}
			else{
				$usePost = dHelper2::dSerialize($_POST);
			}
		}
		
		$usuarObj = dUsuario::isLogged();
		$clienObj = cUsuario::isLogged();
		$saveOk   = $auditObj
				->v('usuar_id',   $usuarObj?$usuarObj->v('id'):false)
				->v('agent_id',   ($clienObj&&$clienObj->getVirtual('agente_acting_as'))?$clienObj->getVirtual('agente_acting_as')->v('id'):false)
				->v('clien_id',   $clienObj?$clienObj->v('id'):false)
				->v('data_hora',  date('d/m/Y H:i:s'))
				->v('acao',       $acao)
				->v('acao_id',    $acao_id)
				->v('explicacao', $explicacao)
				->v('filename',   basename($_SERVER['PHP_SELF']))
				->v('reque_uri',  $_SERVER['REQUEST_URI'])
				->v('post_data',  $usePost)
				->v('ip',         $_SERVER['REMOTE_ADDR'])
				->v('session_id', session_id())
				->save();
		
		if(!$saveOk){
			dSystem::notifyAdmin('MED', "Não consegui salvar um item na auditoria", 
				"O comando save() retornou FALSE, o que indica que o evento em questão ".
				"não foi gravado corretamente. Os erros retornados por dAuditAcesso foram:\r\n".
				"\r\n".
				"- ".implode(",", $auditObj->listErrors(true))."\r\n".
				"\r\n".
				"As informações que seriam gravadas eram as seguintes:\r\n".
				var_export($auditObj->export(), true)."\r\n".
				"\r\n".
				"Isso não é um erro crítico, mas é um evento que deixou de ser considerado no banco de dados e exige revisão."
			);
			return false;
		}
		
		self::$lastId = $auditObj->v('id');
		return true;
	}
	static Function getLastId(){
		if(self::$autoLog){
			// Consolidar o log atual, para termos um ID que possa ser retornado.
			self::log();
		}
		
		return self::$lastId;
	}
	
	Function __destruct(){
		if(!$this->getVirtual('autoLog')){
			return;
		}
		if(dDatabase::$isFatalDead){
			// Se morreu por problemas na conexão com o banco de dados, não tente gravar nada no log.
			return;
		}
		
		self::log();
		return;
	}
}
