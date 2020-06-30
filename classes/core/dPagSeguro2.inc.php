<?php
class dPagSeguro2{
	public  $permissive     = true;
	private $timeout        = 15;
	private $handleErrors   = true;
	private $cbErrorHandler = false;
	private $debug          = false;
	private $errorList      = Array();
	
	private $authData;
	private $sandbox;
	
	Function __construct(){
		$this->authData = Array('app'=>false, 'token'=>false);
	}
	
	// Autenticação:
	Function setAuthToken($email, $token=false, $sandbox=false){
		$this->authData['token'] = Array('email'=>$email, 'token'=>$token);
		$this->setSandbox($sandbox);
	}
	Function setAuthApp  ($appId, $appKey, $authCode=false, $sandbox=false){
		$this->authData['app'] = Array('appId'=>$appId, 'appKey'=>$appKey);
		if($authCode){
			$this->authData['app']['authorizationCode'] = $authCode;
		}
		$this->setSandbox($sandbox);
	}
	Function _getAuthData($authType){
		return $this->authData[$authType];
	}
	Function getSandbox(){
		return $this->sandbox;
	}
	Function setSandbox($yesno){
		$this->sandbox = $yesno;
	}
	
	// Comunicação direta:
	Function getWsBaseUrl       (){
		return "https://ws.".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br";
	}
	Function callWebservice     ($wsUrl, $postVar=false, $headers=Array(), $options=Array()){
		$options += Array(
			'requestMethod'      =>false, // false=Let CURL decies, post, get, put, ...
			'standardizeResponse'=>false, // false|basic|full
			'maxRetries'         =>false, // retry how many times if " Temporarily Unavailable" error.
		);
		$options['maxRetries'] = 1;
		
		if($this->debug){
			echo "<table border='1' cellpadding='5' style='font: 11px Consolas; border-collapse: collapse'>";
			echo "	<tr bgcolor='#00FFFF'><td colspan='2'><b>->callWebservice()</b></td></tr>";
			echo "	<tr><td>wsUrl</td><td>{$wsUrl}</td></tr>";
			echo "	<tr><td>postVar</td><td>";
			dHelper2::dump($postVar);
			echo "</td></tr>";
			echo "	<tr><td>headers</td><td>";
			dHelper2::dump($headers);
			echo "</td></tr>";
			echo "	<tr><td>options</td><td>";
			dHelper2::dump($options);
			echo "</td></tr>";
			echo "</table>";
		}
		if($wsUrl[0] == "/"){
			$wsUrl = $this->getWsBaseUrl().$wsUrl;
		}
		if($options['requestMethod']){
			// Resultado final: "POST https://pagseguro.com.br/...."
			// Isso será processado em dBrowser2.
			$wsUrl = "{$options['requestMethod']} {$wsUrl}";
		}
		
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$b->setTimeout($this->timeout);
		$b->setUserAgent(false);
		
		foreach($headers as $key=>$value){
			$b->setHeader($key, $value);
		}
		if($postVar){
			$b->setPost($postVar);
		}
		
		// Try to execute...
		do{
			// Handle timeouts
			$tryConnection = $b->go($wsUrl);
		} while(!$tryConnection && $options['maxRetries']-->0);
		if(!$tryConnection){
			$this->_addError(0x0001, "Falha na comunicação com o PagSeguro.");
			return false;
		}
		
		$status  = $b->getStatus();   // version=HTTP/1.1, status=503, string=Service Temporarily Unavailable
		$headers = $b->getHeader();
		$body    = $b->getBody();
		
		// Handle temporary errors:
		while(($status['status'] == 503) && $options['maxRetries']-->0){
			// Temporarily Unavailable (Downtime)
			$b->go($wsUrl);
			$status  = $b->getStatus();
			$headers = $b->getHeader();
			$body    = $b->getBody();
		}
		if($status['status'] == 503){
			// Temporarily Unavailable (Downtime)
			$this->_addError(0x0001, "Temporarily Unavailable");
			return false;
		}
		
		// Handle other HTTP errors:
		// --> 200=Resposta bem sucedida
		// --> 400=Falha na validção dos parâmetros, normalmente os erros vêm em XML.
		if($status['status'] != 200 && $status['status'] != 400){
			// 401 Unauthorized
			// 406 No match for accept header
			// 406 Not Acceptable
			$this->_addError(0x0001, "Falha na comunicação com o PagSeguro: ".$status['string']);
			return false;
		}
		
		if(!$options['standardizeResponse']){
			return $body;
		}
		
		return $this->standardizeResponse($headers, $body, $options['standardizeResponse']);
	}
	Function callEndpoint       ($endpoint, $inputData=Array(), $options=Array()){
		// Como padronizar endpoints:
		//   '/transactions'                    --> Correto.
		//   '/v2/transaction/1234-123456-1234' --> $useEndpoint = '/v2/transaction/{code}';
		//   '/v2/transactions?initialDate=xxx' --> $useEndpoint = '/v2/transactions' + $inputData + $options['inputMethod']='GET'
		// 
		$options += Array(
			'requestMethod'  =>'auto',     // auto|get|post|put       - Tipo de requisição
			'authMethod'     =>'auto',     // auto|get|post           - Enviar autenticação via POST ou GET?
			'authType'       =>'auto',     // auto|token|app          - Autenticar com parâmetros Token ou AppId?
			'inputFormat'    =>'auto',     // get|post|xml|json       - Como enviar o $inputData? Isso vai definir o cabeçalho "Content-Type".
			'acceptHeader'   =>'auto',     // auto|false|xml|json     - Cabeçalho Accept no formato XML ou JSON?
			'applyValidation'=>'auto',     // auto|true|false         - Aplicar validação pela classe, antes de enviar os dados ao PagSeguro?
			'permissive'     =>'auto',     // auto|false|partial|full - Modificar o input de campos não obrigatórios, de forma a sempre passar pela validação?
			'maxRetries'     =>3,          // Se o servidor do Pagseguro retornar um erro temporário, tentar novamente quantas vezes?
			'standardizeResponse'=>'full', // Retorna uma resposta padronizada
		);
		$options = array_map('strtolower', $options); // Todas as opções devem estar em letras maiúsculas.
		
		// Se o $endpoint for chamado com queryString, precisaremos separá-los (ex: /v2/transactions?initialDate=xxx)
		$_tmpQueryString = explode("?", $endpoint, 2);
		$endpoint        = $_tmpQueryString[0];
		$getParams       = Array();
		if(sizeof($_tmpQueryString) > 1){
			parse_str($_tmpQueryString[1], $getParams);
		}
		
		// Busca os parâmetros padrões para o endpoint atual
		$epSettings = $this->getEndpointSettings($endpoint, $inputData);
		// Retorno:
		//   requestMethod(post,get,put), endpoint, authType(app,token), authMethod(get,post),
		//   inputFormat(get,post,xml,json,null), acceptHeader(false|xml,json)
		if($options['requestMethod'] == 'auto') $options['requestMethod']   = $epSettings['requestMethod'];
		if($options['authMethod']    == 'auto') $options['authMethod']      = $epSettings['authMethod'];
		if($options['authType']      == 'auto') $options['authType']        = $epSettings['authType'];
		if($options['inputFormat']   == 'auto') $options['inputFormat']     = $epSettings['inputFormat'];
		if($options['acceptHeader']  == 'auto') $options['acceptHeader']    = $epSettings['acceptHeader'];
		if($options['applyValidation']=='auto') $options['applyValidation'] = true;
		if($options['permissive']    == 'auto') $options['permissive']      = $this->permissive;
		
		// Pré-validação em $options:
		if(!$this->authData[$options['authType']]){
			$this->_addError(0x0002, "Falha interna.",
				"O endpoint '{$epSettings['endpoint']}' precisa ser autenticado via '{$options['authType']}', ".
				"e esses dados de autenticação não foram configurados."
			);
			return false;
		}
		if($options['authMethod']  == 'post' && (!is_array($inputData) || $options['inputFormat'] == 'json')){
			$this->_addError(0x0002, "Falha interna.",
				"O endpoint '{$epSettings['endpoint']}' precisa ser autenticado via POST, mas ".
				"os dados em inputData não vieram como Array manipulável, ou o inputFormat está ".
				"definido como JSON, e por isso não podemos adicionar os dados de autenticação ".
				"no post dessa requisição.\r\n".
				"Altere o seu inputData para POST, ou defina options[authMethod]=GET para tentar ".
				"resolver esse problema."
			);
			return false;
		}
		if($options['inputFormat'] == 'xml'  && (!is_string($inputData) || $inputData[0] != '<')){
			$this->_addError(0x0002, "Falha interna.",
				"O endpoint '{$epSettings['endpoint']}' disse que o inputFormat seria um XML, mas ".
				"inputData veio em outro formato, ou não parece ser um XML válido."
			);
			return false;
		}
		
		// Validação e permissividade dos dados em $inputData
		if(is_string($inputData)){
			// Se foi submetido como string, mas o formato for GET ou POST,
			// vamos convertê-lo para Array(), para que possamos mesclar os dados de autenticação.
			if($options['inputFormat'] == 'get'){
				parse_str($inputData, $inputData);
			}
			elseif($options['inputFormat'] == 'post'){
				parse_str($inputData, $inputData);
			}
		}
		if($options['applyValidation'] && $epSettings['inputFormat'] !== null && is_array($inputData) && !$this->preValidate($endpoint, $inputData, $options)){
			// Se inputData for NULL, então não há dados a serem enviados.
			// True significa que passou pela validação.
			// False significa que não passou pela validação.
			// ->_addError deverá ser chamado diretamente pelo método preValidate.
			return false;
		}
		
		// Vamos procesasr o $inputData e direcioná-lo para getParams ou postParams.
		// Lembre-se que $getParams já foi declarado ali em cima e pode já ter valores.
		$postParams = Array();
		if(is_string($inputData)){
			$postParams = $inputData;
		}
		if(is_array($inputData)){
			// Se inputData for um array e o inputFormat for get, mescle com o que já existir em $getParams.
			// Se inputData for um array e o inputFormat for post, então passe para frente como array mesmo, que o curl se encarrega de converter.
			// Se inputData for um array e o inputFormat for json, converta usando json_encode.
			// Se inputData for um array e o inputFormat for xml, não poderia ser um Array. A validação lá em cima deveria ter cuidado do assunto.
			if($options['inputFormat']     == 'get'){
				$getParams += $inputData;
			}
			elseif($options['inputFormat'] == 'post'){
				$postParams = $inputData;
			}
			elseif($options['inputFormat'] == 'json'){
				$postParams = json_encode($inputData);
			}
			elseif($options['inputFormat'] == 'xml'){
				die("Fatal: Não é possível ter um inputFormat=XML com inputFormat=Array.");
			}
		}
		
		// Mesclar dados de autenticação:
		$authData = $this->_getAuthData($options['authType']); // Retornará [email] e [token], ou [appId] [appKey] etc...
		if($options['authMethod'] == 'get'){
			$getParams += $authData;
		}
		elseif($options['authMethod'] == 'post'){
			$postParams += $authData;
		}
		
		// Configura o WebService URL final:
		$wsUrl  = $this->getWsBaseUrl();
		$wsUrl .= $endpoint;
		if($getParams){
			$wsUrl .= "?".http_build_query($getParams);
		}
		
		// Configura as options a serem enviadas como parâmetro em callWebservice:
		$wsOptions = Array(
			'requestMethod'      =>$options['requestMethod'],      
			'standardizeResponse'=>$options['standardizeResponse'],
			'maxRetries'         =>$options['maxRetries'],
		);
		
		// Define os cabeçalhos a serem enviados em callWebservice:
		$headers = Array();
		if($options['inputFormat']){
			if($options['inputFormat'] == 'post'){
				$headers['Content-Type'] = "application/x-www-form-urlencoded; charset=utf8";
			}
			elseif($options['inputFormat'] == 'xml'){
				$headers['Content-Type'] = "application/xml; charset=utf8";
			}
			elseif($options['inputFormat'] == 'json'){
				$headers['Content-Type'] = "application/json; charset=utf8";
			}
		}
		if($options['acceptHeader']){
			if($options['acceptHeader'] == 'xml'){
				$headers['Accept'] = "application/vnd.pagseguro.com.br.v3+xml";
			}
			elseif($options['acceptHeader'] == 'json'){
				$headers['Accept'] = "application/vnd.pagseguro.com.br.v3+json";
			}
			else{
				$headers['Accept'] = $options['acceptHeader'];
			}
		}
		
		return $this->callWebservice($wsUrl, $postParams, $headers, $wsOptions);
	}
	Function standardizeResponse($headers, $body, $level='basic'){
		// $level: basic|full
		// - 'basic': XML/JSon to Array; Padroniza listas; Converte de latin1 p/ utf8; Processa erros
		// - 'full':  Basic + Converte datas para o padrão us-date; Adiciona campos "Str" para respostas são códigos.
		
		// Converte para UTF-8, se necessário;
		if(@stripos($headers['Content-Type'][0], "charset=iso-8850-1")){
			// Auto encode for UTF8.
			$body = utf8_encode($body);
		}
		
		// Converte o conteúdo de XML/Json para Array:
		if(substr($body, 0, 5) == "<?xml" || @stripos($headers['Content-Type'][0], "application/xml")){
			$response = @simplexml_load_string($body);
			if($response === false){
				$this->_addError(0x0002, "A resposta XML do PagSeguro veio mal formatada.", Array('headers'=>$headers, 'body'=>$body));
				return false;
			}
			
			$response = json_decode(json_encode($response), 1);
			
			// Os seguintes caminhos sempre devem ser considerados arrays múltiplos:
			// - <error[]>(...)</error>                                          --> $error[]        = (...)
			// - <items><item[]>(...)</item></items>                             --> $items[]        = (...)
			// - <transactions><transaction[]>(...)</transaction></transactions> --> $transactions[] = (...)
			if(isset($response['error'])){
				if(!isset($response['error'][0])){
					$response['error'] = Array($response['error']);
				}
			}
			if(isset($response['items'])){
				if(!isset($response['items']['item'][0])){
					$response['items'] = Array($response['items']['item']);
				}
				else{
					$response['items'] = $response['items']['item'];
				}
			}
			if(isset($response['transactions'])){
				if(!isset($response['transactions']['transaction'][0])){
					$response['transactions'] = Array($response['transactions']['transaction']);
				}
				else{
					$response['transactions'] = $response['transactions']['transaction'];
				}
			}
		}
		elseif(@stripos($headers['Content-Type'][0], "application/json")){
			$this->_addError(0x0002, "A resposta JSON ainda não foi implementada pela classe dPagSeguro2.", Array('headers'=>$headers, 'body'=>$body));
			return false;
		}
		else{
			$this->_addError(0x0002, "A resposta do PagSeguro veio num formato inesperado.", Array('headers'=>$headers, 'body'=>$body));
			return false;
		}
		
		// Fim da padronização 'basic':
		if($level != 'full'){
			return $response;
		}
		
		// Processamento automático de erros:
		if(isset($response['error'])){
			foreach($response['error'] as $errorData){
				$this->_addError($errorData['code'], $errorData['message']);
			}
			return false;
		}
		
		// Vamos padronizar datas
		$dateKeys = Array('date', 'lastEventDate', 'escrowEndDate');
		foreach($dateKeys as $dateKey){
			if(isset($response[$dateKey]) && $response[$dateKey]){
				$response[$dateKey] = date('Y-m-d H:i:s', strtotime($response[$dateKey]));
			}
		}
		
		// Vamos padronizar uma resposta com múltiplas transactions (normalmente v2Transactions ou v3Transactions)
		if(isset($response['transactions'])){
			foreach($response['transactions'] as $idx=>$item){
				if(isset($item['date'])){
					$response['transactions'][$idx]['date']          = date('Y-m-d H:i:s', strtotime($item['date']));
				}
				if(isset($item['lastEventDate'])){
					$response['transactions'][$idx]['lastEventDate'] = date('Y-m-d H:i:s', strtotime($item['lastEventDate']));
				}
				if(isset($item['paymentMethod']['type'])){
					$response['transactions'][$idx]['paymentMethod']['typeStr'] = $this->_stringTable('paymentType', $item['paymentMethod']['type']);
				}
				if(isset($item['status'])){
					$response['transactions'][$idx]['statusStr'] = $this->_stringTable('transactionStatus', $item['status']);
				}
				if(isset($item['type'])){
					$response['transactions'][$idx]['typeStr']   = $this->_stringTable('transactionType',   $item['type']);
				}
			}
		}
		
		// Vamos adicionar os campos "******Str" para os campos que já conhecemos.
		if(isset($response['paymentMethod']['type'])){
			$response['paymentMethod']['typeStr'] = $this->_stringTable('paymentType', $response['paymentMethod']['type']);
		}
		if(isset($response['paymentMethod']['code'])){
			$response['paymentMethod']['codeStr'] = $this->_stringTable('paymentCode', $response['paymentMethod']['code']);
		}
		if(isset($response['status'])){
			$response['statusStr'] = $this->_stringTable('transactionStatus', $response['status']);
		}
		if(isset($response['type'])){
			$response['typeStr'] = $this->_stringTable('transactionType', $response['type']);
		}
		if(isset($response['shipping']['type'])){
			$response['shipping']['typeStr'] = $this->_stringTable('shippingType', $response['shipping']['type']);
		}
		
		return $response;
	}
	
