<?php
// 2015-07-15: Sistema aprendeu a lidar com payment-request e respostas que as vezes vêm como Json e as vezes como XML.
// 2015-07-10: Bugfix: Quando "quantidade" é enviada como decimal, o Pagseguro retorna "Internal Server Error".
// 2015-02-19: Bugfix de "NOTICE" quando não há histórico.
// 2015-01-06: + Novo método setSandbox($yesno)
//                   + Novo parâmetro $apiVersion em getNotification() e getTransaction. Padrão: 'v2'
//                   * Bugfix em tratamentos de erros (resposta em branco)
// 2014-11-03: * Bugfix em diversos tratamentos de erro, e limite de caracteres no produto (erro e permissive)

// Documentação PagSeguro:
//		https://pagseguro.uol.com.br/v2/guia-de-integracao/visao-geral.html

// Changelog da atualização do PagSeguro (v2 --> v3), em 01/01/2015.
// º API de Notificação (getNotification)
//   - O campo 'lasteventdate' some do exemplo, mas continua documentado e ainda vem na resposta.
//   - O campo 'feeamount' é substituído por <creditorFees><intermediationRateAmount/><intermediationFeeAmount/></creditorFees>
//   - Campos que não possuíam letras maiúsculas, agora possuem:
//         paymentMethod, grossAmount, discountAmount, netAmount
//         extraAmount,   installmentCount, itemCount, areaCode, postalCode
//   - Adicionados novos tipos de 'status da transação', sendo:
//         8=Chargeback debitado, 9=Em contestação
// 
// º API de Transação por Código (getTransaction)
//   - O campo 'lasteventdate' some do exemplo, mas continua documentado;
//   - O campo 'feeamount' é substituído por [creditorFees][intermediationRateAmount|intermediationFeeAmount]
//   - A nomenclatura (maiúsculas/minúsculas) já seguia o novo padrão, então este método não foi alterado.
// 
// * As demais APIs (Checkout, Histórico de Transações, Transações Abandonadas) não foram atualizadas (continuaram na v2).


// Modo de uso:
//   $ps = new dPagSeguro($email, $token);
// 
//   $ps->newPagamento($pedido, $produtos[, $callback])
//		@input
// 			Array $pedido:
//				[reference]
//				[senderEmail], [senderName], [senderAreaCode (2 dígitos DDD)], [senderPhone]
//				[shippingType], [shippingCost]
//				[shippingAddressCountry], [shippingAddressState], [shippingAddressCity], [shippingAddressPostalCode], [shippingAddressDistrict], [shippingAddressStreet], [shippingAddressNumber], [shippingAddressComplement]
//				[extraAmount], [redirectUrl]
//			
// 			Array $produtos []:
//				id, description, amount, quantity
//				[weight], [shippingCost]
// 		
//			Callback $callback($goUrl, $code, $date)
//		
//		@return
//			string $goUrl (url para direcionar o usuário)
// 			FALSE (se houver erros, use listErrors para obter a descricao)
// 
//   $ps->getNotification($type, $code)
//   $ps->getNotification($code)
//   	@input
// 			string $type --> Sempre 'transaction'
//          string $code --> Código da notificação, com > 39 caracteres
//      @return = getTransaction()
// 
//   @ps->getTransaction($code)
//      @input string $code --> 36 caracteres.
//      @return
// 			Array $resposta (https://pagseguro.uol.com.br/v2/guia-de-integracao/consulta-de-transacoes-por-codigo.html)
//          	[code]                --> Código da transação, com 36 caracteres.
//              [reference]           --> Código interno do pedido
//              [status]              --> 1=Ag. Pagam, 2=Em análise, 3=Paga, 4=Disponível, 5=Em disputa, 6=Devolvida, 7=Cancelada
//              [paymentMethod][type] --> 1=Crédito, 2=Boleto, 3=TEF, 4=Saldo PagSeguro, 5=Oi Paggo
//              [paymentMethod][code] --> 101=Visa, 102=MasterCard, 103=American Express, 104=Diners, 105=Hipercard, 106=Aurora, etc...
// 
//   @ps->getTransactionHistory($initialDate, $finalDate[, $page=1[, $maxPageResults=100]])
//   	@input
//          timestamp|usdate|brdate initialDate, finalDate
//          page, maxPageResults
//      @return
//          Array $resposta (https://pagseguro.uol.com.br/v2/guia-de-integracao/consulta-de-transacoes-por-intervalo-de-datas.html)
//              [transactions][transaction] --> []
//                  date, code, type, lastEventDate, grossAmount
// 
//   @ps->getAbandonedHistory  ($initialDate, $finalDate[, $page=1[, $maxPageResults=100]])
//   	@input
//          timestamp|usdate|brdate initialDate, finalDate
//          page, maxPageResults
//      @return
//          Array $resposta (https://pagseguro.uol.com.br/v2/guia-de-integracao/consulta-de-transacoes-abandonadas.html)
//              [transactions][transaction] --> [] 
//                   date, lastEventDate, code, reference, type, status
//                   status, paymentMethod[type], paymentMethod[code]
//                   grossAmount, discountAmount, feeAmount, netAmount, extraAmount
// 
// Sugestões de uso e instalação do PagSeguro:
//   > Integração > Gerar TOKEN
//   > Integração > Página de redirecionamento > Página fixa: "Desativado"
//   > Integração > Página de redirecionamento > Dinâmico: "Ativado" : "transaction_id"
//   > Integração > Pagamentos via API > "Ativado" (Sem isso, o redirecionamento dinâmico não funciona)
//   > Integração > Notificação de transações > "Ativado" (URL NOTIFICACAO)
//   > Integração > Retorno automático de dados > "Desativado"

