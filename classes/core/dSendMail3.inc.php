<?php
/**
	Guia rápido:
		$m = new dSendMail3;
		$m->setFrom("noreply@server.com", "Nome do remetente");
		$m->setTo  ("john@doe.com",       "Nome do destinatário");
		$m->setSubject("Sample subject");
		$m->setMessage($body, $html=true, $nl2br=false);
		$m->send();
		
		// Debugging:
		$m->setSendThrough('nowhere');
		echo $m->send()?"Sucesso":"Falhou";
		echo "<pre>";
		echo "<b>Headers:</b>\r\n{$m->output['headers']}<hr />";
		echo "<b>Body:</b>\r\n{$m->output['body']}";
		echo "</pre>";
		
		// Configurações:
		$m->setCharset    ('UTF8'); // Default
		$m->setSendThrough('MAIL');
		$m->setDKIM($private_key, $dns_prefix='mail');
		
		// Adicionando múltiplos destinatários:
		$m->addTo  ("john@doe.com"[, "Nome do destinatário"]);
		$m->addTo  (Array(
			"john@doe.com",
			Array("john@doe.com", "Nome do destinatário"),
		));
		
		// Adicionando anexos
		$m->addAttachment($filename,  $filedata=false) --> Força arquivo em anexo
		$m->addAuto      ($filename,  $filedata=false) --> Detecta se deve incorporar ou anexar
		$m->loadFromHTML ($text_html, $imagesDir)      --> Carrega automaticamente recursos informados em $imagesDir
		
		// Trabalhando com envio e erros
		$m->getErrorStr();
		
		// Exportando:
		file_put_contents("email.eml", $m->getAsEML());
	
	
	Documentação avançada:
		setSendThrough($Type)
			(MAIL)
			(SMTP,     $Settings)
				[Server]    --> Required.
				[Port]      --> Required.
				[Username]  --> Optional.
				[Password]  --> Optional.
				[SSL]       --> Optional. Can be FALSE, "TLS" or "SSL".
				[Hostname]  --> Optional, used in "EHLO $Hostname". Defaults to HTTP_HOST or "localhost"
				
			(CALLBACK, $cbFunction($this))
			(GMail,    $Username, $Password)
			(Outlook,  $Username, $Password)
			(Yahoo,    $Username, $Password)
			(AmazonSES,$APIKey,   $Secret[, $Region='us-east-1'])
			(Nowhere,  0.00 to 1.00 (Chances of failing))
		
		setTo, setCc, setBcc, addTo, addCc, addBcc
			setTo("!undisclosed-recipients") * Problematic when using MAIL() function
			setTo("email@server.com"[, "Name of person"]);
			setTo(Array(
				"email1@server.com",
				"email2@server.com",
				Array("email3@server.com", "Person 3"),
				Array("email4@server.com", "Person 4")
			));
			* Se "!" for utilizado, o mesmo será utilizado apenas
			  para a montagem do cabeçalho, mas não será citado na comunicação do SMTP.
			* "!" só faz o destinatário ser ignorado em conexões via SMTP. Em conexões utilizando
			  a função MAIL(), o envio é realizado normalmente (embora a exclamação seja removida).
		
		setFrom, setReplyTo, setReturnPath, setSender ($email, $name)
			setSender e setReturnPath é opcional, se não for fornecido será utilizado o valor de setFrom.
		
		setDate ($timestamp[, $Time_In_GMT])
			Define a hora do envio (timestamp).
		
		setSubject ($subject)
		setHeader  ($header, $value[, $force])
			Adiciona um valor ao cabeçalho.
			Para alterar diretamente 'to', 'cc', 'bcc', 'date', 'from', 'reply-to',
			'return-path', 'sender', 'subject', 'content-type', será necessário utilizar $force.
		
		setMessage($body, $html=true, $nl2br=false)
		setMessageMulti($html_part, $text_part)
			Definem o corpo da mensagem, nos dois níveis (html/text)
		
		getTo, getCc, getBcc, getDate, getFrom, getReplyTo, getReturnPath, getSender, getSubject()
		getHeader($header), getMessage($asType)
			Recuperam os valores atuais.
		
		addAuto, addEmbed, addAttachment ($filename, $filedata)
			Adiciona um arquivo nos anexos.
		
		getAttachment([$filename[, only_type[, as_array]]])
			Pega um anexo definido anteriormente.
		
		clearAttachments()
			Remove todos os anexos 
		
		loadFromHTML($text_html, $imagesDir)
		loadFromEML ($text_eml[, $fullLoad])
		loadFromMail($to, $subject, $body, $headers, $addparam, $fullLoad)
		
		getAsEML()
		getSendMailParams()
		setServerWorkAround($wa)
		
		getErrorCode()
		getErrorStr()
		
		sendThroughMail();
	
	Exemplo:
		->setFrom   ('a@b.c', "Seu nome");
		->setTo     ('!undisclosed-recipients');
		->setSubject('Assunto da sua mensagem');
		->setMessage('<b>Eu sou uma mesagem</b>') -- ou -- ->setMessageMulti("<b>Versão em HTML</b>", "Versão em texto puro");
		->addAuto   ('imagem.jpg', file_get_contents('imagem.jpg'));
		->getAsEML() ou;
		->getSendMailParams() ou;
		->sendThroughMail()
	
	To-do:
		--> Suporte a servidores DNS (para obter o MX e delivery direto)
		--> Suporte a e-mail em massa
		--> Mensagens amigáveis para debug
		--> Testar o DKIM
		--> Error Handling
		--> _replaceEmbedLinks considerar <style> e style=""
**/

class dSendMail3{
	var $to;
	var $cc;
	var $bcc;
	var $charset;
	var $headers;
	var $message;
	
	var $config;
	var $loadLevel;  // False, Partial, Full
	var $dkimSigned; // False
	var $debug;
	
	var $attachments;
	var $output;
	var $error;
	
	Function __construct(){
		$this->_reset();
		$this->setCharset('utf-8');
	}
	Function getVersion(){
		return '1.2';
	}
	
	// Set:
	Function setTo     ($email, $name=false){
		return $this->_setDestinations('To', $email, $name);
	}
	Function setCc     ($email, $name=false){
		return $this->_setDestinations('Cc', $email, $name);
	}
	Function setBcc    ($email, $name=false){
		return $this->_setDestinations('Bcc', $email, $name);
	}
	Function setDate   ($timestamp, $Time_In_GMT=false){
		$this->headers['Date'] = $Time_In_GMT?
			@gmdate('r', $timestamp): // Ex: Thu, 14 Oct 2010 18:00:00 -0000
			@date('r',   $timestamp);   // Ex: Thu, 14 Oct 2010 15:00:00 -0300
		
		return true;
	}
	Function setFrom   ($email, $name=false){
		$this->headers['From'] = $this->_normalizeEmail($email, self::_encodeString($name, $this->charset));
		return true;
	}
	Function setReplyTo($email, $name=false){
		$this->headers['Reply-To'] = $this->_normalizeEmail($email, $name);
		return true;
	}
	Function setReturnPath($email, $name=false){
		$this->headers['Return-Path'] = $this->_normalizeEmail($email, $name);
		return true;
	}
	Function setSender    ($email, $name=false){
		$this->headers['Sender'] = $this->_normalizeEmail($email, $name);
		return true;
	}
	Function setSubject($subject){
		$this->headers['Subject'] = self::_encodeString($subject, $this->charset);
		return true;
	}
	Function setHeader ($header, $value, $force=false){
		$header = strtolower(trim($header));
		if(!$force && in_array($header, Array('to', 'cc', 'bcc', 'date', 'from', 'reply-to', 'return-path', 'sender', 'subject', 'content-type'))){
			$this->_setError(0x1, "You MUST use set*** to customize '{$header}'. Otherwise, you'll have to use the FORCE parameter setHeader('{$header}', '{$value}', TRUE).");
			return false;
		}
		
		$header = explode("-", $header);
		$header = implode("-", array_map('ucfirst', $header));
		
		$this->headers[$header] = $value;
		return true;
	}
	Function setMessage($body, $html=true, $nl2br=false){
		if($this->loadLevel == 'PARTIAL'){
			// To-do:
			//   Perform a FULL-LOAD before change message contents.
			//   $this->loadFromEML();
			$this->_setError(0x8, "setMessage() can't continue, because message is not completely loaded yet.");
			return false;
		}
		
		if($html){
			$str_html = ($nl2br?nl2br($body):$body);
			$str_text = preg_replace("/<br.*?>/is", "\r\n", $body); // <br>     --> \r\n
			$str_text = preg_replace("/<\/?b>/i", "*", $str_text);  // <b>x</b> --> *x*
			$str_text = preg_replace("/<style.+?>.+<\/style>/is", "", $str_text);
			$str_text = strip_tags($str_text);
			$str_text = str_replace("\t", "", $str_text);
			$lines    = explode("\n", $str_text);
			$lines    = array_map('trim', $lines);
			$str_text = implode("\n", $lines);
			$str_text = preg_replace("/(\r?\n){2,}/", "\\1\\1", $str_text);
			$str_text = trim($str_text);
		}
		else{
			$str_html = false;
			$str_text = $body;
		}
		
		return $this->setMessageMulti($str_html, $str_text);
	}
	Function setMessageMulti($html_part, $text_part){
		if($this->loadLevel == 'PARTIAL'){
			// To-do:
			//   Perform a FULL-LOAD before change message contents.
			//   $this->loadFromEML();
			$this->_setError(0x8, "setMessageMulti() can't continue, because message is not completely loaded yet.");
			return false;
		}
		
		$this->message = Array('text'=>$text_part, 'html'=>$html_part);
		
		$this->_unMountMimeStructure();
		return true;
	}
	Function setCharset($charset){
		$tcharset = strtolower(str_replace("-", "", $charset));
		if($tcharset == 'utf' ||  $tcharset == 'utf8'){
			$charset = 'UTF-8';
		}
		elseif($tcharset == 'latin1' || $tcharset == 'iso8859' || $tcharset == 'iso88591' || $tcharset == 'iso88592'){
			$charset = 'iso-8859-1';
		}
		$this->charset = $charset;
	}
	
	// Add:
	Function addTo     ($email, $name=false){
		return $this->_addDestinations('To', $email, $name);
	}
	Function addCc     ($email, $name=false){
		return $this->_addDestinations('Cc', $email, $name);
	}
	Function addBcc    ($email, $name=false){
		return $this->_addDestinations('Bcc', $email, $name);
	}
	
	// Get:
	Function getTo(){
		// Array:
		//     Array($email, $name)
		//     Array($email, $name)
		//     Array($email, $name)
		return $this->to;
	}
	Function getCc(){
		return $this->cc;
	}
	Function getBcc(){
		return $this->bcc;
	}
	Function getDate(){
		return strtotime($this->headers['Date']);
	}
	Function getFrom(){
		return self::unNormalizeEmail($this->headers['From']);
	}
	Function getReplyTo(){
		return self::unNormalizeEmail($this->headers['Reply-To']);
	}
	Function getReturnPath(){
		return self::unNormalizeEmail($this->headers['Return-Path']);
	}
	Function getSender(){
		return self::unNormalizeEmail($this->headers['Sender']);
	}
	Function getSubject(){
		return self::unEncodeString($this->headers['Subject']);
	}
	Function getHeader($header=false){
		return $header?
			$this->headers[$header]:
			$this->headers;
	}
	Function getMessage($asType=false){
		if($asType && $asType != 'html' && $asType != 'text'){
			$this->_setError(0x2, "To use getMessage command, you may specify only false, 'html' or 'text' as parameter. You tried '{$asType}', which does not exist.");
			return false;
		}
		
		return $asType?
			$this->message[$asType]:
			$this->message;
	}
	Function getCharset(){
		return $this->charset;
	}
	
