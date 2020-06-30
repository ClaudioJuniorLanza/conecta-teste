<?php // built on 02/04/2019 11:04
/**
 * Class cInvite
 *
 *     Esta classe permitirá utilizar os serviços do ClientCare
 *     para enviar convites manuais para clientes não-cadastrados, através dos métodos:
 *     * getMessage, send
 *
 */
class cInvite extends dDbRow3{
	static Function buildStruct(){
		self::setTable('c_invites');
		self::addField('id,data_hora,to_list,anuncios,subject,custom_msg,is_weekly');
		self::addField('api_campa_id,api_last_update,api_is_paused,api_nsent,api_nclicked');
		self::addField('api_nviewed,api_nbounces,api_data_finish,c_ndestinos,c_message');
		
		self::addValidation('data_hora',   'required',    false,    'Você precisa preencher o campo data hora');
		self::addValidation('to_list',     'required',    false,    'Você precisa preencher o campo to list');
		self::addValidation('anuncios',    'required',    false,    'Você precisa preencher o campo anuncios');
		self::addValidation('subject',     'required',    false,    'Você precisa preencher o campo subject');
		self::addValidation('is_weekly',   'required',    false,    'Você precisa preencher o campo is_weekly');
		self::addValidation('data_hora',   'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('api_last_update', 'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('api_data_finish', 'datetime',    'br',     'Preencha a data no formato dd/mm/aaaa hh:mm');
		self::addValidation('api_campa_id', 'int',         false,    'Preencha o campo api campa id apenas com números');
		self::addValidation('api_is_paused', 'int',         false,    'Preencha o campo api is paused apenas com números');
		self::addValidation('api_nsent',   'int',         false,    'Preencha o campo api nsent apenas com números');
		self::addValidation('api_nclicked', 'int',         false,    'Preencha o campo api nclicked apenas com números');
		self::addValidation('api_nviewed', 'int',         false,    'Preencha o campo api nviewed apenas com números');
		self::addValidation('api_nbounces', 'int',         false,    'Preencha o campo api nbounces apenas com números');
		self::addValidation('c_ndestinos', 'int',         false,    'Preencha o campo c ndestinos apenas com números');
		self::addValidation('api_campa_id', 'nummin',      0,        'Por favor, digite um numero positivo para api campa id');
		self::addValidation('api_is_paused', 'nummin',      0,        'Por favor, digite um numero positivo para api is paused');
		self::addValidation('api_nsent',   'nummin',      0,        'Por favor, digite um numero positivo para api nsent');
		self::addValidation('api_nclicked', 'nummin',      0,        'Por favor, digite um numero positivo para api nclicked');
		self::addValidation('api_nviewed', 'nummin',      0,        'Por favor, digite um numero positivo para api nviewed');
		self::addValidation('api_nbounces', 'nummin',      0,        'Por favor, digite um numero positivo para api nbounces');
		self::addValidation('c_ndestinos', 'nummin',      0,        'Por favor, digite um numero positivo para c ndestinos');
		self::addValidation('anuncios',    'singleline',  false,    'O campo anuncios não pode ter mais de uma linha');
		self::addValidation('subject',     'singleline',  false,    'O campo subject não pode ter mais de uma linha');
		self::addValidation('anuncios',    'strmax',      500,      'O campo anuncios não pode ter mais de 500 caracteres');
		self::addValidation('subject',     'strmax',      250,      'O campo subject não pode ter mais de 250 caracteres');
		
		self::addModifier('data_hora,api_last_update,api_data_finish', 'datetime',    'br');
		self::addModifier('to_list,anuncios,subject,c_message,custom_msg', 'trim');
		self::addModifier('api_campa_id,api_is_paused,api_nsent,api_nclicked,api_nviewed', 'force_int');
		self::addModifier('api_nbounces,c_ndestinos', 'force_int');
		
		self::setDefaultValue('is_weekly', '0');
		self::setDefaultValue('data_hora', date('d/m/Y H:i:s'));
		self::setDefaultValue('subject',   'Já pensou em comercializar sementes on-line?');
		
		self::addEventListener('beforeSave', function(cInvite $obj){
			if(!$obj->getOriginal('to_list') || $obj->hasAliasChanged('to_list')){
				if(!$obj->v('to_list')){
					$obj->setValue('c_ndestinos', 0);
				}
				else{
					$emails = trim(strtolower($obj->v('to_list')));
					$nLines = substr_count($emails, "\n") + 1;
					$obj->setValue('c_ndestinos', $nLines);
				}
			}
			
			if($obj->v('api_campa_id') && !$obj->v('c_message')){
				$obj->v('c_message', $obj->getMessage(true));
			}
			
			return;
		});
		
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
	
	// Preparação para o e-mail semanal para usuários com interesse cadastrado.
	// Será armazenado como:
	//     is_weekly: '1'
	//     to_list:   {usuarId: 123, oportCompra: [1,2,3], oportVenda: [1,2,3])
	//
	static Function weeklyInvites($verbose, &$debugTo=null){
		$_verbose = function($str, $dump=null) use ($verbose, &$debugTo){
			$debugTo[] = $str;
			if($verbose){
				echo "{$str}<br />";
				if($dump !== null){
					echo "<div style='margin-left: 16px; margin-bottom: 16px'>";
					dHelper2::dump($dump);
					echo "</div>";
				}
			}
		};
		
		// O Cron estará definido para 1x por semana... Então caso ocorra 2x no mesmo dia, interrompa.
		// Vale lembrar que o ClientCare (->send()) impede e-mails duplicados a cada 5 dias, então
		// podemos ser um pouco permissivos aqui.
		$db          = dDatabase::start();
		$mostRecent  = $db->singleResult("select UNIX_TIMESTAMP(data_hora) from c_invites where is_weekly='1' limit 1");
		if($mostRecent && strtotime("+1 day", $mostRecent) > time()){
			$_verbose("Já tenho um e-mail weekly enviado recentemente, ignorando.");
			return false;
		}
		
		$anuncIds = []; // [1,2,3,4...] Será armazenado em c_invites.anuncios.
		$allUsers = []; // [id] = cUsuario...
		$allAnunc = []; // [id] = cAnuncio...
		$toList   = []; // { usuarId: 1234, OportCompra: [1,2,3,4], OportVenda: [1,2,3,4] }
		$_verbose("Iniciando geração de convites semanais baseados nos interesses...");
		$rawUsers = cUsuario::multiLoad("where !isnull(interesses_json)");
		foreach($rawUsers as $usuarObj){
			$interesses = $usuarObj->getInteresses();
			
			$_toVenda =
			$_toCompr = [];
			if($interesses['venda']  && $interesses['venda']['userDefined']){
				$_toVenda  = cAnuncio::getOportunidades($usuarObj, 'Compra', 'new'); // Quer vender, então busque anúncios de Compra
			}
			if($interesses['compra'] && $interesses['compra']['userDefined']){
				$_toCompr  = cAnuncio::getOportunidades($usuarObj, 'Venda', 'new'); // Quer comprar, então busque anúncios de Venda
			}
			if(!$_toVenda && !$_toCompr){
				continue;
			}
			
			$_verbose("{$usuarObj->v('nome')} ({$usuarObj->v('id')}): ".sizeof($anuncIds)." oportunidades.");
			$_verbose("... Sendo ".sizeof($_toVenda)." de venda e ".sizeof($_toCompr)." de compra.");
			$_verbose("");
			$appendItem = [
				'usuarId'    =>$usuarObj->v('id'),
				'oportCompra'=>[],
				'oportVenda' =>[],
			];
			foreach($_toCompr as $_anuncObj){
				$_anuncId = $_anuncObj->v('id');
				$anuncIds[] = $_anuncId;
				$allAnunc[$_anuncId] = $_anuncObj;
				$appendItem['oportCompra'][] = $_anuncId;
			}
			foreach($_toVenda as $_anuncObj){
				$_anuncId = $_anuncObj->v('id');
				$anuncIds[] = $_anuncId;
				$allAnunc[$_anuncId] = $_anuncObj;
				$appendItem['oportVenda'][] = $_anuncId;
			}
			
			$allUsers[$usuarObj->v('id')] = $usuarObj;
			$toList[] = $appendItem;
		}
		if(!$toList){
			$_verbose("Nenhuma oportunidade encontrada para os usuários cadastrados.");
			dSystem::log('LOW', "[Weekly] Não há e-mails para enviar nesta semana");
			return true;
		}
		
		$_verbose("Interesses foram detectados, criando a campanha...");
		$inviteObj = new cInvite;
		$inviteObj
			->v('to_list',   json_encode($toList))
			->v('anuncios',  implode(",", $anuncIds))
			->v('subject',   '[Semanal] Encontramos oportunidades que combinam com seus interesses!')
			->v('is_weekly', '1')
			->save();
		
		return $inviteObj->sendWeekly(false, $toList, $allUsers, $allAnunc);
	}
	
	/**
	 * @param bool $dryRun Não envia, apenas exibe na tela o que seria enviado.
	 * @param mixed $toList[] = { 'usuarId'    =>123, 'oportCompra'=>[1,2,3], 'oportVenda' =>[1,2,3] }
	 * @param cUsuario[] $allUsers [usuarId] = cUsuario;
	 * @param cAnuncio[] $allAnunc [anuncId] = cAnuncio;
	 */
	Function sendWeekly($dryRun=false, $toList=null, $allUsers=null, $allAnunc=null){
		if(!$toList){
			$toList = json_decode($this->v('to_list'), true);
		}
		if(!$allUsers){
			$rawUsers = cUsuario::multiLoad("where id IN (".implode(", ", array_map(function($row){ return $row['usuarId']; }, $toList)).")");
			$allUsers = [];
			foreach($rawUsers as $usuarObj){
				$allUsers[$usuarObj->v('id')] = $usuarObj;
			}
		}
		if(!$allAnunc){
			$rawAnunc = cAnuncio::multiLoad("where id IN ({$this->v('anuncios')})");
			$allAnunc = [];
			foreach($rawAnunc as $anuncObj){
				$allAnunc[$anuncObj->v('id')] = $anuncObj;
			}
		}
		
		if($this->v('api_campa_id')){
			// Já foi enviado.
			return false;
		}
		
		$toList      = array_map(function($rowItem) use (&$allUsers, &$allAnunc){
			$usuarObj = $allUsers[$rowItem['usuarId']];
			
			$queremComprar =
			$queremVender  = [];
			foreach($rowItem['oportVenda'] as $_anuncId){
				$anuncObj        = $allAnunc[$_anuncId];
				$queremComprar[]  = "_ROWLINK_{$anuncObj->v('codigo')}'>{$anuncObj->v('varieObj')->v('variedade')}</a>_/ROW_";
			}
			foreach($rowItem['oportCompra'] as $_anuncId){
				$anuncObj        = $allAnunc[$_anuncId];
				$queremVender[]  = "_ROWLINK_{$anuncObj->v('codigo')}]'>{$anuncObj->v('varieObj')->v('variedade')}</a>_/ROW_";
			}
			
			if(!$queremComprar){
				$queremComprar[] = "_ROW_Nenhuma oportunidade._/ROW_";
			}
			if(!$queremVender){
				$queremVender[] = "_ROW_Nenhuma oportunidade._/ROW_";
			}
			
			$toNome   = dHelper2::stringToTitle(mb_strtolower($usuarObj->v('responsavel_nome')));
			return [
				'to_email'=>$usuarObj->v('email'),
				'to_name' =>$toNome,
				'replaces'=>[
					'_QUEREM_COMPRAR_' =>implode("\r\n", $queremComprar),
					'_QUEREM_VENDER_'  =>implode("\r\n", $queremVender),
					'_ROWLINK_'        =>"_ROW_<a style='color: #000' href='_LINK_&codigo=",
					'_ROW_'            =>"<tr><td align='center'>",
					'_/ROW_'           =>"</td></tr>",
					'_LINK_'           =>"[T:{$usuarObj->getLink('ver-anuncio.php')}",
					'_INTERESSES_LINK_'=>"[T:{$usuarObj->getLink('meus-interesses.php')}]",
				],
			];
		}, $toList);
		
		$message = file_get_contents(dSystem::getGlobal('baseDir').'/admin/emails/invite_weekly.html');
		$message = str_replace("_INVITE:ID_",  $this->v('id'), $message);
		if($dryRun){
			echo "<b>Dumping first message:</b><br />";
			dHelper2::dump($toList[0]);
			$dumpMessage = $message;
			echo "<hr />";
			foreach($toList[0]['replaces'] as $replaceFrom=>$replaceTo){
				$dumpMessage = str_replace($replaceFrom, $replaceTo, $dumpMessage);
			}
			echo $dumpMessage;
			die;
		}
		
		$campaParams = Array(
			'policy'    =>  'Conecta-Weekly',
			'data_send' => date('d/m/Y H:i:s'),
			'campanha'  => "Convite semanal com base em interesses (id={$this->v('id')})",
			'subject'   => $this->v('subject'),
			'message'   => $message,
			'toList'    => $toList,
			'paused'    => '0',
			'send_now'  => '0', // '0': Agenda para quando a fila estiver disponível.
		);
		
		$c       = new dClientCare('CSEMENTES-VCK124NASD8');
		$apiResult = $c->sendCampanha($campaParams);
		if(!$apiResult['ok']){
			$this->addError(false, $apiResult['errorMsg']);
			return false;
		}
		$this->v('api_campa_id', $apiResult['campaId'])->save();
		return true;
	}
	
	Function getMessage($preview=false){
		$message  = file_get_contents(dSystem::getGlobal('baseDir').'/admin/emails/invite_template.html');
		if($preview){
			$message  = str_replace("[PIXEL]",      "",  $message);
			$message  = str_replace("[UNSUB_LINK]", "#", $message);
			$message  = preg_replace("/\[T:(.+?)\]/", "\\1", $message);
		}
		
		$allAnuncios = Array('Compra'=>Array(), 'Venda'=>Array());
		if($this->v('anuncios')){
			$allIds      = explode(",", $this->v('anuncios'));
			$rawAnuncios = cAnuncio::multiLoad("where c_anuncios.id IN(".(implode(',', $allIds)).") and status = 'Ag. Propostas'", 'varieObj');
			foreach($rawAnuncios as $anuncObj){
				$allAnuncios[$anuncObj->v('negocio')][] = $anuncObj;
				$allAnuncById[$anuncObj->v('id')] = $anuncObj;
			}
		}
		
		$queremComprar =
		$queremVender = Array();
		foreach($allAnuncios['Compra'] as $anuncObj){
			$queremComprar[] = "<tr><td align='center'>{$anuncObj->v('varieObj')->v('variedade')}</td></tr>";
		}
		foreach($allAnuncios['Venda'] as $anuncObj){
			$queremVender[] = "<tr><td align='center'>{$anuncObj->v('varieObj')->v('variedade')}</td></tr>";
		}
		
		$wholeList = array_merge($queremComprar, $queremVender);
		sort($wholeList);
		$wholeList = array_chunk($wholeList, ceil(sizeof($wholeList)/2));
		if(sizeof($wholeList) < 2){
			$wholeList[] = [];
		}
		
		
		$message = str_replace("_INVITE:ID_",  $this->v('id'), $message);
		$message = str_replace("[CUSTOM_MSG]", nl2br($this->v('custom_msg')),  $message);
		$message = str_replace("[COLUNA1]",    implode("\r\n", $wholeList[0]), $message);
		$message = str_replace("[COLUNA2]",    implode("\r\n", $wholeList[1]), $message);
		return $message;
	}
	
	Function send($testTo=false){
		$toList = $testTo;
		if(!$toList){
			// Não é teste, é envio oficial.
			$toList = explode("\n", strtolower(trim($this->v('to_list'))));
			$toList = array_map('trim', $toList);
			$toList = array_filter($toList);
			$toList = array_unique($toList);
		}
		else{
			$toList = Array($testTo);
		}
		
		$message = $this->getMessage();
		
		$c = new dClientCare('CSEMENTES-VCK124NASD8');
		$apiResult = $c->sendCampanha(Array(
			'policy'    =>  'Conecta-Invites',
			'data_send' => date('d/m/Y H:i:s'),
			'campanha'  => "Convite {$this->v('id')}".($testTo?" (E-mail de teste)":""),
			'subject'   => $this->v('subject'),
			'message'   => $message,
			'toList'    => $toList,
			'paused'    => '0', // ($testTo?'0':'1'),
			'send_now'  => ($testTo?'1':'0'),
		));
		
		if(!$apiResult['ok']){
			$this->addError(false, $apiResult['errorMsg']);
			return false;
		}
		
		if(!$testTo){
			$this->v('api_campa_id', $apiResult['campaId'])->save();
		}
		return true;
	}
	
	static Function syncAll(){
		// Envio iniciado de  0 a 4 horas:        Atualização a cada 30 minutos.
		// Envio iniciado de  4 horas a 24 horas: Atualização a cada 2 horas.
		// Envio iniciado de  1 dia até 1 semana: Atualização a cada 12 horas.
		// Envio iniciado há mais de 1 msemana:   Não atualizar.
		
		// Data do envio:
		$menos4Horas = "NOW() <  DATE_ADD(data_hora, INTERVAL 4 HOUR)";
		$de4a24Horas = "NOW() >= DATE_ADD(data_hora, INTERVAL 4 HOUR) and NOW() < DATE_ADD(data_hora, INTERVAL 1 DAY)";
		$de1a7Dias   = "NOW() >= DATE_ADD(data_hora, INTERVAL 1 DAY)  and NOW() < DATE_ADD(data_hora, INTERVAL 7 DAY)";
		
		// Atualizado por último há....
		$menos4Horas .= "\n\tand NOW() >= DATE_ADD(api_last_update, INTERVAL 30 MINUTE)";
		$de4a24Horas .= "\n\tand NOW() >= DATE_ADD(api_last_update, INTERVAL 2  HOUR)";
		$de1a7Dias   .= "\n\tand NOW() >= DATE_ADD(api_last_update, INTERVAL 12 HOUR)";
		
		array_map(function(cInvite $inviteObj){ return $inviteObj->syncWithAPI(); }, cInvite::multiLoad("where !isnull(api_campa_id) \n\tand {$menos4Horas}"));
		array_map(function(cInvite $inviteObj){ return $inviteObj->syncWithAPI(); }, cInvite::multiLoad("where !isnull(api_campa_id) \n\tand {$de4a24Horas}"));
		array_map(function(cInvite $inviteObj){ return $inviteObj->syncWithAPI(); }, cInvite::multiLoad("where !isnull(api_campa_id) \n\tand {$de1a7Dias}"));
	}
	Function syncWithAPI(){
		if(!$this->v('api_campa_id')){
			// Impossível sincronizar - Não foi enviado.
			echo "Sem campanha id";
			return false;
		}
		
		$c      = new dClientCare('CSEMENTES-VCK124NASD8');
		$status = $c->getCampanhaStatus($this->v('api_campa_id'));
		if(!$status){
			// Não foi possível sincronizar.
			return;
		}
		
		$this->v('api_last_update',  date('d/m/Y H:i:s'));
		$this->v('api_is_paused',    $status['isPaused']);
		$this->v('api_nsent',        $status['nSent']);
		$this->v('api_nclicked',     $status['nClicked']);
		$this->v('api_nviewed',      $status['nViewed']);
		$this->v('api_nbounces',     $status['nBounces']);
		$this->v('api_data_finish',  $status['dataFinished']);
		$this->save();
	}
	
	Function getDetailsLink(){
		$c      = new dClientCare('CSEMENTES-VCK124NASD8');
		return $c->getCampanhaStatusLink($this->v('api_campa_id'));
	}
}
?>