// URL Retorno:
// --> $ps->getTransaction($_GET['transaction_id']) --> Decobre o status da transação de volta. Leia a documentação do PagSeguro para saber o que vem aqui.

// URL Notificação:
// --> $ps->getNotification($_POST['notificationCode'], $_POST['notificationType']) -->  Leia a documentação do PagSeguro para saber o que vem aqui.


class dPagSeguro{
	private $settings;
	private $errors;
	public  $permissive; // Permite que a classe modifique parâmetros para não acusar erro no Pagseguro.
	public  $sandbox;
	public  $debug;
	
	static Function getVersion(){
		return 20150715;
	}
	
	Function __construct          ($email, $token, $receiverEmail=false){
		$this->errors     = Array();
		$this->debug      = false;
		$this->permissive = true;
		$this->sandbox    = false;
		$this->setToken($email, $token, $receiverEmail);
	}
	
	Function setToken             ($email, $token, $receiverEmail=false){
		$this->settings = Array('email'=>$email, 'token'=>$token, 'receiverEmail'=>$receiverEmail);
	}
	Function setSandbox           ($yesno){
		$this->sandbox = $yesno;
	}
	Function newPagamento         ($pedido, $produtos, $callback=false, $nRetries=3){
		// Parâmetros:
		//     https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-pagamentos.html
		// 
		// $pedido:
		//     [reference]
		//     [senderEmail], [senderName], [senderAreaCode (2 dígitos DDD)], [senderPhone]
		//     [shippingType], [shippingCost]
		//     [shippingAddressCountry], [shippingAddressState], [shippingAddressCity], [shippingAddressPostalCode], [shippingAddressDistrict], [shippingAddressStreet], [shippingAddressNumber], [shippingAddressComplement]
		//     [extraAmount], [redirectUrl]
		//     
		// $produtos[]:
		//     id, description, amount, quantity
		//     [weight], [shippingCost]
		// 
		// $callback($goUrl, $response['code'], $response['date']);
		// 
		$this->_clearErrors();
		$this->_checkErrors('newPagamento:pedido',   $pedido);
		$this->_checkErrors('newPagamento:produtos', $produtos);
		if($this->listErrors()){
			return false;
		}
		
		if(array_key_exists('shippingCost', $pedido)){
			$pedido['shippingCost'] = number_format($pedido['shippingCost'], 2, '.', '');
		}
		if(array_key_exists('extraAmount', $pedido)){
			$pedido['extraAmount'] = number_format($pedido['extraAmount'], 2, '.', '');
		}
		
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$b->agent = false;
		$b->setCharset('UTF-8');
		$b->addPost('email',    $this->settings['email']);
		$b->addPost('token',    $this->settings['token']);
		$b->addPost('currency', 'BRL');
		foreach($pedido as $key=>$value){
			$b->addPost($key, $value);
		}
		
		$nProduto = 0;
		foreach($produtos as $produInfo){
			$nProduto++;
			$produInfo['amount'] = number_format($produInfo['amount'], 2, '.', '');
			if(array_key_exists('shippingCost', $produInfo)){
				$produInfo['shippingCost'] = number_format($produInfo['shippingCost'], 2, '.', '');
			}
			foreach($produInfo as $key=>$value){
				$b->addPost("item".ucfirst($key).$nProduto, $value);
			}
		}
		
		// Parâmetros para enviar:
		if($this->settings['receiverEmail']){
			$b->addPost('receiverEmail', $this->settings['receiverEmail']);
		}
		
		$b->addPost('maxAge',   array_key_exists('maxAge',  $pedido)?$pedido['maxAge'] :60*60);
		$b->addPost('maxUses',  array_key_exists('maxUses', $pedido)?$pedido['maxUses']:100);
		$b->go("https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/v2/checkout");
		$body     = $b->getBody();
		$response = $this->_parseXML($body);
		if($response === false){
			$this->_addError("-999", "Resposta desconhecida: {$body}");
			return false;
		}
		
		$this->_checkErrors('newPagamento:response', $response);
		$errorList = $this->listErrors();
		if($errorList){
			// Problemas conhecidos:
			//     (11012) senderName invalid value: Lucas
			//     (11010) senderEmail invalid value: xxxxxxxxxx
			//     (11020) shippingAddressComplement invalid length: restaurante maracões no final de linha da calçada
			//     (-999)  Resposta desconhecida: XXXXX
			// 
			// Como tornar esta classe permissiva?
			//     Apenas os campos preenchidos pelo usuário serão permissivos, ou seja, apenas os que
			//     começarem com sender* e shipping*.
			// 
			//     Problema de comunicação (Resposta Desconhecida) permitirá o re-submit sem alterações
			//     no formulário.
			//     
			//     Outros problemas (como problema no 'Reference' ou nos produtos) não são permissivos, e serão sempre
			//     críticos.
			// 
			if($this->permissive && $nRetries){
				$newPedido   = $pedido;
				$_allowRetry = false;
				foreach($errorList as $errorCode=>$errorStr){
					if($errorCode == -999){
						$_allowRetry = true;
						continue;
					}
					if(preg_match("/^((sender|shipping).+?) invalid/", $errorStr, $out)){
						// Remove o field informado na mensagem de erro, para tornar a classe permissiva.
						unset($newPedido[$out[1]]);
					}
				}
				
				if($_allowRetry || (serialize($newPedido) != serialize($pedido))){
					return $this->newPagamento($newPedido, $produtos, $callback, $nRetries-1);
				}
			}
			return false;
		}
		
		$goUrl = "https://".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/v2/checkout/payment.html?code=".$response['code'];
		if($callback){
			call_user_func($callback, $goUrl, $response['code'], $response['date']);
		}
		return $goUrl;
	}
	Function getNotification      ($type=false, $code=false, $apiVersion='v2'){
		// Parâmetros aceitos:
		//   getNotification('payment-request', '12345');
		//   getNotification('transaction',     '12345', 'v3');
		//   getNotification('12345', 'v3')
		//   getNotification('12345')
		$this->_clearErrors();
		if(!$code || $code == 'v3' || $code == 'v2'){
			$apiVersion = $code?$code:$apiVersion;
			$code = $type;
			$type = 'transaction';
		}
		$params = Array('type'=>$type, 'code'=>$code);
		if($this->_checkErrors('getNotification:params', $params)){
			return false;
		}
		
		$b = new dBrowser2;
		$b->debug = $this->debug;
		if($type == 'transaction'){
			$b->go("https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/{$apiVersion}/transactions/notifications/{$code}?email={$this->settings['email']}&token={$this->settings['token']}");
		}
		elseif($type == 'payment-request'){
			$b->go("https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/payment-request/notifications/{$code}?email={$this->settings['email']}&token={$this->settings['token']}");
		}
		else{
			$this->_addError('-999', "Tipo de notificação desconhecida ({$type})");
			return false;
		}
		$body     = $b->getBody();
		
		if($body == "Not Found" || !trim($body)){
			$this->_addError('-999', "Not found");
			return false;
		}
		
		$stdType = 'xml';
		if($body[0] == '<'){
			$stdType = 'xml';
			$response = $this->_parseXML($body);
		}
		else{
			$response = json_decode($body, true);
			$stdType = 'json';
		}
		
		if($response === false){
			$this->_addError("-999", "Resposta desconhecida: {$body}");
			return false;
		}
		if($this->_checkErrors("getNotification:response:{$stdType}", $response)){
			return false;
		}
		
		if($type == 'transaction' || $type == 'payment-request'){
			return $this->_standardizeResponse("transaction:{$stdType}", $response);
		}
		
		return $response;
	}
	Function getTransaction       ($code, $apiVersion='v2'){
		$this->_clearErrors();
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$b->go("https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/{$apiVersion}/transactions/{$code}?email={$this->settings['email']}&token={$this->settings['token']}");
		$body     = $b->getBody();
		if($body == "Not Found" || !trim($body)){
			$this->_addError('-999', "Not found");
			return false;
		}
		
		$response = $this->_parseXML($body);
		if($response === false){
			$this->_addError("-999", "Resposta desconhecida: {$body}");
			return false;
		}
		if($this->_checkErrors('getTransaction:response', $response)){
			return false;
		}
		return $this->_standardizeResponse('transaction', $response);
	}
	Function getTransactionHistory($initialDate, $finalDate, $page=1, $maxPageResults=100){
		$this->_clearErrors();
		$params = Array('initialDate'=>$initialDate, 'finalDate'=>$finalDate, 'page'=>$page, 'maxPageResults'=>$maxPageResults);
		if($this->_checkErrors('getTransaction', $params))
			return true;
		
		if(stripos($initialDate, "/")){
			// Veio em formato brasileiro:
			//     30/12/2010 --> Converta para US (2010-12-30)
			$initialDate = explode("/", $initialDate);
			$initialDate = "{$initialDate[2]}/{$initialDate[1]}/{$initialDate[0]}";
		}
		elseif(is_numeric($initialDate)){
			// Veio em formato TIMESTAMP:
			//     12345678901 --> Converta para US (2010-12-30)
			$initialDate = strtotime("Y-m-d", $initialDate);
		}
		
		if(stripos($finalDate, "/")){
			// Veio em formato brasileiro:
			//     30/12/2010 --> Converta para US (2010-12-30)
			$finalDate = explode("/", $finalDate);
			$finalDate = "{$finalDate[2]}/{$finalDate[1]}/{$finalDate[0]}";
		}
		elseif(is_numeric($finalDate)){
			// Veio em formato TIMESTAMP:
			//     12345678901 --> Converta para US (2010-12-30)
			$finalDate = strtotime("Y-m-d", $finalDate);
		}
		
		$initialDate .= "T00:00";
		$finalDate   .= ($finalDate == date('Y-m-d'))?
			"T".date('H:i', strtotime("-30 minutes")):
			"T23:59";
		
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$goOk = $b->go(
			"https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/v2/transactions?".
			"email={$this->settings['email']}&".
			"token={$this->settings['token']}&".
			"initialDate={$initialDate}&".
			"finalDate={$finalDate}&".
			"page={$page}&".
			"maxPageResults={$maxPageResults}"
		);
		
		if(!$goOk){
			$this->_addError('-999', "Falha na conexão com o Pagseguro.");
			return false;
		}
		
		$body = $b->getBody();
		if($body == "Not Found" || !trim($body)){
			$this->_addError('-999', "Not found");
			return false;
		}
		
		$response = $this->_parseXML($body);
		if($response === false){
			$this->_addError("-999", "Resposta desconhecida: {$body}");
			return false;
		}
		if($this->_checkErrors('getTransactionHistory:response', $response)){
			return false;
		}
		
		return $this->_standardizeResponse('history', $response);
	}
	Function getAbandonedHistory  ($initialDate, $finalDate, $page=1, $maxPageResults=100){
		$this->_clearErrors();
		$params = Array('initialDate'=>$initialDate, 'finalDate'=>$finalDate, 'page'=>$page, 'maxPageResults'=>$maxPageResults);
		if($this->_checkErrors('getTransaction', $params))
			return true;
		
		if(stripos($initialDate, "/")){
			// Veio em formato brasileiro:
			//     30/12/2010 --> Converta para US (2010-12-30)
			$initialDate = explode("/", $initialDate);
			$initialDate = "{$initialDate[2]}/{$initialDate[1]}/{$initialDate[0]}";
		}
		elseif(is_numeric($initialDate)){
			// Veio em formato TIMESTAMP:
			//     12345678901 --> Converta para US (2010-12-30)
			$initialDate = strtotime("Y-m-d", $initialDate);
		}
		
		if(stripos($finalDate, "/")){
			// Veio em formato brasileiro:
			//     30/12/2010 --> Converta para US (2010-12-30)
			$finalDate = explode("/", $finalDate);
			$finalDate = "{$finalDate[2]}/{$finalDate[1]}/{$finalDate[0]}";
		}
		elseif(is_numeric($finalDate)){
			// Veio em formato TIMESTAMP:
			//     12345678901 --> Converta para US (2010-12-30)
			$finalDate = strtotime("Y-m-d", $finalDate);
		}
		
		$initialDate .= "T00:00";
		$finalDate   .= ($finalDate == date('Y-m-d'))?
			"T".date('H:i', strtotime("-30 minutes")):
			"T23:59";
		
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$b->go(
			"https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/v2/transactions?".
			"email={$this->settings['email']}&".
			"token={$this->settings['token']}&".
			"initialDate{$initialDate}&".
			"finalDate={$finalDate}&".
			"page={$page}&".
			"maxPageResults={$maxPageResults}"
		);
		if($body == "Not Found" || !trim($body)){
			$this->_addError('-999', "Not found");
			return false;
		}
		
		$response = $this->_parseXML($b->getBody());
		if($response === false){
			$this->_addError("-999", "Resposta desconhecida: {$body}");
			return false;
		}
		if($this->_checkErrors('getAbandonedHistory:response', $response)){
			return false;
		}
		return $this->_standardizeResponse('history', $response);
	}
	Function listErrors           (){
		// Retorna Array(errorCode, errorMessage)
		return $this->errors;
	}
	