	// Attachments and Embeds:
	Function addAuto      ($filename, $filedata=false){
		if($filedata === false){
			$filedata = file_get_contents($filename);
			$filename = basename($filename);
		}
		$this->attachments[] = Array('name'=>$filename, 'data'=>$filedata, 'type'=>'auto', 'embed_id'=>false);
	}
	Function addEmbed     ($filename, $filedata=false){
		// Ignora arquivo se ele ja tiver sido incorporado:
		foreach($this->attachments as $exAttach){
			if($exAttach['name'] == $filename && ($exAttach['type'] == 'embed' || $exAttach['type'] == 'auto'))
				return false;
		}
		if($filedata === false){
			$filedata = file_get_contents($filename);
			$filename = basename($filename);
		}
		$this->attachments[] = Array('name'=>$filename, 'data'=>$filedata, 'type'=>'embed', 'embed_id'=>false);
		return true;
	}
	Function addAttachment($filename, $filedata=false){
		// Ignora arquivo se ele ja tiver sido anexado:
		foreach($this->attachments as $exAttach){
			if($exAttach['name'] == $filename && ($exAttach['type'] == 'attachment' || $exAttach['type'] == 'auto'))
				return false;
		}
		$this->attachments[] = Array('name'=>$filename, 'data'=>$filedata, 'type'=>'attachment', 'embed_id'=>false);
	}
	Function getAttachment($filename=false, $only_type=false, $as_array=true){
		if($only_type != false){
			if($only_type != 'auto' && $only_type != 'embed' && $only_type != 'attachment'){
				$this->_setError(0x3, "getAttachment(..., '$only_type'). Types allowed are false, 'auto', 'embed' or 'attachment'.");
				return false;
			}
		}
		
		$ret = Array();
		foreach($this->attachments as $idx=>$attach){
			if((!$filename  || $filename  == $attach['name']) && (!$only_type || $only_type == $attach['type']))
				$ret[] = &$this->attachments[$idx];
			if(!$as_array)
				return $ret[0];
		}
		return $ret;
	}
	Function clearAttachments(){
		$this->attachments = Array();
	}
	
	// Real computing modules
	Function loadFromHTML($message, $imagesDir=false){
		if($imagesDir){
			if(substr($imagesDir, -1) != '/')
				$imagesDir .= "/";
			
			$extensions = Array('gif', 'png', 'jpg', 'jpeg', 'bmp');
			preg_match_all('/(?:"|\')([^"\']+\.('.implode('|', $extensions).'))(?:"|\')/Ui', $message, $images);
			foreach($images[1] as $image){
				if(file_exists($imagesDir . $image)){
					$this->addEmbed($image, file_get_contents($imagesDir . $image));
				}
			}
		}
		
		$this->setMessage($message, true, false);
		return true;
	}
	Function loadFromEML ($filedata, $fullLoad=true){
		// Decodes an EML file so this class can handle its contents.
		// 
		// Possible loads:
		//   PARTIAL ($fullLoad = false)
		//   FULL    ($fullLoad = true)
		// 
		// Partial:
		//   Loads the header and part of the body.
		//   User may handle the headers and have access to raw $this->output['text']
		//   Useful for signing with DKIM without having to load everything into memory
		// 
		// Full:
		//   Loads the header and body.
		//   Mount all headers, attachments, like if the class was built from zero.
		//   (Not implemented yet)
		// 
		$this->_reset();
		
		//   1. Separa o HEADERS e o BODY dentro do $this->output
		$parts = explode("\r\n\r\n", $filedata, 2);
		if(sizeof($parts) != 2){
			echo "dSendMail3: loadFromEML() failed: Body or header was not found.\r\n";
			return false;
		}
		$this->output = Array('headers'=>rtrim($parts[0]), 'body'=>ltrim($parts[1]));
		
		//   2. Importa os cabeçalhos para $this->headers
		$this->_unMountHeaders();
		
		//   3. Separa os conteúdos MIME, e importe-os para os Arrays correspondentes
		$this->message = Array('text'=>$parts[1], 'html'=>false);
		$this->loadLevel = 'PARTIAL';
		
		return true;
	}
	Function loadFromMAIL($to, $subject, $body, $headers='', $addparam=false, $fullLoad=true){
		$headers .= rtrim($headers)."\r\n";
		$headers .= "To: ".self::_encodeString($to, $this->charset)."\r\n";
		$headers .= "Subject: ".self::_encodeString($subject, $this->charset);
		
		return $this->loadFromEML("{$headers}\r\n\r\n{$body}", $fullLoad);
	}
	
	// Export methods:
	Function getAsEML(){
		$this->_mountMimeStructure(); // Monta $this->output['body'];
		if(!$this->_mountHeaders()){  // Monta $this->output['headers'];
			return false;
		}
		
		return
			"{$this->output['headers']}\r\n".
			"\r\n".
			"{$this->output['body']}";
	}
	Function getSendMailParams(){
		/**
			Pelo menos em ambientes UNIX, não é possível informar
			os cabeçalhos "To" e "Subject" no parâmetro $headers, pois
			o PHP vai inserir qualquer coisa que venha nos parâmetros
			$to e $subject ANTES do seu cabeçalho.
			
			Além disso, o PHP também vai remover todas as linhas que contém
			"Bcc:", e utilizá-las apenas na conexão com os respectivos
			servidores de destino. Por isso, o "Bcc" DEVE ser utilizado como
			parâmetro em $headers.
		**/
		
		$this->_mountMimeStructure();                                     // Monta $this->output['body'];
		if(!$this->_mountHeaders(Array('To', 'Subject', 'Return-Path'))){ // Monta $this->output['headers'];
			return false;
		}
		
		// Monta parâmetros:
		$subj = isset($this->headers['Subject'])    ?$this->headers['Subject']    :false;
		$retu = isset($this->headers['Return-Path'])?$this->headers['Return-Path']:false;
		
		$ret = Array();
		$ret['to']       = $this->headers['To'];
		$ret['subject']  = $subj;
		$ret['headers']  = $this->output['headers'];
		$ret['body']     = $this->output['body'];
		$ret['addparam'] = "-f {$retu}";
		
		if($this->config['server_workaround']){
			$this->_applyServerWorkAround($ret['headers'], $ret['body']);
		}
		
		return $ret;
	}
	Function getAllReceivers($normalized=false){
		// Returns Array() containing all valid receivers, only e-mail addresses.
		// This will be a merge of To, Cc and Bcc.
		// 
		// If $normalized, it will be normalized as "John doe" <email@domain.com>
		
		$useTo   = Array();
		array_map(function($email) use (&$useTo, &$normalized){
			if($email[0][0] == '!')
				return;
			
			$useTo[] = $normalized?
				dSendMail3::_normalizeEmail($email[0], $email[1]):
				$email[0];
		}, array_merge($this->to, $this->cc, $this->bcc));
		
		return $useTo;
	}
	
	Function setServerWorkAround($wa){
		// Work-arounds known:
		//   POSTFIX_FORCE_NOT_CR:
		//     Por algum motivo desconhecido, o postfix duplica os <CR><LF> no conteúdo da mensagem,
		//     e também no cabeçalho, quando o mesmo começa com \t ou não possui : em sua separação.
		//     
		//     Sendo assim, esperando esse comportamento do postfix, nessas situações nós temos que
		//     remover os \r\n e deixar apenas \n, para que o postfix faça a conversão novamente.
		//
		$this->config['server_workaround'] = $wa;
	}
	Function _applyServerWorkAround(&$header, &$body){
		if($this->config['server_workaround'] == 'POSTFIX_FORCE_NOT_CR'){
			$header = preg_replace("/([^a-zA-Z0-9].+)\r\n/", "\\1\n", $header);
			$body   = str_replace("\r\n", "\n", $body);
		}
	}
	
	// Error handling:
	Function getErrorCode(){
		return $this->error?$this->error[0]:false;
	}
	Function getErrorStr(){
		return $this->error?$this->error[1]:false;
	}
	
	// DKIM Module
	Function setDKIM($private_key, $dns_prefix='mail'){
		if(!$private_key){
			$this->config['dkim_private_key'] = false;
			$this->config['dkim_dns_prefix']  = false;
			return true;
		}
		
		// Validação
		if(stripos($private_key, "-----BEGIN RSA PRIVATE KEY-----") === false){
			die(get_class($this).": setDKIM(...) falhou: Informe a sua chave privada completa, iniciando com '-----BEGIN RSA PRIVATE KEY-----'.\r\n");
		}
		if(!$dns_prefix){
			die(get_class($this).": setDKIM(...) falhou: Você não forneceu um prefixo válido para o servidor DNS. Ex: mail (no caso de mail._domainkey.dominio.com.br).\r\n");
		}

		$this->config['dkim_private_key'] = $private_key;
		$this->config['dkim_dns_prefix']  = $dns_prefix;
		return true;
	}
	
