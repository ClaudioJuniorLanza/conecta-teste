<?php // built on 26/01/2019 22:08
class cRefVariedade extends dDbRow3{
	static Function buildStruct(){
		self::setTable('c_ref_variedades');
		self::addField('id,variedade,cultura,tecnologia');
		
		self::addValidation('variedade',   'required',    false,    'Você precisa preencher o campo variedade');
		self::addValidation('cultura',     'required',    false,    'Você precisa preencher o campo cultura');
		self::addValidation('variedade',   'singleline',  false,    'O campo variedade não pode ter mais de uma linha');
		self::addValidation('cultura',     'singleline',  false,    'O campo cultura não pode ter mais de uma linha');
		self::addValidation('tecnologia',  'singleline',  false,    'O campo tecnologia não pode ter mais de uma linha');
		self::addValidation('variedade',   'strmax',      150,      'O campo variedade não pode ter mais de 150 caracteres');
		self::addValidation('cultura',     'strmax',      150,      'O campo cultura não pode ter mais de 150 caracteres');
		self::addValidation('tecnologia',  'strmax',      200,      'O campo tecnologia não pode ter mais de 200 caracteres');
		
		self::addModifier('variedade,cultura,tecnologia', 'trim');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
}
?>