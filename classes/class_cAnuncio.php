<?php // built on 27/01/2019 11:27
class cAnuncio extends dDbRow3{
	static Function buildStruct(){
		self::setTable('c_anuncios');
		self::addField('id,usuar_id,codigo,negocio,data_anuncio,data_ini_cotacao');
		self::addField('data_encerrado,encerrado_motivo,status,varie_id');
		self::addField('categoria,germinacao,embalagem,vigor_ea48h,peneira,tratam_indust');
		self::addField('pms,quantidade,frete,valor_por_embalagem,regiao,forma_pgto');
		self::addField('tratam_texto,troca_varie_id,valor_royalties');
		self::addField('autoexpire_data,valor_por_kg,uf,cidade');
		
		self::addExt('usuarObj', 'cUsuario::usuar_id');
		self::addExt('varieObj', 'cRefVariedade::varie_id');
		self::addExt('trocaVarieObj', 'cRefVariedade::troca_varie_id');
		
		self::addValidation(false, 'callback', function(cAnuncio $obj, $value, $aliasName){
			// valor_por_embalagem OU valor_por_kg sempre serão obrigatórios.
			if(dHelper2::forceFloat($obj->v('valor_por_embalagem'))<1 && dHelper2::forceFloat($obj->v('valor_por_kg'))<1){
				$obj->addError(false, "Informe o valor desejado.");
				return false;
			}
			return true;
		});
		self::addValidation(false, 'callback', function(cAnuncio $obj, $value, $aliasName){
			// Temos validação diferente (campos obrigatórios) para cada tipo de anúncio.
			// Compra:  Apenas "Região", "Embalagem", "Quantidade" e "Valor" obrigatórios (Verificados fora deste método)
			// Troca:   Troca_varie_id também é obrigatório, além dos acima.
			// Venda:   Todos os campos obrigatórios, exceto "troca_varie_id"
			
			if($obj->v('negocio') == 'Compra'){
				// Passou automaticamente pela validação.
				return true;
			}
			if($obj->v('negocio') == 'Troca'){
				$isRequired = Array(
					'troca_varie_id' => "Você precisa informar o que você deseja em troca.",
				);
			}
			else{
				$isRequired = Array(
					'categoria'     => false,
					'germinacao'    => false,
					'vigor_ea48h'   => false,
					'peneira'       => false,
					'tratam_indust' => false,
					'pms'           => "Você precisa preencher o campo PMS",
					'frete'         => false,
					'forma_pgto'    => false,
				);
			}
			
			$errorList  = Array();
			foreach($isRequired as $fieldName=>$customErrorStr){
				$_val = $obj->v($fieldName);
				if(!is_string($_val) || !trim($_val) || !$_val){
					$errorList[] = $customErrorStr?
						$customErrorStr:
						"Você precisa preencher o campo {$fieldName}.";
				}
			}
			
			if($errorList){
				// Não passou!
				foreach($errorList as $aliasName=>$errorStr){
					$obj->addError($aliasName, $errorStr);
				}
				
				return false;
			}
			
			// Passou!
			return true;
		});
		
		self::addValidation('usuar_id', 'required', false, 'Você precisa preencher o campo usuar id');
		self::addValidation('codigo', 'required', false, 'Você precisa preencher o campo codigo');
		self::addValidation('negocio', 'required', false, 'Você precisa preencher o campo negocio');
		self::addValidation('data_anuncio', 'required', false, 'Você precisa preencher o campo data anuncio');
		self::addValidation('varie_id', 'required', false, 'Você precisa preencher o campo varie id');
		self::addValidation('embalagem', 'required', false, 'Você precisa preencher o campo embalagem');
		self::addValidation('quantidade', 'required', false, 'Você precisa preencher o campo quantidade');
		// self::addValidation('regiao', 'required', false, 'Você precisa preencher o campo região');
		// self::addValidation('valor_por_embalagem', 'required', false, 'Você precisa preencher o campo valor');
		self::addValidation('data_anuncio', 'datetime', 'br', 'Preencha a data_anuncio no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_ini_cotacao', 'datetime', 'br', 'Preencha a data_ini_cotacao no formato dd/mm/aaaa hh:mm');
		self::addValidation('data_encerrado', 'datetime', 'br', 'Preencha a data_encerrado no formato dd/mm/aaaa hh:mm');
		self::addValidation('usuar_id', 'int', false, 'Preencha o campo usuar id apenas com números');
		self::addValidation('varie_id', 'int', false, 'Preencha o campo varie id apenas com números');
		self::addValidation('troca_varie_id', 'int', false, 'Preencha o campo troca_varie id apenas com números');
		self::addValidation('quantidade', 'int', false, 'Preencha o campo quantidade apenas com números inteiros');
		self::addValidation('usuar_id', 'nummin', 0, 'Por favor, digite um numero positivo para usuar id');
		self::addValidation('varie_id', 'nummin', 0, 'Por favor, digite um numero positivo para varie id');
		self::addValidation('troca_varie_id', 'nummin', 0, 'Por favor, digite um numero positivo para varie id');
		self::addValidation('quantidade', 'nummin', 0, 'Por favor, digite um numero positivo para quantidade');
		self::addValidation('negocio', 'regex', '(Compra|Venda|Troca)', 'Opção inválida para negocio');
		self::addValidation('status', 'regex', '(Em Análise|Ag\\. Propostas|Concluído|Cancelado)', 'Opção inválida para status');
		self::addValidation('codigo', 'singleline', false, 'O campo codigo não pode ter mais de uma linha');
		self::addValidation('encerrado_motivo', 'singleline', false, 'O campo encerrado motivo não pode ter mais de uma linha');
		self::addValidation('categoria', 'singleline', false, 'O campo categoria não pode ter mais de uma linha');
		self::addValidation('germinacao', 'singleline', false, 'O campo germinacao não pode ter mais de uma linha');
		self::addValidation('embalagem', 'singleline', false, 'O campo embalagem não pode ter mais de uma linha');
		self::addValidation('vigor_ea48h', 'singleline', false, 'O campo vigor ea48h não pode ter mais de uma linha');
		self::addValidation('peneira', 'singleline', false, 'O campo peneira não pode ter mais de uma linha');
		self::addValidation('tratam_indust', 'singleline', false, 'O campo tratam indust não pode ter mais de uma linha');
		self::addValidation('pms', 'singleline', false, 'O campo pms não pode ter mais de uma linha');
		self::addValidation('frete', 'singleline', false, 'O campo frete não pode ter mais de uma linha');
		self::addValidation('regiao', 'singleline', false, 'O campo regiao não pode ter mais de uma linha');
		self::addValidation('forma_pgto', 'singleline', false, 'O campo forma pgto não pode ter mais de uma linha');
		self::addValidation('codigo', 'strmax', 16, 'O campo codigo não pode ter mais de 8 caracteres');
		self::addValidation('encerrado_motivo', 'strmax', 250, 'O campo encerrado motivo não pode ter mais de 250 caracteres');
		self::addValidation('categoria', 'strmax', 30, 'O campo categoria não pode ter mais de 30 caracteres');
		self::addValidation('germinacao', 'strmax', 30, 'O campo germinacao não pode ter mais de 30 caracteres');
		self::addValidation('embalagem', 'strmax', 150, 'O campo embalagem não pode ter mais de 150 caracteres');
		self::addValidation('vigor_ea48h', 'strmax', 30, 'O campo vigor ea48h não pode ter mais de 30 caracteres');
		self::addValidation('peneira', 'strmax', 30, 'O campo peneira não pode ter mais de 30 caracteres');
		self::addValidation('tratam_indust', 'strmax', 30, 'O campo tratam indust não pode ter mais de 30 caracteres');
		self::addValidation('pms', 'strmax', 30, 'O campo pms não pode ter mais de 30 caracteres');
		self::addValidation('frete', 'strmax', 30, 'O campo frete não pode ter mais de 30 caracteres');
		self::addValidation('regiao', 'strmax', 150, 'O campo regiao não pode ter mais de 150 caracteres');
		self::addValidation('forma_pgto', 'strmax', 150, 'O campo forma pgto não pode ter mais de 150 caracteres');
		
		self::addModifier('usuar_id,varie_id,troca_varie_id,quantidade', 'force_int');
		self::addModifier('codigo,encerrado_motivo,categoria,germinacao,embalagem', 'trim');
		self::addModifier('vigor_ea48h,peneira,tratam_indust,pms,frete,regiao,forma_pgto', 'trim');
		self::addModifier('data_anuncio,data_ini_cotacao,data_encerrado', 'datetime', 'br');
		self::addModifier('valor_por_embalagem,valor_royalties', 'force_float');
		
		self::addModifier('autoexpire_data', 'datetime', 'br');
		self::addModifier('valor_por_kg',    'force_float');
		self::addValidation('autoexpire_data', 'datetime', 'br', 'Preencha a data da expiração no formato dd/mm/aaaa hh:mm');
		self::addValidation('uf',              'strexact',    2, "O campo UF deveria ter exatamente 2 caracteres.");
		self::addValidation('cidade',          'strmax',    150, "O campo Cidade está muito grande, reduza.");
		
		self::setDefaultValue('data_anuncio', date('d/m/Y H:i:s'));
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
	
	/**
	 * Otimiza a query para listar oportunidades para cada tipo de negócio.
	 * Se $negocio for *false*, os contadores serão retornados.
	 * @param String $negocio 'Venda' | 'Compra' | 'Troca'
	 * @param String $filter 'cotando' | 'arquivadas'
	 * @return Array
	 */
	static Function getOportunidades(cUsuario $usuarObj, $negocio=false, $filter='cotando'){
		if(!$negocio){
			// Retornando contador de oportunidades não-lidas.
			return [
				'Compra'=>intval(sizeof(self::getOportunidades($usuarObj, 'Compra', 'new'))),
				'Venda' =>intval(sizeof(self::getOportunidades($usuarObj, 'Venda',  'new'))),
				'Troca' =>intval(sizeof(self::getOportunidades($usuarObj, 'Troca',  'new'))),
			];
		}
		
		if(!in_array($negocio, ['Compra', 'Venda', 'Troca'])){
			trigger_error(E_USER_ERROR, "Parâmetro inválido para getOportunidades.");
			return [];
		}
		
		
		$_xtraWhere = '';
		if($filter == 'cotando'){
			$_xtraWhere = "and c_anuncios.status = 'Ag. Propostas' and ISNULL(propoObj.status)";
		}
		elseif($filter == 'arquivadas'){
			$_xtraWhere = "and propoObj.status = 'Sem Interesse'";
		}
		elseif($filter == 'new'){
			$_xtraWhere = "and c_anuncios.status = 'Ag. Propostas' and ISNULL(propoObj.id)";
		}
		
		$_useGroup  = ($negocio == 'Venda')?
			'compra':
			($negocio=='Compra'?'venda':'troca');
		
		$interesses = $usuarObj->getInteressesAsSQL($_useGroup);
		if(!$interesses){
			// Usuário disse não ter interesse...
			return [];
		}
		// [userDefined] [appendWhere]
		if($interesses['appendWhere']){
			$_xtraWhere .= "\r\n\tand {$interesses['appendWhere']}";
		}
		
		if(!cProposta::structExists()){
			cProposta::buildStruct(); // Necessário para cProposta::sGetDbAliasNames().
		}
		$anuncList = cAnuncio::multiLoad([
			'...' => function(&$select, &$from, &$mapPrefix) use ($usuarObj, $negocio, $_xtraWhere){
				$allAlias = cProposta::sGetDbAliasNames(true);
				foreach($allAlias as $_aliasName => $_fieldValue){
					$select[] = "propoObj.{$_fieldValue} as `propoObj.{$_aliasName}`";
				}
				
				$from[]                 = "left join c_propostas propoObj on propoObj.anunc_id = c_anuncios.id and propoObj.usuar_id = '{$usuarObj->v('id')}'";
				$mapPrefix['propoObj.'] = function($anuncObj, $data){
					$_propoObj = false;
					if($data['id']){
						$_propoObj = new cProposta;
						$_propoObj->loadArray($data, Array(
							'format'          => 'db',
							'setLoaded'       => true,
							'noChecks'        => true,
							'overwriteLoaded' => true,
						));
					}
					$anuncObj->setVirtual('propoObj', $_propoObj);
				};
				
				return "
					where
						c_anuncios.negocio   = '{$negocio}'
					and c_anuncios.usuar_id != '{$usuarObj->v('id')}'
					and c_anuncios.status   = 'Ag. Propostas'
					and (
						c_anuncios.status NOT IN('Cancelado', 'Em Análise') OR
						(c_anuncios.status IN ('Ag. Aceite', 'Ag. Intermediação', 'Concluído') and !isnull(propoObj.status))
					)
					{$_xtraWhere}
					order by data_anuncio desc
				";
			},
			'loadExt'=>'trocaVarieObj,varieObj',
		]);
		
		return $anuncList;
	}
	static Function loadCounts($usuarObj){
		$db       = dDatabase::start();
		$count    = Array(
			'anuncios'=>0,
			'propostas'=>['recebidas'=>0, 'enviadas'=>0],
			'oportunidades'=>['Compra'=>0, 'Venda'=>0, 'Troca'=>0],
		);
		
		$count['anuncios']               = $db->singleResult("select count(id) from c_anuncios  where usuar_id='{$usuarObj->v('id')}' and status IN('Ag. Propostas', 'Em Análise')");
		
		$count['propostas']['enviadas']  = $db->singleResult("select count(id) from c_propostas where usuar_id='{$usuarObj->v('id')}' and status IN('Aceita')");
		$count['propostas']['recebidas'] = $db->singleResult("
			select
				count(p.id)
			from
				c_propostas p 
				inner join c_anuncios a on p.anunc_id = a.id
			where
				a.usuar_id='{$usuarObj->v('id')}' and
				p.status IN('Enviada','Aceita') and
				!isnull(p.data_revisado)
		");
		
		$oportCount = self::getOportunidades($usuarObj);
		$count['oportunidades']['Compra'] = $oportCount['Compra'];
		$count['oportunidades']['Venda']  = $oportCount['Venda'];
		$count['oportunidades']['Troca']  = $oportCount['Troca'];
		return $count;
	}
	
	/**
	 * @return cProposta;
	 */
	Function getPropoObj(cUsuario $usuarObj){
		if($usuarObj->v('id') == $this->v('usuar_id')){
			trigger_error("Erro crítico: Chamando getPropoObj sem necessidade.");
			return false;
		}
		
		$_virtualKey = "_propoObj.{$usuarObj->v('id')}";
		if($this->hasVirtual($_virtualKey)){
			// Já carregado, retorne o que tá em cache.
			return $this->getVirtual($_virtualKey);
		}
		$ret = cProposta::load(false, ['...'=>"where anunc_id='{$this->v('id')}' and usuar_id='{$usuarObj->v('id')}'",]);
		$this->setVirtual($_virtualKey, $ret);
		return $ret;
	}
	
	// Layout - v2:
	Function renderAnuncio($usuarObj=false, $settings=[]){
		// $usuarObj: Quem está interagindo com o anúncio.
		//            Saberemos automaticamente se é o anunciante, um proponente ou apenas um curioso.
		//            Pode ser cUsuario ou String.
		
		if(!$usuarObj){
			$usuarObj = cUsuario::isLogged();
		}
		
		$settings = dHelper2::addDefaultToArray($settings, [
			'expandPropostas'=>false,
		]);
		
		dHelper2::includePage(dSystem::getGlobal('baseDir').'/app/inc.anuncio-v2.php', [
			'anuncObj'  => $this,
			'usuarObj'  => $usuarObj,
			'settings'  => $settings,
		]);
	}
	
	// 1. Criação do Anúncio: Em Análise
	//    * O Anunciante recebe um e-mail de confirmação;
	//    * O Administrador recebe um e-mail para liberar o anuncio.
	Function afterCreate(){
		$this->notifyAnunciante(
			"Seu anúncio foi criado!",
			"Vamos revisar os dados e enviar para cotação.<br />".
			"Você será avisado assim que houver alguma novidade."
		);
		$this->notifyAdministrador(
			"Novo anúncio foi criado!",
			"O anúncio foi colocado Em Análise, para que você possa ".
			"revisar e colocá-lo em cotação."
		);
	}
	
	// Mudando para "Ag. Propostas" (Em Cotação)
	//    --> Quem deve tomar esta ação: Administrador
	//    --> Usuário recebe um e-mail de que o anúncio dele está liberado.
	Function enableCotacao(){
		if($this->v('status') != 'Em Análise'){
			return false;
		}
		
		$this->v('data_ini_cotacao', date('d/m/Y H:i:s'));
		$this->v('status',           'Ag. Propostas');
		if(!$this->save()){
			return false;
		}
		
		$this->notifyAnunciante(
			"Seu anúncio foi aprovado!",
			"Agora é só aguardar as propostas!<br />".
			"Você será notificado assim que surgir algum interessado."
		);
		
		return true;
	}
	Function encerrarAnuncio($fromAdmin=false){
		// Anúncio encerrado, pelo administrador ou porque expirou mesmo.
		// Por enquanto, sem notificação.
		if($this->v('status') != 'Ag. Propostas'){
			return false;
		}
		
		$this->v('data_encerrado', date('d/m/Y H:i:s'));
		$this->v('status', 'Concluído');
		return $this->save();
	}
	Function reativarAnuncio($extendTime = "+1 week"){
		// * Não vamos considerar extendTime=false, pois o sistema
		//   deverá ter, em algum momento, um auto-expire (ex: 1 mês)...
		//
		//   Ao reativar o anúncio, provavelmente esse auto-expire também já
		//   terá sido atingido, então o anúncio vai reiniciar e será encerrado
		//   sozinho.
		
		// Administrador pediu para reiniciar o anúncio.
		// Nenhuma notificação aqui.
		
		if($this->v('status') != 'Cancelado' && $this->v('status') != 'Concluído'){
			return false;
		}
		
		$this->v('data_encerrado',   date('d/m/Y', strtotime($extendTime)).' 23:59:59');
		$this->v('encerrado_motivo', false);
		$this->v('status', 'Ag. Propostas');
		return $this->save();
	}
	Function rejeitarAnuncio($motivo){
		if($this->v('status') != 'Em Análise' && $this->v('status') != 'Ag. Propostas'){
			return false;
		}
		
		$this->v('status', 'Cancelado');
		$this->v('encerrado_motivo', $motivo);
		if($this->save()){
			if($motivo){
				$this->notifyAnunciante("Seu anúncio foi rejeitado", $motivo);
			}
			return true;
		}
		return false;
	}
	
	// Novo sistema de propostas, versão 2.
	Function propostaRevisada(cProposta $propoObj, $notify=true){
		// Notifica o anunciante que ele recebeu uma proposta!
		if($propoObj->v('data_revisado')){
			// Já estava revisada.
			return false;
		}
		
		if($propoObj->v('data_revisado', date('d/m/Y H:i:s'))->save()){
			if($notify){
				$this->notifyAnunciante(
					"Você recebeu uma proposta!",
					"Acesse o sistema para ver os detalhes."
				);
			}
			return true;
		}
		return false;
	}
	Function propostaAdminRejeitou(cProposta $propoObj, $motivo, $allowRedo){
		if($this->v('status') == 'Rejeitada pelo Admin' || $this->v('status') == 'Cancelada'){
			// Proposta já num status que não faz sentido mudar.
			return false;
		}
		
		$propoObj->v('status', 'Rejeitada pelo Admin');
		$propoObj->v('data_revisado', date('d/m/Y H:i:s'));
		$propoObj->v('cancelada_motivo', $motivo);
		if($propoObj->save()){
			if($propoObj->v('cancelada_motivo')){
				if($allowRedo){
					$motivo .= "\r\n";
					$motivo .= "\r\n";
					$motivo .= "Você pode acessar o sistema e fazer uma nova proposta.";
				}
				
				$this->notifyProponente($propoObj, "Sua proposta foi rejeitada", $motivo);
			}
			if(!$allowRedo){
				return true;
			}
		}
		
		if($allowRedo){
			// Pode refazer a proposta, então vamos apagar tudo que o cara tinha preenchido e retornar a proposta ao padrão.
			$propoObj->v('data_proposta', false);
			$propoObj->v('data_revisado', false);
			$propoObj->v('data_aceite',   false);
			$propoObj->v('status',        false);
			$propoObj->v('cancelada_motivo', false);
			$propoObj->v('varie_id', false);
			$propoObj->v('valor', false);
			$propoObj->v('justificativa', false);
			$propoObj->v('regiao', false);
			return $propoObj->save();
		}
		return false;
	}
	Function propostaChangeStatus(cProposta $propoObj, $newStatus){
		if($propoObj->v('status', $newStatus)->save()){
			return true;
		}
		return false;
	}
	
	// 3. Proponente está fazendo uma oferta
	//    --> Quem deve tomar esta ação: Proponente ou Administrador
	//    --> Próxima ação: Administrador
	Function sendProposta(cUsuario $usuarObj, $preco, $regiao, $justificativa=false, $dontNotify=false){
		if($this->v('status') != 'Ag. Propostas'){
			die("Desculpe, este anúncio não está mais disponível.");
		}
		
		$propoObj = $this->getPropoObj($usuarObj);
		if(!$propoObj){
			$propoObj = new cProposta;
			$propoObj->v('anunc_id', $this->v('id'));
			$propoObj->v('usuar_id', $usuarObj->v('id'));
		}
		if($propoObj->v('status') && $propoObj->v('status') != 'Sem Interesse'){
			die("Sua proposta já foi enviada, e não pode mais ser modificada.");
		}
		
		$propoObj->v('data_proposta', date('d/m/Y H:i:s'));
		$propoObj->v('status', 'Enviada');
		$propoObj->v('valor', $preco);
		$propoObj->v('regiao', $regiao);
		$propoObj->v('justificativa', $justificativa);
		if($propoObj->save()){
			if(!$dontNotify){
				$this->notifyAdministrador(
					"Nova proposta",
					"Nova proposta recebida, de {$propoObj->v('usuarObj')->v('nome')}.<br />".
					"<br />".
					"Acesse o sistema para revisar e liberar essa proposta para o anunciante."
				);
			}
			return true;
		}
		
		$this->appendErrors($propoObj);
		return false;
	}
	
	// 4. Desfazer proposta?
	Function undoProposta($propoObj, $motivo, $allowRedo){
		if(!$allowRedo){
			$propoObj->v('status', 'Cancelada');
			$propoObj->v('cancelada_motivo', $motivo);
		}
		else{
			$propoObj->v('status', '');
			$propoObj->v('data_proposta', '');
			$propoObj->v('data_revisado', '');
			$propoObj->v('data_aceite', '');
			$propoObj->v('valor', '');
			$propoObj->v('regiao', '');
			$propoObj->v('justificativa', '');
		}
		
		if($propoObj->save()){
			if($motivo){
				$this->notifyProponente($propoObj, "Sua proposta foi rejeitada", $motivo);
			}
			return true;
		}
		$this->appendErrors($propoObj);
		
		return false;
	}
	
	// 4. Administrador revisou as propostas e está encaminhando para o anunciante
	//    --> Quem deve tomar esta ação: Administrador
	//    --> Próxima ação: Anunciante
	Function encaminharPropostas($propoIds){
		if($this->v('status') != 'Ag. Propostas'){
			$this->addError(false, "Não é possível encaminhar as propostas para um anúncio {$this->v('status')}.");
			
			return false;
		}
		
		$exPropostas = cProposta::multiLoad("where anunc_id='{$this->v('id')}' and id IN('".implode("','", $propoIds)."') and isnull(data_revisado)");
		if(!$exPropostas){
			return false;
		}
		
		foreach($exPropostas as $propoObj){
			$propoObj->v('data_revisado', date('d/m/Y H:i:s'));
			$propoObj->save();
		}
		
		$this->notifyAnunciante(
			"Você recebeu ".sizeof($exPropostas)." propostas!",
			"Acesse o sistema para ver as propostas e fechar negócios."
		);
		return true;
	}
	
	// 5. Anunciante aceitou ou rejeitou uma proposta recebida.
	Function setAceite($propoObj){
		// Uma proposta só pode ser aceita se...
		// --> Tiver sido revisada pelo administrador (data_revisado)
		// --> Estiver como 'Enviada'
		if(!$propoObj->v('data_revisado')){
			$this->addError(false, "Impossível aceitar essa proposta sem uma revisão do administrador antes.");
			return false;
		}
		if($propoObj->v('status') != "Enviada"){
			$this->addError(false, "Essa proposta não está mais disponível para aprovação. Recarregue a página.");
			return false;
		}
		
		$propoObj->v('status',      'Aceita');
		$propoObj->v('data_aceite', date('d/m/Y H:i:s'));
		$propoObj->save();
		
		$this->notifyAdministrador(
			"Anunciante aceitou uma proposta",
			"O anúncio ficou marcado como Aguardando Intermediação, aguardando sua intermediação.<br />".
			"O proponente não será notificado automaticamente."
		);
		
		return true;
	}
	Function setRejected($propoObj){
		// Uma proposta só pode ser aceita se...
		// --> Tiver sido revisada pelo administrador (data_revisado)
		// --> Estiver como 'Enviada'
		if(!$propoObj->v('data_revisado')){
			$this->addError(false, "Impossível aceitar essa proposta sem uma revisão do administrador antes.");
			return false;
		}
		if($propoObj->v('status') != "Enviada"){
			$this->addError(false, "Essa proposta não está mais disponível para aprovação. Recarregue a página.");
			return false;
		}
		
		$propoObj->v('status',      'Rejeitada');
		$propoObj->save();
		
		$this->notifyAdministrador(
			"Anunciante rejeitou uma proposta",
			"O proponente foi notificado dessa rejeição."
		);
		
		$this->notifyProponente($propoObj, "Sua proposta foi negada", "O anunciante não aceitou a sua oferta.");
		
		return true;
	}
	
	// Explicando melhor:
	// --> Quando o cliente aceita as propostas (setAceite), o status muda para Concluído ou Ag. Interm,
	//     mas o campo data_encerrado fica em aberto, indicando que precisa de uma revisão por parte do adm.
	// --> Este método deve revisar esse status (Ag Interm ou Concluido) e definir a data_encerrado.
	// --> Ou então, o adm pode tomar as decisões de "Aceite" no lugar do cliente.
	Function setAceiteFromAdmin(){
		if(!in_array($this->v('status'), Array('Ag. Aceite', 'Ag. Intermediação', 'Concluído')) || $this->v('data_encerrado')){
			$this->addError(false, "Este anúncio não precisa ser revisado pelo administrador (Não é Ag. Interm) ou tem Data_Encerrado.");
			return false;
		}
		
		$allPropostas = cProposta::multiLoad("where anunc_id='{$this->v('id')}' and status IN ('Enviada', 'Aceita', 'Rejeitada')");
		$anyAguard     = false;
		$allAceitas    = Array();
		$allRejeitadas = Array();
		foreach($allPropostas as $_propoObj){
			if($_propoObj->v('status') == 'Enviada'){
				$anyAguard = true;
				continue;
			}
			if($_propoObj->v('status') == 'Aceita' || $_propoObj->v('status') == 'Negócio Fechado'){
				$allAceitas[] = $_propoObj;
				continue;
			}
			if($_propoObj->v('status') == 'Rejeitada' || $_propoObj->v('status') == 'Negócio Desfeito'){
				$allRejeitadas[] = $_propoObj;
				continue;
			}
		}
		
		if($anyAguard){
			$this->addError(false, "Não é possível consolidar enquanto há propostas aguardando o aceite.");
			return false;
		}
		
		$this->v('data_encerrado', date('d/m/Y h:i:s'));
		if($allAceitas){
			$this->v('status', "Ag. Intermediação");
		}
		else{
			$this->v('status', "Concluído");
			$this->updateMotivo(true);
		}
		
		$ret = $this->save();
		if($ret){
			foreach($allAceitas as $_propoObj){
				$this->notifyProponente($_propoObj,
					"Sua proposta foi aceita!",
					"Parabéns pelo negócio. Entraremos em contato em breve, com instruções de formalização."
				);
			}
			foreach($allRejeitadas as $_propoObj){
				$this->notifyProponente($_propoObj,
					"Sua proposta não foi aceita.",
					"Alguém fez uma oferta melhor que a sua."
				);
			}
			
			return true;
		}
		return false;
	}
	
	// 6. Administrador já fez a intermediação e concluiu a proposta (ou mudou o status de alguma proposta).
	//    --> Quem deve tomar esta ação: Administrador (O anunciante também?)
	function setConcluido($appendMotivo=false){
		// Ex: $appendMotivo = "Expirou automaticamente.";
		// To-do:
		// --> Notificar proponentes sobre o negócio fechado: Aguardar contato.
		// --> Notificar o anunciante também?
		if(!$this->v('data_encerrado') || !in_array($this->v('status'), Array('Ag. Intermediação', 'Concluído'))){
			$this->addError(false, "Não posso chamar ->setConcluido.");
			return false;
		}
		
		$propoAceitas  = cProposta::multiLoad("where anunc_id='{$this->v('id')}' and status = 'Aceita'");
		$propoRejected = cProposta::multiLoad("where anunc_id='{$this->v('id')}' and status = 'Rejeitada'");
		
		$this->v('data_encerrado', date('d/m/Y H:i:s'));
		$this->v('status', 'Concluído');
		
		$setMotivo = "Encerrado sem receber propostas";
		if($propoRejected){
			$setMotivo = "Encerrado sem propostas aceitas";
		}
		if($propoAceitas){
			$setMotivo = "Encerrado com propostas aceitas";
		}
		
		return $this->v('encerrado_motivo', $setMotivo.($appendMotivo?". {$appendMotivo}":""))->save();
	}
	
	// 7. Administrador pode alternar o status das propostas (Rejeitada, Aceita, Negócio Fechado, Negócio Desfeito)
	function updateMotivo($dontSave=false){
		if($this->v('status') != 'Concluído'){
			$this->addError(false, "Não é possível definir/atualizar o motivo de uma proposta que não está concluída.");
			return false;
		}
		
		$propoAceitas  = cProposta::multiLoad("where anunc_id='{$this->v('id')}' and status IN ('Aceita', 'Negócio Fechado')");
		$propoRejected = cProposta::multiLoad("where anunc_id='{$this->v('id')}' and status IN ('Rejeitada', 'Negócio Desfeito')");
		$setMotivo     = "Encerrado sem receber propostas";
		if($propoRejected){
			$setMotivo = "Encerrado sem propostas aceitas";
		}
		if($propoAceitas){
			$setMotivo = "Encerrado com propostas aceitas";
		}
		
		$this->v('encerrado_motivo', $setMotivo);
		return $dontSave?
			true:
			$this->save();
	}
	
	// 8. Cancelando o anúncio.
	Function setCancelado($motivo){
		$emAnalise = ($this->v('status') == 'Em Análise');
		$this->v('status', 'Cancelado');
		$this->v('data_encerrado', date('d/m/Y H:i:s'));
		$this->v('encerrado_motivo', $motivo);
		$this->v('data_ini_cotacao', false);
		$this->v('data_encerrado', false);
		if($this->save()){
			if($emAnalise && $motivo){
				// Notificar anunciante que sua proposta foi cancelada?
				$this->notifyAnunciante("Seu anúncio foi recusado", $motivo);
			}
			return true;
		}
		
		return false;
	}
	Function setExpirado(){
		// Expira o anúncio automaticamente.
		return $this->setConcluido("Expirou automaticamente.");
	}
	
	Function userEncerrarAnuncio(){
		// O próprio usuário está encerrando este anúncio.
		if($this->v('status') == 'Em Análise'){
			// Se não foi aprovado, então não pode ficar como "Concluído".
			// Vai direto para "Cancelado."
			$this->v('status',           'Cancelado');
			$this->v('data_ini_cotacao', false);
			$this->v('data_encerrado',   date('d/m/Y H:i:s'));
			$this->v('encerrado_motivo', "Cancelado pelo anunciante antes da revisão/análise.");
		}
		elseif($this->v('status') == 'Ag. Propostas'){
			// Estava publicado, vamos arquivá-lo.
			$this->v('status',           'Concluído');
			$this->v('data_encerrado',   date('d/m/Y H:i:s'));
			$this->v('encerrado_motivo', "Cancelado pelo anunciante.");
		}
		else{
			$this->addError(false, "Seu anúncio já foi encerrado. Atualize a página para revisar.");
			return false;
		}
		return $this->save();
	}
	
	Function notifyAdministrador($assunto, $mensagem){
		$grupo = "E-mails referentes a anúncios, enviados para o administrador";
		return $this->notify('admin', $assunto, $mensagem, $grupo);
	}
	Function notifyAnunciante($assunto, $mensagem){
		$grupo = "E-mails referentes a anúncios, enviados para o anunciante";
		return $this->notify('anunciante', $assunto, $mensagem, $grupo);
	}
	Function notifyProponente($propoObj, $assunto, $mensagem){
		$grupo = "E-mails referentes a anúncios, enviados para o proponente";
		return $this->notify($propoObj, $assunto, $mensagem, $grupo);
	}
	
	Function notify($quem, $assunto, $mensagem, $grupo){
		// $quem:     'admin' | 'anunciante' | $propoObj
		// $mensagem: asHtml, sem nenhum tratamento.
		// * Template será adicionado automaticamente.
		
		$usuarObj  = false;
		$template  = false;
		if(is_string($quem) && $quem == 'admin'){
			$template  = 'admin';
			$replace   = Array(
				'[MENSAGEM_INTRO]' => substr(strip_tags($mensagem), 0, 80),
				'[CODIGO]'         => $this->v('codigo'),
				'[ASSUNTO]'        => $assunto,
				'[MENSAGEM]'       => $mensagem,
				'[LINK_PAINEL]'    => $_SERVER["SERVER_NAME"]."/admin/anunc_edit.php?id={$this->v('id')}",
			);
		}
		else{
			$usuarObj = is_object($quem)?
				$quem->v('usuarObj'): // Autor da proposta
				$this->v('usuarObj'); // Autor do anúncio
			
			$template = 'anuncio';
			$replace  = Array(
				'[MENSAGEM_INTRO]'  => substr(strip_tags($mensagem), 0, 80),
				'[NOME]'            => $usuarObj->v('responsavel_nome')?
					$usuarObj->v('responsavel_nome'):
					$usuarObj->v('nome'),
				'[ASSUNTO]'         => $assunto,
				'[MENSAGEM]'        => $mensagem,
				'[CODIGO]'          => $this->v('codigo'),
				'[VARIEDADE]'       => ($this->v('varieObj')->v('cultura') . ' - ' . $this->v('varieObj')->v('variedade')),
				'[LINK_VER_ANUNCIO]'=> $usuarObj->getLink($this->getLink()),
			);
		}
		
		$sendTo = $usuarObj?
			$usuarObj->v('email'):
			dConfiguracao::getConfig('CORE/MAIL_TO');
		$sendSubject = "{$assunto} ({$this->v('varieObj')->v('cultura')}: {$this->v('varieObj')->v('variedade')})";
		
		// Se o anunciante tiver um agente relacionado, envie o e-mail para o agente!
		if($usuarObj && $usuarObj->v('agentObj')){
			$sendTo = $usuarObj->v('agentObj')->v('email');
			if(!$sendTo){
				return false;
			}
			
			$sendSubject = "[Agente] ".$sendSubject;
		}
		
		dEmail::sendFromTemplate(Array(
			'template' => $template,
			'replace'  => $replace,
			'to'       => $sendTo,
			'subject'  => $sendSubject,
			'grupo'    => $grupo,
		));
		
		return true;
	}
	
	Function markAsRead($usuarObj){
		if($usuarObj->v('id') == $this->v('usuar_id')){
			// Usuário é o dono deste anúncio.
			return false;
		}
		
		$propoObj = $this->getPropoObj($usuarObj);
		if(!$propoObj){
			// Só será "new" quando não houver a proposta.
			$propoObj = new cProposta;
			$propoObj->v('anunc_id', $this->v('id'));
			$propoObj->v('usuar_id', $usuarObj->v('id'));
			$propoObj->save();
		}
		return true;
	}
	Function getLink($fullPath=false, cUsuario $usuarObj=null){
		// Se $usuarObj, retorna o link com auto=login ativado.
		if($usuarObj){
			return $usuarObj->getLink($this->getLink(false));
		}
		
		// Pega o link da proposta.
		return
			($fullPath?dSystem::getGlobal('baseUrlSSL')."/":"").
			"ver-anuncio.php?codigo={$this->v('codigo')}";
	}
	
	Function delete(){
		// Exclui tudo que está relacionado.
		$allPropostas = cProposta::multiLoad("where anunc_id='{$this->v('id')}'");
		foreach($allPropostas as $propoObj){
		    $propoObj->delete();
		}
		
		return parent::delete();
	}
	
	public Function calculaCustoHa($_distancia){
		$precoKg = $this->v('valor_por_kg');
		if(!$precoKg){
			$precoPorUn = $this->v('valor_por_embalagem');
			if(!$precoPorUn){
				return false;
			}
			
			$kiloPorUn  = false;
			switch($this->v('embalagem')){
				case "Saco 20 Kg":
					$kiloPorUn = 20;
					break;
				case "Saco 25 Kg":
					$kiloPorUn = 25;
					break;
				case "Saco 40 Kg":
					$kiloPorUn = 40;
					break;
				case "Big Bag 800 Kg":
					$kiloPorUn = 800;
					break;
				case "Big Bag 1000 Kg":
					$kiloPorUn = 1000;
					break;
			}
			if(!$kiloPorUn){
				return false;
			}
			
			$precoKg = $precoPorUn/$kiloPorUn;
			if(!$precoKg){
				return false;
			}
		}
		
		$plantabilidade = 315000; // Static.
		$pms            = $this->v('pms');
		if($pms == "N/A" || $pms == "NÃO APLICÁVEL" || !$pms){
			return false;
		}
		if($pms == "Até 80" || $pms == "Até 80,99"){
			$pms = 80;
		}
		if($pms == "a partir de 255"){
			$pms = 255;
		}
		else if(stripos($pms, " a ") !== false){
			// 459 a 464
			$pms = explode(" a ", $pms);
			$pms = (round($pms[0])+round($pms[1])/2);
		}
		else{
			// Mantém o que veio.
			$pms = dHelper2::forceFloat($pms);
			if(!$pms){
				// Não tem PMS válido, não tem como calcular o preço/ha.
				return false;
			}
		}
		
		$kgPorHa = $plantabilidade*$pms/1000000/0.85;
		return $precoKg * $kgPorHa;
	}
	
	static Function checkExpired(){
		$toExpire = cAnuncio::multiLoad("where !ISNULL(autoexpire_data) and autoexpire_data < now() and status='Ag. Propostas'");
		foreach($toExpire as $anuncObj){
			$anuncObj->setExpirado();
		}
	}
}