	// Sending helper
	private $sendThrough;
	Function setSendThrough($sendThrough){
		// --> setSendThrough('Mail') ........................................................ Send using internal MAIL() function
		// --> setSendThrough('Nowhere',  $errProb=0) ........................................ Simulate sending. $errProb from 0 to 1 (0-100%)
		// --> setSendThrough('GMail',    $username, $password) .............................. Send using default GMail   smtp settings
		// --> setSendThrough('Outlook',  $username, $password) .............................. Send using default Outlook smtp settings
		// --> setSendThrough('Yahoo',    $username, $password) .............................. Send using default Yahoo   smtp settings
		// --> setSendThrough('SMTP', $settings(Server,Port,Username,Password,SSL,Hostname)... Send using custom SMTP server settings
		// --> setSendThrough('AmazonSES',$APIKey,   $Secret, [, $Region='us-east-1']) ....... Send using Amazon SES
		// --> setSendThrough('Callback', $cbFunction($dsmObject)) ........................... User-defined sending method
		$sendThrough = strtoupper($sendThrough);
		
		if($sendThrough == 'MAIL'){
			$this->sendThrough = Array('Type'=>$sendThrough);
		}
		if($sendThrough == 'SMTP'){
			$this->sendThrough = Array(
				'Type'    =>$sendThrough,
				'Settings'=>func_get_arg(1),
			);
		}
		if($sendThrough == 'AMAZONSES'){
			$_args = func_get_args();
			$this->sendThrough = Array(
				'Type'  =>$sendThrough,
				'ApiKey'=>$_args[1],
				'Secret'=>$_args[2],
				'Region'=>sizeof($_args)>3?$_args[3]:false,
			);
		}
		if($sendThrough == 'CALLBACK'){
			$this->sendThrough = Array(
				'Type'      =>$sendThrough,
				'cbFunction'=>func_get_arg(1),
			);
		}
		
		// Predef. settings:
		if($sendThrough == 'NOWHERE'){
			$errProbability = (func_num_args()>1)?func_get_arg(1):0;
			$this->setSendThrough('CALLBACK', function($dsmObject) use ($errProbability){
				$dsmObject->_log("Sending through nowhere...");
				$dsmObject->_mountMimeStructure();
				if(!$dsmObject->_mountHeaders(Array('Bcc'))){
					$dsmObject->_log("Erro ao chamar _mountHeaders. Verifique ->getErrorStr().");
					return false;
				}
				
				$useFrom = dSendMail3::unNormalizeEmail($dsmObject->headers['From']);
				$dsmObject->_log("MAIL FROM: {$useFrom[0]}");
				$dsmObject->_log("RCPT TO: ".implode(', ', $dsmObject->getAllReceivers()));
				
				$sendOk = !(rand(0, 100) < ($errProbability*100));
				if(!$sendOk){
					$dsmObject->_setError(0x7, "sendThrough(Nowhere) sample error: errProbability was: {$errProbability}");
					return false;
				}
				
				return true;
			});
		}
		if($sendThrough == 'GMAIL'){
			$this->setSendThrough('SMTP', Array(
				'Server'  =>'smtp.gmail.com', 
				'Port'    =>'587',
				'SSL'     =>'TLS',
				'Username'=>@func_get_arg(1),
				'Password'=>@func_get_arg(2),
			));
		}
		if($sendThrough == 'OUTLOOK'){
			$this->setSendThrough('SMTP', Array(
				'Server'  =>'smtp-mail.outlook.com', 
				'Port'    =>'587',
				'SSL'     =>'TLS',
				'Username'=>@func_get_arg(1),
				'Password'=>@func_get_arg(2),
			));
		}
		if($sendThrough == 'YAHOO'){
			$this->setSendThrough('SMTP', Array(
				'Server'  =>'smtp.mail.yahoo.com', 
				'Port'    =>'465',
				'SSL'     =>'SSL',
				'Username'=>@func_get_arg(1),
				'Password'=>@func_get_arg(2),
			));
		}
	}
	Function sendThroughMail(){
		$info = $this->getSendMailParams();
		return mail($info['to'], $info['subject'], $info['body'], $info['headers'], $info['addparam']);
	}
	Function sendThroughSMTP($settings){
		if(func_num_args() > 1){
			$settings = Array(
				'Server'  =>func_get_arg(0),
				'Port'    =>func_get_arg(1),
			);
			if(func_num_args() >= 3){
				$settings['Username'] = func_get_arg(2);
				$settings['Password'] = func_get_arg(3);
			}
			if(func_num_args() >= 5){
				$settings['SSL']  = func_get_arg(4);
			}
		}
		
		//$this->_log("Enviando através de SMTP");
		$settings += Array(
			'Server'  =>false, 
			'Port'    =>25,
			'SSL'     =>false,
			'Username'=>false,
			'Password'=>false,
			'Hostname'=>(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:"localhost"),
			'Timeout' =>30,
		);
		extract($settings);
		
		$SSL  = strtoupper($SSL);
		$smtp = new smtp_class;
		$smtp->host_name      = $Server;
		$smtp->host_port      = $Port;
		$smtp->ssl            = ($SSL==='SSL'||$SSL=='1'||$SSL===true);
		$smtp->start_tls      = ($SSL==='TLS'||$SSL=='2');
		$smtp->localhost      = $Hostname;
		$smtp->timeout        = $Timeout;
		$smtp->data_timeout   = 0;
		$smtp->debug          = $this->debug;
		$smtp->html_debug     = false;
		$smtp->pop3_auth_host = "";
		$smtp->user           = $Username;
		$smtp->password       = $Password;
		$smtp->realm          = false;
		
		$this->_mountMimeStructure();            // Monta $this->output['body'];
		if(!$this->_mountHeaders(Array('Bcc'))){ // Monta $this->output['headers'], removendo o Bcc
			return false;
		}
		
		$useFrom = self::unNormalizeEmail($this->headers['From']);
		$useTo   = $this->getAllReceivers();
		$ok      = $smtp->SendMessage($useFrom[0], $useTo, $this->output['headers'], $this->output['body']);
		if(!$ok){
			$this->_setError(0x7, $smtp->error);
			return false;
		}
		
		return true;
	}
	Function sendThroughAmazon($apiKey, $secret, $region='us-east-1'){
		// Tem que ser PHP5.5 ou maior.
		if(phpversion() < 5.5){
			$errorSubject  = "Alerta dSendMail3: Não é possível usar a AmazonSES na versão ".phpversion();
			$errorMessage  = "AWS SDK for PHP é compatível com PHP5.5 ou maior.\r\n";
			
			if(class_exists('dSystem')){
				dSystem::notifyAdmin('HIGH', $errorSubject, $errorMessage, true);
				die;
			}
			echo "<b style='color: #F00'>{$errorSubject}</b><br />";
			echo "<div style='padding-left: 8px; margin-left: 8px; border-left: 2px solid #F00'>";
			echo nl2br($errorMessage);
			echo "</div>";
			die;
		}
		
		// A classe existe?
		if(!class_exists('Aws\\Ses\\SesClient')){
			if(!file_exists(__DIR__ . "/aws-sdk.phar")){
				$errorSubject  = "Alerta dSendMail3: AWS SDK for PHP não encontrado.";
				$errorMessage  = "O sistema pediu para enviar via AmazonSES, mas a classe não foi encontrada.\r\n";
				$errorMessage .= "1) Adicione o arquivo aws-sdk.phar junto com dSendMail3, ou;\r\n";
				$errorMessage .= "2) Inclua o AWS SDK for PHP manualmente antes de chamar ->send(), e tente novamente.\r\n";
				
				if(class_exists('dSystem')){
					dSystem::notifyAdmin('HIGH', $errorSubject, $errorMessage, true);
					die;
				}
				echo "<b style='color: #F00'>{$errorSubject}</b><br />";
				echo "<div style='padding-left: 8px; margin-left: 8px; border-left: 2px solid #F00'>";
				echo nl2br($errorMessage);
				echo "</div>";
				die;
			}
			
			require_once "aws-sdk.phar";
		}
		
		// Validações de preenchimento:
		if(!$apiKey || !$secret){
			$this->_setError(0x9, "Não é possível enviar pela Amazon sem informar um conjunto APIKey e Secret.");
			return false;
		}
		
		$creden = new Aws\Credentials\Credentials($apiKey, $secret, null, time() + 60*60*24);
		$client = new Aws\Ses\SesClient(Array(
			'version'     => '2010-12-01',
			'region'      => $region,
			'credentials' => $creden,
			'verify'      => false, // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html
			'debug'       => $this->debug,
			'http'        => Array(
				'connect_timeout'=>5,
			),
		));
		
		$useTo = $this->getAllReceivers(true);                    // Retorna lista no padrão "Destinatário" <email@dominio.com>
		$this->_mountMimeStructure();                             // Monta $this->output['body'];
		$this->_mountHeaders(Array('Bcc', 'Date', 'Message-Id')); // Monta $output['headers']. Tem que ser chamada depois de _mountMimeStructure
		$useFrom = self::unNormalizeEmail($this->headers['From']);
		
		if(sizeof($useTo) > 50){
			echo "De acordo com a documentação da AWS, o limite máximo por envio são 50 destinatários.<br />";
			die;
		}
		
		$rawMessage = "{$this->output['headers']}\n\n{$this->output['body']}";
		$sendParams = Array(
		    'Destinations' => $useTo,
			'Source'       => $useFrom[0],
		    'RawMessage'   => Array(
		    	'Data'=>$rawMessage,
		    ),
		);
		
		try{
			
			$client->sendRawEmail($sendParams);
			return true;
		}
		catch(Aws\Ses\Exception\SesException $e){
			$this->_setError($e->getCode(), $e->getMessage());
			return false;
		}
	}
	Function send(){
		if($this->sendThrough['Type'] == 'MAIL'){
			return $this->sendThroughMail();
		}
		if($this->sendThrough['Type'] == 'SMTP'){
			return $this->sendThroughSMTP($this->sendThrough['Settings']);
		}
		if($this->sendThrough['Type'] == 'AMAZONSES'){
			return ($this->sendThrough['Region'])?
				$this->sendThroughAmazon($this->sendThrough['ApiKey'], $this->sendThrough['Secret'], $this->sendThrough['Region']):
				$this->sendThroughAmazon($this->sendThrough['ApiKey'], $this->sendThrough['Secret']);
		}
		if($this->sendThrough['Type'] == 'CALLBACK'){
			return call_user_func($this->sendThrough['cbFunction'], $this);
		}
		
		return false;
	}
	
	// Static helpers:
	static public Function unNormalizeEmail($email){
		$nome  = preg_replace("/(.+) .+?$/", "\\1", $email);
		$email = preg_replace("/.+ /", "",          $email);
		$nome  = preg_replace("/^['\"]+/", "",      $nome);
		$nome  = preg_replace("/['\"]+$/", "",      $nome);
		$email = preg_replace("/^</", "",           $email);
		$email = preg_replace("/>$/", "",           $email);
		
		return Array(trim($email), ($nome==$email)?false:trim(self::unEncodeString($nome)));
	}
	static public Function unEncodeString($string){
		$latin1Encs = Array('iso88592', 'iso88591', 'iso8859', 'latin1');
		$utf8Encs   = Array('utf8', 'utf-8');
		
		if(preg_match_all("/=\?(.+?)\?([QB])\?(.+?)\?=/", $string, $out)){
			foreach($out[0] as $idx=>$matchStr){
				$enc  = strtolower(str_replace("-", "", $out[1][$idx])); // Strips all '-'. iso-8859-1 goes to iso8859, utf-8 goes to utf8.
				$type = $out[2][$idx];
				$str  = $out[3][$idx];
				
				if($type == 'Q'){
					// Quote? Simple encoding.
					if(in_array($enc, $latin1Encs)){
						$str = preg_replace_callback("/=(..)/", function($str){ return chr(hexdec($str[1])); }, $str);
					}
					elseif(in_array($enc, $utf8Encs)){
						$str = preg_replace_callback("/=(..)/", function($str){ return chr(hexdec($str[1])); }, $str);
					}
					else{
						echo "dSendMail3: Cannot handle encoding {$enc}. Impossible to decode message.\r\n";
					}
				}
				elseif($type == 'B'){
					// Base64
					if(in_array($enc, $latin1Encs)){
						$str = base64_decode($str);
					}
					elseif(in_array($enc, $utf8Encs)){
						$str = base64_decode($str);
					}
					else{
						echo "dSendMail3: Cannot handle encoding {$enc}. Impossible to decode message.\r\n";
					}
				}
				$string = str_replace($matchStr, $str, $string);
			}
		}
		
		return $string;
	}
	
	// Private helpers:
	Function _reset(){
		$this->to = $this->cc = $this->bcc       = Array();
		$this->error          = $this->loadLevel = false;
		
		$this->output  = Array('headers'=>false, 'body'=>false);
		$this->message = Array('text'=>false,    'html'=>false);
		$this->attachments = 
		$this->headers     = 
		$this->config      = Array();
		$this->config['remove_dupes']        = true;
		$this->config['autofill_date']       = true;
		$this->config['autofill_replyto']    = true;
		$this->config['autofill_returnpath'] = true;
		$this->config['autofill_sender']     = true;
		$this->config['autofill_message_id'] = true;
		$this->config['server_workaround']   = false;
		$this->config['dkim_private_key']    = false;
		$this->config['dkim_dns_prefix']     = false;
		
		$this->setSendThrough('Mail');
	}
	
