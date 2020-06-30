<?php
class cToken extends dDbRow3Plus{
	static function buildStruct(){
		self::autoStruct('c_tokens', ['ext' =>[
			'usuarObj'=>'dUsuario',
		], 'dump'=>false, 'allowInProducao'=>true]);
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
}
