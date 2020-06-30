<?php // built on 11/05/2015 00:24
class dAnexo extends dDbRow3Plus{
	static Function buildStruct(){
		self::setTable('d_anexos');
		self::addField('id,rel,rel_id,data_add,data_mod,filename,fileext,filesize');
		
		self::addValidation('rel',         'required',    false,    'Você precisa preencher o campo rel');
		self::addValidation('data_add',    'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_mod',    'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('rel_id',      'int',         false,    'Preencha o campo rel id apenas com números');
		self::addValidation('filesize',    'int',         false,    'Preencha o campo filesize apenas com números');
		self::addValidation('rel_id',      'nummin',      0,        'Por favor, digite um numero positivo para rel id');
		self::addValidation('rel',         'singleline',  false,    'O campo rel não pode ter mais de uma linha');
		self::addValidation('filename',    'singleline',  false,    'O campo filename não pode ter mais de uma linha');
		self::addValidation('fileext',     'singleline',  false,    'O campo fileext não pode ter mais de uma linha');
		self::addValidation('rel',         'strmax',      40,       'O campo rel não pode ter mais de 40 caracteres');
		self::addValidation('filename',    'strmax',      250,      'O campo filename não pode ter mais de 250 caracteres');
		self::addValidation('fileext',     'strmax',      15,       'O campo fileext não pode ter mais de 15 caracteres');
		
		self::addModifier('rel,filename,fileext', 'trim');
		self::addModifier('rel_id,filesize', 'force_int');
		self::addModifier('data_add,data_mod', 'datetime',    'br');
		
		self::setDefaultValue('data_add', date('d/m/Y H:i:s'));
		self::setDefaultValue('data_mod', date('d/m/Y H:i:s'));
		
		self::enableSetFile();
		self::setAuditing  (Array('dAuditObjeto', 'cbAuditCallback'));
	}
	
	/**
	 * Facilita o recebimento de múltiplos anexos para o mesmo objeto.
	 *
	 * @param $formField string Nome do input. Ex: Se <input type=file name=addAnexo[]>, o formField é 'addAnexo'
	 * @param $rel       string Relacionado a.... Por padrão, o nome da classe que está armazenando o arquivo.
	 * @param $rel_id    int    ID relacionado à classe.
	 * @param &$succMsg  array  Sucessos serão colocados aqui.
	 * @param &$errMsg   array  Erros serão colocados aqui.
	 */
	static Function handleUpload($formField, $rel, $rel_id, &$succMsg, &$errMsg){
		// Considere <input type='file' name='addAnexo[]'>
		// Ex: handleUpload('addAnexo', 'eProduto', 23, $succMsg, $errMsg)
		if(!@$_FILES || !@$_FILES[$formField]){
			return;
		}
		
		if(!is_array($_FILES[$formField]['tmp_name'])){
			$_FILES[$formField]['tmp_name'] = Array($_FILES[$formField]['tmp_name']);
		}
		if(!is_array($_FILES[$formField]['name'])){
			$_FILES[$formField]['name'] = Array($_FILES[$formField]['name']);
		}
		if(!is_array($_FILES[$formField]['error'])){
			$_FILES[$formField]['error'] = Array($_FILES[$formField]['error']);
		}
		
		foreach($_FILES[$formField]['tmp_name'] as $idx=>$tmpName){
			$fileName  = $_FILES[$formField]['name'][$idx];
			$errorCode = $_FILES[$formField]['error'][$idx]; // Leia: https://www.php.net/manual/pt_BR/features.file-upload.errors.php
			if($fileName){
				$anexoObj = new dAnexo;
				$anexoObj->v('rel',      $rel);
				$anexoObj->v('rel_id',   $rel_id);
				$anexoObj->v('filename', $fileName);
				if($anexoObj->save()){
					if($anexoObj->setFile($tmpName, $fileName)){
						$succMsg[] = "Arquivo {$fileName} recebido com sucesso.";
					}
					else{
						$errMsg[] = "Não foi possível receber o arquivo {$fileName} no servidor. Contate um administrador.";
					}
				}
				else{
					$errMsg[] = "Não foi possível receber {$fileName}: ".implode("\r\n", $anexoObj->listErrors(true));
				}
			}
			else if($fileName && $errorCode){
				if($errorCode == UPLOAD_ERR_INI_SIZE){
					$errMsg[] = "Upload falhou: O limite de ".ini_get('upload_max_filesize')." foi ultrapassado.";
				}
				else if($errorCode == UPLOAD_ERR_FORM_SIZE){
					$errMsg[] = "Upload falhou: O arquivo ultrapassa os limites aceitos.";
				}
				else if($errorCode == UPLOAD_ERR_PARTIAL){
					$errMsg[] = "Upload falhou: A transferência foi interrompida antes de concluir.";
				}
				else if($errorCode == UPLOAD_ERR_NO_FILE){
					$errMsg[] = "Upload falhou: Seu navegador não enviou nenhum arquivo.";
				}
				else if($errorCode == UPLOAD_ERR_CANT_WRITE){
					$errMsg[] = "Upload falhou: Servidor mal configurado. Contate um administrador.";
				}
				else if($errorCode == UPLOAD_ERR_EXTENSION){
					$errMsg[] = "Upload falhou: Conflito no servidor. Contate um administrador.";
				}
			}
		}
	}
}

