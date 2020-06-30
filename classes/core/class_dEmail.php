<?php
class dEmail extends dDbRow3{
	static Function buildStruct(){
		self::setTable('d_emails');
		self::addField('id,data_hora,tipo,mailfrom,replyto_name,replyto_mail,mailto,subject,text_html,dsm_object,deleted');
		
		self::addValidation('data_hora',   'required',    false,    'Você precisa preencher o campo data_hora');
		self::addValidation('mailfrom',    'required',    false,    'Você precisa preencher o campo mailfrom');
		self::addValidation('mailto',      'required',    false,    'Você precisa preencher o campo mailto');
		self::addValidation('subject',     'required',    false,    'Você precisa preencher o campo subject');
		self::addValidation('text_html',   'required',    false,    'Você precisa preencher o campo text_html');
		self::addValidation('data_hora',   'datetime',    'br',     'Preencha a data_hora no formato dd/mm/aaaa hh:mm');
		self::addValidation('deleted',     'regex',       '(0|1)',  'Opção inválida para deleted');
		self::addValidation('tipo',        'singleline',  false,    'O campo tipo não pode ter mais de uma linha');
		self::addValidation('mailfrom',    'singleline',  false,    'O campo mailfrom não pode ter mais de uma linha');
		self::addValidation('replyto_name','singleline',  false,    'O campo replyto_name não pode ter mais de uma linha');
		self::addValidation('replyto_mail','singleline',  false,    'O campo replyto_mail não pode ter mais de uma linha');
		self::addValidation('mailto',      'singleline',  false,    'O campo mailto não pode ter mais de uma linha');
		self::addValidation('subject',     'singleline',  false,    'O campo subject não pode ter mais de uma linha');
		self::addValidation('tipo',        'strmax',      250,      'O campo mailfrom não pode ter mais de 250 caracteres');
		self::addValidation('mailfrom',    'strmax',      250,      'O campo mailfrom não pode ter mais de 250 caracteres');
		self::addValidation('replyto_name','strmax',      250,      'O campo replyto_name não pode ter mais de 250 caracteres');
		self::addValidation('replyto_mail','strmax',      250,      'O campo replyto_mail não pode ter mais de 250 caracteres');
		self::addValidation('mailto',      'strmax',      250,      'O campo mailto não pode ter mais de 250 caracteres');
		self::addValidation('subject',     'strmax',      250,      'O campo subject não pode ter mais de 250 caracteres');
		
		self::addModifier('data_hora',        'datetime',    'br');
		self::addModifier('tipo,mailfrom,replyto_name,replyto_mail,mailto,subject,text_html,dsm_object', 'trim');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
		
		self::addToConstructor(function($obj){
			if($obj->isAliasEnabled('tipo'))      $obj->setValue('tipo',      'Geral');
			if($obj->isAliasEnabled('data_hora')) $obj->setValue('data_hora', date('d/m/Y H:i:s'));
			if($obj->isAliasEnabled('deleted'))   $obj->setValue('deleted',   '0');
		});
	}
	
	
	// Static -- Helpers:
	static Function sendFromTemplate($settings){
		$settings = dHelper2::addDefaultToArray($settings, Array(
			'template' => false,
			'replace'  => Array(),
			// -- or --
			'message'  => false,
			// -- and... --
			'to'       => false,
			'grupo'    => false,
			'subject'  => false,
			'dontSend' => false,
		));
		extract($settings, EXTR_REFS);
		
		$basePath     = dSystem::getGlobal('baseDir')."/app/email/";
		if(!$message && $template){
			$templatePath = "{$basePath}{$template}.html";
			if(!file_exists($templatePath)){
				die("Template {$template} não encontrado.");
			}
			
			$message = file_get_contents($templatePath);
			foreach($replace as $replaceFrom => $replaceTo){
				$message = str_replace($replaceFrom, $replaceTo, $message);
			}
			
			
		}
		$message = str_replace("[ASSUNTO]", $subject, $message);
		$message = str_replace("[DADOS_TECNICOS]", "<b>Dados técnicos:</b><br />".implode("<br />", self::_getDadosTecnicos()), $message);
		$message = str_replace("[LINK_PAINEL]", $_SERVER["SERVER_NAME"] . "/admin/", $message);

		$m = new dSendMail3;
		$m->setSendThrough('GMail',    'yclpjr@gmail.com', 'pl@n$$0n@1982');
		$m->setCharset('UTF-8');
		$m->setFrom   (dConfiguracao::getConfig('CORE/MAIL_FROM'), dConfiguracao::getConfig('CORE/NOME_DO_SITE'));
		$m->setReplyTo(dConfiguracao::getConfig('CORE/MAIL_FROM'), dConfiguracao::getConfig('CORE/NOME_DO_SITE'));
		$m->setTo     ($to);
		$m->setSubject($subject);
		($template)?
			$m->loadFromHTML($message, $basePath):
			$m->setMessage  ($message);
		
		$dsmToSave = clone $m;
		$dsmToSave->attachments = false;
		
		$emailObj = new dEmail;
		$doSave   = $emailObj
			->v('data_hora',    date('d/m/Y H:i:s'))
			->v('tipo',         $grupo)
			->v('mailfrom',     dConfiguracao::getConfig('CORE/MAIL_FROM'))
			->v('replyto_mail', dConfiguracao::getConfig('CORE/MAIL_FROM'))
			->v('replyto_name', dConfiguracao::getConfig('CORE/NOME_DO_SITE'))
			->v('mailto',       $to)
			->v('subject',      $m->getSubject())
			->v('text_html',    $m->getMessage('html'))
			->v('dsm_object',   serialize($dsmToSave))
			->save();
		
		$doSend = ($dontSend || dSystem::getGlobal('localHosted'))?true:$m->send();
		if(!$doSave || !$doSend){
			dSystem::notifyAdmin('HIGH',
				"Falha ao salvar ou registrar envio de e-mail",
				"Salvando no banco de dados: ".($doSave?"OK, ID={$doSave}":"Falhou, erros:\r\n".implode("\r\n", $emailObj->listErrors(true)))."\r\n".
				"Enviando pela função MAIL(): ".($doSend?"OK":"Falhou")."\r\n".
				"\r\n".
				"Não é uma falha crítica, mas deve ser analisada.\r\n".
				"O e-mail foi mandado mesmo assim.\r\n"
			);
			return false;
		}
		
		return $doSend;
	}
	
