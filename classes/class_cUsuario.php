<?php // built on 18/01/2019 16:56
class cUsuario extends dDbRow3{
	static Function buildStruct(){
		self::setTable('c_usuarios');
		self::addField('id,data_cadastro,data_lastlogin,renasem,tipo,nome');
		self::addField('responsavel_nome,cpf_cnpj,rg_ie,atividade,cep,uf');
		self::addField('cidade,bairro,endereco,numero,complemento,referencia,fone1,fone2');
		self::addField('email,senha,avaliacao,facebook_id,googleacc_id,disabled');
		self::addField('dados_bancarios,interesse_regioes,interesse_culturas,interesse_produtos');
		self::addField('interesses_json,observacoes_internas');
		
		self::addField('agente_id,agente_pending,agente_vendedor,agente_captador,comerciante_sem_renasem,categoria');
		self::addExt('agentObj', 'cUsuario::agente_id');
		self::addModifier('agente_id', 'int');
		
		self::addValidation('comerciante_sem_renasem','regex',       '(0|1)',   'Opção inválida para comerciante-sem-renasem');
		self::addValidation('agente_pending',         'required',    false,    'Erro técnico: flag agente-pending não pode ficar em branco.');
		self::addValidation('agente_vendedor',        'required',    false,    'Erro técnico: flag agente-v não pode ficar em branco.');
		self::addValidation('agente_captador',        'required',    false,    'Erro técnico: flag agente-c não pode ficar em branco.');
		self::addValidation('agente_pending',         'regex',       '(0|1)', 'Opção inválida para flag agente-pending');
		self::addValidation('agente_vendedor',        'regex',       '(0|1)', 'Opção inválida para flag agente-v');
		self::addValidation('agente_captador',        'regex',       '(0|1)', 'Opção inválida para flag agente-c');
		self::addValidation('categoria',              'regex',       '(Agricultor|Revenda|Grupo de Compra|Outros)', 'Opção inválida para categoria do usuário');
		
		self::setDefaultValue('comerciante_sem_renasem',  '0');
		self::setDefaultValue('agente_pending',  '0');
		self::setDefaultValue('agente_vendedor', '0');
		self::setDefaultValue('agente_captador', '0');
		
		self::addValidation('renasem',      'unique', false, "RENASEM já cadastrado.  <a href='#' onclick=\"$('#btnChatOnline').click(); return false;\">Esqueceu sua senha?</a>");
		self::addValidation('email',        'unique', false, "E-mail já se encontra cadastrado. <a href='#' onclick=\"$('#btnChatOnline').click(); return false;\">Esqueceu sua senha?</a>");
		self::addValidation('facebook_id',  'unique', false, "Essa conta do Facebook já está associada a outro usuário. <a href='login.php'>Deseja fazer login?</a>");
		self::addValidation('googleacc_id', 'unique', false, "Essa conta do Google já está associada a outro usuário. <a href='login.php'>Deseja fazer login?</a>");
		
		// Validações do grupo 'notAgente' serão chamadas pelo Callback acima, caso não seja o cadastro de um agente.
		self::addValidation('renasem',          'required',    false,    'Informe o número do RENASEM',                 'notAgente');
		self::addValidation('tipo',             'required',    false,    'Informe se você é pessoa física ou jurídica', 'notAgente');
		self::addValidation('responsavel_nome', 'required',    false,    'Informe o nome do responsável',               'notAgente');
		
		// Validações do grupo 'clienteRepresentado'
		self::addValidation('responsavel_nome', 'required',    false,    'Informe o nome do responsável',           'clienteRepresentado');
		self::addValidation('categoria',        'required',    false,    'Selecione o tipo de atividade',           'clienteRepresentado');
		self::addValidation('cpf_cnpj',         'required',    false,    'Informe o CNPJ do cliente',               'clienteRepresentado');
		self::addValidation('endereco',         'required',    false,    'Preencha o endereço completo do cliente', 'clienteRepresentado');
		self::addValidation('uf',               'required',    false,    'Preencha o endereço completo do cliente', 'clienteRepresentado');
		self::addValidation('cidade',           'required',    false,    'Preencha o endereço completo do cliente', 'clienteRepresentado');
		
		// Validações de type checking:
		self::addValidation('avaliacao',        'regex',       '(0|1|2|3|4|5)', 'Opção inválida para avaliacao');
		self::addValidation('nome',             'required',    false,    'Informe o nome ou razão social');
		self::addValidation('fone1',            'required',    false,    'Informe o número telefone');
		self::addValidation('email',            'required',    false,    'Informe o endereço de e-mail');
		self::addValidation('data_cadastro',    'required',    false,    'Erro interno: data_cadastro não pode ficar em branco.');
		self::addValidation('disabled',         'required',    false,    'Erro técnico: disabled não pode ficar em branco.');
		
		self::addValidation('data_cadastro',  'datetime',    'br',      'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_lastlogin', 'datetime',    'br',      'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('email',          'email',       false,     'Por favor, informe um e-mail válido');
		self::addValidation('tipo',           'regex',       '(pf|pj)', 'Opção inválida para tipo');
		self::addValidation('disabled',       'regex',       '(0|1)',   'Opção inválida para disabled');
		
		self::addValidation('renasem',     'singleline',  false,    'O campo renasem não pode ter mais de uma linha');
		self::addValidation('nome',        'singleline',  false,    'O campo nome completo não pode ter mais de uma linha');
		self::addValidation('responsavel_nome', 'singleline',  false,    'O campo responsavel nome não pode ter mais de uma linha');
		self::addValidation('cpf_cnpj',    'singleline',  false,    'O campo CPF/CNPJ não pode ter mais de uma linha');
		self::addValidation('rg_ie',       'singleline',  false,    'O campo RG/IE não pode ter mais de uma linha');
		self::addValidation('atividade',   'singleline',  false,    'O campo atividade não pode ter mais de uma linha');
		self::addValidation('cep',         'singleline',  false,    'O campo cep não pode ter mais de uma linha');
		self::addValidation('uf',          'singleline',  false,    'O campo uf não pode ter mais de uma linha');
		self::addValidation('cidade',      'singleline',  false,    'O campo cidade não pode ter mais de uma linha');
		self::addValidation('bairro',      'singleline',  false,    'O campo bairro não pode ter mais de uma linha');
		self::addValidation('endereco',    'singleline',  false,    'O campo endereco não pode ter mais de uma linha');
		self::addValidation('numero',      'singleline',  false,    'O campo numero não pode ter mais de uma linha');
		self::addValidation('complemento', 'singleline',  false,    'O campo complemento não pode ter mais de uma linha');
		self::addValidation('referencia',  'singleline',  false,    'O campo referencia não pode ter mais de uma linha');
		self::addValidation('fone1',       'singleline',  false,    'O campo telefone não pode ter mais de uma linha');
		self::addValidation('fone2',       'singleline',  false,    'O campo telefone não pode ter mais de uma linha');
		self::addValidation('email',       'singleline',  false,    'O campo e-mail não pode ter mais de uma linha');
		self::addValidation('senha',       'singleline',  false,    'O campo senha não pode ter mais de uma linha');
		self::addValidation('facebook_id', 'singleline',  false,    'O campo facebook id não pode ter mais de uma linha');
		self::addValidation('googleacc_id','singleline',  false,    'O campo googleacc id não pode ter mais de uma linha');
		self::addValidation('renasem',     'strmax',      30,       'O campo renasem não pode ter mais de 30 caracteres');
		self::addValidation('nome',        'strmax',      250,      'O campo nome completo não pode ter mais de 250 caracteres');
		self::addValidation('responsavel_nome', 'strmax', 250,      'O campo responsavel nome não pode ter mais de 250 caracteres');
		self::addValidation('cpf_cnpj',    'strmax',      250,      'O campo CPF/CNPJ não pode ter mais de 250 caracteres');
		self::addValidation('rg_ie',       'strmax',      250,      'O campo RG/IE não pode ter mais de 250 caracteres');
		self::addValidation('atividade',   'strmax',      250,      'O campo atividade não pode ter mais de 250 caracteres');
		self::addValidation('cep',         'strmax',      8,        'O campo cep não pode ter mais de 8 caracteres');
		self::addValidation('uf',          'strmax',      2,        'O campo uf não pode ter mais de 2 caracteres');
		self::addValidation('cidade',      'strmax',      250,      'O campo cidade não pode ter mais de 250 caracteres');
		self::addValidation('bairro',      'strmax',      250,      'O campo bairro não pode ter mais de 250 caracteres');
		self::addValidation('endereco',    'strmax',      250,      'O campo endereco não pode ter mais de 250 caracteres');
		self::addValidation('numero',      'strmax',      250,      'O campo numero não pode ter mais de 250 caracteres');
		self::addValidation('complemento', 'strmax',      250,      'O campo complemento não pode ter mais de 250 caracteres');
		self::addValidation('referencia',  'strmax',      250,      'O campo referencia não pode ter mais de 250 caracteres');
		self::addValidation('fone1',       'strmax',      150,      'O campo telefone não pode ter mais de 150 caracteres');
		self::addValidation('fone2',       'strmax',      150,      'O campo telefone não pode ter mais de 150 caracteres');
		self::addValidation('email',       'strmax',      150,      'O campo e-mail não pode ter mais de 150 caracteres');
		self::addValidation('senha',       'strmax',      50,       'O campo senha não pode ter mais de 50 caracteres');
		self::addValidation('facebook_id', 'strmax',      250,      'O campo facebook id não pode ter mais de 250 caracteres');
		self::addValidation('googleacc_id','strmax',      250,      'O campo googleacc id não pode ter mais de 250 caracteres');
		
		// Auto-generated
		self::addModifier('data_cadastro,data_lastlogin', 'datetime',   'br');
		self::addModifier('renasem,nome,responsavel_nome', 'trim');
		self::addModifier('rg_ie,atividade,fone1,fone2,email',          'trim');
		self::addModifier('cep,uf,cidade,bairro,endereco,numero,complemento,referencia', 'trim');
		self::addModifier('observacoes_internas', 'trim');
		self::addModifier('senha,facebook_id,googleacc_id',             'trim');
		self::addModifier('interesses_json', 'json');
		
		self::addModifier('nome_completo,responsavel_nome', 'ucfirst');
		self::addModifier('cpf_cnpj,cep', 'force_numbers');
		self::addModifier('cpf_cnpj',    'number_mask', '###.###.###-##|11');
		self::addModifier('cpf_cnpj',    'number_mask', '####.####/####-##|14');
		self::addModifier('rg_ie',       'number_mask', '##.##.### #|8');
		self::addModifier('cep',         'number_mask', '#####-###|8');
		
		self::addModifier('uf',          'upper');
		self::addModifier('email',       'lower');
		
		self::addModifier('renasem', 'callback', function($obj, $string){
			// Converte apenas na hora de jogar para o banco de dados, senão a validação não vai pegar.
			return dHelper2::formataRenasem($string);
		}, 'basic2db');
		
		// Campos adicionais em 2020-04-29
        self::addField('data_nasc,estado_civil');
        self::addValidation('data_nasc',    'date',       'br',  'Preencha Data de Nascimento no formato dd/mm/aaaa');
        self::addValidation('estado_civil', 'singleline', false, 'O campo Estado Civil não pode ter mais de uma linha');
        self::addValidation('estado_civil', 'strmax',     150,   'O campo Estado Civil não pode ter mais de 150 caracteres');
        self::addModifier('data_nasc',    'date',     'br');
        self::addModifier('estado_civil', 'trim');
		
		/**
			$senha = substr($senha, 0, 30);
			$senha = md5($senha.$id*478)
		**/
		$_passwordEmpty = "** criptografada **";
		self::addValidation   ('senha',  'callback', function($obj, $password)        use ($_passwordEmpty){
			if( $obj->getVirtual('senha-donthash')){
				// Se pediu para não hashear a senha, é porque já está passando uma
				// senha tratada. Dessa forma, aceite o valor fornecido sem reclamar.
				return true;
			}
			if( $obj->getVirtual('loginType') == 'noPassword'){
				// Se tiver o virtual '_ignorePassword', ignora a validação da senha...
				return true;
			}
			if(!strlen($obj->getValue('senha')) && !strlen($obj->getValue('facebook_id')) && !strlen($obj->getValue('googleacc_id'))){
				$obj->addError('senha',   "Você precisa selecionar ao menos uma rede social ou informar uma senha.");
				return false;
			}
			
			// Lembrando que a validação de senha ocorre apenas com os dados no formato 'basic', antes
			// de serem chamados para o banco de dados.
			if(!$password){
				// Podemos aceitar senha em branco, se houver um facebook_id (verificação feita anteriormente).
				return true;
			}
			if( $password == $_passwordEmpty){
				// Nenhuma mudança, vamos aceitar sem validação.
				return true;
			}
			
			$_failed = false;
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
			
			if(!$_failed && $obj->hasVirtual('resenha')){
				if(!trim($obj->getVirtual('resenha'))){
					$obj->addError('resenha', "Repita a senha, para confirmar que você digitou corretamente.");
					$_failed = true;
				}
				elseif($password != trim($obj->getVirtual('resenha'))){
					$obj->addError('senha', "A senha e a confirmação de senha não conferem. Por favor, re-digite ambos.");
					$_failed = true;
				}
			}
			return !$_failed;
		});
		self::addModifier     ('senha',  'callback', function($obj, $password, $when) use ($_passwordEmpty){
			if($when == 'raw2basic'){
				$password = trim($password);
				return strlen($password)?$password:false;
			}
			if($when == 'basic2db'){
				if(!strlen($password)){
					return false;
				}
				if( $password == $_passwordEmpty){
					// Não alterou a senha, ela se mantém como $_passwordEmpty.
					return $password;
				}
				if( $obj->getVirtual('senha-donthash')){
					$obj->removeVirtual('senha-donthash');
					$obj->v('senha', $_passwordEmpty);
					return $password;
				}
				
				// Se solicitar getValue no formato 'basic' a partir deste ponto, já deve ser empty de novo.
				$obj->setValue('senha', $_passwordEmpty);
				
				// Mas como estamos lidando com basic2db, precisamos armazenar o hash...
				$senha = md5   ($password.'cUsuario'.dSystem::getGlobal('hashkey'));
				# echo "- passwordDebug: Primeira criptografia é: {$senha}.<br />";
				
				$senha = substr($senha, 0, 30)."OH";
				# echo "- passwordDebug: Valor a ser saltado é: {$senha}.<br />";
				
				// Se for um usuário logado, podemos saltear com o 'id' e obter a string final.
				// Se for um novo usuário, vamos adicionar _pendingPasswordSalt, para montar o
				// hash final depois que houver um id válido.
				if($obj->v('id')){
					$senha = md5($senha.($obj->v('id')*478));
					# echo "- passwordDebug: Post-salt ficou como: {$senha}.<br />";
				}
				else{
					$obj->setVirtual('_pendingPasswordSalt', $senha);
				}
				
				return $senha;
			}
			if($when == 'db2basic'){
				return $password?
					$_passwordEmpty:
					false;
			}
		}, 'raw2basic,basic2db,db2basic');
		self::addEventListener('afterCreate',        function($obj){
			if($prevSenha = $obj->getVirtual('_pendingPasswordSalt')){
				# echo "- passwordDebug: Aplicando _pendingPasswordSalt à {$prevSenha}.<br />";
				$obj->removeVirtual('_pendingPasswordSalt');
				$newSenha = md5($prevSenha.$obj->v('id')*478);
				# echo "- passwordDebug: Resultado final ficou {$newSenha}<br />";
				$obj->setVirtual('senha-donthash', true);
				$obj->v('senha', $newSenha)->save();
			}
		});
		
		self::setDefaultValue('data_cadastro', date('d/m/Y H:i:s'));
		self::setDefaultValue('disabled',      '0');
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
	Function importFromRenasem($renasem){
		$exRenasem = cRenasem::load($renasem, 'renasem');
		if(!$exRenasem){
			return false;
		}
		
		$cpfCnpj = preg_replace("/[^0-9]/", "", $exRenasem->v('cpf_cnpj'));
		$tipo    = (strlen($cpfCnpj)<=12)?'pf':'pj';
		
		$this
			->v('renasem',      $exRenasem->v('renasem'))
			->v('tipo',         $tipo)
			->v('nome',         $exRenasem->v('nome'))
			->v('cpf_cnpj',     $exRenasem->v('cpf_cnpj'))
			->v('atividade',    $exRenasem->v('atividade'))
			->v('cep',          $exRenasem->v('cep'))
			->v('uf',           $exRenasem->v('uf'))
			->v('cidade',       $exRenasem->v('cidade'))
			->v('endereco',     $exRenasem->v('endereco'));
		
		return true;
	}
	
	Function isAgente(){
		return $this->v('agente_pending')||$this->v('agente_vendedor')||$this->v('agente_captador');
	}
	Function isAgenteOrRedirect($allowPendente=false){
		// Se não for agente, redireciona pra home do comerciante (newauction).
		// Se não for comerciante também, morre com erro fatal.
		if(!$this->isAgente() && !$this->isComerciante()){
			// Jamais deveria atingir este ponto: Usuário não é comerciante nem agente.
			// Lembrando que agentes pendentes *são considerados* em isAgente().
			dSystem::notifyAdmin('MED', "Usuário não é agente nem comerciante - Não deveria estar logado",
				"Usuário id: {$this->v('id')}.\r\n".
				"Controle realizado em {$_SERVER['PHP_SELF']}.\r\n".
				"\r\n".
				"Mostrando mensagem de erro fatal para o usuário.\r\n",
				true
			);
			die;
		}
		if(!$this->isAgente()){
			dHelper2::redirectTo("newauction.php");
			die;
		}
		
		// Daqui pra baixo, sabemos que ->isAgente() é true.
		if(!$allowPendente && $this->v('agente_pending')){
			// Está pendente: Redirecione pra agente_central, onde aparecerá a mensagem de "Aguarde liberação."
			dHelper2::redirectTo("agente_central.php");
			die;
		}
		return true;
	}
	
	/**
	 * Se o agente estiver "simulando login" como um comerciante,
	 * este método vai retornar o usuário *do agente*.
	 *
	 * @return cUsuario Agente que está simulando o login.
	 */
	Function getAgente(){
		// Trata-se de um agente "agindo em nome..."?
		return $this->getVirtual('agente_acting_as');
	}
	
	/**
	 * Ativa a função "Agente Acting As...".
	 * Deste momento em diante, todas as chamadas para cUsuario::isLogged()
	 * retornarão o usuário comerciante. Para obter o agente, utilize ->getAgente().
	 *
	 * @param $usuarObj cUsuario
	 */
	Function agenteActAs(cUsuario $usuarObj){
		if(!$this->agenteCanActAs($usuarObj)){
			$this->agenteStopActing();
			dHelper2::redirectTo("agente_clientes.php");
			die;
		}
		
		dAuditAcesso::log("INIT_ACTING", $usuarObj->v('id'), "Agente começou a agir em nome do cliente {$usuarObj->v('nome')}");
		$_SESSION['agente_acting_as'] = $usuarObj->v('id');
	}
	Function agenteCanActAs(cUsuario $usuarObj){
		// echo "agenteCanActAs: Verificando se {$this->v('nome')} pode agir em nome de {$usuarObj->v('nome')}<br />";
		
		if(!$this->isAgente()){
			// echo "Não sou agente, não posso agir como...";
			return false;
		}
		if( $this->v('agente_pending')){
			// echo "Sou agente, mas meu acesso está bloqueado.";
			return false;
		}
		
		if(!$usuarObj->isComerciante()){
			// echo "Não tem sentido simular login com um usuário que não é comerciante.";
			return false;
		}
		if( $usuarObj->isAgente()){
			// echo "O outro usuário é agente, um agente não pode agir como outro...";
			return false;
		}
		
		if(!$this->isAgenteOf($usuarObj)){
			// echo "Não sou o agente responsável por esse comerciante...";
			return false;
		}
		if( $usuarObj->v('id') == $this->v('id')){
			// echo "Sou eu mesmo!";
			return false;
		}
		
		return true;
	}
	Function agenteGetActingAs(){
		// Se estiver "agindo como..." alguém, retorne esse alguém.
		$actingAsId = @$_SESSION['agente_acting_as'];
		if(!$actingAsId){
			return false;
		}
		
		// echo "- agenteGetActing as: Simulando login como {$actingAsId}<br />";
		$usuarObj = cUsuario::load($actingAsId);
		// echo "- agenteGetActing as: Simulando login como {$usuarObj->v('nome')}<br />";
		if(!$usuarObj){
			return false;
		}
		if(!$this->agenteCanActAs($usuarObj)){
			$this->agenteStopActing();
			return false;
		}
		
		return $usuarObj;
	}
	Function agenteStopActing(){
		if(isset($_SESSION['agente_acting_as'])){
			dAuditAcesso::log("STOP_ACTING", $_SESSION['agente_acting_as'], "Agente parou de agir em nome do cliente.");
			unset($_SESSION['agente_acting_as']);
			// echo '<pre>';
			// trigger_error("Foi chamado stopActing.()");
			// echo '</pre>';
		}
	}
	
	Function isComerciante(){
		return $this->v('renasem') ||  $this->v('comerciante_sem_renasem');
	}
	Function isComercianteOrRedirect(){
		if(!$this->isAgente() && !$this->isComerciante()){
			// Jamais deveria atingir este ponto: Usuário não é comerciante nem agente.
			// Lembrando que agentes pendentes *são considerados* em isAgente().
			dSystem::notifyAdmin('MED', "Usuário não é agente nem comerciante - Não deveria estar logado",
				"Usuário id: {$this->v('id')}.\r\n".
				"Controle realizado em {$_SERVER['PHP_SELF']}.\r\n".
				"\r\n".
				"Mostrando mensagem de erro fatal para o usuário.\r\n",
				true
			);
			die;
		}
		if(!$this->isComerciante()){
			dHelper2::redirectTo("agente_central.php");
			die;
		}
		
		return true;
	}
	
	Function isAgenteOf($usuarObj){
		// Verifica se $usuarObj->v('agente_id') é o usuário atual.
		return ($usuarObj->v('agente_id')&&$usuarObj->v('agente_id')==$this->v('id'));
	}
	Function isAgenteOfOrDie($usuarObj){
		if($this->isAgenteOf($usuarObj)){
			return true;
		}
		
		layCima("Acesso negado");
		echo "<p style='margin: 16px'>Este cliente já está associado a outro agente. Se discorda disso, entre em contato com seu supervisor.</p>";
		layBaixo();
		die;
	}
	
	Function notifyNewUser($password){
		if($this->v('agente_pending')){
			dEmail::sendFromTemplate(Array(
				'template' => 'admin',
				'subject'  => "Novo AGENTE cadastrado",
				'replace'  => Array(
					'[LINK_PAINEL]' => "http://conectasementes.plansson.dev.br/admin/cliente_edit.php?id={$this->v('id')}",
					'[MENSAGEM]' =>
						"Um novo usuário acaba de se cadastrar como AGENTE Conecta Sementes, e está com o cadastro pendente de liberação.<br />".
						"<br />".
						"Cliente: {$this->v('nome')}<br />".
						"Telefone de contato: {$this->v('fone1')}<br />".
						"Endereço de e-mail: {$this->v('email')}<br />",
				),
				'to'       => dConfiguracao::getConfig('CORE/MAIL_TO'),
				'grupo'    => "Novo agente cadastrado (Versão Administrador)",
			));
			return true;
		}
		
		dEmail::sendFromTemplate(Array(
			'template' => 'cadastro',
			'replace'  => Array(
				'[NOME]'               => $this->v('responsavel_nome')?$this->v('responsavel_nome'):$this->v('nome'),
				'[SENHA]'              => $password?$password:"Login apenas com redes sociais",
				'[LINK_CRIAR_ANUNCIO]' => $this->getLink('newauction.php'),
			),
			'to'       => $this->v('email'),
			'grupo'    => "Novo usuário cadastrado",
			'subject'  => "Boas vindas da Conecta Sementes",
		));
		dEmail::sendFromTemplate(Array(
			'template' => 'admin',
			'subject'  => "Novo usuário cadastrado",
			'replace'  => Array(
				'[LINK_PAINEL]' => "https://conectasementes.plansson.dev.br/admin/cliente_edit.php?id={$this->v('id')}",
				'[MENSAGEM]' =>
					"Um novo usuário acaba de se cadastrar no sistema Conecta Sementes.<br />".
					"RENASEM: {$this->v('renasem')}<br />".
					"Cliente: {$this->v('nome')}<br />".
					"Nome do responsável: {$this->v('responsavel_nome')}<br />".
					"Telefone de contato: {$this->v('fone1')}<br />".
					"Endereço de e-mail: {$this->v('email')}<br />",
			),
			'to'       => dConfiguracao::getConfig('CORE/MAIL_TO'),
			'grupo'    => "Novo usuário cadastrado (Versão Administrador)",
		));
	}
	
	/**
	 * Referência básica para o formato de interesses_json:
	 *      [troca]         true/false
	 *      [venda|compra]
	 *          [ativar]    true/false
	 *          --- regiao/embalagem ---
	 *          [notRegiao]    []
	 *          [notEmbalagem] [Saco 5Kg]
	 *          --- sementes ---
	 *          [tudo]      true/false
	 *          [excetoStr] [soja, milho, sorgo]
	 *          [excetoIds] [12, 15, 18]
	 *          [onlyStr]   [sorgo, feijão]
	 *          [onlyIds]   [18, 21]
	 */
	Function interesseBuild(){
		$_placeHolder = [
			'ativar'       => false,
			'notRegiao'    => [],
			'notEmbalagem' => [],
			'tudo'         => true,
			'excetoStr'    => [],
			'excetoIds'    => [],
			'onlyStr'      => [],
			'onlyIds'      => [],
		];
		return [
			'compra'=>$_placeHolder,
			'venda' =>$_placeHolder,
			'troca' =>false,
		];
	}
	Function setInteresse($type, $group=null, $item=null, $trueFalse=null){
		// setInteresseToggle(compra/venda, true/false);
		// setInteresseToggle(compra/venda, 'tudo', true);
		// setInteresseToggle(compra/venda, 'tudo', false);
		// setInteresseToggle(compra/venda, 'regiao', 'AM', true);
		// setInteresseToggle(compra/venda, 'regiao', 'AM', false);
		$nArgs = func_num_args();
		$dados = $this->getValue('interesses_json');
		if(!$dados){
			$dados = $this->interesseBuild();
		}
		
		// Define interesse no grupo principal (compra/ativar)
		if($nArgs == 2){
			// Define venda/compra/troca como true/false.
			if($type == 'troca'){
				$dados[$type] = $group;
				return $this->setValue('interesses_json', $dados)->save();
			}
			
			$dados[$type]['ativar'] = $group;
			return $this->setValue('interesses_json', $dados)->save();
		}
		
		// Define interesse nas sementes (tudo ou nada).
		elseif($nArgs == 3 && $group == 'tudo'){
			// Define troca como true/false
			$dados[$type]['tudo'] = $item;
			return $this->setValue('interesses_json', $dados)->save();
		}
		
		// Define interesse em região ou embalagem
		elseif($nArgs == 4){
			if($group != 'embalagem' && $group != 'regiao'){
				trigger_error("Grupo desconhecido: {$group}", E_USER_ERROR);
				die("Erro fatal.");
			}
			
			$_useGroup = 'not'.ucfirst($group); // notRegiao | notEmbalagem
			$_useList  = ($group == 'regiao')?
				dHelper2::csDropRegiao():
				dHelper2::csDropEmbalagem();
			
			if(!in_array($item, $_useList)){
				// Estou adicionando uma exceção que não existe...
				// Pode ser uma SQL Injection, ou algum anúncio com dados fora do padrão.
				
				$db        = dDatabase::start();
				$fromAnunc = $db->singleResult("select 1 from c_anuncios where status = 'Ag. Propostas' and {$group} = '".addslashes($item)."' limit 1");
				if(!$fromAnunc){
					// Realmente não existe na lista pré-definida e não é de nenhum anúncio....
					$this->addError(false, "Desculpe, opção não encontrada.");
					return false;
				}
			}
			
			$listaNao = $dados[$type][$_useGroup];
			if($trueFalse){
				// Se type=venda, group=regiao, item=PR e true, então remove da lista "não"
				$listaNao = array_diff($listaNao, [$item]);
			}
			else{
				$listaNao = array_unique(array_merge($listaNao, [$item]));
			}
			
			$dados[$type][$_useGroup] = $listaNao;
			return $this->setValue('interesses_json', $dados)->save();
		}
	}
	
	Function addException($type, $stringOrId, $isString){
		// addException('compra', 'Soja'); // Quero comprar tudo, MENOS Soja.
		// addException('compra', 123);    // Quero comprar tudo, MENOS Soja e Variedade ID=123
		return $this->_addExceptionOrOnly($type, $stringOrId, 'exceto', $isString);
	}
	Function addOnly($type, $stringOrId, $isString){
		// addOnly('compra', 'Soja'); // Quero comprar APENAS Soja.
		// addOnly('compra', 123);    // Quero comprar APENAS Soja ou Variedade ID=123
		return $this->_addExceptionOrOnly($type, $stringOrId, 'only', $isString);
	}
	Function _addExceptionOrOnly($type, $stringOrId, $prefixKey, $isString=false){
		// type:       'compra' ou 'venda'
		// stringOrId: varieId | "Cultura" | "Partial String" com até 20 caracteres.
		// prefixKey:  'exceto' ou 'only'
		// isString:   Se $stringOrId é um id ou uma string.
		
		// Retorno:
		// > false, ou:
		// > [type: ID|STR, value: "..."]
		$stringOrId = trim($stringOrId);
		if(!$isString && !is_numeric($stringOrId)){
			$this->addError(false, "Parâmetro inválido: Esperando id.");
			return false;
		}
		
		if($isString){
			// Em relação a string, vamos armazenar da seguinte forma:
			// 1) Se for uma "Cultura" válida, armazenamos como String mesmo.
			// 2) Se for uma VARIEDADE, vamos substituir pelo ID.
			// 3) Se for uma outra string parcial, utilizamos até o limite de 20 caracteres.
			$_isCultura   = function() use ($stringOrId){
				$_cultList = dHelper2::csListCulturas();
				$_cultIdx  = array_search(mb_strtolower($stringOrId), $_cultList);
				return ($_cultIdx!==false)?$_cultList[$_cultIdx]:false;
			};
			$_isVariedade = function() use ($stringOrId){
				$sqlWhere = "REPLACE(variedade, ' ', '') = REPLACE('".addslashes($stringOrId)."', ' ', '')";
				$listObjs = cRefVariedade::multiLoad("where {$sqlWhere} limit 1", ['onlyFields'=>'id']);
				if(sizeof($listObjs)){
					return $listObjs[0]->v('id');
				}
				return false;
			};
			
			$strCultura = $_isCultura();
			if($strCultura){
				// Garante que a cultura informada tem a mesma grafia do dHelper2::csDropCulturas.
				$stringOrId = $strCultura;
			}
			elseif($idVariedade = $_isVariedade()){
				// Substitui pelo ID da variedade desejada.
				$stringOrId = $idVariedade;
				$isString   = false;
			}
			else{
				// Escrita livre só existe se $type for 'only'. Em caso de exceções,
				// o id ou a cultura sempre serão enviados pelo ajax, não terá liberdade
				// para escrever.
				if($type == 'exceto'){
					$this->addError(false, "Erro interno: Impossível adicionar exceção com texto livre.");
					dSystem::notifyAdmin(
						"MED",
						"Impossível adicionar exceção com texto livre.",
						"Usuário '{$this->v('id')}' tentou adicionar a string '{$stringOrId}', ".
						"que não foi detectada como sendo uma cultura em dHelper2::csListCulturas() ".
						"nem foi encontrada no banco de dados como sendo uma variedade. ".
						"Mostrando mensagem de erro interno pro cliente."
					);
					return false;
				}
				
				// Vamos aplicar validação básica sobre essa string.
				$stringOrId = preg_replace("/ +/", " ", $stringOrId);
				if(preg_match("/[^[:alnum:] ]/u", $stringOrId)){
					$this->addError(false, "Você informou caracteres não permitidos.");
					return false;
				}
				
				if(strlen($stringOrId) < 3){
					$this->addError(false, "Insira ao menos 3 caracteres para aplicar este filtro.");
					return false;
				}
				if(strlen($stringOrId) > 20){
					$this->addError(false, "Este filtro ficou muito grande para ser adicionado.");
					return false;
				}
			}
		}
		else{
			$stringOrId = intval($stringOrId);
			if($stringOrId < 0){
				$this->addError(false, "Parâmetro inválido.");
				return false;
			}
		}
		
		// Vamos armazenar, evitando duplicados.
		$dados = $this->getValue('interesses_json');
		if(!$dados){
			$dados = $this->interesseBuild();
		}
		
		if(!$isString){
			// Vamos salvar na lista de IDs.
			$stringOrId = intval($stringOrId);
			$db         = dDatabase::start();
			$isValidId  = $db->singleResult("SELECT id FROM c_ref_variedades WHERE id = '{$stringOrId}' LIMIT 1");
			if(!$isValidId){
				$this->addError(false, "Desculpe, o item informado não foi encontrado.");
				return false;
			}
			
			if(sizeof($dados[$type][$prefixKey.'Ids']) >= 100){
				$this->addError(false, "Você atingiu seu limite de filtros. Exclua alguns itens para adicionar outros.");
				return false;
			}
			if(!in_array($isValidId, $dados[$type][$prefixKey.'Ids'])){
				$dados[$type][$prefixKey.'Ids'][] = $isValidId;
				$this->setValue('interesses_json', $dados)->save();
			}
			
			return ['type'=>'ID', 'value'=>$isValidId];
		}
		
		// Vamos salvar na lista de Strings.
		// * A lista de Strings terá um limite de 50 items.
		// * Se passar disso, vamos excluir os primeiros.
		$exLista = $dados[$type][$prefixKey.'Str'];
		if(sizeof($exLista) >= 30){
			$this->addError(false, "Você atingiu seu limite de filtros. Exclua alguns itens para adicionar outros.");
			return false;
		}
		
		$isNew   = !preg_grep("/^".preg_quote($stringOrId)."$/i", $exLista);
		if($isNew){
			$dados[$type][$prefixKey.'Str'][] = $stringOrId;
			$this->setValue('interesses_json', $dados)->save();
		}
		else{
			$this->addError(false, "Este item já constava na sua lista.");
			return false;
		}
		
		return ['type'=>'STR', 'value'=>$stringOrId];
	}
	
	Function removeException($type, $stringOrId, $isString){
		return $this->_removeExceptionOrOnly($type, $stringOrId, 'exceto', $isString);
	}
	Function removeOnly($type, $stringOrId, $isString){
		return $this->_removeExceptionOrOnly($type, $stringOrId, 'only', $isString);
	}
	Function _removeExceptionOrOnly($type, $stringOrId, $prefixKey, $isString){
		// prefixKey: 'exceto' ou 'only'
		$dados = $this->getValue('interesses_json');
		if(!$dados){
			return false;
		}
		
		// echo "Pretendo excluir: {$stringOrId}<br />";
		
		if(!$isString && ($foundIdx = array_search($stringOrId, $dados[$type][$prefixKey.'Ids'])) !== NULL){
			unset($dados[$type][$prefixKey.'Ids'][$foundIdx]);
		}
		if( $isString && ($foundIdx = array_search($stringOrId, $dados[$type][$prefixKey.'Str'])) !== NULL){
			unset($dados[$type][$prefixKey.'Str'][$foundIdx]);
		}
		
		$this->setValue('interesses_json', $dados)->save();
		return true;
	}
	
	/**
	 * Este método vai converter o 'interesses_json' do banco de dados em variáveis de
	 * fácil manipulação.
	 *
	 * @param bool $evenDisabled Se false, retorna apenas o consolidado.
	 * @param String $onlyType Opções: compra | venda | troca
	 * @return array|false Retorno é:
	 * Array:
	 * <table border='0' cellpadding='4' cellspacing='1' style='background: #000'>
	 *  <tr bgcolor='#FFFFFF'><td bgcolor='#CCFFCC' color='red'>$type</td><td></td></tr>
	 *  <tr bgcolor='#FFFFFF'>
	 *      <td>compra|venda</td>
	 *      <td>
	 *          Será <b>false</b> se (!$evenDisabled e !ativar), ou...<br /><br />
	 *          <table border='0' cellpadding='4' cellspacing='1' style='background: #000'>
	 *              <tr bgcolor='#EEEEEE'><td>ativar</td><td>true/false</td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>notRegiao</td><td><i>false</i> ou [AL, AM, SC]</td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>notEmbalagem</td><td><i>false</i> ou [ASD, ASD, ASD]</td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>tudo</td><td>true/false (leia como 'todas as sementes')</td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>excetoStr</td><td>[soja, milho, sorgo, feijão, etc]<br /><small>* Apenas se tudo=true ou $evenDisabled</small></td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>excetoIds</td><td>[1,2,3,4,5,6]<br /><small>* Apenas se tudo=true ou $evenDisabled</small></td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>onlyStr</td><td>[soja, milho, sorgo, feijão, etc]<br /><small>* Apenas se tudo=false ou $evenDisabled</small></td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>onlyIds</td><td>[1,2,3,4,5,6]<br /><small>* Apenas se tudo=false ou $evenDisabled</small></td></tr>
	 *              <tr bgcolor='#EEEEEE'><td>userDefined</td><td>true/false, indica se usuário fez alguma customização</td></tr>
	 *          </table>
	 *      </td>
	 *  </tr>
	 *  <tr bgcolor='#FFFFFF'>
	 *      <td>troca</td>
	 *      <td>true | false</td>
	 *  </tr>
	 * </table>
	 * * Se interesses_json for NULL ou (!venda e !compra), retorne tudo ativo (padrão).<br />
	 * * Se !venda ou !compra, troca será false.
	 */
	Function getInteresses($evenDisabled=false, $onlyType=false){
		// $evenDisabled garante que tudo será retornado, mesmo se 'ativar'=>false.
		// O $evenDisabled será usado em meus-interesses.php
		$dados = $this->v('interesses_json');
		if($evenDisabled){
			if(!$dados){
				return $this->interesseBuild();
			}
			return dHelper2::addDefaultToArray($this->v('interesses_json'), $this->interesseBuild());
		}
		
		if(!$dados || (!$dados['compra']['ativar'] && !$dados['venda']['ativar'])){
			// Nenhum interesse definido, o banco de dados está vazio.
			// Vamos retornar o padrão, que é tudo ativado.
			$dados = $this->interesseBuild();
			$dados['compra']['userDefined'] = false;
			$dados['venda' ]['userDefined'] = false;
			$dados['troca'] = true;
		}
		else{
			foreach(['compra', 'venda'] as $_group){
				// Converte [compra|venda] para false, se não houver o ativar.
				if(!$dados[$_group]['ativar']){
					$dados[$_group] = false;
				}
				else{
					// Se usuário falou "apenas...", mas não informou nada, volta para "tudo"
					if(!$dados[$_group]['tudo'] && !$dados[$_group]['onlyIds'] && !$dados[$_group]['onlyStr']){
						$dados[$_group]['tudo'] = true;
					}
					
					// Remove o onlyIds|Str ou excetIds|Str caso não sejam aplicáveis.
					if($dados[$_group]['tudo']){
						unset($dados[$_group]['onlyStr']);
						unset($dados[$_group]['onlyIds']);
					}
					else{
						unset($dados[$_group]['excetoStr']);
						unset($dados[$_group]['excetoIds']);
					}
					
					// Adiciona userDefined:
					$dados[$_group]['userDefined'] = true;
					if($dados[$_group]['ativar']    &&  $dados[$_group]['tudo']
						&& !$dados[$_group]['notRegiao'] && !$dados[$_group]['notEmbalagem']
						&& !$dados[$_group]['excetoIds'] && !$dados[$_group]['excetoStr']){
						$dados[$_group]['userDefined'] = false;
					}
				}
			}
			
			// Se não quer comprar ou vender, não pode trocar.
			if(!$dados['compra'] || !$dados['venda']){
				$dados['troca'] = false;
			}
		}
		
		return $onlyType?
			$dados[$onlyType]:
			$dados;
	}
	
	/**
	 * Retorno esperado: <i>false</i> se desativou esse interesse, ou...
	 *      [userDefined] => true / false
	 *      [appendWhere] => ["anuncObj.negocio='Venda'", "anuncObj.regiao NOT IN (...)"]
	 *      Futuro: [explainText]  => ["Regiões TO, SP, PR e mais 4...", "Todas as embalagens", "Todas as sementes, exceto 4..."]
	 */
	Function getInteressesAsSQL($type, $tableAnuncios='c_anuncios', $tableVariedade='varieObj'){
		// Retorna uma lista a ser inserida no WHERE...
		if($type != 'compra' && $type != 'venda' && $type != 'troca'){
			trigger_error("Parâmetro inválido para {$type}");
			return false;
		}
		
		// Como lidar com a troca?
		// --> varie_id       tem que bater com os interesses de compra
		// --> regiao         tem que bater com os interesses de compra
		// --> embalagem      tem que bater com os interesses de compra
		// --> troca_varie_id tem que bater com os interesses de venda
		$interesses = $this->getInteresses();
		if($type == 'troca' && (!$interesses['venda'] || !$interesses['compra'])){
			// Impossível levantar interesses de troca, pois compra ou venda estão inativos.
			return false;
		}
		if($type == 'troca' && !$interesses['troca']){
			// Poderia, mas não quer trocar...
			return false;
		}
		
		$dados = ($type=='troca' || $type=='compra')?
			$interesses['compra']:
			$interesses['venda'];
		
		if(!$dados){
			// Impossível levantar interesses de {$type}, pois eles não foram definidos.
			return false;
		}
		
		$typeToNegocio = [
			'troca'  => 'Troca',
			'compra' => 'Venda',
			'venda'  => 'Compra',
		];
		$whereList     = [];
		if($dados['userDefined']){
			if($dados['notRegiao']){
				$_notRegiao = array_map(function($item){
					return "'".addslashes($item)."'";
				}, $dados['notRegiao']);
				$whereList[] = "{$tableAnuncios}.regiao NOT IN(".implode(", ", $_notRegiao).")";
			}
			if($dados['notEmbalagem']){
				$_notEmbalagem = array_map(function($item){
					return "'".addslashes($item)."'";
				}, $dados['notEmbalagem']);
				$whereList[] = "{$tableAnuncios}.embalagem NOT IN(".implode(", ", $_notEmbalagem).")";
			}
			if($dados['tudo']){
				// Tudo, exceto...
				$_excetoIds = array_map('intval', $dados['excetoIds']);
				$_excetoStr = array_map(function($item){
					return str_replace(["%", "_", "'", '"'], "", $item);
				}, $dados['excetoStr']);
				
				if($_excetoIds){
					$whereList[] = "{$tableAnuncios}.varie_id NOT IN(".implode(", ", $_excetoIds).")";
				}
				if($_excetoStr){
					foreach($_excetoStr as $_excetoItem){
						$whereList[] = "CONCAT({$tableVariedade}.cultura, {$tableVariedade}.variedade)  NOT LIKE '%".addslashes($_excetoItem)."%'";
					}
				}
			}
			elseif(!$dados['tudo']){
				// Apenas os selecionados...
				$_onlyIds = array_map('intval', $dados['onlyIds']);
				$_onlyStr = array_map(function($item){
					return str_replace(["%", "_", "'", '"'], "", $item);
				}, $dados['onlyStr']);
				
				$_onlyOptions = [];
				if($_onlyIds){
					$_onlyOptions[] = "{$tableAnuncios}.varie_id IN(".implode(", ", $_onlyIds).")";
				}
				if($_onlyStr){
					foreach($_onlyStr as $_onlyItem){
						$_onlyOptions[] = "CONCAT({$tableVariedade}.cultura, {$tableVariedade}.variedade) LIKE '%".addslashes($_onlyItem)."%'";
					}
				}
				
				if($_onlyOptions){
					$whereList[] = "(".implode(" OR \r\n", $_onlyOptions).")";
				}
			}
		}
		if($type == 'troca'){
			$trocaDados = $interesses['venda'];
			if($trocaDados['tudo']){
				// Tudo, exceto...
				$_excetoIds = array_map('intval', $trocaDados['excetoIds']);
				$_excetoStr = array_map(function($item){
					return str_replace(["%", "_", "'", '"'], "", $item);
				}, $trocaDados['excetoStr']);
				
				if($_excetoIds){
					$whereList[] = "{$tableAnuncios}.troca_varie_id NOT IN(".implode(", ", $_excetoIds).")";
				}
				if($_excetoStr){
					foreach($_excetoStr as $_excetoItem){
						$whereList[] = "CONCAT(trocaVarieObj.cultura, trocaVarieObj.variedade)  NOT LIKE '%".addslashes($_excetoItem)."%'";
					}
				}
			}
			elseif(!$dados['tudo']){
				// Apenas os selecionados...
				$_onlyIds = array_map('intval', $trocaDados['onlyIds']);
				$_onlyStr = array_map(function($item){
					return str_replace(["%", "_", "'", '"'], "", $item);
				}, $trocaDados['onlyStr']);
				
				$_onlyOptions = [];
				if($_onlyIds){
					$_onlyOptions[] = "{$tableAnuncios}.troca_varie_id IN(".implode(", ", $_onlyIds).")";
				}
				if($_onlyStr){
					foreach($_onlyStr as $_onlyItem){
						$_onlyOptions[] = "CONCAT(trocaVarieObj.cultura, {$tableVariedade}.variedade) LIKE '%".addslashes($_onlyItem)."%'";
					}
				}
				
				if($_onlyOptions){
					$whereList[] = "(".implode(" OR \r\n", $_onlyOptions).")";
				}
			}
			
			if($trocaDados['userDefined']){
				$dados['userDefined'] = false;
			}
		}
		
		return [
			'userDefined'=>$dados['userDefined'],
			'appendWhere'=>implode("\r\n\tand ", $whereList),
		];
	}
	Function matchesInteresses(cAnuncio $anuncObj){
		if($anuncObj->v('usuar_id') == $this->v('id')){
			trigger_error("Não deveria rodar matchesInteresses no anuncio do próprio usuário.");
			return false;
		}
		
		$type = $anuncObj->v('negocio');
		if($type == 'Venda')      $type = 'compra';
		elseif($type == 'Compra') $type = 'venda';
		elseif($type == 'Troca')  $type = 'troca';
		
		// Como lidar com a troca?
		// --> varie_id       tem que bater com os interesses de compra
		// --> regiao         tem que bater com os interesses de compra
		// --> embalagem      tem que bater com os interesses de compra
		// --> troca_varie_id tem que bater com os interesses de venda
		$interesses = $this->getInteresses();
		if(!$interesses[$type]){
			// Marcou que não tem interesse em comprar|vender|trocar.
			return false;
		}
		
		if($type == 'troca' && !$interesses['compra']['userDefined'] && !$interesses['venda']['userDefined']){
			// Não customizou interesses em compra+venda..
			// Então também não customizou em troca.
			return true;
		}
		if(!$interesses[$type]['userDefined']){
			// Não customizou interesses em compra|venda
			return true;
		}
		
		$dados = ($type=='troca' || $type=='compra')?
			$interesses['compra']:
			$interesses['venda'];
		
		if($dados['userDefined']){
			// Só vai "pular" este bloco caso seja troca.
			if($dados['notRegiao']    && in_array($anuncObj->v('regiao'),    $dados['notRegiao'])){
				// Unmatch: Regiao indesejada.
				return false;
			}
			if($dados['notEmbalagem'] && in_array($anuncObj->v('embalagem'), $dados['notEmbalagem'])){
				// Unmatch: Embalagem indesejada.
				return false;
			}
			
			if($dados['tudo']){
				// Tudo, exceto...
				$_excetoIds = $dados['excetoIds'];
				if(in_array($anuncObj->v('varie_id'), $_excetoIds)){
					// Disse que quer tudo, menos essa variedade...
					return false;
				}
				
				$_excetoStr = $dados['excetoStr'];
				if($_excetoStr){
					$_tryString = dHelper2::removeAccents(mb_strtolower("{$anuncObj->v('varieObj')->v('cultura')} {$anuncObj->v('varieObj')->v('variedade')}"));
					foreach($_excetoStr as $_excetoItem){
						$_excetoItem = dHelper2::removeAccents(mb_strtolower($_excetoItem));
						if(strpos($_tryString, $_excetoItem) !== false){
							// Disse que quer tudo, menos o que contém esses temos...
							return false;
						}
					}
				}
			}
			elseif(!$dados['tudo']){
				// Apenas os selecionados...
				$_onlyIds  = $dados['excetoIds'];
				$_anyMatch = false;
				if(in_array($anuncObj->v('varie_id'), $_onlyIds)){
					// Encontrei uma das variedades que ele deseja!
					$_anyMatch = true;
				}
				
				$_onlyStr = $dados['excetoStr'];
				if(!$_anyMatch && $_onlyStr){
					$_tryString = dHelper2::removeAccents(mb_strtolower("{$anuncObj->v('varieObj')->v('cultura')} {$anuncObj->v('varieObj')->v('variedade')}"));
					foreach($_onlyStr as $_onlyItem){
						$_excetoItem = dHelper2::removeAccents(mb_strtolower($_onlyItem));
						if(strpos($_tryString, $_excetoItem) !== false){
							$_anyMatch = true;
						}
					}
				}
				
				if(!$_anyMatch){
					// Não encontrei o que ele deseja nem em Ids e nem em Str.
					return false;
				}
			}
		}
		
		if($type == 'troca'){
			$dadosVenda = $interesses['venda'];
			if($dadosVenda['userDefined']){
				// Em resumo, usuário disse que vende de tudo, então
				// ele pode trocar qualquer coisa.
				return true;
			}
			
			$_tryString = dHelper2::removeAccents(mb_strtolower("{$anuncObj->v('trocaVarieObj')->v('cultura')} {$anuncObj->v('trocaVarieObj')->v('variedade')}"));
			if($dadosVenda['tudo']){
				// Troco por qualquer coisa, exceto...
				$_excetoIds = $dados['excetoIds'];
				if(in_array($anuncObj->v('troca_varie_id'), $_excetoIds)){
					// Disse que tem de tudo, menos essa variedade...
					return false;
				}
				
				$_excetoStr = $dados['excetoStr'];
				if($_excetoStr){
					foreach($_excetoStr as $_excetoItem){
						$_excetoItem = dHelper2::removeAccents(mb_strtolower($_excetoItem));
						if(strpos($_tryString, $_excetoItem) !== false){
							// Disse que tem de tudo, menos o que contém esses termos...
							return false;
						}
					}
				}
			}
			elseif(!$dados['tudo']){
				// Apenas os selecionados...
				$_onlyIds  = $dados['excetoIds'];
				$_anyMatch = false;
				if(in_array($anuncObj->v('troca_varie_id'), $_onlyIds)){
					// Encontrei uma das variedades que ele deseja!
					$_anyMatch = true;
				}
				
				$_onlyStr = $dados['excetoStr'];
				if(!$_anyMatch && $_onlyStr){
					foreach($_onlyStr as $_onlyItem){
						$_excetoItem = dHelper2::removeAccents(mb_strtolower($_onlyItem));
						if(strpos($_tryString, $_excetoItem) !== false){
							$_anyMatch = true;
						}
					}
				}
				
				if(!$_anyMatch){
					// Não encontrei o que ele deseja nem em Ids e nem em Str.
					return false;
				}
			}
		}
		
		// Todas as validações acima tem a função de negar o anúncio.
		// Se ele não foi negado, então passou por tudo.
		return true;
	}
	
	Function getAvaliacao(){
		$this->v('avaliacao');
		if(!$this->v('avaliacao')){
			// Padrão:
			return 5;
		}
	}
	Function getLink($link){
		// Acessos através do link são logados imediatamente.
		// $usuarObj->getLink('newauction.php')
		
		$validDue = date('ymd', strtotime("+1 month"));
		$userId   = $this->v('id');
		$userPwd  = self::getDb()->singleResult("select senha from c_usuarios where id='{$this->v('id')}'");
		$userHash = substr(md5($validDue . $userId . $userPwd . dSystem::getGlobal('hashkey') . 'append-hash-for-auto-login'), -8);
		
		$extraParams = "restore={$userId}{$validDue}{$userHash}";
		$link       .= ((strpos($link, "?") == false)?"?":"&").$extraParams;
		
		return "https://conectasementes.plansson.dev.br/app/{$link}";
	}
	static Function _parseRestore($string){
		// Esperado: userId . $validDue (6) . $userHash (8).
		if(strlen($string) < 15){
			return false;
		}
		
		$userHash   = substr($string, -8);
		$validDue   = substr($string, -8 -6, 6);
		$userId     = substr($string, 0, -8 -6);
		$userPwd    = self::getDb()->singleResult("select senha from c_usuarios where id='".intval($userId)."'");
		$expectHash = substr(md5($validDue . $userId . $userPwd . dSystem::getGlobal('hashkey') . 'append-hash-for-auto-login'), -8);
		
		if($expectHash != $userHash){
			return false;
		}
		
		if($validDue < date('ymd')){
			return false;
		}
		
		return self::load($userId);
	}
	
	// Login:
	// ---------------------------------------------------
	static $loggedObj = false;
	static Function loginWithFacebook ($facebookId){
		$clienObj = self::load(Array('cbMakeQuery'=>"where facebook_id='".addslashes($facebookId)."'"));
		if($clienObj){
			return self::setLogged($clienObj);
		}
		return false;
	}
	static Function loginWithGoogle ($googleId){
		$clienObj = self::load(Array('cbMakeQuery'=>"where facebook_id='".addslashes($googleId)."'"));
		if($clienObj){
			return self::setLogged($clienObj);
		}
		return false;
	}
	static Function loginWithPassword ($renasemOrEmailOrCpf, $password, $pwdHashed=false){
		if(!trim($password)){
			return false;
		}
		$clienObj = self::searchUser($renasemOrEmailOrCpf, $password, $pwdHashed);
		if($clienObj){
			return self::setLogged($clienObj);
		}
		return false;
	}
	static Function setLogged         ($clienObj){
		$_SESSION['Cliente'] = $clienObj->v('id');
		$clienObj->setValue('data_lastlogin', date('d/m/Y H:i:s'))->save();
		return self::$loggedObj = $clienObj;
	}
	static Function searchUser        ($renasemOrEmailOrCpf, $tryPassword, $pwdHashed=false){
		$tryRenasem = dHelper2::formataRenasem($renasemOrEmailOrCpf);
		$tryEmail   = $tryRenasem?false:((strpos($renasemOrEmailOrCpf, "@") !== false)?$renasemOrEmailOrCpf:false);
		$tryCpfCnpj = $tryEmail  ?false:preg_replace("/[^0-9]/", "", $renasemOrEmailOrCpf);
		
		$sqlWhere = "WHERE ";
		if($tryRenasem){
			$sqlWhere .= "renasem = '".addslashes($tryRenasem)."'";
		}
		elseif($tryEmail){
			$sqlWhere .= "email = '".addslashes($tryEmail)."'";
		}
		elseif(strlen($tryCpfCnpj) >= 11 && $tryCpfCnpj <= 15){
			// CPF: 11, CNPJ: 14 ou 15. Apenas numérico.
			$sqlWhere .= "cpf_cnpj = '".addslashes($tryCpfCnpj)."'";
		}
		else{
			// Tudo em branco, ou fora do padrão... Cancele!
			return false;
		}
		
		if(!$tryPassword){
			return false;
		}
		
		if(!$pwdHashed){
			// Senha enviada em formato "raw". Vamos hashear antes de comparar.
			$usePassword = trim($tryPassword);
			$usePassword = substr(md5($usePassword.'cUsuario'.dSystem::getGlobal('hashkey')), 0, 30).'OH';
			$usePassword = "MD5(concat('{$usePassword}', c_usuarios.id*478))";
		}
		else{
			// Senha já está hashed, vamos apenas comparar.
			$usePassword = "'".addslashes($tryPassword)."'";
		}
		$sqlWhere   .= " and senha = {$usePassword}";
		$sqlWhere .= " limit 1";
		return self::load(Array('cbMakeQuery'=>$sqlWhere));
	}
	
	/** @return $this|bool */
	static Function isLogged          ($asAgenteIfAvailable=false){
		if(!$asAgenteIfAvailable){
			// Verifica se está logado.. Se estiver logado e for um agente,
			// verifica se está Acting as.. outra pessoa.. Se tiver, retorne
			// essa pessoa, ao invés do agente.
			$usuarObj = self::isLogged(true);
			if(!$usuarObj){
				// Não está logado.
				return false;
			}
			
			$clienObj = $usuarObj?
				$usuarObj->agenteGetActingAs():
				false;
			
			if($clienObj){
				// Está agindo como...
				// Salva o usuário original logado:
				$clienObj->setVirtual('agente_acting_as', $usuarObj);
				return $clienObj;
			}
			
			return $usuarObj;
		}
		
		if(self::$loggedObj){
			if(!self::$loggedObj->isLoaded()){
				return false;
			}
			return self::$loggedObj;
		}
		if(isset($_GET['restore'])){
			// Restaurar sessão automaticamente (reverter getLink)
			// Redirecionar imediatamente, pra não manter o código de restore no histórico.
			$usuarObj = self::_parseRestore($_GET['restore']);
			if($usuarObj){
				self::setLogged($usuarObj);
				$curUrl = $_SERVER['REQUEST_URI'];
				$curUrl = str_replace("restore={$_GET['restore']}&", "", $curUrl);
				$curUrl = str_replace("?restore={$_GET['restore']}", "", $curUrl);
				$curUrl = str_replace("&restore={$_GET['restore']}", "", $curUrl);
				dHelper2::redirectTo($curUrl);
				die;
			}
		}
		
		$sessLoginId   = @$_SESSION['Cliente'];
		if($sessLoginId){
			$clienObj = self::load($sessLoginId);
			return $clienObj;
		}
		
		if(isset($_COOKIE['conecta-rme'])){ // Remember Me
			$authData = explode("O", $_COOKIE['conecta-rme'], 3);
			if(sizeof($authData) == 3 && $authData[0] == 'CS'){
				// Tenta auto-login por existência do Cookie.
				$clienObj = self::loginWithPassword($authData[1], $authData[2], true);
				if($clienObj){
					self::rememberMeSaveCookie($clienObj);
					return $clienObj;
				}
			}
			self::rememberMeRemoveCookie();
		}
		
		return false;
	}
	
	/** @return $this|bool */
	static Function isLoggedOrRedirect($asAgenteIfAvailable=false){
		$ret = self::isLogged($asAgenteIfAvailable);
		if(!$ret){
			$_SESSION['ClienteAfterLoginGoTo'] = $_SERVER['REQUEST_URI'];
			dHelper2::redirectTo(dSystem::getGlobal('baseUrl').'app/login.php');
		}
		return $ret;
	}
	static Function logOut            (){
		unset($_SESSION['Cliente']);
		unset($_SESSION['agente_acting_as']);
		self::rememberMeRemoveCookie();
		self::$loggedObj = false;
	}
	
	static Function rememberMeSaveCookie(cUsuario $clienObj){
		$db       = dDatabase::start();
		$saveStr  = "CS"."O"; // Signature
		$saveStr .= ($clienObj->v('renasem')?$clienObj->v('renasem'):$clienObj->v('email'))."O"; // Username
		$saveStr .= $db->singleResult("select senha from c_usuarios where id='{$clienObj->v('id')}' limit 1");
		
		$_curUrl     = parse_url(dSystem::getGlobal('baseUrl'));
		$_cookiePath = @$_curUrl['path'];
		setcookie('conecta-rme', $saveStr, strtotime("+1 year"), $_cookiePath);
	}
	static Function rememberMeRemoveCookie(){
		$_curUrl     = parse_url(dSystem::getGlobal('baseUrl'));
		$_cookiePath = @$_curUrl['path'];
		setcookie('conecta-rme', '', strtotime("-2 days"), $_cookiePath);
	}
	
	Function delete(){
		// Excluir anuncios relacionados a este usuario.
		$allAnuncs    = cAnuncio::multiLoad("where usuar_id='{$this->v('id')}'");
		foreach($allAnuncs as $anuncObj){
		    $anuncObj->delete();
		}
		
		return parent::delete();
	}
}
?>