	Function _mountMimeStructure(){               // Monta output['body']. Precisa ser chamado antes de _mountHeaders().
		if($this->loadLevel == 'PARTIAL'){
			// Cannot replace existing body contents.
			# echo ":: Cannot replace existing body contents.<br />";
			return false;
		}
		if($this->output['body'] !== false){
			# echo ":: Returning cached.<br />";
			return $this->output;
		}
		
		# echo ":: Generating.<br />";
		$this->_replaceEmbedLinks();
		
		$isPlainText   = ($this->message['html'] == false);
		$hasAttachment = $this->attachments;
		$hasEmbed      = $this->_hasEmbed();
		$hasOnlyOne    = ( $hasAttachment && !$hasEmbed) || (!$hasAttachment && $hasEmbed);
		$hasBoth       = ( $hasAttachment &&  $hasEmbed);
		$hasNone       = (!$hasAttachment && !$hasEmbed);
		
		$B_Pri = uniqid('aa');
		$B_Alt = uniqid('bb');
		$B_Lnk = uniqid('cc');
		
		$body    = Array();
		
		if($this->loadLevel == 'PARTIAL'){
			// Content is already built, can't touch it.
			$body[]    = $this->message['text'];
		}
		elseif($isPlainText && $hasNone){
			// Plain text, nothing to do...
			$this->headers['Content-Type'] = "text/plain; charset=\"{$this->charset}\"";
			$body[]    = $this->message['text'];
		}
		elseif($isPlainText && $hasAttachment){
			// Plain text with attachments...
			$this->headers['Content-Type'] = "multipart/mixed; boundary=\"{$B_Pri}\"";
			$body[] = "This is a multi-part message in MIME format.";
			$body[] = "";
			$body[] = "--{$B_Pri}";
			$body[] = "Content-Type: text/plain; charset=\"{$this->charset}\"";
			$body[] = "Content-Transfer-Encoding: 7bit";
			$body[] = "";
			$body[] = $this->_encodeAs($this->message['text'], '7bit');
			
			// Para cada anexo:
			foreach($this->attachments as $att){
				$body[] = "--{$B_Pri}";
				$body[] = "Content-Type: ".$this->_getMimeType($att['name'])."; name=\"{$att['name']}\"";
				$body[] = "Content-Transfer-Encoding: base64";
				$body[] = "Content-Disposition: attachment; filename=\"{$att['name']}\"";
				$body[] = "";
				$body[] = $this->_encodeAs($att['data'], 'base64');
				$body[] = "";
			}
			$body[] = "--{$B_Pri}--";
			$body[] = "";
		}
		elseif(!$isPlainText && $hasNone){
			$this->headers['Content-Type'] = "multipart/alternative; boundary=\"{$B_Pri}\"";
			$body[] = "This is a multi-part message in MIME format.";
			$body[] = "";
			$body[] = "--{$B_Pri}";
			$body[] = "Content-Type: text/plain; charset=\"{$this->charset}\"";
			if($this->debug){
				$body[] = "";
				$body[] = $this->message['text'];
			}
			else{
				$body[] = "Content-Transfer-Encoding: quoted-printable";
				$body[] = "";
				$body[] = $this->_encodeAs($this->message['text'], 'quoted-printable');
			}
			$body[] = "--{$B_Pri}";
			$body[] = "Content-Type: text/html; charset=\"{$this->charset}\"";
			if($this->debug){
				$body[] = "";
				$body[] = $this->message['html'];
			}
			else{
				$body[] = "Content-Transfer-Encoding: quoted-printable";
				$body[] = "";
				$body[] = $this->_encodeAs($this->message['html'], 'quoted-printable');
			}
			$body[] = "";
			$body[] = "--{$B_Pri}--";
			$body[] = "";
		}
		else{
			// É HTML, e tem ANEXO OU EMBED.
			if($hasNone)    $B_Lnk = $B_Alt = $B_Pri;
			if($hasOnlyOne) $B_Lnk = $B_Alt;
			
			$this->headers['Content-Type'] = "multipart/mixed; boundary=\"{$B_Pri}\"";
			$body[] = "This is a multi-part message in MIME format.";
			$body[] = "";
			
			$body[] = "--{$B_Pri}";
			if(!$hasNone){
				if($hasBoth){
					$body[] = "Content-Type: multipart/related; type=\"multipart/alternative\"; boundary=\"{$B_Alt}\"";
					$body[] = "";
					$body[] = "";
					$body[] = "--{$B_Alt}";
				}
				$body[] = "Content-Type: multipart/alternative; boundary=\"{$B_Lnk}\"";
				$body[] = "";
				$body[] = "";
				$body[] = "--{$B_Lnk}";
			}
			
			$body[] = "Content-Type: text/plain; charset=\"{$this->charset}\"";
			if($this->debug){
				$body[] = "";
				$body[] = $this->message['text'];
			}
			else{
				$body[] = "Content-Transfer-Encoding: quoted-printable";
				$body[] = "";
				$body[] = $this->_encodeAs($this->message['text'], 'quoted-printable');
			}
			$body[] = "--{$B_Lnk}";
			$body[] = "Content-Type: text/html; charset=\"{$this->charset}\"";
			if($this->debug){
				$body[] = "";
				$body[] = $this->message['html'];
			}
			else{
				$body[] = "Content-Transfer-Encoding: quoted-printable";
				$body[] = "";
				$body[] = $this->_encodeAs($this->message['html'], 'quoted-printable');
			}
			$body[] = "";
			
			if(!$hasNone){
				$body[] = "--{$B_Lnk}--";
				$body[] = "";
				
				// Para cada EMBED:
				foreach($this->attachments as $att){
					if(!$att['embed_id'])
						continue;
					
					$body[] = "--{$B_Alt}";
					$body[] = "Content-Type: ".$this->_getMimeType($att['name'])."; name=\"{$att['name']}\"";
					$body[] = "Content-Transfer-Encoding: base64";
					$body[] = "Content-ID: <{$att['embed_id']}>";
					$body[] = "";
					$body[] = $this->_encodeAs($att['data'], 'base64');
					$body[] = "";
				}
				
				if($hasBoth){
					$body[] = "--{$B_Alt}--";
					$body[] = "";
				}
				
				// Para cada ATTACHMENT
				foreach($this->attachments as $att){
					if($att['embed_id'])
						continue;
					
					$body[] = "--{$B_Pri}";
					$body[] = "Content-Type: ".$this->_getMimeType($att['name'])."; name=\"{$att['name']}\"";
					$body[] = "Content-Transfer-Encoding: base64";
					$body[] = "Content-Disposition: attachment; filename=\"{$att['name']}\"";
					$body[] = "";
					$body[] = $this->_encodeAs($att['data'], 'base64');
					$body[] = "";
				}
			}
			
			$body[] = "--{$B_Pri}--";
			$body[] = "";
		}
		
		$this->output['body'] = implode("\r\n", $body);
		return $this->output;
	}
	Function _mountHeaders($ignoreHeaders=false){ // Monta ->headers['To', 'Cc', 'Bcc', 'Date', etc., sem exceção] + ->output['headers'] (com os filtros pertinentes)
		if(!$ignoreHeaders){
			$ignoreHeaders = Array();
		}
		if($this->config['remove_dupes']){
			$this->_removeDupes();
		}
		
		// Assume default values and mount to $this->output['headers']
		if(!in_array('From',    $ignoreHeaders) && !isset($this->headers['From'])){
			$this->_setError(0x4, "You MUST define a SENDER for your message. Use setFrom.");
			return false;
		}
		if(!in_array('Subject', $ignoreHeaders) && !isset($this->headers['Subject'])){
			$this->_setError(0x5, "You MUST define a subject for your message. Use setSubject.");
			return false;
		}
		if(!$this->message['html'] && !$this->message['text']){
			$this->_setError(0x6, "You MUST define your message using setMessage.");
			return false;
		}
		
		if(!isset($this->headers['MIME-Version']) && $this->message['html']){
			$this->headers['MIME-Version'] = '1.0';
		}
		
		// Padronizando To, Cc e Bcc:
		// $this->headers['To'] = Array:
		//     Array(email, nome)
		//     Array(email, nome)
		//     Array(email, false)
		//     Array(email, false)
		//     Array(email, nome)
		//     Array(!email, false)
		//     Array(!email, nome)
		foreach(Array('to', 'cc', 'bcc') as $_to){
			// Se existir $this->headers[To], ignore $this->to.
			if(isset($this->headers[ucfirst($_to)]) && $this->headers[ucfirst($_to)] !== false){
				continue;
			}
			if(!$this->$_to){
				continue;
			}
			
			$_list  = Array();
			foreach($this->$_to as $__to){
				$_email = $__to[0];
				$_name  = $__to[1];
				if($_email[0] == '!'){
					$_email = substr($_email, 1);
				}
				
				$_list[] = $this->_normalizeEmail($_email, $_name);
			}
			$this->setHeader(ucfirst($_to), implode(",\n\t", $_list), true);
		}
		if(false){
		if((!isset($this->headers['To'])  || ($this->headers['To'] ==false)) && $this->to){
			$tempList = Array();
			foreach($this->to as $to){
				if($to[0][0] == "!")
					$to[0] = substr($to[0], 1);
				$tempList[] = $this->_normalizeEmail($to[0], false);
			}
			$this->setHeader('To', implode(", ", $tempList), true);
		}
		if((!isset($this->headers['Cc'])  || ($this->headers['Cc'] ==false)) && $this->cc){
			$tempList = Array();
			foreach($this->cc as $cc){
				$tempList[] = $this->_normalizeEmail($cc[0], $cc[1]);
			}
			$this->setHeader('Cc', implode(", ", $tempList), true);
		}
		if((!isset($this->headers['Bcc']) || ($this->headers['Bcc']==false)) && $this->bcc){
			$tempList = Array();
			foreach($this->bcc as $bcc){
				$tempList[] = $this->_normalizeEmail($bcc[0], $bcc[1]);
			}
			$this->setHeader('Bcc', implode(", ", $tempList), true);
		}
		}
		if($this->config['autofill_date']       && !isset($this->headers['Date'])){
			$this->setDate(time());
		}
		$_from = $this->getFrom();
		$_from = array_shift($_from);
		if($this->config['autofill_replyto']    && !isset($this->headers['Reply-To'])){
			$this->setReplyTo($_from);
		}
		if($this->config['autofill_returnpath'] && !isset($this->headers['Return-Path'])){
			$this->setReturnPath($_from);
		}
		if($this->config['autofill_sender']     && !isset($this->headers['Sender'])){
			$this->setSender($_from);
		}
		if($this->config['autofill_message_id'] && !isset($this->headers['Message-Id'])){
			$fromDomain = preg_replace("/.+@/", "", $_from);
			$this->setHeader('Message-Id', '<'.date('Ymdhis').".".strtoupper(uniqid())."@{$fromDomain}".'>');
		}
		
		$this->_signDKIM();
		
		// Some headers SHOULD follow this order:
		//   * Exception is the mail function, which modifies the header out of our control.
		$headers      = Array();
		$firstHeaders = Array('MIME-Version', 'From', 'To', 'Date', 'Subject');
		$lastHeaders  = Array('Content-Type', 'Message-Id', 'DKIM-Signature', 'DomainKey-Signature');
		
		// First headers:
		foreach($firstHeaders as $item){
			$inIgnore = in_array($item, $ignoreHeaders);
			if(!$inIgnore && isset($this->headers[$item]))
				$headers[] = "{$item}: {$this->headers[$item]}";
		}
		
		// Middle headers:
		foreach($this->headers as $item=>$value){
			$inIgnore = in_array($item, $ignoreHeaders);
			$inFirst  = in_array($item, $firstHeaders);
			$inLast   = in_array($item, $lastHeaders);
			if(!$inIgnore && !$inFirst && !$inLast)
				$headers[] = "{$item}: {$value}";
		}
		
		// Last headers:
		foreach($lastHeaders as $item){
			$inIgnore = in_array($item, $ignoreHeaders);
			if(!$inIgnore && isset($this->headers[$item]))
				$headers[] = "{$item}: {$this->headers[$item]}";
		}
		
		$this->output['headers'] = implode("\n", $headers);
		return true;
	}
	
	Function _log($msg){
		echo "Debug: {$msg}\n";
	}
	
	Function _replaceEmbedLinks(){
		if(!$this->attachments)
			return true;
		
		foreach($this->attachments as $idx=>$att){
			if($att['type'] == 'attachment'){
				continue;
			}
			if($att['embed_id'] != false){
				continue;
			}
			
			$uniqid = uniqid('ii_');
			
			// Processa <img src=""> <td background=""> ou qualquer outro atributo
			$regex  = "/(<[^>]+[=\"'])".preg_quote($att['name'], '/')."([^>]*>)/";
			if(preg_match($regex, $this->message['html'])){
				$this->message['html']      = preg_replace($regex, "\\1cid:{$uniqid}\\2", $this->message['html']);
				$this->attachments[$idx]['embed_id'] = $uniqid;
			}
			
			// Processa background-image: url(xxxx.jpg) -- Ps: Precisa checar se está dentro do atributo style="" ou <style>
			$regex  = "/(background-image: url\('?)".preg_quote($att['name'], '/')."('?\))/";
			
			if(preg_match($regex, $this->message['html'])){
				$this->message['html'] = preg_replace($regex, "\\1cid:{$uniqid}\\2", $this->message['html']);
				$this->attachments[$idx]['embed_id'] = $uniqid;
			}
			
			// To-Do:
			// - Permitir style="background-image: xxxx.jpg"
			// - Permitir style="cursor: url(xxxx.jpg)"
			// - Revisar também dentro de <style>
		}
		
		return true;
	}
	Function _hasEmbed(){
		if($this->attachments) foreach($this->attachments as $att)
			if($att['embed_id'])
				return true;
		return false;
	}
	
