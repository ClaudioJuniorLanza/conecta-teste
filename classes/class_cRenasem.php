<?php // built on 18/01/2019 18:15
class cRenasem extends dDbRow3{
	static Function buildStruct(){
		self::setTable('c_renasens');
		self::addField('id,uf,cidade,renasem,valido_status,valido_expira,atividade');
		self::addField('cpf_cnpj,nome,endereco,cep');
		
		self::addValidation('renasem',     'required',    false,    'Você precisa preencher o campo renasem');
		self::addValidation('nome',        'required',    false,    'Você precisa preencher o campo nome');
		self::addValidation('valido_status', 'regex',       '(APTO|Renovar)', 'Opção inválida para valido status');
		self::addValidation('uf',          'singleline',  false,    'O campo uf não pode ter mais de uma linha');
		self::addValidation('cidade',      'singleline',  false,    'O campo cidade não pode ter mais de uma linha');
		self::addValidation('renasem',     'singleline',  false,    'O campo renasem não pode ter mais de uma linha');
		self::addValidation('valido_expira', 'singleline',  false,    'O campo valido expira não pode ter mais de uma linha');
		self::addValidation('atividade',   'singleline',  false,    'O campo atividade não pode ter mais de uma linha');
		self::addValidation('cpf_cnpj',    'singleline',  false,    'O campo CPF/CNPJ não pode ter mais de uma linha');
		self::addValidation('nome',        'singleline',  false,    'O campo nome não pode ter mais de uma linha');
		self::addValidation('endereco',    'singleline',  false,    'O campo endereco não pode ter mais de uma linha');
		self::addValidation('cep',         'singleline',  false,    'O campo cep não pode ter mais de uma linha');
		self::addValidation('cep',         'strexact',    8,        'Você digitou um CEP inválido, verifique e tente novamente');
		self::addValidation('uf',          'strmax',      2,        'O campo uf não pode ter mais de 2 caracteres');
		self::addValidation('cidade',      'strmax',      150,      'O campo cidade não pode ter mais de 150 caracteres');
		self::addValidation('renasem',     'strmax',      20,       'O campo renasem não pode ter mais de 20 caracteres');
		self::addValidation('valido_expira', 'strmax',      50,       'O campo valido expira não pode ter mais de 50 caracteres');
		self::addValidation('atividade',   'strmax',      80,       'O campo atividade não pode ter mais de 80 caracteres');
		self::addValidation('cpf_cnpj',    'strmax',      20,       'O campo CPF/CNPJ não pode ter mais de 20 caracteres');
		self::addValidation('nome',        'strmax',      500,      'O campo nome não pode ter mais de 500 caracteres');
		self::addValidation('endereco',    'strmax',      500,      'O campo endereco não pode ter mais de 500 caracteres');
		self::addValidation('cep',         'strmax',      8,        'O campo cep não pode ter mais de 8 caracteres');
		
		self::addModifier('uf',          'upper');
		self::addModifier('uf,cidade,renasem,valido_expira,atividade,cpf_cnpj,nome,endereco', 'trim');
		self::addModifier('cep',         'trim');
		self::addModifier('cidade,nome,endereco', 'ucfirst');
		self::addModifier('cpf_cnpj,cep', 'force_numbers');
		self::addModifier('cpf_cnpj',    'number_mask', '###.###.###-##|11');
		self::addModifier('cpf_cnpj',    'number_mask', '####.####/####-##|14');
		self::addModifier('cep',         'number_mask', '#####-###|8');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
}
?>