	Function preValidate($endpoint, &$data, &$options){
		// Este trabalho está incompleto, e deverá ser realizado/completado com o passar do tempo.
		$this->_clearErrors();
		$_this = $this;
		$permissive = $options['permissive'];
		
		$_validate = function($property, $validation) use (&$data, $permissive, $_this){
			if(!$validation){
				return true;
			}
			
			if(!is_string($validation) && is_callable($validation)){
				if(!@$data[$property]){
					return true;
				}
				return $validation($data[$property]);
			}
			
			$rules      = explode(";", $validation);
			if(!strlen(@$data[$property])){
				if(!in_array('required', $rules)){
					# echo "{$property}: Está vazio e não tem required: Considera que está tudo bem.<br />";
					return true;
				}
				
				// É obrigatório. Mas se existir alguma regra "=", a permissividade pode utilizá-la.
				if($permissive){
					foreach($rules as $rule){
						if($rule[0] == '='){
							$data[$property] = substr($rule, 1);
						}
					}
				}
				if(!strlen(@$data[$property])){
					// Se ainda assim (mesmo após a permissividade)
					$_this->_addError(0x0003, "O campo {$property} é obrigatório");
					return false;
				}
			}
			
			foreach($rules as $rule){
				if($rule == 'birth'){
					if(!$permissive && preg_match("/[^0-9\/]", $string)){
						$_this->_addError(0x0003, "o campo {$property} deve ter o formato dd/MM/yyyy");
						return false;
					}
					else{
						$string = preg_replace("/[^0-9\/]/", "", $string);
					}
					
					$parts = explode("/", $string);
					if(sizeof($parts)   != 3 || sizeof($parts[0]) > 2 || sizeof($parts[1]) > 2 || sizeof($parts[2]) > 4){
						$_this->_addError(0x0003, "o campo {$property} deve ter o formato dd/MM/yyyy");
						return false;
					}
					if($parts[0] > 31 || $parts[1] > 12 || $parts[2] < 1900 || $parts[2] > date('Y')){
						$_this->_addError(0x0003, "o campo {$property} deve ter o formato dd/MM/yyyy");
						return false;
					}
					
					if($permissive){
						$parts[0] = sprintf("%02d", $parts[0]);
						$parts[1] = sprintf("%02d", $parts[1]);
					}
					if(strlen($parts[0]) != 2 || strlen($parts[1]) != 2){
						$_this->_addError(0x0003, "o campo {$property} deve ter o formato dd/MM/yyyy");
						return false;
					}
					
					if($permissive){
						$string = implode("/", $parts);
					}
					return true;
				}
				elseif($rule == 'lower'){
					$_lower = strtolower($data[$property]);
					if($permissive){
						$data[$property] = $_lower;
					}
					elseif($data[$property] != $_lower){
						$this->_addError(0x0003, "O campo {$property} precisa ser fornecida em letras minúsculas");
					}
				}
				elseif($rule == 'upper'){
					$_upper = strtoupper($data[$property]);
					if($permissive){
						$data[$property] = $_upper;
					}
					elseif($data[$property] != $_upper){
						$_this->_addError(0x0003, "O campo {$property} precisa ser fornecida em letras maiúsculas");
					}
				}
				elseif($rule == 'numeric'){
					if(preg_match("/[^0-9]/", $data[$property])){
						if($permissive){
							$data[$property] = preg_replace("/[^0-9]/", "", $data[$property]);
						}
						else{
							$_this->_addError(0x0003, "O campo {$property} deve conter apenas números");
						}
					}
				}
				elseif($rule == 'decimal'){
					// Diferente dos demais métodos, esperamos que os valores numéricos
					// sejam recebidos como FLOAT ou INTEGER aqui, e não pré-formatados.
					// Exemplo: shipping.amount=21.5. Dessa forma, vamos alterar este valor,
					// pré-formatando para o padrão do PagSeguro (21.50).
					// 
					// Por questões de consistência, a permissividade não terá nenhum efeito aqui.
					if(!is_numeric($data[$property])){
						$_this->_addError(0x0003, "O campo {$property} deve ser um valor numérico ou decimal");
					}
					else{
						$data[$property] = number_format($data[$property], 2, '.', '');
					}
				}
				elseif($rule == '>0'){
					if(is_numeric($data[$property]) && !($data[$property] > 0)){
						$_this->_addError(0x0003, "O campo {$property} deve ser maior do que ZERO.");
					}
				}
				elseif($rule == '>=0'){
					if(is_numeric($data[$property]) && !($data[$property] >= 0)){
						$_this->_addError(0x0003, "O campo {$property} deve ser maior ou igual a ZERO.");
					}
				}
				elseif($rule == 'integer'){
					$_int = intval($data[$property]);
					if($data[$property] != $_int){
						if($permissive){
							$data[$property] = $_int;
						}
						else{
							$_this->_addError(0x0003, "O campo {$property} deve ser um valor inteiro positivo");
						}
					}
				}
				elseif($rule[0] == '=') {
					$_equalTo = substr($rule, 1);
					if($permissive){
						$data[$property] = $_equalTo;
					}
					elseif($data[$property] != $_equalTo){
						$_this->_addError(0x0003, "O campo {$property} deve ser '{$_equalTo}'");
					}
				}
				elseif(substr($rule, 0, 4) == 'max='){
					$_len = substr($rule, 4);
					if(strlen($data[$property]) > $_len){
						if($permissive){
							$data[$property] = substr($data[$property], 0, $_len);
						}
						else{
							$_this->_addError(0x0003, "O campo {$property} não pode ter mais de {$_len} caracteres.");
						}
					}
				}
				elseif(substr($rule, 0, 4) == 'min='){
					$_len = substr($rule, 4);
					if(strlen($data[$property]) < $_len){
						$_this->_addError(0x0003, "O campo {$property} não pode ter menos de {$_len} caracteres.");
					}
				}
				elseif(substr($rule, 0, 4) == 'len='){
					$_len = substr($rule, 4);
					if(strlen($data[$property]) != $_len){
						if($permissive){
							unset($data[$property]);
						}
						else{
							$_this->_addError(0x0003, "O campo {$property} deve ter exatamente {$_len} caracteres.");
						}
					}
				}
				elseif(substr($rule, 0, 5) == 'enum='){
					$options = explode(",", substr($rule, 5));
					if(!in_array($data[$property], $options)){
						$_this->_addError(0x0003, "O campo {$property} deve uma das opções dentre ".implode(",", $options).".");
					}
				}
				elseif($rule == 'shippingType'){
					if(!$string){
						return true;
					}
					
					if($string == '1' || $string == '2' || $string == '3'){
						return true;
					}
					if(!$permissive){
						$this->_addError(0x0003, "O campo shipping.type deve ter o valor 1, 2 ou 3");
						return false;
					}
					
					$string = strtolower($string);
					if($string == 'pac'){
						$string = '1';
					}
					elseif($string == 'sedex'){
						$string = '2';
					}
					else{
						$string = '3';
					}
					
					return true;
				}
				elseif($rule == 'required'){
					// Ignorar, já foi trabalhado anteriormente.
				}
				else{
					echo "Can't validate '{$property}' as '{$rule}'.<br />\r\n";
				}
			}
		};
		
		if( $endpoint == '/v2/checkout'){ // $checkout
			$_validate('currency',        'required;=BRL');
			$_validate('reference',       'max=200');
			$_validate('notificationURL', 'max=255');
			$_validate('redirectURL',     'max=255');
			$_validate('timeout',         'integer');
			$_validate('maxAge',          'integer');
			$_validate('maxUses',         'integer');
			$_validate('enableRecovery',  'enum=true,false');
			$_validate('receiverEmail',   '');
			$x = 1; do{ // items
				$_validate('itemId'.$x,           'required;max=100');
				$_validate('itemDescription'.$x,  'required;max=100');
				$_validate('itemAmount'.$x,       'required;decimal;>0');
				$_validate('itemQuantity'.$x,     'required;integer');
				$_validate('itemWeight'.$x,       'decimal;>=0');
				$_validate('itemShippingCost'.$x, 'decimal;>=0');
				$x++;
			} while(isset($data['itemId'.$x]));
			
			$_validate('senderName',      'max=50');
			$_validate('senderEmail',     'max=50');
			$_validate('senderAreaCode',  'numeric;len=2');
			$_validate('senderPhone',     'numeric;min=7;max=9');
			$_validate('senderCPF',       'numeric;len=11');
			$_validate('senderCNPJ',      'numeric');
			$_validate('extraAmount',     'decimal'); // IMPORTANTE! extraAmount pode ser <0, 0 ou >0
			$_validate('shippingType',    'shippingType');
			$_validate('shippingCost',    'decimal;>=0');
			$_validate('shippingAddressStreet',     'max=80');
			$_validate('shippingAddressNumber',     'max=20');
			$_validate('shippingAddressComplement', 'max=40');
			$_validate('shippingAddressDistrict',   'max=60');
			$_validate('shippingAddressCity',       'max=60');
			$_validate('shippingAddressState',      'upper;len=2');
			$_validate('shippingAddressCountry',    '=BRA');
			$_validate('shippingAddressPostalCode', 'numeric;len=8');
			$_validate('shippingAddressRequired',   'enum=null,true,false');
			
			if( @$data['senderName'] && strpos($data['senderName'], ' ') == false){
				// SenderName não ter espaço é crítico para o Pagseguro.
				// No entanto, não enviar um senderName é bem aceito.
				if($permissive){
					unset($data['senderName']);
				}
				else{
					$this->_addError(0x0003, "O campo senderName deve conter no mínimo duas palavras (nome e sobrenome)");
				}
			}
			if( @$data['senderCPF'] &&  @$data['senderCNPJ']){
				$this->_addError(0x0003, "Você não pode informar senderCPF e senderCNPJ na mesma requisição");
				return false;
			}
		}
		if( $endpoint == '/transactions'){
			if($permissive){
				array_walk($data, 'trim');
			}
			
			$_validate('payment.mode',    'required;=default');
			$_validate('payment.method',  'required;enum=creditCard,boleto,eft');
			$_validate('currency',        'required;=BRL');
			$_validate('notificationURL', 'max=255');
			$_validate('reference',       'max=200');
			$_validate('sender.name',     'required;max=50');
			$_validate('sender.email',    'required;max=50');
			$_validate('sender.bornDate', 'birth');
			$_validate('sender.areaCode', 'required;numeric;len=2');
			$_validate('sender.phone',    'required;numeric;min=7;max=9');
			$_validate('sender.CPF',      'numeric;len=11');
			$_validate('sender.CNPJ',     'numeric');
			$_validate('sender.hash',     'required');
			$x = 1; do{ // items[]
				$_validate('item['.$x.'].id',          'required;max=100');
				$_validate('item['.$x.'].description', 'required;max=100');
				$_validate('item['.$x.'].amount',      'required;decimal;>0'); // IMPORTANTE! Amount precisa ser >0.
				$_validate('item['.$x.'].quantity',    'required;integer');
				$x++;
			} while(isset($data['item['.$x.'].id']));
			$_validate('extraAmount',     'decimal'); // IMPORTANTE! extraAmount pode ser <0, 0 ou >0
			
			$_validate('shipping.type',   'shippingType');
			$_validate('shipping.cost',   'decimal;>=0');
			$_validate('shipping.address.street',     'max=80');
			$_validate('shipping.address.number',     'max=20');
			$_validate('shipping.address.complement', 'max=40');
			$_validate('shipping.address.district',   'max=60');
			$_validate('shipping.address.city',       'max=60');
			$_validate('shipping.address.state',      'upper;len=2');
			$_validate('shipping.address.country',    '=BRA');
			$_validate('shipping.address.postalCode', 'numeric;len=8');
			$_validate('shipping.address.required', 'enum=null,true,false');
			
			if( @$data['sender.CPF'] &&  @$data['sender.CNPJ']){
				$this->_addError(0x0003, "Você não pode informar sender.CPF e sender.CNPJ na mesma requisição");
				return false;
			}
			if(!@$data['sender.CPF'] && !@$data['sender.CNPJ']){
				$this->_addError(0x0003, "Você precisa informar sender.CPF ou sender.CNPJ");
				return false;
			}
			if(@$data['payment.method'] == 'creditCard'){
				// Esses campos são obrigatórios apenas para cartões de crédito nacionais.
				// Ou, se for pessoa jurídica, já que não teremos o CPF do cartão de crédito.
				
				$_validate('creditCard.token',          'required');
				$_validate('installment.quantity',      'required;integer');
				$_validate('installment.quantity',      function(&$string) use ($_this, $permissive){
					if($string >= 1 && $string <= 18){
						return true;
					}
					if($permissive){
						$string = ($string<1)?1:18;
						return true;
					}
					$_this->_addError(0x0003, "Quantidade de parcelas (installment.quantity) deveria estar entre 1 e 18");
					return false;
				});
				$_validate('installment.value',         'required;decimal;>=0');
				$_validate('installment.noInterestInstallmentQuantity', 'integer');
				
				// Billing Address:
				$_validate('billingAddress.street',     'max=80');
				$_validate('billingAddress.number',     'max=20');
				$_validate('billingAddress.complement', 'max=40');
				$_validate('billingAddress.district',   'max=60');
				$_validate('billingAddress.city',       'max=60');
				$_validate('billingAddress.state',      'upper;len=2');
				$_validate('billingAddress.country',    '=BRA');
				$_validate('billingAddress.postalCode', 'numeric;len=8');
				
				// Holder Name:
				$_validate('creditCard.holder.name',      'max=50');
				$_validate('creditCard.holder.birthDate', 'birth');
				$_validate('creditCard.holder.areaCode',  'numeric;len=2');
				$_validate('creditCard.holder.phone',     'numeric;min=7,max=9');
				$_validate('creditCard.holder.CPF',       'numeric;len=11');
			}
			if(@$data['payment.method'] == 'eft'){
				$_validate('bank.name', 'required;lower;enum=bradesco,itau,bancodobrasil,banrisul,hsbc');
			}
			
			// Split payment:
			if(@$data['primaryReceiver.publicKey']){
				$_validate('primaryReceiver.publicKey',  'required;max=40');
				$x = 1; while(isset($data['receiver['.$x.'].publicKey'])){
					$_validate('receiver['.$x.'].publicKey',   'required;max=40');
					$_validate('receiver['.$x.'].amount',      'required;decimal;>=0');
					$_validate('receiver['.$x.'].ratePercent', 'decimal;>=0');
					$_validate('receiver['.$x.'].feePercent',  'decimal;>=0');
					$x++;
				}
			}
		}
		if( $endpoint == '/v2/transactions' && $options['requestMethod'] == 'post'){
			if($permissive){
				array_walk($data, 'trim');
			}
			
			$_validate('paymentMode',    'required;=default');
			$_validate('paymentMethod',  'required;enum=creditCard,boleto,eft');
			$_validate('currency',        'required;=BRL');
			$_validate('notificationURL', 'max=255');
			$_validate('reference',       'max=200');
			$_validate('senderName',     'required;max=50');
			$_validate('senderEmail',    'required;max=50');
			$_validate('senderBornDate', 'birth');
			$_validate('senderAreaCode', 'required;numeric;len=2');
			$_validate('senderPhone',    'required;numeric;min=7;max=9');
			$_validate('senderCPF',      'numeric;len=11');
			$_validate('senderCNPJ',     'numeric');
			$_validate('senderHash',     'required');
			$x = 1; do{ // items
				$_validate('itemId'.$x,           'required;max=100');
				$_validate('itemDescription'.$x,  'required;max=100');
				$_validate('itemAmount'.$x,       'required;decimal;>0');
				$_validate('itemQuantity'.$x,     'required;integer');
				$x++;
			} while(isset($data['itemId'.$x]));
			$_validate('extraAmount',     'decimal'); // IMPORTANTE! extraAmount pode ser <0, 0 ou >0
			
			$_validate('shippingType',   'shippingType');
			$_validate('shippingCost',   'decimal;>=0');
			$_validate('shippingAddressStreet',     'max=80');
			$_validate('shippingAddressNumber',     'max=20');
			$_validate('shippingAddressComplement', 'max=40');
			$_validate('shippingAddressDistrict',   'max=60');
			$_validate('shippingAddressCity',       'max=60');
			$_validate('shippingAddressState',      'upper;len=2');
			$_validate('shippingAddressCountry',    '=BRA');
			$_validate('shippingAddressPostalCode', 'numeric;len=8');
			$_validate('shippingAddressRequired',   'enum=null,true,false');
			
			if( @$data['senderCPF'] &&  @$data['senderCNPJ']){
				$this->_addError(0x0003, "Você não pode informar senderCPF e senderCNPJ na mesma requisição");
				return false;
			}
			if(!@$data['senderCPF'] && !@$data['senderCNPJ']){
				$this->_addError(0x0003, "Você precisa informar senderCPF ou senderCNPJ");
				return false;
			}
			if(@$data['paymentMethod'] == 'creditCard'){
				// Esses campos são obrigatórios apenas para cartões de crédito nacionais.
				// Ou, se for pessoa jurídica, já que não teremos o CPF do cartão de crédito.
				
				$_validate('creditCardToken',          'required');
				$_validate('installmentQuantity',      'required;integer');
				$_validate('installmentQuantity',      function(&$string) use ($_this, $permissive){
					if($string >= 1 && $string <= 18){
						return true;
					}
					if($permissive){
						$string = ($string<1)?1:18;
						return true;
					}
					
					$_this->_addError(0x0003, "Quantidade de parcelas (installmentQuantity) deveria estar entre 1 e 18");
					return false;
				});
				$_validate('installmentValue',        'required;decimal;>=0');
				$_validate('noInterestInstallmentQuantity', 'integer');
				
				// Billing Address:
				$_validate('billingAddressStreet',     'max=80');
				$_validate('billingAddressNumber',     'max=20');
				$_validate('billingAddressComplement', 'max=40');
				$_validate('billingAddressDistrict',   'max=60');
				$_validate('billingAddressCity',       'max=60');
				$_validate('billingAddressState',      'upper;len=2');
				$_validate('billingAddressCountry',    '=BRA');
				$_validate('billingAddressPostalCode', 'numeric;len=8');
				
				// Holder Name:
				$_validate('creditCardHolderName',      'max=50');
				$_validate('creditCardHolderBirthDate', 'birth');
				$_validate('creditCardHolderAreaCode',  'numeric;len=2');
				$_validate('creditCardHolderPhone',     'numeric;min=7,max=9');
				$_validate('creditCardHolderCPF',       'numeric;len=11');
			}
			if(@$data['paymentMethod'] == 'eft'){
				$_validate('bankName', 'required;lower;enum=bradesco,itau,bancodobrasil,banrisul,hsbc');
			}
			
			// Split payment:
			if(@$data['primaryReceiver.publicKey']){
				$this->_addError(0x0003, "Erro interno",
					"Para utilizar o Split Payment você precisa utilizar o endpoint /transaction ao invés do /v2/transaction."
				);
				return false;
			}
		}
		if(($endpoint == '/v2/transactions' && $options['requestMethod'] == 'get') || $endpoint == '/v3/transactions'  || $endpoint == '/v2/transactions/abandoned' || $endpoint == '/v3/transactions/abandoned'){
			// Busca por transações.
			// O parâmetro "reference" OU "initialDate" são obrigatórios.
			if(isset($data['reference'])){
				// Se houver reference já passou pela validação.
				return true;
			}
			
			// Se não houver 'reference', o único parâmetro obrigatório será o initialDate.
			// Se veio sem ele, recupere todas as transações de 29 dias até hoje.
			if(!isset($data['initialDate'])){
				if($permissive){
					$data['initialDate'] = date('Y-m-d 00:00', strtotime("-29 days"));
				}
				else{
					$this->_addError(0x0003, "O campo initialDate é obrigatório");
				}
			}
			
			if($permissive){
				// Se veio em formato brasileiro, converta para us-date.
				// Ex: 30/01/2017 --> 2017-01-30
				$data['initialDate'] = preg_replace("/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/", "\\3-\\2-\\1", $data['initialDate']);
				
				// Se veio em timestamp, converta para us-date.
				if(is_numeric($data['initialDate'])){
					$data['initialDate'] = date('Y-m-d', $data['initialDate']);
				}
				
				// Se veio sem horário, insira um horário.
				if(strlen($data['initialDate']) <= 10){
					$data['initialDate'] .= " 00:00";
				}
				
				// Se o horário veio com segundos, remova-os.
				if(strlen($data['initialDate']) > 16){
					$data['initialDate'] = substr($data['initialDate'], 0, 16);
				}
				
				// Só lembrando que o separador de data-hora deve ser "T", ou invés de espaço (" ").
				$data['initialDate'] = str_replace(" ", "T", $data['initialDate']);
				
				if(isset($data['finalDate'])){
					$data['finalDate']   = preg_replace("/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/", "\\3-\\2-\\1", $data['finalDate']);
					if(is_numeric($data['finalDate'])){
						$data['finalDate'] = date('Y-m-d', $data['initialDate']);
					}
					if(strlen($data['finalDate'])   <= 10){
						$data['finalDate'] .= " 23:59";
					}
					if(strlen($data['finalDate'])   > 16){
						$data['finalDate'] = substr($data['finalDate'], 0, 16);
					}
					
					// A data final não pode ser maior do que a atual.
					$_curDate = date('Y-m-d H:i');
					if($data['finalDate'] > $_curDate){
						$data['finalDate'] = $_curDate;
					}
					
					$data['finalDate']   = str_replace(" ", "T", $data['finalDate']);
				}
			}
		}
		
		if($this->errorList){
			// Erros ocorreram, a validação falhou.
			return false;
		}
		
		// Se chegou até aqui, é porque não sabemos como validar os parâmetros deste endpoint.
		// Se não sabe, mande para o PagSeguro fazer a validação.
		return true;
	}
	Function getEndpointSettings($endpoint, &$inputData){
		// Esta função tem como objetivo retornar as configurações adequadas
		// para todos os endpoints conhecidos. Essas configurações podem ser
		// sobrescritas pelas $options em ->callEndpoint, se necessário.
		// 
		// Alguns endpoints possuem variações em suas configurações, dependendo do
		// método ser GET ou POST. São eles: '/v2/transactions' e '/pre-approvals'.
		// 
		// Nesses casos, o parâmetro $inputData decidirá qual é o método adequado
		// para os parâmetros fornecidos, e retornará o conteúdo correspondente.
		// 
		$useMethod = false;
		if($endpoint == '/v2/transactions'){
			// Se $inputData for uma string ou um array contendo "initialDate", então
			// sabemos que estamos lidando com o parâmetro GET (listar transações).
			// Se nenhum parâmetro for fornecido em $inputData, vamos assumir que é GET.
			// 
			// Caso contrário, estamos iniciando uma transação, lidando com o parâmetro POST.
			// 
			$useMethod = (!$inputData || is_string($inputData) || isset($inputData['initialDate']))?'GET':'POST';
		}
		if($endpoint == '/pre-approvals'){
			die("getEndpointSettings: A classe ainda não foi instruída sobre como detectar o método correto para /pre-approvals.");
		}
		
		
		// Como montar e manter essa tabela de configurações sempre atualizada?
		// 1. Acessa a planilha em https://docs.google.com/spreadsheets/d/1uumyhLFXL9zVOceAiz-kDSbxIkZN3WnV4YjJeKhrJZc
		// 2. Abra um script em branco, execute o comando abaixo e cole o resultado na sequencia:
		//      dPagSeguro::convertEndpointTable('(cole o conteúdo da planilha aqui)')
		
		if($endpoint == '/v2/checkout'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/v2/checkout',
				'authType' => 'token',
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v2/checkouts-qrcode'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/v2/checkouts-qrcode',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'get',
				'inputFormat' => 'xml',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v2/sessions'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/v2/sessions',
				'authType' => 'token',
				'authMethod' => 'post',
				'inputFormat' => NULL,
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/sessions'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/sessions',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => NULL,
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/v2/transactions' && $useMethod == 'POST'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/v2/transactions',
				'authType' => 'token',
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/transactions'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/transactions',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/v2\/transactions\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v2/transactions/{$code}',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => NULL,
				'acceptHeader' => false,
			);
		}
		elseif(preg_match('/^\/v3\/transactions\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v3/transactions/{$code}',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => NULL,
				'acceptHeader' => false,
			);
		}
		elseif(preg_match('/^\/v2\/transactions\/notifications\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v2/transactions/notifications/{$code}',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => NULL,
				'acceptHeader' => false,
			);
		}
		elseif(preg_match('/^\/v3\/transactions\/notifications\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v3/transactions/notifications/{$code}',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => NULL,
				'acceptHeader' => false,
			);
		}
		elseif(preg_match('/^\/payment-request\/notifications\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/payment-request/notifications/{$code}',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/v2/transactions' && $useMethod == 'GET'){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v2/transactions',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => 'get',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v3/transactions'){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v3/transactions',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => 'get',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v2/transactions/abandoned'){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v2/transactions/abandoned',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => 'get',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v2/authorizations/request'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/v2/authorizations/request',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v2/authorizations'){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/v2/authorizations',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'get',
				'inputFormat' => 'get',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/v2/authorizations'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/v2/authorizations',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => false,
			);
		}
		elseif($endpoint == '/sessions'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/sessions',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => NULL,
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/pre-approvals' && $useMethod == 'GET'){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/pre-approvals',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => 'get',
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/pre-approvals' && $useMethod == 'POST'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/pre-approvals',
				'authType' => 'token',
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/pre-approvals/request'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/pre-approvals/request',
				'authType' => 'token',
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/pre-approvals/payment'){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/pre-approvals/payment',
				'authType' => 'token',
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif($endpoint == '/pre-approvals/notifications/'){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/pre-approvals/notifications/',
				'authType' => 'token',
				'authMethod' => 'get',
				'inputFormat' => 'get',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/notifications\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/pre-approvals/notifications/{$code}',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/pre-approvals/{$code}',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+\/payment-orders$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'get',
				'endpoint' => '/pre-approvals/{$code}/payment-orders',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+\/status$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'put',
				'endpoint' => '/pre-approvals/{$code}/status',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+\/cancel$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'put',
				'endpoint' => '/pre-approvals/{$code}/cancel',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+\/discount$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'put',
				'endpoint' => '/pre-approvals/{$code}/discount',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+\/payment-method$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'put',
				'endpoint' => '/pre-approvals/{$code}/payment-method',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		elseif(preg_match('/^\/pre-approvals\/[0-9a-z\-\_\.]+\/payment-orders\/[0-9a-z\-\_\.]+\/payment$/i', $endpoint)){
			$settings = Array(
				'requestMethod' => 'post',
				'endpoint' => '/pre-approvals/{$code}/payment-orders/{$paymentOrderCode}/payment',
				'authType' => ($this->authData['app']?'app':'token'),
				'authMethod' => 'post',
				'inputFormat' => 'post',
				'acceptHeader' => 'xml',
			);
		}
		
		return $settings;
	}
	
	Function setPermissive($yesno){
		$this->permissive = $yesno;
	}
	Function setDebug     ($yesno){
		$this->debug = $yesno;
	}
	
	Function _stringTable($table, $value){
		if($table == 'paymentType'){
			if($value == 1) return "Cartão de crédito";
			if($value == 2) return "Boleto";
			if($value == 3) return "Débito Online";
			if($value == 4) return "Saldo PagSeguro";
			if($value == 5) return "Oi Paggo";
			if($value == 6) return "Depósito em conta";
		}
		if($table == 'paymentCode'){
			if($value == 101) return "Cartão de crédito Visa";
			if($value == 102) return "Cartão de crédito MasterCard";
			if($value == 103) return "Cartão de crédito American Express";
			if($value == 104) return "Cartão de crédito Diners";
			if($value == 105) return "Cartão de crédito Hipercard";
			if($value == 106) return "Cartão de crédito Aura";
			if($value == 107) return "Cartão de crédito Elo";
			if($value == 108) return "Cartão de crédito PLENOCard";
			if($value == 109) return "Cartão de crédito PersonalCard";
			if($value == 110) return "Cartão de crédito JCB";
			if($value == 111) return "Cartão de crédito Discover";
			if($value == 112) return "Cartão de crédito BrasilCard";
			if($value == 113) return "Cartão de crédito FORTBRASIL";
			if($value == 114) return "Cartão de crédito CARDBAN";
			if($value == 115) return "Cartão de crédito VALECARD";
			if($value == 116) return "Cartão de crédito Cabal";
			if($value == 117) return "Cartão de crédito Mais!";
			if($value == 118) return "Cartão de crédito Avista";
			if($value == 119) return "Cartão de crédito GRANDCARD";
			if($value == 201) return "Boleto Bradesco";
			if($value == 202) return "Boleto Santander";
			if($value == 301) return "Débito online Bradesco";
			if($value == 302) return "Débito online Itaú";
			if($value == 303) return "Débito online Unibanco";
			if($value == 304) return "Débito online Banco do Brasil";
			if($value == 305) return "Débito online Banco Real";
			if($value == 306) return "Débito online Banrisul";
			if($value == 307) return "Débito online HSBC";
			if($value == 401) return "Saldo PagSeguro";
			if($value == 501) return "Oi Paggo";
			if($value == 701) return "Depósito em conta - Banco do Brasil";
		}
		if($table == 'transactionStatus'){
			if($value ==  0) return "Iniciado";
			if($value ==  1) return "Aguardando pagamento";  // WAITING_PAYMENT
			if($value ==  2) return "Em análise";            // IN_REVIEW
			if($value ==  3) return "Paga";                  // APPROVED
			if($value ==  4) return "Disponível";            // AVAILABLE
			if($value ==  5) return "Em disputa";            // IN_DISPUTE
			if($value ==  6) return "Devolvida";             // RETURNED
			if($value ==  7) return "Cancelada";             // CANCELLED
			if($value ==  8) return "Debitado";              // SELLER_CHARGEBACK
			if($value ==  9) return "Retenção temporária";   // CONTESTATION
			if($value == 10) return "Processando devolução"; // PROCESSING_REFUND
			if($value == 11) return "Aguardando captura";    // PRE_AUTHORIZED
		}
		if($table == 'transactionType'){
			if($value ==  1) return "CHECKOUT";         // Checkout simples ou transparente
			if($value ==  2) return "TRANSFER";         // Transferência
			if($value ==  3) return "FUNDS_ADDITION";   // Adição de fundos
			if($value ==  4) return "WITHDRAW";           
			if($value ==  5) return "CHARGING";           
			if($value ==  6) return "DONATION";           
			if($value ==  7) return "BONUS";           
			if($value ==  8) return "BONUS_REPASS";           
			if($value ==  9) return "OPERATIONAL";           
			if($value == 10) return "POLITICAL_DONATION";           
			if($value == 11) return "PRE-APPROVAL";     // Recorrência
			if($value == 12) return "SECONDARY";
			if($value == 13) return "VALIDATOR";
		}
		if($table == 'shippingType'){
			if($value == 1)  return "PAC";
			if($value == 2)  return "Sedex";
			if($value == 3)  return "Outro";
		}
		
		return "Desconhecido({$table}:{$value})";
	}
	
	// Facilitadores (métodos totalmente opcionais, feitos para agilizar a sua vida).
	Function newPagamento   ($inputData, $items, $options=Array()){
		// inputData:
		//     https://docs.google.com/spreadsheets/d/1uumyhLFXL9zVOceAiz-kDSbxIkZN3WnV4YjJeKhrJZc
		//     Se options[inputAs]='auto',
		// items:     
		//     [id], [description], [amount], [quantity]
		//     Se options[endpoint] for /v2/checkout, +opcionais: [weight], [shippingCost]
		// options:
		//     inputAs:  auto|v2|v3. * Padrão: auto
		//     endpoint: 'v2/checkout' | '/v2/transactions' | '/transactions'. Padrão: 'v2/checkout'
		// 
		// Tipos de retorno:
		//    Se o endpoint for /v2/checkout, o retorno conterá [date] e [code].
		//    Se o endpoint for /transactions ou /v2/transactions, o retorno será uma $transactionV2 ou $transactionV3.
		// 
		
		$options += Array(
			'inputAs' =>'auto',
			'endpoint'=>'/v2/transactions',
		);
		
		if(!in_array($options['endpoint'], Array('/v2/checkout', '/v2/transactions', '/transactions'))){
			$this->_addError(0x0002, "Endpoint inválido para newPagamento");
			return false;
		}
		
		{ // $v2v3Table[] = [v2, v3]
			$v2v3Table = Array(
				Array('paymentMode',     'payment.mode'),
				Array('paymentMethod',   'payment.method'),
				Array('currency',        'currency'),
				Array('reference',       'reference'),
				Array('notificationURL', 'notificationURL'),
				Array('redirectURL',     ''),
				Array('timeout',         ''),
				Array('maxAge',          ''),
				Array('maxUses',         ''),
				Array('enableRecovery',  ''),
				Array('receiverEmail',   ''),
				Array('extraAmount',     'extraAmount'),
				Array('senderName',      'sender.name'),
				Array('senderEmail',     'sender.email'),
				Array('senderBornDate',  'sender.bornDate'),
				Array('senderHash',      'sender.hash'),
				Array('senderIp',        'sender.ip'),
				Array('senderAreaCode',  'sender.areaCode'),
				Array('senderPhone',     'sender.phone'),
				Array('senderCPF',       'sender.CPF'),
				Array('senderCNPJ',      'sender.CNPJ'),
				Array('shippingAddressRequired', 'shipping.address.required'),
				Array('shippingType',    'shipping.type'),
				Array('shippingCost',    'shipping .cost'),
				Array('shippingAddressStreet',     'shipping.address.street'),
				Array('shippingAddressNumber',     'shipping.address.number'),
				Array('shippingAddressComplement', 'shipping.address.complement'),
				Array('shippingAddressDistrict',   'shipping.address.district'),
				Array('shippingAddressCity',       'shipping.address.city'),
				Array('shippingAddressState',      'shipping.address.state'),
				Array('shippingAddressCountry',    'shipping.address.country'),
				Array('shippingAddressPostalCode', 'shipping.address.postalCode'),
				Array('dynamicPaymentMethodMessageBoleto',     'dynamicPaymentMethodMessage.boleto'),
				Array('dynamicPaymentMethodMessageCreditCard', 'dynamicPaymentMethodMessage.creditCard'),
				Array('bankName',        'bank.name'),
				Array('creditCardToken',           'creditCard.token'),
				Array('creditCardHolderName',      'creditCard.holder.name'),
				Array('creditCardHolderBirthDate', 'creditCard.holder.birthDate'),
				Array('creditCardHolderAreaCode',  'creditCard.holder.areaCode'),
				Array('creditCardHolderPhone',     'creditCard.holder.phone'),
				Array('creditCardHolderCPF',       'creditCard.holder.CPF'),
				Array('billingAddressStreet',      'billingAddress.street'),
				Array('billingAddressNumber',      'billingAddress.number'),
				Array('billingAddressComplement',  'billingAddress.complement'),
				Array('billingAddressDistrict',    'billingAddress.district'),
				Array('billingAddressCity',        'billingAddress.city'),
				Array('billingAddressState',       'billingAddress.state'),
				Array('billingAddressPostalCode',  'billingAddress.postalCode'),
				Array('billingAddressCountry',     'billingAddress.country'),
				Array('installmentQuantity',       'installment.quantity'),
				Array('installmentValue',          'installment.value'),
				Array('noInterestInstallmentQuantity', 'installment.noInterestInstallmentQuantity'),
				
				// Os itens abaixo não possuem equivalencia, então serão ignorados.
				# Array('', 'primaryReceiver.email'),
				# Array('', 'primaryReceiver.publicKey'),
				# Array('', 'receiver[$i].publicKey'),
				# Array('', 'receiver[$i].split.amount'),
				# Array('', 'receiver[$i].split.ratePercent'),
				# Array('', 'receiver[$i].split.feePercent'),
				# Array('paymentMethodGroup$i', ' '),
				# Array('paymentMethodConfigKey$i_1', ' '),
				# Array('paymentMethodConfigValue$i_1', ' '),
			);
			for($i = 1; $i <= 10; $i++){
				$v2v3Table[] = Array("itemId{$i}", "item[{$i}].id");
				$v2v3Table[] = Array("itemDescription{$i}", "item[{$i}].description");
				$v2v3Table[] = Array("itemAmount{$i}", "item[{$i}].amount");
				$v2v3Table[] = Array("itemQuantity{$i}", "item[{$i}].quantity");
			}
		}
		
		$finalData = Array();
		$useVidx   = ($options['endpoint']=='/transactions')?1:0; //0=v2, 1=v3
		$inputAsV2 = ($options['inputAs']=='v2' || $options['inputAs'] == 'auto');
		$inputAsV3 = ($options['inputAs']=='v3' || $options['inputAs'] == 'auto');
		
		foreach($v2v3Table as $keys){
			$v2key   = $keys[0];
			$v3key   = $keys[1];
			if($inputAsV3 && isset($inputData[$v3key])){
				$_useKey = $v3key;
				$_useVal = $inputData[$v3key];
			}
			elseif($inputAsV2 && isset($inputData[$v2key])){
				$_useKey = $v2key;
				$_useVal = $inputData[$v2key];
			}
			else{
				// Esta $key não consta nos dados do cliente.
				continue;
			}
			
			if(!$keys[$useVidx]){
				$this->_addError(0x0002, "Erro interno", "Não é possível converter '{$_useKey}' para o padrão utilizado em {$options['endpoint']}.");
				return false;
			}
			
			// Mover o valor de inputData para $finalData.
			$finalData[$keys[$useVidx]] = $_useVal;
			unset($inputData[$_useKey]);
		}
		if($items) foreach($items as $idx=>$item){
			# Array('itemId$i', 'item[$i].id'),
			# Array('itemDescription$i', 'item[$i].description'),
			# Array('itemAmount$i', 'item[$i].amount'),
			# Array('itemQuantity$i', 'item[$i].quantity'),
			# Array('itemWeight$i', ''),
			# Array('itemShippingCost$i', ''),
			$i = ($idx+1);
			$finalData[$useVidx==0?"itemId{$i}":"item[{$i}].id"]             = @$item['id'];
			$finalData[$useVidx==0?"itemDescription{$i}":"item[{$i}].description"] = @$item['description'];
			$finalData[$useVidx==0?"itemAmount{$i}":"item[{$i}].amount"]     = @$item['amount'];
			$finalData[$useVidx==0?"itemQuantity{$i}":"item[{$i}].quantity"] = @$item['quantity'];
			if($useVidx==0){
				if(isset($item['weight'])){
					$finalData["itemId{$i}Weight"]       = $item['weight'];
				}
				if(isset($item['shippingCost'])){
					$finalData["itemId{$i}ShippingCost"] = $item['shippingCost'];
				}
			}
		}
		
		if($this->debug){
			if($inputData){
				echo "<b>dPagSeguro2:</b> Chegamos ao final de newPagamento e não utilizamos todas as informações em inputData.<br />";
				echo "Informações que sobraram:<br />";
				dHelper2::dump($inputData);
				echo "<hr />";
			}
		}
		
		return $this->callEndpoint($options['endpoint'], $finalData);
	}
	Function v2Checkout     ($inputData, $returnAs=false){
		$ret = $this->newPagamento($inputData, false, Array('endpoint'=>'/v2/checkout'));
		if(!$ret){
			return false;
		}
		
		if($returnAs == 'code'){
			return $ret['code'];
		}
		elseif($returnAs == 'checkoutURL'){
			return "https://".($this->sandbox?"sandbox.":"")."pagseguro.uol.com.br/v2/checkout/payment.html?code=".$ret['code'];
		}
		return $ret;
	}
	Function getNotification($notificType, $notificCode, $apiVersion='v2'){
		if($notificType == 'transaction'){
			return $this->callEndpoint("/{$apiVersion}/transactions/notifications/{$notificCode}");
		}
		if($notificType == 'payment-request'){
			// To-do: Descobrir qual a apiVersion retornada, e impedir que este método seja chamado pela versão errada.
			return $this->callEndpoint("/payment-request/notifications/{$notificCode}");
		}
		
		$this->_addError(0x0002, "Erro interno", "Não sei como lidar com notificações type={$notificType}.");
		return false;
	}
	Function getTransaction ($code, $apiVersion='v2'){
		return $this->callEndpoint("/{$apiVersion}/transactions/{$code}");
	}
	
	
	// $ps->v2Checkout($params, $isLightbox?'code':'checkoutURL');
	
	// Error handling:
	Function listErrors  ($onlyStrings=false){
		if($onlyStrings){
			$return = Array();
			foreach($this->errorList as $errorItem){
				$return[] = $errorItem['message'];
			}
			return $return;
		}
		return $this->errorList;
	}
	Function _clearErrors(){
		$this->errorList = Array();
	}
	Function _addError   ($errorCode, $errorStr, $debugMessage=false){
		if($this->debug){
			echo "<div style='background: #CCC; border: 1px solid #888; padding: 8px; margin-bottom: 8px; font: 11px Consolas'>";
			echo "<b>dPagSeguro2->_addError(code={$errorCode}, str={$errorStr}, ...)</b>\r\n";
			if($debugMessage){
				echo dHelper2::dump($debugMessage);
			}
			echo "</div>";
		}
		$this->errorList[] = Array('code'=>$errorCode, 'message'=>$errorStr, 'debugMessage'=>$debugMessage);
	}
	
	// Helpers:
	static Function convertEndpointTable($content){
		$content    = explode("\n", trim($content, "\r\n"));
		$headers    = explode("\t", rtrim($content[0]));
		array_unshift($headers, "Endpoint");
		$headers[1] = "RequestMethod";
		$content    = array_map(function($line) use (&$headers){
			$values       = explode("\t", rtrim($line, "\r\n"));
			$_tmpEndpoint = explode(" ", $values[0], 2);
			array_unshift($values, $_tmpEndpoint[1]);
			$values[1] = $_tmpEndpoint[0];
			
			foreach($values as $idx=>$value){
				if($value === '')   $values[$idx] = '---';
				if($value == 'Sim') $values[$idx] = true;
				if($value == 'Não') $values[$idx] = false;
			}
			return array_combine($headers, $values);
		}, $content);
		
		$isFirst    = true;
		$exportCode = Array();
		foreach($content as $row){
			if($row['Resposta'] == 'Resposta'){
				// Cabeçalho, ignore.
				continue;
			}
			
			$considerMethod = false;
			if($row['Endpoint'] == '/v2/transactions' || $row['Endpoint'] == '/pre-approvals'){
				$considerMethod = true;
			}
			
			$strIf        = ($isFirst?"if":"elseif");
			$isFirst      = false;
			$endpoint     = $row['Endpoint'];
			$useMethod    = $row['RequestMethod'];
			$exportedCode  = "Array(\r\n";
			
			$authTypeEval = "(\$this->authData['app']?'app':'token')";
			$row          = Array(
				'requestMethod'=>strtolower($useMethod),
				'endpoint'    =>$endpoint,
				'authType'    =>($row['AT:App'] ? "[AT_EVAL]":'token'),
				'authMethod'  =>($row['AM:Post']?'post':'get'),
				'inputFormat' =>($row['I:Post'] ?'post':($row['I:Get']?'get':($row['I:Xml']?'xml':($row['I:Json']?'json':null)))),
				'acceptHeader'=>($row['A:Xml']  ?'xml':($row['A:Json']?'json':false)),
			);
			
			$exportedCode = var_export($row, true).";";
			$exportedCode = str_replace(Array("\r\n", "\r", "\n"), "\r\n", $exportedCode);
			$exportedCode = str_replace("array (", "Array(", $exportedCode);
			$exportedCode = str_replace("'[AT_EVAL]'", $authTypeEval, $exportedCode);
			$exportedCode = str_replace("  ", "\t\t\t\t", $exportedCode);
			$exportedCode = str_replace(");", "\t\t\t);", $exportedCode);
			if(preg_match_all("/({\\\$.+?})/", $endpoint, $out)){
				$useRegex     = "/^".str_replace("/", "\\/", $endpoint)."$/i";
				$useRegex     = str_replace($out[1], "[0-9a-z\-\_\.]+", $useRegex);
				$ifLine       = "{$strIf}(preg_match('{$useRegex}', \$endpoint)";
			}
			else{
				$ifLine       = "{$strIf}(\$endpoint == '{$endpoint}'";
			}
			if($considerMethod){
				$ifLine .= " && \$useMethod == '{$useMethod}'";
			}
			
			$ifLine .= "){";
			$exportCode[] = "		{$ifLine}";
			$exportCode[] = "			\$settings = ".$exportedCode;
			$exportCode[] = "		}";
		}
		echo implode("\r\n", $exportCode)."\r\n";
	}
}

# require_once "dBrowser2.inc.php";
# require_once "dHelper2.inc.php";

# $ps = new dPagSeguro2;
# $ps->setAuthToken('alexandrebr@gmail.com', 'EAD955F80DF84ED797B0A0464E9F41D5', true);
# $ps->setAuthApp  ('app0564356174', 'C729A2B66E6EE4FBB4B2AF80DCF15155', false, true);
# $ps->setSandbox(true);
# $ps->setDebug(true);
