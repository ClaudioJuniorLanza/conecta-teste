<?php
class dUsuario extends dDbRow3{
	// A função "Logar como" vai guardar o usuário original aqui, sempre no
	// config.php.
	static $cloakedMaster;
	
	static Function buildStruct(){
		self::setTable('d_usuarios');
		self::addField('id,usuar_id,data_cadastro,data_ult_login,username,senha,disabled,deleted');
		self::addExt  ('usuarObj', 'dUsuario::usuar_id');
		
		if(dSystem::getGlobal('currentVersion') >= 1.3){
			self::addField     ('facebook_id,facebook_invite');
			self::addValidation('facebook_id',     'strmax',      50,       'Resultado inesperado para facebook_id (muito comprido)');
			
			self::addValidation('facebook_invite', 'int',         false,    'Preencha o campo facebook_invite apenas com números');
			self::addModifier  ('facebook_invite', 'force_int');
		}
		if(dSystem::getGlobal('currentVersion') >= 1.6){
			self::addField     ('google_id');
			self::addValidation('google_id',   'strmax',      50,       'Resultado inesperado para google_id (muito comprido)');
		}
		if(dSystem::getGlobal('currentVersion') >= 1.7){
			self::addField     ('email');
			self::addValidation('email',         'email',     false,    'Informe um endereço de e-mail válido');
			self::addModifier  ('email',         'trim');
		}
		
		self::addValidation(false,           'unique',    'username', 'O username informado já está cadastrado para outro usuário.');
		self::addValidation('usuar_id',      'required',  false,    'Falha na programação: Não informado campo dUser:usuar_id. Avise o responsável.');
		self::addValidation('data_cadastro', 'required',  false,    'Falha na programação: Não informado campo dUser:data_cadastro. Avise o responsável.');
		self::addValidation('username',      'required',  false,    'Você precisa preencher o username');
		self::addValidation('data_cadastro', 'datetime',  'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_ult_login','datetime',  'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('usuar_id',    'int',         false,    'Preencha o campo usuar_id apenas com números');
		self::addValidation('usuar_id',    'nummin',      0,        'Falha na programação: Preciso de número positivo em dUser:usuar_id');
		self::addValidation('disabled',    'regex',       '(0|1)',  'Falha na programação: Opção inválida para dUser:disabled');
		self::addValidation('deleted',     'regex',       '(0|1)',  'Falha na programação: Opção inválida para dUser:deleted');
		self::addValidation('username',    'singleline',  false,    'O campo username não pode ter mais de uma linha');
		self::addValidation('username',    'strmax',      150,      'O campo username não pode ter mais de 150 caracteres');
		
		self::addModifier('usuar_id', 'force_int');
		self::addModifier('data_cadastro,data_ult_login', 'datetime',    'br');
		self::addModifier('username,senha',   'trim');
		
		$_passwordEmpty = "** Criptografada **";
		self::addValidation('senha', 'callback', function($obj, $password, $when) use ($_passwordEmpty){
			// Lembrando que a validação de senha ocorre apenas com os dados no formato 'basic', antes
			// de serem chamados para o banco de dados.
			if($password == $_passwordEmpty){
				// Nenhuma mudança, vamos aceitar sem validação.
				return true;
			}
			
			$_failed = false;
			if(dDbRow3::sValFailed($password, 'required', false)){
				$obj->addError('senha', "Por favor, informe uma senha válida.");
				return false;
			}
			if(dDbRow3::sValFailed($password, 'strmin', 4)){
				$obj->addError('senha', "Por favor, preencha uma senha com no mínimo 4 caracteres.");
				$_failed = true;
			}
			if(dDbRow3::sValFailed($password, 'regex', '[a-zA-Z]')){
				$obj->addError('senha', "Pedimos que você tenha ao menos UMA letra na sua senha.");
				$_failed = true;
			}
			if(dDbRow3::sValFailed($password, 'regex', '[1-9]')){
				$obj->addError('senha', "Pedimos que você tenha pelo menos UM número na sua senha.");
				$_failed = true;
			}
			
			return !$_failed;
		});
		self::addModifier  ('senha', 'callback', function($obj, $password, $when) use ($_passwordEmpty){
			if($when == 'raw2basic'){
				return trim($password);
			}
			if($when == 'basic2db'){
				if($password == $_passwordEmpty){
					// Pediu para calcular do _noString para o banco de dados.
					if($obj->getOriginal('senha')){
						trigger_error("Problema confuso na criptografia da senha: Tentando criptografar duas vezes.");
						return false;
					}
					return $password;
				}
				
				// Se solicitar o valor, já é _noString de novo.
				$obj->setValue('senha', $_passwordEmpty);
				
				// Mas para a QUERY, vamos calcular o hash.
				return md5($password.(dSystem::getGlobal('hashkey')));
			}
			if($when == 'db2basic'){
				return $_passwordEmpty;
			}
		}, 'raw2basic,basic2db,db2basic');
		
		self::setDefaultValue('data_cadastro',    function(){
			return date('d/m/Y H:i:s');
		}, 'basic');
		self::setDefaultValue('disabled,deleted', '0', 'basic');
		
		self::setAuditing  (Array('dAuditObjeto', 'cbAuditCallback'));
	}
	
	// Proteção da senha:
	Function valPassword($obj, $password){
		// Validação de senha.
		//   Error String em caso de fracasso.
		//   FALSE em caso de sucesso.
		
		// castMsg($level, $string=false){ // (0=OK  1=Warn  2=Critical  3=Debug)
		$this->castMsg(3, "Validação de senha solicitada: {$password}...");
		
		// Quando passar pela validação?
		// - Se for um novo objeto ou;
		// - Se a senha tiver sido alterada (e não estiver em branco);
		
		// Quando não passar pela validação?
		// - Se for um objeto existente (com uma senha já definida) e:
		//   - A senha não foi alterada ou;
		//   - A nova senha é branca ''. Assume-se que não quer alterar a senha.
		
		// Passar pela validação?
		$shouldValidate = (!$obj->getPrimaryValue() || ($obj->getValue('senha') != $obj->getOriginal('senha') && $obj->getValue('senha')));
		
		$this->castMsg(3, "Validação de senha solicitada. A nova senha é {$password}, passar pela validação? ".($shouldValidate?'SIM':'NÃO'));
		if($shouldValidate){
			// Definindo uma nova senha, vamos pelas validações padrão...
			// Se der erro em alguma, o retorno é TRUE.
			if($this->validateString($password, false, 'required', 'senha')){
				return "Por favor, preencha uma senha válida.";
			}
			if($this->validateString($password, 4, 'strmin', 'senha')){
				return "Por favor, preencha uma senha com no mínimo 4 caracteres.";
			}
			
			$this->castMsg(3, "Passou por todas as validações, senha foi aceita pela validação.");
			// Chegou até aqui? Então está liberado para o modifier.
			return false;
		}
		
		$this->castMsg(3, "Ignorando validação, pois é um objeto existente e a senha não foi alterada.");
		// Chegou até aqui? Então é um objeto existente e a senha não foi alterada.
		// Assim, não precisa passar pela validação, mantém o que está no banco de dados.
		return false;
	}	
	Function modPassword($obj, $password, $toDb){
		// Objeto      = $this
		// password = A senha em questão
		// toDb        = 0=Exportando Array, 1=Para banco de dados, 2=Vindo do banco de dados
		
		if($toDb == 1){
			// Tenho o objeto, e estou salvando-o no banco de dados...
			// Salvar apenas o HASH no banco de dados.
			
			// Se sou um objeto carregado e a senha não foi alterada...
			// Não altera a senha já criptografada constante no banco de dados...
			if($this->getPrimaryValue() && ($this->getOriginal('senha') == $password || !$password)){
				$this->castMsg(3, "Não vou alterar a senha, salvando {$this->getVirtual('senha_criptografada')}...");
				return $this->getVirtual('senha_criptografada');
			}
			else{
				// Caso contrário (novo objeto, ou senha alterada)
				$this->castMsg("Vou alterar a senha para o novo hash de {$password}...");
				return md5($password.(dSystem::getGlobal('hashkey')));
			}
		}
		elseif($toDb == 2){
			$this->castMsg("Recebendo do banco de dados, vou salvar senha_criptografada como {$password} e alterar para CRIPTOGRAFADA...");
			$this->setVirtual('senha_criptografada', $password);
			return "*** CRIPTOGRAFADA ***";
		}
		
		// loadFromArray() e saveToArray() utilizam o toDb=1.
		// Dessa forma, vamos retornar o valor que tiver, sem nenhuma modificação, já que o loadFromArray($_POST) é mais utilizado do que o saveToArray().
		$this->castMsg("loadFromArray() ou saveToArray(), não vou aplicar nenhuma modificação.");
		return $password;
	}
	
	// Proteção contra exclusão direta (Sistema de 'Lixeira')
	Function delete($forceDelete=false){
		if(!$forceDelete){
			return $this->setValue('deleted', '1')->save();
		}
		$deleteOk      = parent::delete();
		if($deleteOk){
			$allPerms    = dUsuarPermissao::multiLoad(Array('cbMakeQuery'=>"where usuar_id='{$this->v('id')}'"));
			$allSettings = dUsuarSetting  ::multiLoad(Array('cbMakeQuery'=>"where usuar_id='{$this->v('id')}'"));
			if($allPerms)    foreach($allPerms    as $obj){
				$obj->delete();
			}
			if($allSettings) foreach($allSettings as $obj){
				$obj->delete();
			}
		}
		return $deleteOk;
	}
	
	// Permissões:
	private $permsTable = false;
	private Function loadPerms ($forceReload=false){
		if($this->permsTable === false || $forceReload){
			$db               = dDatabase::start();
			$this->permsTable = $db->singleColumn("select permissao from d_usuar_permissoes where usuar_id='{$this->getPrimaryValue()}'");
			if(!$this->permsTable){
				$this->permsTable = Array();
			}
		}
		return $this->permsTable;
	}
	public  Function checkPerms($forWhat, $param=false){
		$userPerms = $this->loadPerms();
		
		if(in_array($forWhat, $userPerms))
			return true;
		
		if(in_array('MANAGER_ACCOUNT', $userPerms) && $forWhat != 'MASTER_ACCOUNT')
			return true;
		
		return in_array('MASTER_ACCOUNT', $userPerms);
	}
	public  Function checkPermsOrDie($forWhat, $param=false){
		$gotAuth = $this->checkPerms($forWhat, $param);
		if(!$gotAuth){
			if(!array_key_exists('fakeLoginOriginal', $_SESSION)){
				dSystem::notifyAdmin('LOW', "Permissão foi negada ({$this->getValue('username')}: {$forWhat})",
					"Permissões foram negadas e exibidas para o cliente através do método dUsuario({$this->getValue('username')})->checkPermsOrDie({$forWhat}).\r\n".
					"Normalmente, nenhum cliente deveria ver uma mensagem de permissões negadas. Eles nunca deveriam ".
					"conseguir realizar ações que lhe seriam negadas.\r\n".
					"\r\n".
					"Dessa forma, embora não seja crítico, vale um monitoramento.\r\n".
					"A permissão negada foi {$forWhat}, o usuário logado é o id={$this->getPrimaryValue()}, username={$this->getValue('username')}"
				);
			}
			
			dHelper2::includePage(dSystem::getGlobal('baseDir')."/admin/usuario_noperms.php", Array('debug'=>"{$this->getPrimaryValue()}:{$forWhat}"));
			die;
		}
		return $gotAuth;
	}
	static  Function getAllPerms(){
		$allPerms = Array();
		
		$allPerms['Permissões de alto nível'] = true;
		$allPerms['MASTER_ACCOUNT']                 = "Master do sistema, apenas para configurações avançadas e debug.";
		$allPerms['MANAGER_ACCOUNT']                = "Gerente do sistema, cliente responsável por administrar o sistema.";
		
		$allPerms['Gerenciar usuários'] = true;
		$allPerms['USER_MANAGE']               = "Pode gerenciar sub-usuários?";
		$allPerms['USER_MANAGE_ALL']           = "Pode gerenciar todos os usuários do sistema?";
		$allPerms['USER_AUDITORIA']            = "Pode acessar a auditoria nos usuários que tem acesso?";
		$allPerms['USER_DELETE']               = "Pode excluir (lixeira) os usuários?";
		$allPerms['USER_WIPE']                 = "Pode excluir (definitivo) os usuários?";
		$allPerms['USER_TRASHBIN']             = "Pode ver a lixeira?";
		$allPerms['USER_LOGIN_AS']             = "Pode simular login com outro usuário, diretamente pelo sistema?";
		
		$allPerms['E-mails enviados pelo sistema'] = true;
		$allPerms['SENTMAIL_VIEW']            = "Pode ver todos os e-mails enviados pelo sistema? Incluindo formulário de contato, etc...";
		$allPerms['SENTMAIL_CHANGEGROUP']     = "Pode alterar o grupo/categoria de determinado e-mail?";
		$allPerms['SENTMAIL_DELETE']          = "Pode excluir (lixeira) e-mails enviados pelo sistema?";
		$allPerms['SENDMAIL_WIPE']            = "Pode excluir (definitivo) e-mails enviados pelo sistema?";
		$allPerms['SENDMAIL_TRASHBIN']        = "Pode ver a lixeira?";
		
		return $allPerms;
	}
	
	// Preferências:
	private $settingsTable = false;
	private Function loadSettings($forceReload=false){
		if($this->settingsTable === false || $forceReload){
			$db               = dDatabase::start();
			$this->settingsTable = $db->singleIndexV("select chave,IF(ISNULL(valor_text),valor_char,valor_text) as valor from d_usuar_settings where usuar_id='{$this->getPrimaryValue()}'");
			if(!$this->settingsTable){
				$this->settingsTable = Array();
			}
		}
		return $this->settingsTable;
	}
	public  Function setSetting  ($chave, $valor){
		$this->loadSettings();
		
		$prefeObj = dUsuarSetting::loadOrNew(Array('cbMakeQuery'=>"where usuar_id='{$this->v('id')}' and chave='".addslashes($chave)."'"));
		$prefeObj->setValue('usuar_id',   $this->v('id'));
		$prefeObj->setValue('chave',      $chave);
		$prefeObj->setValue('valor_char', (strlen($valor)>150)?false:$valor);
		$prefeObj->setValue('valor_text', (strlen($valor)>150)?$valor:false);
		$prefeObj->save();
		
		$this->settingsTable[$chave] = $valor;
	}
	public  Function getSetting  ($chave){
		$this->loadSettings();
		if(!array_key_exists($chave, $this->settingsTable))
			return null;
		
		return $this->settingsTable[$chave];
	}
	public  Function delSetting  ($chave){
		$prefeObj = dUsuarSetting::load(Array('cbMakeQuery'=>"where usuar_id='{$this->v('id')}' and chave='".addslashes($chave)."'"));
		if($prefeObj){
			$prefeObj->delete();
			unset($this->settingsTable[$chave]);
			return true;
		}
		return false;
	}
	
	// Social login features:
	static  Function fbValidateInvite($code, $hash){
		self::fbExpireInvites();
		
		$temp     = explode("d", $code, 2);
		$userId   = ($temp[0]/258);
		$fbInvite = strrev($temp[1]);
		
		$usuarObj = dUsuario::load($userId, Array('onlyFields'=>Array('facebook_invite','data_cadastro','disabled','deleted')));
		if(!$usuarObj){
			# echo "<b>fbValidateInvite</b> - Usuário não encontrado.<br />";
			return false;
		}
		if( $usuarObj->v('disabled')){
			# echo "<b>fbValidateInvite</b> - Usuário está desativado.<br />";
			return false;
		}
		if( $usuarObj->v('deleted')){
			# echo "<b>fbValidateInvite</b> - Usuário consta como excluído.<br />";
			return false;
		}
		if(!$usuarObj->v('facebook_invite')){
			# echo "<b>fbValidateInvite</b> - Convite não é mais válido.<br />";
			return false;
		}
		if( $usuarObj->v('facebook_invite') != $fbInvite){
			# echo "<b>fbValidateInvite</b> - O invite informado é diferente do que temos no sistema. Seu={$fbInvite}, Sistema={$usuarObj->v('facebook_invite')}.<br />";
			return false;
		}
		
		$expectedCode = (258*$usuarObj->v('id')).'d'.strrev($usuarObj->v('facebook_invite'));
		$expectedHash = md5($expectedCode.dSystem::getGlobal('hashKey').$usuarObj->v('id'));
		if($expectedCode != $code){
			# echo "<b>fbValidateInvite</b> - O código informado é diferente do que temos no sistema. Seu={$fbInvite}, Sistema={$code}.<br />";
			return false;
		}
		if($expectedHash != $hash){
			# echo "<b>fbValidateInvite</b> - O hash informado é diferente do que temos no sistema. Seu={$hash}, Sistema={$expectedHash}.<br />";
			return false;
		}
		
		return dUsuario::load($usuarObj->v('id'));
	}
	static  Function fbExpireInvites (){
		// Expirando convites do Facebook
		// --> Se o timestamp for maior que do o campo facebook_invite.
		$invitesExpired = dUsuario::multiLoad("where (!ISNULL(facebook_invite) and facebook_invite < '".time()."')");
		foreach($invitesExpired as $tmpUsuarObj){
			$tmpUsuarObj->v('facebook_invite', false)->save();
		}
	}
	public  Function fbGetInviteLink ($ttl=false){
		if($ttl){
			$this->v('facebook_invite', strtotime($ttl))->save();
		}
		if(!$this->v('facebook_invite')){
			return false;
		}
		
		$code = (258*$this->v('id')).'d'.strrev($this->v('facebook_invite'));
		$hash = md5($code.dSystem::getGlobal('hashKey').$this->v('id'));
		return (dSystem::getGlobal('baseUrlSSL')?dSystem::getGlobal('baseUrlSSL'):dSystem::getGlobal('baseUrl'))."admin/usuario_accept.php?code={$code}&hash={$hash}";
	}
	
	// Login features
	
	// Auto Remember me:
	// --> Como "salvar":
	//         cookie:       uid|rememberHash;
	//         rememberHash: md5(username . pwd . fbId . googleId . '-crm');
	static Function getUserHash($userId){
		$db       = dDatabase::start();
		$_addHash = '-hrm-' . dSystem::getGlobal('hashkey');
		$data     = $db->singleLine("select id,username,email,senha,google_id,facebook_id from " . self::structGet('tableName') . " where id='{$userId}'");
		if(!$data){
			// Never should get here.
			die("Não encontrei os dados do cliente id={$userId}");
			return false;
		}
		
		return md5(implode("", $data).$_addHash);
	}
	static Function checkRememberMe(){
		self::buildStruct();
		$ns = md5(dConfiguracao::getConfig('CORE/NOME_DO_SITE'));
		$_cookieKey = 'arm-' . substr(md5(dConfiguracao::getConfig('CORE/NOME_DO_SITE')), -8);
		if(!isset($_COOKIE[$_cookieKey])){
			// echo "Não encontrei o cookie $_cookieKey<br />";
			return false;
		}
		
		$parts = explode("|", $_COOKIE[$_cookieKey]);
		if(sizeof($parts) != 2){
			setcookie($_cookieKey, '', time() - 60*60*24);
			// echo "Cookie RememberMe mal formatado (n. parts).<br />";
			return false;
		}
		if(!is_numeric($parts[0])){
			setcookie($_cookieKey, '', time() - 60*60*24);
			// echo "Cookie RememberMe mal formatado (uid invalido).<br />";
			return false;
		}
		if(strlen($parts[1]) != 32){
			setcookie($_cookieKey, '', time() - 60*60*24);
			// echo "Cookie RememberMe mal formatado (hash != 32).<br />";
			return false;
		}
		
		$userId       = intval($parts[0]);
		$cookieHash   = trim($parts[1]);
		$expectedHash = self::getUserHash($userId);
		if($expectedHash != $cookieHash){
			setcookie($_cookieKey, '', time() - 60*60*24);
			// echo "Hash no sistema é diferente do hash do cookie.";
			return false;
		}
		
		$usuarObj = dUsuario::load($userId);
		dUsuario::setAsLogged($usuarObj);
		$usuarObj->saveRememberMe();
		
		return $usuarObj;
	}
	public Function saveRememberMe(){
		// Salva o cookie 'Remember Me'.
		// Formato: "userId|$hashedUser|$hashedPassword|hashedArmString"
		//   $userId:     Integer simples
		//   $hashedUser: md5($user      . '-extraHashRememberMeUser')
		//   $hashedPwd:  md5($hashedPwd . '-extraHashRememberMePwd')
		//   $hashedArmString: Um md5 simples de tudo o que há acima.
		self::buildStruct();
		
		$_cookieKey = 'arm-' . substr(md5(dConfiguracao::getConfig('CORE/NOME_DO_SITE')), -8);
		$userId     = $this->v('id');
		$hash       = self::getUserHash($userId);
		
		$_baseUrl = dSystem::getGlobal('baseUrlSSL')?
			dSystem::getGlobal('baseUrlSSL'):
			dSystem::getGlobal('baseUrl');
		$_baseDetails = parse_url($_baseUrl);
		$_expireAt    = (time() + 60 * 60 * 24 * 365);
		setcookie($_cookieKey, "{$userId}|{$hash}", $_expireAt, $_baseDetails['path'], $_baseDetails['host'], null, true);
		
		return true;
	}
	
	// Login methods:
	static $Scache = Array();
	static Function setAsLogged($u){
		$_SESSION['ecUserLoggedId'] = $u->getPrimaryValue();
		$u->setValue('data_ult_login', date('d/m/Y H:i:s'))->save();
		self::$Scache['logged'] = $u;
	}
	static Function isLoggedOrRedirect($to=false){
		$user = self::isLogged();
		if(!$user){
			$to = base64_encode($to?$to:$_SERVER['REQUEST_URI']);
			header("Location: login.php?gg={$to}");
			die;
		}
		return $user;
	}
	static Function isLogged($allowDisabled=false){
		// Já foi detectado que ele está logado e o objeto está iniciado...
		if(isset(self::$Scache['logged']) && is_object(self::$Scache['logged']))
			return self::$Scache['logged'];
		
		// Está logado, mas precisamos iniciar o objeto...
		if(isset($_SESSION['ecUserLoggedId'])){
			$uid  = $_SESSION['ecUserLoggedId'];
			if($user = dUsuario::load($uid)){
				// A seção existe, mas o usuário foi excluído ou desativado (depois de se logar)
				if($user->getValue('deleted')){
					return false;
				}
				if($user->getValue('disabled') && !$allowDisabled){
					return false;
				}
				
				self::$Scache['logged'] = $user;
				return $user;
			}
		}
		
		// Está logado via Cookie, ou é false...
		$usuarObj = self::checkRememberMe();
		
		return $usuarObj;
	}
	static Function logIn($username, $senha){
		$username = trim($username);
		$senha    = trim($senha);
		
		$trySenha = md5($senha.dSystem::getGlobal('hashkey'));
		$user = dUsuario::load(Array('cbMakeQuery'=>"where username='".addslashes($username)."' and senha='".addslashes($trySenha)."' and disabled='0' and deleted='0'"));
		if($user){
			self::setAsLogged($user);
			return $user;
		}
		
		return false;
	}
	static Function logOut(){
		$_cookieKey = 'arm-'.substr(md5(dConfiguracao::getConfig('CORE/NOME_DO_SITE')), -8);
		if(isset($_COOKIE[$_cookieKey])){
			$_baseUrl = dSystem::getGlobal('baseUrlSSL')?
				dSystem::getGlobal('baseUrlSSL'):
				dSystem::getGlobal('baseUrl');
			
			$_baseDetails = parse_url($_baseUrl);
			$_expireAt    = (time() - 60*60*24);
			
			setcookie($_cookieKey, '', $_expireAt, $_baseDetails['path'], $_baseDetails['host'], null); // Expira o cookie em '/'
			setcookie($_cookieKey, '', $_expireAt);                                                     // Expira o cookie em '/admin/' (cookie antigo, se houver)
			unset($_COOKIE[$_cookieKey]);
		}
		
		unset($_SESSION['ecUserLoggedId']);
		unset($_SESSION['fakeLoginOriginal']);
		return true;
	}
}

