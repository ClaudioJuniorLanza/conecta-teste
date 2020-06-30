<?php // built on 10/06/2012 15:42
class dUsuarPermissao extends dDbRow3{
	static Function buildStruct(){
		self::setTable('d_usuar_permissoes');
		self::addField('id,usuar_id,permissao');
		self::addExt  ('usuarObj', 'dUsuario::usuar_id');
		
		self::addValidation(false, 'unique', 'usuar_id,permissao',  'Permissão ja existe.');
		self::addValidation('usuar_id',    'required',    false,    'Você precisa preencher o campo usuar_id');
		self::addValidation('permissao',   'required',    false,    'Você precisa preencher o campo permissao');
		self::addValidation('usuar_id',    'int',         false,    'Preencha o campo usuar_id apenas com números');
		self::addValidation('usuar_id',    'nummin',      0,        'Por favor, digite um numero positivo para usuar_id');
		self::addValidation('permissao',   'singleline',  false,    'O campo permissao não pode ter mais de uma linha');
		self::addValidation('permissao',   'strmax',      50,       'O campo permissao não pode ter mais de 50 caracteres');
		
		self::addModifier('usuar_id',    'force_int');
		self::addModifier('permissao',   'trim');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
}
?>
