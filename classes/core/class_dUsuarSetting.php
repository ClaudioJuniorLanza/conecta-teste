<?php // built on 06/04/2014 21:16
class dUsuarSetting extends dDbRow3{
	static Function buildStruct(){
		self::setTable('d_usuar_settings');
		self::addField('id,usuar_id,chave,valor_char,valor_text');
		self::addExt  ('usuarObj', 'dUsuario::usuar_id');
		
		self::addValidation('usuar_id',    'required',    false,    'Você precisa preencher o campo usuar id');
		self::addValidation('chave',       'required',    false,    'Você precisa preencher o campo chave');
		self::addValidation('usuar_id',    'int',         false,    'Preencha o campo usuar id apenas com números');
		self::addValidation('usuar_id',    'nummin',      0,        'Por favor, digite um numero positivo para usuar id');
		self::addValidation('chave',       'singleline',  false,    'O campo chave não pode ter mais de uma linha');
		self::addValidation('valor_char',  'singleline',  false,    'O campo valor char não pode ter mais de uma linha');
		self::addValidation('chave',       'strmax',      150,      'O campo chave não pode ter mais de 150 caracteres');
		self::addValidation('valor_char',  'strmax',      250,      'O campo valor char não pode ter mais de 250 caracteres');
		
		self::addModifier('usuar_id',    'force_int');
		self::addModifier('chave,valor_char,valor_text', 'trim');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
}
?>