	static Function getStringByCode($what, $code){
		if($what == 'status'){
			if($code == '1') return "Aguardando pagamento";
			if($code == '2') return "Em análise";
			if($code == '3') return "Paga";
			if($code == '4') return "Disponível";
			if($code == '5') return "Em disputa";
			if($code == '6') return "Devolvida";
			if($code == '7') return "Cancelada";
			return false;
		}
		if($what == 'pagamento'){
			if($code == '1') return "Cartão de crédito";
			if($code == '2') return "Boleto";
			if($code == '3') return "Débito online (TEF)";
			if($code == '4') return "Saldo PagSeguro";
			if($code == '5') return "Oi Paggo";
			
			if($code == '101') return "Cartão de crédito Visa.";
			if($code == '102') return "Cartão de crédito MasterCard.";
			if($code == '103') return "Cartão de crédito American Express.";
			if($code == '104') return "Cartão de crédito Diners.";
			if($code == '105') return "Cartão de crédito Hipercard.";
			if($code == '106') return "Cartão de crédito Aura.";
			if($code == '107') return "Cartão de crédito Elo.";
			if($code == '108') return "Cartão de crédito PLENOCard.";
			if($code == '109') return "Cartão de crédito PersonalCard.";
			if($code == '110') return "Cartão de crédito JCB.";
			if($code == '111') return "Cartão de crédito Discover.";
			if($code == '112') return "Cartão de crédito BrasilCard.";
			if($code == '113') return "Cartão de crédito FORTBRASIL.";
			if($code == '201') return "Boleto Bradesco. *";
			if($code == '202') return "Boleto Santander.";
			if($code == '301') return "Débito online Bradesco.";
			if($code == '302') return "Débito online Itaú.";
			if($code == '303') return "Débito online Unibanco. *";
			if($code == '304') return "Débito online Banco do Brasil.";
			if($code == '305') return "Débito online Banco Real. *";
			if($code == '306') return "Débito online Banrisul.";
			if($code == '307') return "Débito online HSBC.";
			if($code == '401') return "Saldo PagSeguro.";
			if($code == '501') return "Oi Paggo.";
			
			if(strlen($code) == 3){
				// Se vier um código não listado, recupere o genérico.
				// Ex: 198 --> Retorna "Cartão de crédito".
				return self::getStringByCode($what, $code[0]);
			}
			
			return false;
		}
		if($what == 'frete'){
			if($code == '1') return "PAC";
			if($code == '2') return "SEDEX";
			if($code == '3') return "Outro";
		}
		
		return false;
	}
	static Function getParcelasValorMin  ($valor){
		// Retorno:
		//   Array('valor_total'=>, 'valor_parcela'=>, 'n_parcelas'=>)
		
		$tabela = self::getTabelaParcelamento($valor);
		return array_pop($tabela);
	}
	static Function getTabelaParcelamento($valor){
		// Fonte de informações:
		//   https://pagseguro.uol.com.br/para_seu_negocio/parcelamento_com_acrescimo.jhtml/
		// 
		// Formato do retorno:
		// Array[nParc] => Array('valor_total'=>, 'valor_parcela'=>, 'n_parcelas'=>)
		//     
		$fator = Array(
			1	=> 1.00000, 2	=> 0.52255, 3	=> 0.35347, 4	=> 0.26898, 5	=> 0.21830,
			6	=> 0.18453, 7	=> 0.16044, 8	=> 0.14240, 9	=> 0.12838, 10	=> 0.11717,
			11	=> 0.10802, 12	=> 0.10040, 13	=> 0.09397, 14	=> 0.08846, 15	=> 0.08371,
			16	=> 0.07955, 17	=> 0.07589, 18	=> 0.07265,
		);
		
		$tabela = Array();
		for($nParc = 1; $nParc <= 12; $nParc++){
			$parcela = $nParc;
			$val_par = $valor   * $fator[$nParc];
			if($val_par < 5)
				break;
			
			$tabela[$nParc] = Array(
				'valor_total'  =>$val_par * $nParc,
				'valor_parcela'=>round($val_par, 2),
				'n_parcelas'   =>$nParc
			);
		}
		return $tabela;
	}
	
