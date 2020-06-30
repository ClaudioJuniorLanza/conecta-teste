<?php // built on 27/01/2019 11:27
class cProposta extends dDbRow3{
	static Function buildStruct(){
		self::setTable('c_propostas');
		self::addField('id,anunc_id,usuar_id,data_proposta,status');
		self::addField('cancelada_motivo,varie_id,valor,regiao');
		self::addField('data_revisado,data_aceite,justificativa');
		
		self::addExt('usuarObj', "cUsuario::usuar_id");
		self::addExt('anuncObj', "cAnuncio::anunc_id");
		self::addExt('varieObj', "cRefVariedade::varie_id");
		
		self::addValidation('anunc_id',    'required',    false,    'Você precisa preencher o campo anunc id');
		self::addValidation('usuar_id',    'required',    false,    'Você precisa preencher o campo usuar id');
		self::addValidation('data_proposta', 'datetime',  'br',     'Preencha a data_proposta no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_revisado', 'datetime',  'br',     'Preencha a data_revisado no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_aceite', 'datetime',    'br',     'Preencha a data_aceite no formato dd/mm/aaaa hh:mm');
		self::addValidation('anunc_id',    'int',         false,    'Preencha o campo anunc id apenas com números');
		self::addValidation('usuar_id',    'int',         false,    'Preencha o campo usuar id apenas com números');
		self::addValidation('varie_id',    'int',         false,    'Preencha o campo varie id apenas com números');
		self::addValidation('anunc_id',    'nummin',      0,        'Por favor, digite um numero positivo para anunc id');
		self::addValidation('usuar_id',    'nummin',      0,        'Por favor, digite um numero positivo para usuar id');
		self::addValidation('varie_id',    'nummin',      0,        'Por favor, digite um numero positivo para varie id');
		self::addValidation('status',      'regex',       '(Sem Interesse|Enviada|Rejeitada|Rejeitada pelo Admin|Aceita|Negócio Fechado|Negócio Desfeito|Cancelada)', 'Opção inválida para status');
		self::addValidation('cancelada_motivo', 'singleline',  false,    'O campo cancelada motivo não pode ter mais de uma linha');
		self::addValidation('justificativa', 'singleline',  false,    'O campo justificativa não pode ter mais de uma linha');
		self::addValidation('regiao',      'singleline',  false,    'O campo regiao não pode ter mais de uma linha');
		self::addValidation('cancelada_motivo', 'strmax',      250,      'O campo cancelada motivo não pode ter mais de 250 caracteres');
		self::addValidation('justificativa', 'strmax',      150,      'O campo justificativa não pode ter mais de 150 caracteres');
		self::addValidation('regiao',      'strmax',      150,      'O campo regiao não pode ter mais de 150 caracteres');
		
		self::addModifier('anunc_id,usuar_id,varie_id', 'force_int');
		self::addModifier('data_proposta,data_revisado,data_aceite', 'datetime',    'br');
		self::addModifier('cancelada_motivo,justificativa,regiao', 'trim');
		self::addModifier('valor',       'force_float');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
	Function getColor(){
		$myPropoColor = 'lightgray'; // Desconhecido.
		if($this->v('status') == 'Enviada'){
			$myPropoColor = 'yellow';
		}
		elseif(in_array($this->v('status'), ['Rejeitada', 'Rejeitada pelo Admin', 'Negócio Desfeito'])){
			$myPropoColor = 'lightgray';
		}
		elseif($this->v('status') == 'Aceita'){
			$myPropoColor = 'lightgreen';
		}
		elseif($this->v('status') == 'Negócio Fechado'){
			$myPropoColor = 'green';
		}
		return $myPropoColor;
	}
}
?>