	Function _setDestinations($type, $email, $name){
		$type = strtolower($type);
		if(!in_array($type, Array('to', 'cc', 'bcc'))){
			return false;
		}
		
		$this->$type = Array();
		$this->_addDestinations($type, $email, $name);
	}
	Function _addDestinations($type, $email, $name){
		// Expected:
		//   $type  should be 'to', 'cc', 'bcc'
		//   $email should be either:
		//     a) A string to pair with $name ($email, $name)
		//     b) An array[], each being:
		//        b1) String $email
		//        b2) Array($email, $name)
		//        
		$type = strtolower($type);
		if(!in_array($type, Array('to', 'cc', 'bcc'))){
			return false;
		}
		if(!is_array($email)){
			$email = Array(Array($email, $name));
		}
		
		// Removes any conflictant information inside the headers.
		unset($this->headers[ucfirst($type)]);
		
		// Standardize the $email to $list.
		// On $email, the $name part is optional. On $list, it WILL certainly exists.
		$list = Array();
		foreach($email as $item){
			$list[] = is_string($item)?
				Array($item, false):
				Array(trim($item[0]), (sizeof($item)>1)?$item[1]:false);
		}
		
		// Append list to $this->to, cc, bcc
		$this->$type = array_merge($this->$type?$this->$type:Array(), $list);
		
		return true;
	}
	Function _removeDupes(){
		$nRemoved = 0;
		if($this->to || $this->cc || $this->bcc){
			$list   = Array();
			$remove = Array();
			foreach(Array('to', 'cc', 'bcc') as $type){
				if(!$this->$type)
					continue;
				
				foreach($this->$type as $idx=>$item){
					$tryMail = strtolower($item[0]);
					if(in_array($tryMail, $list)){
						$remove[$type][] = $idx;
						continue;
					}
					$list[] = $tryMail;
				}
			}
			foreach($remove as $type=>$removeList){
				foreach($removeList as $idx){
					unset($this->{$type}[$idx]);
					$nRemoved++;
				}
			}
		}
		
		return $nRemoved;
	}
	Function _normalizeEmail($email, $name=false, $ignore_name=false){
		if($ignore_name || $name === false)
			return $email;
		
		if(!strlen($name))
			return "<{$email}>";
		
		if(!preg_match("/[<>'\",;]/", $name))
			return self::_encodeString($name, $this->charset).' <'.$email.'>';
		
		return '"'.self::_encodeString($name, $this->charset).'" <'.$email.'>';
	}
	static Function _encodeString($string, $charset){
		preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $string, $matches);
        foreach ($matches[1] as $value){
            $replacement = preg_replace_callback('/([\x20\x80-\xFF])/',  function($ret){
                return "=".strtoupper(dechex(ord($ret[1])));
            }, $value);
            $string      = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $string);
        }
        
        return $string;
	}
	Function _encodeAs($str, $as){
		if($as == '7bit' || $as == '8bit'){
			return $str;
		}
		if($as == 'base64'){
			return rtrim(chunk_split(base64_encode($str), 76, "\r\n"));
		}
		if($as == 'quoted-printable'){
			$line_max = 71;
			$lines  = preg_split("/\r?\n/", $str);
			$eol    = "\r\n";
			$escape = '=';
			$output = '';
			
			while(list(, $line) = each($lines)){
				$linlen     = strlen($line);
				$newline = '';
				
				for ($i = 0; $i < $linlen; $i++){
					$char = substr($line, $i, 1);
					$dec  = ord($char);
					
					if (($dec == 32) AND ($i == ($linlen - 1))){    // convert space at eol only
						$char = '=20';
					}
					elseif($dec == 9){
						// Do nothing if a tab.
					}
					elseif(($dec == 61) || ($dec < 32 ) || ($dec > 126)){
						$char = $escape . strtoupper(sprintf('%02s', dechex($dec)));
					}
					
					if ((strlen($newline) + strlen($char)) >= $line_max){        // MAIL_MIMEPART_CRLF is not counted
						$output  .= $newline . $escape . $eol;                    // soft line break; " =\r\n" is okay
						$newline  = '';
					}
					$newline .= $char;
				}
				$output .= $newline . $eol;
			}
			$output = substr($output, 0, -1 * strlen($eol)); // Don't want last crlf
			return $output;
		}
		return "?????";
	}
	
	Function _unMountHeaders(){
		$ret     = Array();
		$lastKey = false;
		$lines   = explode("\r\n", $this->output['headers']);
		foreach($lines as $line){
			$appendPrev = ($lastKey && ($line[0] == ' ' || $line[0] == "\t" || !strpos($line, ': ')));
			if($appendPrev){
				$this->headers[$lastKey] .= "\r\n".$line;
				continue;
			}
			$parts   = explode(": ", $line, 2);
			$lastKey = $parts[0];
			$this->headers[$lastKey] = $parts[1];
		}
		
		foreach(Array('To', 'Cc', 'Bcc') as $item){
			if(isset($this->headers[$item])){
				$list = explode(",", $this->headers[$item]);
				for($x = 0; $x < sizeof($list); $x++){
					$list[$x] = self::unNormalizeEmail($list[$x]);
				}
				$this->_setDestinations($item, $list, false);
			}
		}
	}
	Function _unMountMimeStructure(){
		// Remove all 'embed_id' from embed attachments.
		foreach($this->attachments as $idx=>$att){
			if($att['type'] != 'attachment'){
				$this->attachments[$idx]['embed_id'] = false;
			}
		}
		
		// Clear body cache.
		$this->output['body'] = false;
	}
	
	Function _getMimeType($filename){
		$mimeTable   = Array();
		$mimeTable[] = "xls,xlsx:application/excel";
		$mimeTable[] = "doc,docx:application/msword";
		$mimeTable[] = "ppt,pptx:application/powerpoint";
		$mimeTable[] = "pdf:application/pdf";
		$mimeTable[] = "rtf:application/rtf";
		$mimeTable[] = "zip:application/zip";
		$mimeTable[] = "tgz:application/x-gtar";
		$mimeTable[] = "gz:application/x-gzip";
		$mimeTable[] = "js:application/x-javascript";
		$mimeTable[] = "ppd,psd:application/x-photoshop";
		$mimeTable[] = "swf,swc:application/x-shockwave-flash";
		$mimeTable[] = "mid,midi,kar:audio/midi";
		$mimeTable[] = "mp3:audio/mpeg";
		$mimeTable[] = "ra:audio/x-realaudio";
		$mimeTable[] = "wav:audio/wav";
		$mimeTable[] = "gif:image/gif";
		$mimeTable[] = "png:image/png";
		$mimeTable[] = "bmp:image/bitmap";
		$mimeTable[] = "jpg,jpeg:image/jpeg";
		$mimeTable[] = "tif,tiff:image/tiff:";
		$mimeTable[] = "css:text/css";
		$mimeTable[] = "txt:text/plain";
		$mimeTable[] = "htm,html:text/html";
		$mimeTable[] = "xml:text/xml";
		$mimeTable[] = "mpg,mpeg:video/mpeg";
		$mimeTable[] = "qt,mov:video/quicktime";
		$mimeTable[] = "avi:x-ms-video";
		$mimeTable[] = "eml:message/rfc822";
		
		$ext = preg_replace("/.*\./", "", $filename);
		foreach($mimeTable as $mime){
			$mime = explode(":", $mime);
			$exts = explode(",", $mime[0]);
			foreach($exts as $e)
				if($ext == $e)
					return $mime[1];
		}
		return "application/octet-stream";
	}
	
	Function _clearError(){
		$this->error = false;
		return true;
	}
	Function _setError($code, $explanation){
		if($this->debug){
			echo "<b>dSendMail3:</b> Setting error($code): {$explanation}<br />\r\n";
		}
		$this->error = Array($code, $explanation);
		return true;
	}

	/** Private DKIM **/
	Function _signDKIM(){
		// Carrega todas as variáveis necessárias e adiciona a assinatura ao cabeçalho.
		if(!$this->config['dkim_private_key'])
			return false;
		
		$DKIM_a  = 'rsa-sha1';       // Signature & hash algorithms
		$DKIM_c  = 'relaxed/simple'; // Canonicalization of header/body
		$DKIM_q  = 'dns/txt';        // Query method
		$DKIM_t  = time() ;          // Signature Timestamp = number of seconds since 00:00:00 on January 1, 1970 in the UTC time zone
		
		$subject_header = "Subject: {$this->headers['Subject']}";
		$from_header    = "From: {$this->headers['From']}";
		$to_header      = "To: {$this->headers['To']}";
		$body           = $this->output['body'];
		
		$DKIM_s  = $this->config['dkim_dns_prefix'];
		$DKIM_d  = preg_replace("/.+@/", "", array_shift($this->getFrom()));
		$DKIM_i  = "@{$DKIM_d}";
		
		$from    = str_replace('|','=7C', $this->_DKIMQuotedPrintable($from_header)) ;
		$to      = str_replace('|','=7C', $this->_DKIMQuotedPrintable($to_header)) ;
		$subject = str_replace('|','=7C', $this->_DKIMQuotedPrintable($subject_header)) ; // Copied header fields (dkim-quoted-printable
		$body    = $this->_DKIMSimpleBodyCanonicalization($body);
		$DKIM_l  = strlen($body) ; // Length of body (in case MTA adds something afterwards)
		$DKIM_bh = base64_encode(pack("H*", sha1($body))) ; // Base64 of packed binary SHA-1 hash of body
		$i_part  = ($DKIM_i == '')? '' : " i=$DKIM_i;" ;
		$b       = '' ; // Base64 encoded signature
		$dkim    = "DKIM-Signature: v=1; a={$DKIM_a}; q={$DKIM_q}; l={$DKIM_l}; s={$DKIM_s};\r\n".
			"\tt={$DKIM_t}; c={$DKIM_c};\r\n".
			"\th=From:To:Subject;\r\n".
			"\td={$DKIM_d};{$i_part}\r\n".
			"\tz={$from}\r\n".
			"\t|{$to}\r\n".
			"\t|{$subject};\r\n".
			"\tbh={$DKIM_bh};\r\n".
			"\tb=";
			
		$to_be_signed = $this->_DKIMRelaxedHeaderCanonicalization("{$from_header}\r\n{$to_header}\r\n{$subject_header}\r\n{$dkim}");
		$b            = $this->_DKIMBlackMagic($to_be_signed) ;
		
		$this->headers['DKIM-Signature'] = substr($dkim, 16).$b;
	}
	Function _DKIMBlackMagic($str){
		if(!function_exists('openssl_sign')){
			return "[HASH:".md5($str)."]";
		}
		if(openssl_sign($str, $signature, $this->config['dkim_private_key']))
			return base64_encode($signature) ;
		else
			die(get_class($this).": setDKIM() - Não foi possivel assinar a mensagem com a chave fornecida.") ;
	}
	Function _DKIMQuotedPrintable($txt){
		// Retorna apenas caracteres imprimíveis no $txt.
		// Caracteres aceitos:
		//   !"#$%&'()*+,-./01...9:
		//   <>?@ABC...Z?\]^_`abc...z{|}~
		
		$tmp  = "";
		$line = "";
		for($i = 0; $i < strlen($txt); $i++){
			$ord = ord($txt[$i]);
			if(((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E))){
				$line .= $txt[$i];
			}
			else{
				$line .= "=".sprintf("%02X",$ord);
			}
		}
		return $line;
	}
	Function _DKIMRelaxedHeaderCanonicalization($s){
		// First unfold lines
		$s=preg_replace("/\r\n\s+/"," ",$s) ;
		// Explode headers & lowercase the heading
		$lines=explode("\r\n",$s) ;
		foreach ($lines as $key=>$line){
			list($heading,$value)=explode(":",$line,2) ;
			$heading=strtolower($heading) ;
			$value=preg_replace("/\s+/"," ",$value) ; // Compress useless spaces
			$lines[$key]=$heading.":".trim($value) ; // Don't forget to remove WSP around the value
		}
		// Implode it again
		$s=implode("\r\n",$lines) ;
		// Done :-)
		return $s ;
	}
	Function _DKIMSimpleBodyCanonicalization($body){
		if ($body == '')
			return "\r\n" ;
		
		// Just in case the body comes from Windows, replace all \r\n by the Unix \n
		$body=str_replace("\r\n","\n",$body) ;
		// Replace all \n by \r\n
		$body=str_replace("\n","\r\n",$body) ;
		// Should remove trailing empty lines... I.e. even a trailing \r\n\r\n
		// TODO
		while (substr($body,strlen($body)-4,4) == "\r\n\r\n")
			$body=substr($body,0,strlen($body)-2) ;
		return $body ;
	}
}