	Function _standardizeResponse($type, $response){
		if($type == 'transaction:xml'){
			if(array_key_exists('id', $response['items']['item'])){
				$response['items']['item'] = Array($response['items']['item']);
			}
		}
		if($type == 'transaction:json'){
			$response['items'] = Array('item'=>$response['items']);
		}
		if($type == 'history'){
			if(isset($response['transactions']['transaction']['date'])){
				$response['transactions']['transaction'] = Array($response['transactions']['transaction']);
			}
		}
		
		return $response;
	}
	Function _clearErrors(){
		$this->errors = Array();
	}
	Function _addError   ($code, $message){
		$this->errors[$code] = $message;
	}
	Function _checkErrors($where, &$data){
		// TRUE if has any errors.
		// FALSE is no errors found.
		
		// Parâmetros:
		//     https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-pagamentos.html
		// 
		// $pedido:
		//     [reference]
		//     [senderEmail], [senderName], [senderAreaCode (2 dígitos DDD)], [senderPhone]
		//     [shippingType], [shippingCost]
		//     [shippingAddressCountry], [shippingAddressState], [shippingAddressCity], [shippingAddressPostalCode], [shippingAddressDistrict], [shippingAddressStreet], [shippingAddressNumber], [shippingAddressComplement]
		//     [extraAmount], [redirectUrl]
		//     
		// $produtos[]:
		//     id, description, amount, quantity
		//     [weight], [shippingCost]
		if($where == 'newPagamento:pedido'){
			$pedido = &$data;
			$permis = $this->permissive;
			
			if( @$pedido['senderName']   && !preg_match("/.+ .+/", $pedido['senderName'])){
				if($permis){
					unset($pedido['senderName']);
				}
				else{
					$this->_addError('-999', "Nome do cliente (senderName) deve ser completo (com sobrenome)");
				}
			}
			if( @$pedido['senderName']   &&  preg_match("/  +/", $pedido['senderName'])){
				if($permis){
					$pedido['senderName'] = preg_replace("/  +/", " ", $pedido['senderName']);
				}
				else{
					$this->_addError('-999', "Campo senderName tem dois espaços entre os nomes");
				}
			}
			if( isset($pedido['shippingAddressRequired'])){
				if($permis){
					$pedido['shippingAddressRequired'] = (!$pedido['shippingAddressRequired'] || strtolower($pedido['shippingAddressRequired']) == 'false')?
						'false':
						'true';
				}
				else{
					$this->_addError('-999', "Campo shippingAddressRequired deve ser 'true' ou 'false'");
				}
			}
			if(!isset($pedido['shippingAddressRequired']) || $pedido['shippingAddressRequired'] == 'true'){
				if(!@$pedido['shippingType'] || !in_array(strtolower($pedido['shippingType']), Array('1', '2', '3', 'pac', 'sedex', 'outro'))){
					if($permis){
						$pedido['shippingType'] = 3;
					}
					else{
						$this->_addError('-999', "Campo shippingType é obrigatório");
					}
				}
				if( @$pedido['shippingAddressComplement'] && strlen($pedido['shippingAddressComplement']) > 40){
					if($permis){
						$pedido['shippingAddressComplement'] = substr($pedido['shippingAddressComplement'], 0, 40);
					}
					else{
						$this->_addError('-999', "Campo shippingAddressComplement ultrapassou o limite de 40 caracteres.");
					}
				}
				if( @$pedido['shippingAddressNumber'] && strlen($pedido['shippingAddressNumber']) > 20){
					if($permis){
						$pedido['shippingAddressNumber'] = substr($pedido['shippingAddressNumber'], 0, 20);
					}
					else{
						$this->_addError('-999', "Campo shippingAddressNumber ultrapassou o limite de 20 caracteres.");
					}
				}
				if( @$pedido['shippingAddressState']  && strlen($pedido['shippingAddressState']) != 2 ){
					if($permis){
						unset($pedido['shippingAddressState']);
					}
					else{
						$this->_addError('-999', "Campo shippingAddressState deve ter exatamente 2 caracteres.");
					}
				}
				if(strtolower($pedido['shippingType']) == 'pac'){
					$pedido['shippingType'] = '1';
				}
				elseif(strtolower($pedido['shippingType']) == 'sedex'){
					$pedido['shippingType'] = '2';
				}
				else{
					$pedido['shippingType'] = '3';
				}
			}
		}
		if($where == 'newPagamento:produtos'){
			$produtos = &$data;
			$permis   = $this->permissive;
			
			foreach($produtos as $idx=>$produto){
				if(strlen($produto['description']) > 100){
					if($permis){
						$produtos[$idx]['description'] = trim(substr($produto['description'], 0, 97))."...";
					}
					else{
						$this->_addError('-999', "Descricao do produto nao pode ultrapassar 100 caracteres.");
					}
				}
				if(strpos($produto['quantity'], '.') !== false){
					if(intval($produto['quantity']) == floatval($produto['quantity'])){
						if($permis){
							$produtos[$idx]['quantity'] = intval($produto['quantity']);
						}
						else{
							$this->_addError('-999', "A quantidade informada contém casas decimais, o que não é aceito pelo pagseguro.");
						}
					}
				}
			}
		}
		if($where == 'getNotification:params'){
			// Nothing to validate.
		}
		if($where == 'getTransactionHistory:params'){
			// Nothing to validate.
		}
		if($where == 'newPagamento:response'){
			if(array_key_exists('error', $data)){
				if(array_key_exists('code', $data['error']))
					$data['error'] = Array($data['error']);
				
				foreach($data['error'] as $errorItem){
					$this->_addError($errorItem['code'], $errorItem['message']);
				}
				return true;
			}
		}
		if($where == 'getNotification:response:xml' || $where == 'getTransaction:response:xml'){
			if(array_key_exists('error', $data)){
				if(array_key_exists('code', $data['error']))
					$data['error'] = Array($data['error']);
				
				foreach($data['error'] as $errorItem){
					$this->_addError($errorItem['code'], $errorItem['message']);
				}
				return true;
			}
			
		}
		if($where == 'getNotification:response:json' || $where == 'getTransaction:response:json'){
			if($data['error']){
				if(@$data['error']['code']){
					$this->_addError($data['error']['code'], $data['error']['message']);
				}
				else{
					foreach($data['error'] as $errorItem){
						$this->_addError($errorItem['code'], $errorItem['message']);
					}
				}
				return true;
			}
		}
		if($where == 'getTransactionHistory:response' || $where == 'getAbandonedHistory:response'){
			if(array_key_exists('error', $data)){
				if(array_key_exists('code', $data['error']))
					$data['error'] = Array($data['error']);
				
				foreach($data['error'] as $errorItem){
					$this->_addError($errorItem['code'], $errorItem['message']);
				}
				return true;
			}
		}
		
		return false;
	}
	Function _parseXML($body){
		try{
			$xml = @new SimpleXMLElement($body);
		}
		catch(Exception $ex){
			return false;
		}
		
		$allXML = $this->_easyObjToArray($xml);
		return $allXML;
	}
	Function _easyObjToArray($data){
		if (is_object($data))
			$data = get_object_vars($data);
		return (is_array($data))?array_map(Array('dPagSeguro', '_easyObjToArray'), $data) : $data;
	}
}
