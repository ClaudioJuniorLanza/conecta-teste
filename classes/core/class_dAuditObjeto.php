<?php
class dAuditObjeto extends dDbRow3{
	static Function buildStruct(){
		self::structSet('primaryKeyNotUnique', true);
		self::setTable('d_audit_objetos');
		self::addField('id,usuar_id,audit_id,objet_id,class,data_hora,acao,dados');
		
		self::addExt('acessObj', 'dAuditAcesso::audit_id');
		
		self::addValidation('objet_id',    'required',    false,    'Você precisa preencher o campo objet id');
		self::addValidation('class',       'required',    false,    'Você precisa preencher o campo class');
		self::addValidation('data_hora',   'required',    false,    'Você precisa preencher o campo data hora');
		self::addValidation('acao',        'required',    false,    'Você precisa preencher o campo acao');
		self::addValidation('data_hora',   'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('usuar_id',    'int',         false,    'Preencha o campo usuar id apenas com números');
		self::addValidation('audit_id',    'int',         false,    'Preencha o campo audit id apenas com números');
		self::addValidation('objet_id',    'int',         false,    'Preencha o campo objet id apenas com números');
		self::addValidation('usuar_id',    'nummin',      0,        'Por favor, digite um numero positivo para usuar id');
		self::addValidation('audit_id',    'nummin',      0,        'Por favor, digite um numero positivo para audit id');
		self::addValidation('objet_id',    'nummin',      0,        'Por favor, digite um numero positivo para objet id');
		self::addValidation('class',       'singleline',  false,    'O campo class não pode ter mais de uma linha');
		self::addValidation('acao',        'singleline',  false,    'O campo acao não pode ter mais de uma linha');
		self::addValidation('class',       'strmax',      80,       'O campo class não pode ter mais de 80 caracteres');
		self::addValidation('acao',        'strmax',      30,       'O campo acao não pode ter mais de 30 caracteres');
		
		self::addModifier('usuar_id,audit_id,objet_id', 'force_int');
		self::addModifier('class,acao,dados', 'trim');
		self::addModifier('data_hora',   'datetime',    'br');
	}
	
	static $paused = false;
	static $queue  = Array();
	static Function setPaused($yesno=true){
		self::$paused = $yesno;
	}
	static Function cbAuditCallback($obj, $id, $event, $data){
		if(dSystem::getGlobal('currentVersion') < 1.2){
			// Evita erros fatais durante o processo de atualização.
			return false;
		}
		if(self::$paused){
			return;
		}
		
		$usuarObj = dUsuario::isLogged();
		self::$queue[] = Array(
			'usuar_id' =>$usuarObj?$usuarObj->v('id'):false,
			'audit_id' =>dAuditAcesso::getLastId(),
			'objet_id' =>$id,
			'class'    =>get_class($obj),
			'data_hora'=>date('d/m/Y H:i:s'),
			'acao'     =>$event,
			'dados'    =>(is_bool($data)&&!$data)?false:dHelper2::dSerialize($data),
		);
	}
	static Function flushQueue(){
		if(!self::$queue)
			return;
		
		self::getDb()->query("start transaction");
		foreach(self::$queue as $row){
			$eventObj = new dAuditObjeto;
			$saveOk   = $eventObj->loadArray($row)->save();
			if(!$saveOk){
				dSystem::notifyAdmin('MED', "Não foi possível salvar auditoria do objeto",
					"A classe ".get_class($obj)." (objeto id={$id} tentou salvar o seguinte objeto 'dAuditObjeto', mas ".
					"ocorreu um erro e essa auditoria não pode ser registrada.\r\n".
					"\r\n".
					"Dump do evento que falhou:\r\n".
					var_export($eventObj)."\r\n".
					"\r\n"
				);
				return false;
			}
		}
		self::getDb()->query("commit");
	}
}

register_shutdown_function(Array('dAuditObjeto', 'flushQueue'));