	static Function sendContato($dados){
		// Valores esperados:
		//   'tipo'   =>  (Será salvo no banco de dados. Se vier FALSE, não grava.)
		//   'to'     =>  (Para quem será enviado esse e-mail)
		//   'from'   =>  (Quem será o remetente)
		//   'subject'=>  (Assunto do e-mail)
		//   'campos' =>  (Array com o qual montar o corpo do e-mail)
		//   'anexos'  => (Arquivos para anexar: [] Array('filename'=>..., 'filedata'=>...))
		//   'dontSend'=> (Padrão: false, se for TRUE vai apenas arquivar, sem enviar via smtp)
		if(isset($dados['from']) && !is_array($dados['from'])){
			$dados['from'] = Array($dados['from'], false);
		}
		if(isset($dados['to'])   && !is_array($dados['to']  )){
			$dados['to'] = Array($dados['to'], false);
		}
		
		
		if(isset($dados['anexo']) && !isset($dados['anexos'])){
			$dados['anexos'] = $dados['anexo'];
			unset($dados['anexo']);
		}
		$dados = dHelper2::addDefaultToArray($dados, Array(
			'tipo'    =>false,
			'from'    =>Array(false, false),
			'to'      =>Array(false, false),
			'header'  =>false,
			'subject' =>false,
			'campos'  =>Array(),
			'anexo'   =>Array(),
			'dontSend'=>false,
		));
		extract($dados, EXTR_REFS);
		
		$templatePath = dSystem::getGlobal('baseDir')."/admin/emails/contato.html";
		$message      = file_get_contents($templatePath);
		$message      = str_replace("[ASSUNTO]",           $subject,                                      $message);
		$message      = str_replace("[HEADER]",            $header?$header:$subject,                      $message);
		$message      = str_replace("[CORE/NOME_DO_SITE]", dConfiguracao::getConfig('CORE/NOME_DO_SITE'), $message); 
		
		$dadosRegex   = "/\[DADOS:INICIO\](.+?)\[DADOS:FIM\]/s";
		$anexoRegex   = "/\[ANEXO:INICIO\](.+?)\[ANEXO:FIM\]/s";
		
		$finalDados   = Array();
		preg_match($dadosRegex, $message, $templateDados);
		foreach($campos as $campo=>$valor){
			if(is_array($valor)){
				$valor = implode(", ", $valor);
			}
			if($campo != 'email'){
				$valor = ucfirst($valor);
			}
			$valor = htmlspecialchars($valor);
			$valor = nl2br($valor);
			
			$tmpDados = $templateDados[1];
			$tmpDados = str_replace("[CAMPO]", ucfirst($campo), $tmpDados);
			$tmpDados = str_replace("[VALOR]", $valor, $tmpDados);
			$finalDados[] = $tmpDados;
		}
		$message = preg_replace($dadosRegex, implode("", $finalDados), $message);
		$message = preg_replace($anexoRegex, $anexos?"\\1":"", $message);
		
		$dadosTecnicos = self::_getDadosTecnicos();
		$message = str_replace("[DADOS_TECNICOS]", implode("<br />", $dadosTecnicos), $message);
		
		$m = new dSendMail3;
		$m->setCharset('UTF-8');
		$m->setFrom   ($from[0], $from[1]);
		$m->setTo     ($to[0], $to[1]);
		$m->setReplyTo($campos['email'], $campos['nome']);
		$m->setSubject($subject);
		$m->loadFromHtml($message, dirname($templatePath));
		
		$emailObj = new dEmail;
		$reply    = dSendMail3::unNormalizeEmail($m->getHeader('Reply-To'));
		$doSave   = $emailObj
			->v('data_hora',    date('d/m/Y H:i:s'))
			->v('tipo',         $tipo)
			->v('mailfrom',     $from[0])
			->v('mailto',       $to  [0])
			->v('replyto_name', $reply[1])
			->v('replyto_mail', $reply[0])
			->v('subject',      $m->getSubject())
			->v('text_html',    $m->getMessage('html'))
			->v('dsm_object',   serialize($m))
			->save();
		
		if($anexos){
			foreach($anexos as $idx=>$anexoItem){
				$filename = &$anexos[$idx]['filename'];
				$filedata = &$anexos[$idx]['filedata'];
				$m->addAttachment($filename, $filedata);
				
				if($doSave){
					$anexoObj = new dAnexo;
					$anexoObj->v('rel', 'dEmail')->v('rel_id', $emailObj->v('id'))->save();
					$anexoObj->setFileData($filename, $filedata);
				}
			}
		}
		
		$doSend = ($dontSend || dSystem::getGlobal('localHosted'))?true:$m->send();
		if(!$doSave || !$doSend){
			dSystem::notifyAdmin('HIGH',
				"Falha ao salvar ou registrar envio de e-mail",
				"Salvando no banco de dados: ".($doSave?"OK, ID={$doSave}":"Falhou, erros:\r\n".implode("\r\n", $emailObj->listErrors(true)))."\r\n".
				"Enviando pela função MAIL(): ".($doSend?"OK":"Falhou")."\r\n".
				"\r\n".
				"Não é uma falha crítica, mas deve ser analisada.\r\n".
				"O e-mail foi mandado mesmo assim.\r\n"
			);
			return false;
		}
		
		return $doSend;
	}
	