/**
	SMTP Class
	@author 	Manuel Lemos
	@website	http://www.phpclasses.org/package/14-PHP-Sends-e-mail-messages-via-SMTP-protocol.html
	@version	2014-11-23
**/
class smtp_class {
	var $user = "";
	var $realm = "";
	var $password = "";
	var $workstation = "";
	var $authentication_mechanism = "";
	var $host_name = "";
	var $host_port = 25;
	var $socks_host_name = '';
	var $socks_host_port = 1080;
	var $socks_version = '5';
	var $http_proxy_host_name = '';
	var $http_proxy_host_port = 80;
	var $user_agent = 'SMTP Class (http://www.phpclasses.org/smtpclass $Revision: 1.48 $)';
	var $ssl = 0;
	var $start_tls = 0;
	var $localhost = "";
	var $timeout = 0;
	var $data_timeout = 0;
	var $direct_delivery = 0;
	var $error = "";
	var $debug = 0;
	var $html_debug = 0;
	var $esmtp = 1;
	var $esmtp_extensions = array();
	var $exclude_address = "";
	var $getmxrr = "GetMXRR";
	var $pop3_auth_host = "";
	var $pop3_auth_port = 110;
	var $state = "Disconnected";
	var $connection = 0;
	var $pending_recipients = 0;
	var $next_token = "";
	var $direct_sender = "";
	var $connected_domain = "";
	var $result_code;
	var $disconnected_error = 0;
	var $esmtp_host = "";
	var $maximum_piped_recipients = 100;
	