	static Function _getDadosTecnicos(){
		$dadosTecnicos = Array();
		$dadosTecnicos[] = "Data e hora do envio: ".date('d/m/Y H:i:s');
		$dadosTecnicos[] = "Endereço IP: {$_SERVER["REMOTE_ADDR"]} (".gethostbyaddr($_SERVER["REMOTE_ADDR"]).")";
		if(@$_SERVER["HTTP_X_FORWARDED_FOR"]){
			$dadosTecnicos[] = "Proxy detectado: {$_SERVER['HTTP_X_FORWARDED_FOR']}";
		}
		if(@$_SERVER["HTTP_REFERER"]){
			$dadosTecnicos[] = "Página de origem: {$_SERVER['HTTP_REFERER']}";
		}
		if(@$_SERVER["HTTP_USER_AGENT"]){
			$dadosTecnicos[] = "Navegador: {$_SERVER['HTTP_USER_AGENT']}";
		}
		if(@session_id()){
			$dadosTecnicos[] = "Identificador de visita: ".session_id();
		}
		
		return $dadosTecnicos;
	}
	
	Function delete(){
		// Exclui os anexos relacionados:
		$anexos = array_map(function($obj){
			$obj->delete();
		}, dAnexo::multiLoad("where rel='dEmail' and rel_id='{$this->v('id')}'"));
		
		return parent::delete();
	}
}