	Function Tokenize($string, $separator = ""){
		if(!strcmp($separator, "")){
			$separator = $string;
			$string    = $this->next_token;
		}
		for($character = 0; $character < strlen($separator); $character++){
			if(GetType($position = strpos($string, $separator[$character])) == "integer")
				$found = (IsSet($found) ? min($found, $position) : $position);
		}
		if(IsSet($found)){
			$this->next_token = substr($string, $found + 1);
			return (substr($string, 0, $found));
		} else {
			$this->next_token = "";
			return ($string);
		}
	}
	Function OutputDebug($message){
		$message .= "\n";
		if($this->html_debug)
			$message = str_replace("\n", "<br />\n", HtmlEntities($message));
		echo $message;
		flush();
	}
	Function SetDataAccessError($error){
		$this->error = $error;
		if(function_exists("socket_get_status")){
			$status = socket_get_status($this->connection);
			if($status["timed_out"])
				$this->error .= ": data access time out";
			elseif($status["eof"]){
				$this->error .= ": the server disconnected";
				$this->disconnected_error = 1;
			}
		}
		return ($this->error);
	}
	Function SetError($error){
		return ($this->error = $error);
	}
	Function GetLine(){
		for($line = "";;){
			if(feof($this->connection)){
				$this->error = "reached the end of data while reading from the SMTP server conection";
				return ("");
			}
			if(GetType($data = @fgets($this->connection, 100)) != "string" || strlen($data) == 0){
				$this->SetDataAccessError("it was not possible to read line from the SMTP server");
				return ("");
			}
			$line .= $data;
			$length = strlen($line);
			if($length >= 2 && substr($line, $length - 2, 2) == "\r\n"){
				$line = substr($line, 0, $length - 2);
				if($this->debug)
					$this->OutputDebug("S $line");
				return ($line);
			}
		}
	}
	Function PutLine($line){
		if($this->debug)
			$this->OutputDebug("C $line");
		if(!@fputs($this->connection, "$line\r\n")){
			$this->SetDataAccessError("it was not possible to send a line to the SMTP server");
			return (0);
		}
		return (1);
	}
	Function PutData(&$data){
		if(strlen($data)){
			if($this->debug)
				$this->OutputDebug("C $data");
			if(!@fputs($this->connection, $data)){
				$this->SetDataAccessError("it was not possible to send data to the SMTP server");
				return (0);
			}
		}
		return (1);
	}
	Function VerifyResultLines($code, &$responses){
		$responses = array();
		Unset($this->result_code);
		while(strlen($line = $this->GetLine($this->connection))){
			if(IsSet($this->result_code)){
				if(strcmp($this->Tokenize($line, " -"), $this->result_code)){
					$this->error = $line;
					return (0);
				}
			} else {
				$this->result_code = $this->Tokenize($line, " -");
				if(GetType($code) == "array"){
					for($codes = 0; $codes < count($code) && strcmp($this->result_code, $code[$codes]); $codes++);
					if($codes >= count($code)){
						$this->error = $line;
						return (0);
					}
				} else {
					if(strcmp($this->result_code, $code)){
						$this->error = $line;
						return (0);
					}
				}
			}
			$responses[] = $this->Tokenize("");
			if(!strcmp($this->result_code, $this->Tokenize($line, " ")))
				return (1);
		}
		return (-1);
	}
	Function FlushRecipients(){
		if($this->pending_sender){
			if($this->VerifyResultLines("250", $responses) <= 0)
				return (0);
			$this->pending_sender = 0;
		}
		for(; $this->pending_recipients; $this->pending_recipients--){
			if($this->VerifyResultLines(array(
				"250",
				"251"
			), $responses) <= 0)
				return (0);
		}
		return (1);
	}
	Function Resolve($domain, &$ip, $server_type){
		if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $domain))
			$ip = $domain;
		else {
			if($this->debug)
				$this->OutputDebug('Resolving ' . $server_type . ' server domain "' . $domain . '"...');
			if(!strcmp($ip = @gethostbyname($domain), $domain))
				$ip = "";
		}
		if(strlen($ip) == 0 || (strlen($this->exclude_address) && !strcmp(@gethostbyname($this->exclude_address), $ip)))
			return ($this->SetError("could not resolve the host domain \"" . $domain . "\""));
		return ('');
	}
	Function ConnectToHost($domain, $port, $resolve_message){
		if($this->ssl){
			$version     = explode(".", function_exists("phpversion") ? phpversion() : "3.0.7");
			$php_version = intval($version[0]) * 1000000 + intval($version[1]) * 1000 + intval($version[2]);
			if($php_version < 4003000)
				return ("establishing SSL connections requires at least PHP version 4.3.0");
			if(!function_exists("extension_loaded") || !extension_loaded("openssl"))
				return ("establishing SSL connections requires the OpenSSL extension enabled");
		}
		if(strlen($this->Resolve($domain, $ip, 'SMTP')))
			return ($this->error);
		if(strlen($this->socks_host_name)){
			switch($this->socks_version){
				case '4':
					$version = 4;
					break;
				case '5':
					$version = 5;
					break;
				default:
					return ('it was not specified a supported SOCKS protocol version');
					break;
			}
			$host_ip   = $ip;
			$host_port = $port;
			if(strlen($this->error = $this->Resolve($this->socks_host_name, $ip, 'SOCKS')))
				return ($this->error);
			if($this->ssl)
				$ip = "ssl://" . ($socks_host = $this->socks_host_name);
			else
				$socks_host = $ip;
			if($this->debug)
				$this->OutputDebug("Connecting to SOCKS server \"" . $socks_host . "\" port " . $this->http_proxy_host_port . "...");
			if(($this->connection = ($this->timeout ? fsockopen($ip, $this->socks_host_port, $errno, $error, $this->timeout) : fsockopen($ip, $this->socks_host_port, $errno, $error)))){
				$timeout = ($this->data_timeout ? $this->data_timeout : $this->timeout);
				if($timeout && function_exists("socket_set_timeout"))
					socket_set_timeout($this->connection, $timeout, 0);
				if(strlen($this->socks_host_name)){
					if($this->debug)
						$this->OutputDebug('Connected to the SOCKS server ' . $this->socks_host_name);
					$send_error    = 'it was not possible to send data to the SOCKS server';
					$receive_error = 'it was not possible to receive data from the SOCKS server';
					switch($version){
						case 4:
							$command = 1;
							$user    = '';
							if(!fputs($this->connection, chr($version) . chr($command) . pack('nN', $host_port, ip2long($host_ip)) . $user . Chr(0)))
								$error = $this->SetDataAccessError($send_error);
							else {
								$response = fgets($this->connection, 9);
								if(strlen($response) != 8)
									$error = $this->SetDataAccessError($receive_error);
								else {
									$socks_errors = array(
										"\x5a" => '',
										"\x5b" => 'request rejected',
										"\x5c" => 'request failed because client is not running identd (or not reachable from the server)',
										"\x5d" => 'request failed because client\'s identd could not confirm the user ID string in the request'
									);
									$error_code   = $response[1];
									$error        = (IsSet($socks_errors[$error_code]) ? $socks_errors[$error_code] : 'unknown');
									if(strlen($error))
										$error = 'SOCKS error: ' . $error;
								}
							}
							break;
						case 5:
							if($this->debug)
								$this->OutputDebug('Negotiating the authentication method ...');
							$methods = 1;
							$method  = 0;
							if(!fputs($this->connection, chr($version) . chr($methods) . chr($method)))
								$error = $this->SetDataAccessError($send_error);
							else {
								$response = fgets($this->connection, 3);
								if(strlen($response) != 2)
									$error = $this->SetDataAccessError($receive_error);
								elseif(Ord($response[1]) != $method)
									$error = 'the SOCKS server requires an authentication method that is not yet supported';
								else {
									if($this->debug)
										$this->OutputDebug('Connecting to SMTP server IP ' . $host_ip . ' port ' . $host_port . '...');
									$command      = 1;
									$address_type = 1;
									if(!fputs($this->connection, chr($version) . chr($command) . "\x00" . chr($address_type) . pack('Nn', ip2long($host_ip), $host_port)))
										$error = $this->SetDataAccessError($send_error);
									else {
										$response = fgets($this->connection, 11);
										if(strlen($response) != 10)
											$error = $this->SetDataAccessError($receive_error);
										else {
											$socks_errors = array(
												"\x00" => '',
												"\x01" => 'general SOCKS server failure',
												"\x02" => 'connection not allowed by ruleset',
												"\x03" => 'Network unreachable',
												"\x04" => 'Host unreachable',
												"\x05" => 'Connection refused',
												"\x06" => 'TTL expired',
												"\x07" => 'Command not supported',
												"\x08" => 'Address type not supported'
											);
											$error_code   = $response[1];
											$error        = (IsSet($socks_errors[$error_code]) ? $socks_errors[$error_code] : 'unknown');
											if(strlen($error))
												$error = 'SOCKS error: ' . $error;
										}
									}
								}
							}
							break;
						default:
							$error = 'support for SOCKS protocol version ' . $this->socks_version . ' is not yet implemented';
							break;
					}
					if(strlen($this->error = $error)){
						fclose($this->connection);
						return ($error);
					}
				}
				return ('');
			}
		} elseif(strlen($this->http_proxy_host_name)){
			if(strlen($error = $this->Resolve($this->http_proxy_host_name, $ip, 'SMTP')))
				return ($error);
			if($this->ssl)
				$ip = 'ssl://' . ($proxy_host = $this->http_proxy_host_name);
			else
				$proxy_host = $ip;
			if($this->debug)
				$this->OutputDebug("Connecting to HTTP proxy server \"" . $ip . "\" port " . $this->http_proxy_host_port . "...");
			if(($this->connection = ($this->timeout ? @fsockopen($ip, $this->http_proxy_host_port, $errno, $error, $this->timeout) : @fsockopen($ip, $this->http_proxy_host_port, $errno, $error)))){
				if($this->debug)
					$this->OutputDebug('Connected to HTTP proxy host "' . $this->http_proxy_host_name . '".');
				$timeout = ($this->data_timeout ? $this->data_timeout : $this->timeout);
				if($timeout && function_exists("socket_set_timeout"))
					socket_set_timeout($this->connection, $timeout, 0);
				if($this->PutLine('CONNECT ' . $domain . ':' . $port . ' HTTP/1.0') && $this->PutLine('User-Agent: ' . $this->user_agent) && $this->PutLine('')){
					if(GetType($response = $this->GetLine()) == 'string'){
						if(!preg_match('/^http\\/[0-9]+\\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)$/i', $response, $matches))
							return ($this->SetError("3 it was received an unexpected HTTP response status"));
						$error = $matches[1];
						switch($error){
							case '200':
								for(;;){
									if(GetType($response = $this->GetLine()) != 'string')
										break;
									if(strlen($response) == 0)
										return ('');
								}
								break;
							default:
								$this->error = 'the HTTP proxy returned error ' . $error . ' ' . $matches[2];
								break;
						}
					}
				}
				if($this->debug)
					$this->OutputDebug("Disconnected.");
				fclose($this->connection);
				$this->connection = 0;
				return ($this->error);
			}
		} else {
			if($this->ssl)
				$ip = 'ssl://' . ($host = $domain);
			elseif($this->start_tls)
				$ip = $host = $domain;
			else
				$host = $ip;
			if($this->debug)
				$this->OutputDebug("Connecting to SMTP server \"" . $host . "\" port " . $port . "...");
			if(($this->connection = ($this->timeout ? @fsockopen($ip, $port, $errno, $error, $this->timeout) : @fsockopen($ip, $port, $errno, $error))))
				return ("");
		}
		$error = ($this->timeout ? strval($error) : "??");
		switch($error){
			case "-3":
				return ("-3 socket could not be created");
			case "-4":
				return ("-4 dns lookup on hostname \"" . $domain . "\" failed");
			case "-5":
				return ("-5 connection refused or timed out");
			case "-6":
				return ("-6 fdopen() call failed");
			case "-7":
				return ("-7 setvbuf() call failed");
		}
		return ("could not connect to the host \"" . $domain . "\": " . $error);
	}
	Function SASLAuthenticate($mechanisms, $credentials, &$authenticated, &$mechanism){
		$authenticated = 0;
		if(!function_exists("class_exists") || !class_exists("sasl_client_class")){
			$this->error = "it is not possible to authenticate using the specified mechanism because the SASL library class is not loaded";
			return (0);
		}
		$sasl = new sasl_client_class;
		$sasl->SetCredential("user", $credentials["user"]);
		$sasl->SetCredential("password", $credentials["password"]);
		if(IsSet($credentials["realm"]))
			$sasl->SetCredential("realm", $credentials["realm"]);
		if(IsSet($credentials["workstation"]))
			$sasl->SetCredential("workstation", $credentials["workstation"]);
		if(IsSet($credentials["mode"]))
			$sasl->SetCredential("mode", $credentials["mode"]);
		do {
			$status = $sasl->Start($mechanisms, $message, $interactions);
		} while($status == SASL_INTERACT);
		switch($status){
			case SASL_CONTINUE:
				break;
			case SASL_NOMECH:
				if(strlen($this->authentication_mechanism)){
					$this->error = "authenticated mechanism " . $this->authentication_mechanism . " may not be used: " . $sasl->error;
					return (0);
				}
				break;
			default:
				$this->error = "Could not start the SASL authentication client: " . $sasl->error;
				return (0);
		}
		if(strlen($mechanism = $sasl->mechanism)){
			if($this->PutLine("AUTH " . $sasl->mechanism . (IsSet($message) ? " " . base64_encode($message) : "")) == 0){
				$this->error = "Could not send the AUTH command";
				return (0);
			}
			if(!$this->VerifyResultLines(array(
				"235",
				"334"
			), $responses))
				return (0);
			switch($this->result_code){
				case "235":
					$response      = "";
					$authenticated = 1;
					break;
				case "334":
					$response = base64_decode($responses[0]);
					break;
				default:
					$this->error = "Authentication error: " . $responses[0];
					return (0);
			}
			for(; !$authenticated;){
				do {
					$status = $sasl->Step($response, $message, $interactions);
				} while($status == SASL_INTERACT);
				switch($status){
					case SASL_CONTINUE:
						if($this->PutLine(base64_encode($message)) == 0){
							$this->error = "Could not send the authentication step message";
							return (0);
						}
						if(!$this->VerifyResultLines(array(
							"235",
							"334"
						), $responses))
							return (0);
						switch($this->result_code){
							case "235":
								$response      = "";
								$authenticated = 1;
								break;
							case "334":
								$response = base64_decode($responses[0]);
								break;
							default:
								$this->error = "Authentication error: " . $responses[0];
								return (0);
						}
						break;
					default:
						$this->error = "Could not process the SASL authentication step: " . $sasl->error;
						return (0);
				}
			}
		}
		return (1);
	}
	Function StartSMTP($localhost){
		$success                = 1;
		$this->esmtp_extensions = array();
		$fallback               = 1;
		if($this->esmtp || strlen($this->user)){
			if($this->PutLine('EHLO ' . $localhost)){
				if(($success_code = $this->VerifyResultLines('250', $responses)) > 0){
					$this->esmtp_host = $this->Tokenize($responses[0], " ");
					for($response = 1; $response < count($responses); $response++){
						$extension                          = strtoupper($this->Tokenize($responses[$response], " "));
						$this->esmtp_extensions[$extension] = $this->Tokenize("");
					}
					$success  = 1;
					$fallback = 0;
				} else {
					if($success_code == 0){
						$code = $this->Tokenize($this->error, " -");
						switch($code){
							case "421":
								$fallback = 0;
								break;
						}
					}
				}
			} else
				$fallback = 0;
		}
		if($fallback){
			if($this->PutLine("HELO $localhost") && $this->VerifyResultLines("250", $responses) > 0)
				$success = 1;
		}
		return ($success);
	}
	Function Connect($domain = ""){
		if(strcmp($this->state, "Disconnected")){
			$this->error = "connection is already established";
			return (0);
		}
		$this->disconnected_error = 0;
		$this->error              = $error = "";
		$this->esmtp_host         = "";
		$this->esmtp_extensions   = array();
		$hosts                    = array();
		if($this->direct_delivery){
			if(strlen($domain) == 0)
				return (1);
			$hosts   = $weights = $mxhosts = array();
			$getmxrr = $this->getmxrr;
			if(function_exists($getmxrr) && $getmxrr($domain, $hosts, $weights)){
				for($host = 0; $host < count($hosts); $host++)
					$mxhosts[$weights[$host]] = $hosts[$host];
				KSort($mxhosts);
				for(Reset($mxhosts), $host = 0; $host < count($mxhosts); Next($mxhosts), $host++)
					$hosts[$host] = $mxhosts[Key($mxhosts)];
			} else {
				if(strcmp(@gethostbyname($domain), $domain) != 0)
					$hosts[] = $domain;
			}
		} else {
			if(strlen($this->host_name))
				$hosts[] = $this->host_name;
			if(strlen($this->pop3_auth_host)){
				$user = $this->user;
				if(strlen($user) == 0){
					$this->error = "it was not specified the POP3 authentication user";
					return (0);
				}
				$password = $this->password;
				if(strlen($password) == 0){
					$this->error = "it was not specified the POP3 authentication password";
					return (0);
				}
				$domain      = $this->pop3_auth_host;
				$this->error = $this->ConnectToHost($domain, $this->pop3_auth_port, "Resolving POP3 authentication host \"" . $domain . "\"...");
				if(strlen($this->error))
					return (0);
				if(strlen($response = $this->GetLine()) == 0)
					return (0);
				if(strcmp($this->Tokenize($response, " "), "+OK")){
					$this->error = "POP3 authentication server greeting was not found";
					return (0);
				}
				if(!$this->PutLine("USER " . $this->user) || strlen($response = $this->GetLine()) == 0)
					return (0);
				if(strcmp($this->Tokenize($response, " "), "+OK")){
					$this->error = "POP3 authentication user was not accepted: " . $this->Tokenize("\r\n");
					return (0);
				}
				if(!$this->PutLine("PASS " . $password) || strlen($response = $this->GetLine()) == 0)
					return (0);
				if(strcmp($this->Tokenize($response, " "), "+OK")){
					$this->error = "POP3 authentication password was not accepted: " . $this->Tokenize("\r\n");
					return (0);
				}
				fclose($this->connection);
				$this->connection = 0;
			}
		}
		if(count($hosts) == 0){
			$this->error = "could not determine the SMTP to connect";
			return (0);
		}
		for($host = 0, $error = "not connected"; strlen($error) && $host < count($hosts); $host++){
			$domain = $hosts[$host];
			$error  = $this->ConnectToHost($domain, $this->host_port, "Resolving SMTP server domain \"$domain\"...");
		}
		if(strlen($error)){
			$this->error = $error;
			return (0);
		}
		$timeout = ($this->data_timeout ? $this->data_timeout : $this->timeout);
		if($timeout && function_exists("socket_set_timeout"))
			socket_set_timeout($this->connection, $timeout, 0);
		if($this->debug)
			$this->OutputDebug("Connected to SMTP server \"" . $domain . "\".");
		if(!strcmp($localhost = $this->localhost, "") && !strcmp($localhost = getenv("SERVER_NAME"), "") && !strcmp($localhost = getenv("HOST"), ""))
			$localhost = "localhost";
		$success = 0;
		if($this->VerifyResultLines("220", $responses) > 0){
			$success = $this->StartSMTP($localhost);
			if($this->start_tls){
				if(!IsSet($this->esmtp_extensions["STARTTLS"])){
					$this->error = "server does not support starting TLS";
					$success     = 0;
				} elseif(!function_exists('stream_socket_enable_crypto')){
					$this->error = "this PHP installation or version does not support starting TLS";
					$success     = 0;
				} elseif($success = ($this->PutLine('STARTTLS') && $this->VerifyResultLines('220', $responses) > 0)){
					//$this->OutputDebug('Starting TLS cryptograpic protocol');
					if(!($success = stream_socket_enable_crypto($this->connection, 1, STREAM_CRYPTO_METHOD_TLS_CLIENT)))
						$this->error = 'could not start TLS connection encryption protocol';
					else {
						//$this->OutputDebug('TLS started');
						$success = $this->StartSMTP($localhost);
					}
				}
			}
			if($success && strlen($this->user) && strlen($this->pop3_auth_host) == 0){
				if(!IsSet($this->esmtp_extensions["AUTH"])){
					$this->error = "server does not require authentication";
					if(IsSet($this->esmtp_extensions["STARTTLS"]))
						$this->error .= ', it probably requires starting TLS';
					$success = 0;
				} else {
					if(strlen($this->authentication_mechanism))
						$mechanisms = array(
							$this->authentication_mechanism
						);
					else {
						$mechanisms = array();
						for($authentication = $this->Tokenize($this->esmtp_extensions["AUTH"], " "); strlen($authentication); $authentication = $this->Tokenize(" "))
							$mechanisms[] = $authentication;
					}
					$credentials = array(
						"user" => $this->user,
						"password" => $this->password
					);
					if(strlen($this->realm))
						$credentials["realm"] = $this->realm;
					if(strlen($this->workstation))
						$credentials["workstation"] = $this->workstation;
					$success = $this->SASLAuthenticate($mechanisms, $credentials, $authenticated, $mechanism);
					if(!$success && !strcmp($mechanism, "PLAIN")){
						$mechanisms  = array(
							"PLAIN"
						);
						$credentials = array(
							"user" => $this->user,
							"password" => $this->password
						);
						if(strlen($this->realm)){
							$success = $this->SASLAuthenticate($mechanisms, $credentials, $authenticated, $mechanism);
						}
						if(!$success){
							$credentials["mode"] = SASL_PLAIN_EXIM_DOCUMENTATION_MODE;
							$success             = $this->SASLAuthenticate($mechanisms, $credentials, $authenticated, $mechanism);
						}
						if(!$success){
							$credentials["mode"] = SASL_PLAIN_EXIM_MODE;
							$success             = $this->SASLAuthenticate($mechanisms, $credentials, $authenticated, $mechanism);
						}
					}
					if($success && strlen($mechanism) == 0){
						$this->error = "it is not supported any of the authentication mechanisms required by the server";
						$success     = 0;
					}
				}
			}
		}
		if($success){
			$this->state            = "Connected";
			$this->connected_domain = $domain;
		} else {
			fclose($this->connection);
			$this->connection = 0;
		}
		return ($success);
	}
	Function MailFrom($sender){
		if($this->direct_delivery){
			switch($this->state){
				case "Disconnected":
					$this->direct_sender = $sender;
					return (1);
				case "Connected":
					$sender = $this->direct_sender;
					break;
				default:
					$this->error = "direct delivery connection is already established and sender is already set";
					return (0);
			}
		} else {
			if(strcmp($this->state, "Connected")){
				$this->error = "connection is not in the initial state";
				return (0);
			}
		}
		$this->error = "";
		if(!$this->PutLine("MAIL FROM:<$sender>"))
			return (0);
		if(!IsSet($this->esmtp_extensions["PIPELINING"]) && $this->VerifyResultLines("250", $responses) <= 0)
			return (0);
		$this->state = "SenderSet";
		if(IsSet($this->esmtp_extensions["PIPELINING"]))
			$this->pending_sender = 1;
		$this->pending_recipients = 0;
		return (1);
	}
	Function SetRecipient($recipient){
		if($this->direct_delivery){
			if(GetType($at = strrpos($recipient, "@")) != "integer")
				return ("it was not specified a valid direct recipient");
			$domain = substr($recipient, $at + 1);
			switch($this->state){
				case "Disconnected":
					if(!$this->Connect($domain))
						return (0);
					if(!$this->MailFrom("")){
						$error = $this->error;
						$this->Disconnect();
						$this->error = $error;
						return (0);
					}
					break;
				case "SenderSet":
				case "RecipientSet":
					if(strcmp($this->connected_domain, $domain)){
						$this->error = "it is not possible to deliver directly to recipients of different domains";
						return (0);
					}
					break;
				default:
					$this->error = "connection is already established and the recipient is already set";
					return (0);
			}
		} else {
			switch($this->state){
				case "SenderSet":
				case "RecipientSet":
					break;
				default:
					$this->error = "connection is not in the recipient setting state";
					return (0);
			}
		}
		$this->error = "";
		if(!$this->PutLine("RCPT TO:<$recipient>"))
			return (0);
		if(IsSet($this->esmtp_extensions["PIPELINING"])){
			$this->pending_recipients++;
			if($this->pending_recipients >= $this->maximum_piped_recipients){
				if(!$this->FlushRecipients())
					return (0);
			}
		} else {
			if($this->VerifyResultLines(array(
				"250",
				"251"
			), $responses) <= 0)
				return (0);
		}
		$this->state = "RecipientSet";
		return (1);
	}
	Function StartData(){
		if(strcmp($this->state, "RecipientSet")){
			$this->error = "connection is not in the start sending data state";
			return (0);
		}
		$this->error = "";
		if(!$this->PutLine("DATA"))
			return (0);
		if($this->pending_recipients){
			if(!$this->FlushRecipients())
				return (0);
		}
		if($this->VerifyResultLines("354", $responses) <= 0)
			return (0);
		$this->state = "SendingData";
		return (1);
	}
	Function PrepareData($data){
		return (preg_replace(array(
			"/\n\n|\r\r/",
			"/(^|[^\r])\n/",
			"/\r([^\n]|\$)/D",
			"/(^|\n)\\./"
		), array(
			"\r\n\r\n",
			"\\1\r\n",
			"\r\n\\1",
			"\\1.."
		), $data));
	}
	Function SendData($data){
		if(strcmp($this->state, "SendingData")){
			$this->error = "connection is not in the sending data state";
			return (0);
		}
		$this->error = "";
		return ($this->PutData($data));
	}
	Function EndSendingData(){
		if(strcmp($this->state, "SendingData")){
			$this->error = "connection is not in the sending data state";
			return (0);
		}
		$this->error = "";
		if(!$this->PutLine("\r\n.") || $this->VerifyResultLines("250", $responses) <= 0)
			return (0);
		$this->state = "Connected";
		return (1);
	}
	Function ResetConnection(){
		switch($this->state){
			case "Connected":
				return (1);
			case "SendingData":
				$this->error = "can not reset the connection while sending data";
				return (0);
			case "Disconnected":
				$this->error = "can not reset the connection before it is established";
				return (0);
		}
		$this->error = "";
		if(!$this->PutLine("RSET") || $this->VerifyResultLines("250", $responses) <= 0)
			return (0);
		$this->state = "Connected";
		return (1);
	}
	Function Disconnect($quit = 1){
		if(!strcmp($this->state, "Disconnected")){
			$this->error = "it was not previously established a SMTP connection";
			return (0);
		}
		$this->error = "";
		if(!strcmp($this->state, "Connected") && $quit && (!$this->PutLine("QUIT") || ($this->VerifyResultLines("221", $responses) <= 0 && !$this->disconnected_error)))
			return (0);
		if($this->disconnected_error)
			$this->disconnected_error = 0;
		else
			fclose($this->connection);
		$this->connection = 0;
		$this->state      = "Disconnected";
		if($this->debug)
			$this->OutputDebug("Disconnected.");
		return (1);
	}
	Function SendMessage($sender, $recipients, $headers, $body){
		if(($success = $this->Connect())){
			if(($success = $this->MailFrom($sender))){
				for($recipient = 0; $recipient < count($recipients); $recipient++){
					if(!($success = $this->SetRecipient($recipients[$recipient])))
						break;
				}
				if($success && ($success = $this->StartData())){
					if(is_array($headers)){
						for($header_data = "", $header = 0; $header < count($headers); $header++)
							$header_data .= $headers[$header] . "\r\n";
					}
					else{
						$header_data = $headers."\r\n";
					}
					$success = ($this->SendData($header_data . "\r\n") && $this->SendData($this->PrepareData($body)) && $this->EndSendingData());
				}
			}
			$error              = $this->error;
			$disconnect_success = $this->Disconnect($success);
			if($success)
				$success = $disconnect_success;
			else
				$this->error = $error;
		}
		return ($success);
	}
}

/**
	SASL Class (Simple Authentication and Security Layer: Single API for standard authentication mechanisms
	@author 	Manual Lemos
	@website	http://www.phpclasses.org/package/1888-PHP-Single-API-for-standard-authentication-mechanisms.html
	@version	2005-10-31
**/
define("SASL_INTERACT", 2);
define("SASL_CONTINUE", 1);
define("SASL_OK",       0);
define("SASL_FAIL",    -1);
define("SASL_NOMECH",  -4);
class sasl_interact_class{
	var $id;
	var $challenge;
	var $prompt;
	var $default_result;
	var $result;
};
class sasl_client_class{
	var $error='';
	var $mechanism='';
	var $encode_response=1;
	
	/* Private variables */
	var $driver;
	var $drivers=array(
		"Digest"   => array("digest_sasl_client_class",   "digest_sasl_client.php"   ),
		"CRAM-MD5" => array("cram_md5_sasl_client_class", "cram_md5_sasl_client.php" ),
		"LOGIN"    => array("login_sasl_client_class",    "login_sasl_client.php"    ),
		"NTLM"     => array("ntlm_sasl_client_class",     "ntlm_sasl_client.php"     ),
		"PLAIN"    => array("plain_sasl_client_class",    "plain_sasl_client.php"    ),
		"Basic"    => array("basic_sasl_client_class",    "basic_sasl_client.php"    )
	);
	var $credentials=array();

	/* Public functions */
	Function SetCredential($key,$value){
		$this->credentials[$key]=$value;
	}
	Function GetCredentials(&$credentials,$defaults,&$interactions){
		Reset($credentials);
		$end=(GetType($key=Key($credentials))!="string");
		for(;!$end;)
		{
			if(!IsSet($this->credentials[$key]))
			{
				if(IsSet($defaults[$key]))
					$credentials[$key]=$defaults[$key];
				else
				{
					$this->error="the requested credential ".$key." is not defined";
					return(SASL_NOMECH);
				}
			}
			else
				$credentials[$key]=$this->credentials[$key];
			Next($credentials);
			$end=(GetType($key=Key($credentials))!="string");
		}
		return(SASL_CONTINUE);
	}
	Function Start($mechanisms, &$message, &$interactions){
		if(strlen($this->error))
			return(SASL_FAIL);
		if(IsSet($this->driver))
			return($this->driver->Start($this,$message,$interactions));
		$no_mechanism_error="";
		for($m=0;$m<count($mechanisms);$m++)
		{
			$mechanism=$mechanisms[$m];
			if(IsSet($this->drivers[$mechanism]))
			{
				if(!class_exists($this->drivers[$mechanism][0]))
					require(dirname(__FILE__)."/".$this->drivers[$mechanism][1]);
				$this->driver=new $this->drivers[$mechanism][0];
				if($this->driver->Initialize($this))
				{
					$this->encode_response=1;
					$status=$this->driver->Start($this,$message,$interactions);
					switch($status)
					{
						case SASL_NOMECH:
							Unset($this->driver);
							if(strlen($no_mechanism_error)==0)
								$no_mechanism_error=$this->error;
							$this->error="";
							break;
						case SASL_CONTINUE:
							$this->mechanism=$mechanism;
							return($status);
						default:
							Unset($this->driver);
							$this->error="";
							return($status);
					}
				}
				else
				{
					Unset($this->driver);
					if(strlen($no_mechanism_error)==0)
						$no_mechanism_error=$this->error;
					$this->error="";
				}
			}
		}
		$this->error=(strlen($no_mechanism_error) ? $no_mechanism_error : "it was not requested any of the authentication mechanisms that are supported");
		return(SASL_NOMECH);
	}
	Function Step($response, &$message, &$interactions){
		if(strlen($this->error))
			return(SASL_FAIL);
		return($this->driver->Step($this,$response,$message,$interactions));
	}
};

define("SASL_LOGIN_STATE_START",             0);
define("SASL_LOGIN_STATE_IDENTIFY_USER",     1);
define("SASL_LOGIN_STATE_IDENTIFY_PASSWORD", 2);
define("SASL_LOGIN_STATE_DONE",              3);
class login_sasl_client_class{
	var $credentials=array();
	var $state=SASL_LOGIN_STATE_START;

	Function Initialize(&$client)
	{
		return(1);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_LOGIN_STATE_START)
		{
			$client->error="LOGIN authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>"",
			"realm"=>""
		);
		$defaults=array(
			"realm"=>""
		);
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
			$this->state=SASL_LOGIN_STATE_IDENTIFY_USER;
		Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
			case SASL_LOGIN_STATE_IDENTIFY_USER:
				$message=$this->credentials["user"].(strlen($this->credentials["realm"]) ? "@".$this->credentials["realm"] : "");
				$this->state=SASL_LOGIN_STATE_IDENTIFY_PASSWORD;
				break;
			case SASL_LOGIN_STATE_IDENTIFY_PASSWORD:
				$message=$this->credentials["password"];
				$this->state=SASL_LOGIN_STATE_DONE;
				break;
			case SASL_LOGIN_STATE_DONE:
				$client->error="LOGIN authentication was finished without success";
				break;
			default:
				$client->error="invalid LOGIN authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

define("SASL_PLAIN_STATE_START",    0);
define("SASL_PLAIN_STATE_IDENTIFY", 1);
define("SASL_PLAIN_STATE_DONE",     2);
define("SASL_PLAIN_DEFAULT_MODE",            0);
define("SASL_PLAIN_EXIM_MODE",               1);
define("SASL_PLAIN_EXIM_DOCUMENTATION_MODE", 2);
class plain_sasl_client_class{
	var $credentials=array();
	var $state=SASL_PLAIN_STATE_START;

	Function Initialize(&$client)
	{
		return(1);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_PLAIN_STATE_START)
		{
			$client->error="PLAIN authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>"",
			"realm"=>"",
			"mode"=>""
		);
		$defaults=array(
			"realm"=>"",
			"mode"=>""
		);
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
		{
			switch($this->credentials["mode"])
			{
				case SASL_PLAIN_EXIM_MODE:
					$message=$this->credentials["user"]."\0".$this->credentials["password"]."\0";
					break;
				case SASL_PLAIN_EXIM_DOCUMENTATION_MODE:
					$message="\0".$this->credentials["user"]."\0".$this->credentials["password"];
					break;
				default:
					$message=$this->credentials["user"]."\0".$this->credentials["user"].(strlen($this->credentials["realm"]) ? "@".$this->credentials["realm"] : "")."\0".$this->credentials["password"];
					break;
			}
			$this->state=SASL_PLAIN_STATE_DONE;
		}
		else
			Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
/*
			case SASL_PLAIN_STATE_IDENTIFY:
				switch($this->credentials["mode"])
				{
					case SASL_PLAIN_EXIM_MODE:
						$message=$this->credentials["user"]."\0".$this->credentials["password"]."\0";
						break;
					case SASL_PLAIN_EXIM_DOCUMENTATION_MODE:
						$message="\0".$this->credentials["user"]."\0".$this->credentials["password"];
						break;
					default:
						$message=$this->credentials["user"]."\0".$this->credentials["user"].(strlen($this->credentials["realm"]) ? "@".$this->credentials["realm"] : "")."\0".$this->credentials["password"];
						break;
				}
				var_dump($message);
				$this->state=SASL_PLAIN_STATE_DONE;
				break;
*/
			case SASL_PLAIN_STATE_DONE:
				$client->error="PLAIN authentication was finished without success";
				return(SASL_FAIL);
			default:
				$client->error="invalid PLAIN authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};
