<?php
/**
	dFacebook
	------------------------------------------------------------
	Uma maneira simples e rápida de utilizar a caixa de login do Facebook
	e obter um AccessToken para utilização da Graph API.
	
	Funcionamento:
	------------------------------------------------------------
		1. Inicie a classe sempre da seguinte forma:
		$facebookObj = dFacebook::start();
		$facebookObj->setConfig($appId, $appSecret, $defaultSettings, $loginMode='webservice')
		
		2. Para saber se o usuário está autenticado, ou solicitar o login, utilize:
		$facebookObj->isLogged();
		$facebookObj->isLoggedOrRequestLogin();
		
		3. Se isLogged() retornar TRUE, você pode saber quem é o usuário logado utilizando a Graph API:
		$facebookObj->graphRequest('/me');
	
	
	Configurações ($defaultSettings)
	------------------------------------------------------------
		[*redirect_uri] (ex: https://www.meusite.com.br/resposta.php)
		[*state]        (ex: 1234567) > Leia mais abaixo
		[*scope]        (ex: 'email,other_permissions')
		[*display]      (ex: 'popup')
		[*auth_type]    (ex: 'rerequest')
		* Todos os campos citados acima são opcionais. Se ocultados, o sistema fará de tudo para funcionar sem eles.
	
		* State:
		    Desde 02/2018 o Facebook considera /response diferente de /response?sid=1234567.
			Por isso, qualquer parâmetro GET que seria passado no redirect_url, tem que ser enviado via "state".
			O requestAccessToken também recebeu um 3º parâmetro, que é o STATE.
	
	$loginMode (webservice ou direct)?
	------------------------------------------------------------
		Ambos funcionam exatamente da mesma forma e com a mesma sintaxe.
		No entanto, se você não utilizar o webservice, você deverá autorizar a 'redirect_uri' no Facebook.
	
	Data Flow (WebService)
	------------------------------------------------------------
		1. O site fará um request (server-server) para https://ws.imaginacom.com/facebook/api.php?action=requestLogin
		2. O webservice retornará a URL completa do facebook, para a qual o cliente deverá ser direcionado
		3. O site mandará então o cliente para aquele endereço (facebook) onde será solicitada permissão
		4. O facebook mandará o cliente de volta para o webservice https://ws.imaginacom.com/facebook/response.php?code=XXXX
		5. O webservice fará uma nova requisição server-server para o facebook, verificando a existência do CODE,
		   e armazenará o AccessToken e o Expires, e mandará o cliente de volta para o site.
		7. O site fará um request (server-server) para https://ws.imaginacom.com/facebook/api.php?action=requestLogin
		8. O webservice retornará todas as informações obtidas (AccessToken, Expires e Error)
		
		Em resumo:
		- Site       =(server-side)=> Webservice: Processo invisível para o  cliente;
		- Site       =(client-side)=> Facebook:   Cliente vê a página de login request do facebook;
		- Facebook   =(client-side)=> Webservice: Código de autorização (ou mensagem de decline)
		- Webservice =(server-side)=> Facebook:   Obtém o AccessToken, e o armazena numa sessão temporária
		- Webservice =(client-side)=> Site:       Cliente recarrega o site
		- Site       =(server-side)=> Webservice: O site transfere a sessão temporária do Webservice para si próprio
		
		Ou seja:
		- 3 Requisições server-server    (2 para o webservice, 1 para o facebook)
		- 3 Redirecionamentos do cliente (Site para o Facebook; Facebook para o Webservice; Webservice de volta para o Site)
	
	
	Data Flow (Direct)
	------------------------------------------------------------
		1. O site te redirecionará diretamente para o a URL de login do Facebook
		2. O facebook retornará diretamente para o site
		3. O site conectará (server-server) no Facebook para obter o AccessToken.
		
		Em resumo:
		- Site       =(client-side)=> Facebook:   Cliente vê a página de login request do facebook;
		- Facebook   =(client-side)=> Site:       Código de autorização (ou mensagem de decline)
		- Site       =(server-side)=> Facebook:   Obtém o AccessToken, e o armazena na sessão atual
	
	To-do:
		Criptografar a comunicação entre esta classe e o webserver.
**/
class dFacebook{
	static  $instances;
	private $uniqueId;
	private $clientId;
	private $clientSecret;
	private $accessToken;
	private $tokenExpires;
	private $defaultSettings;
	private $loginMode;
	private $cache;
	public  $lastError;
	public  $debug;
	
	static Function getVersion    (){
		return 1.20180329;
	}
	static Function start         ($id='_default_'){
		if(self::$instances && array_key_exists($id, self::$instances)){
			return self::$instances[$id];
		}
		
		$fb = new dFacebook;
		$fb->uniqueId = $id;
		
		if(!isset($_SESSION['dFacebook'][$fb->uniqueId])){
			$_SESSION['dFacebook'][$fb->uniqueId] = Array();
		}
		else{
			$fb->accessToken  = @$_SESSION['dFacebook'][$fb->uniqueId]['AccessToken'];
			$fb->tokenExpires = @$_SESSION['dFacebook'][$fb->uniqueId]['Expires'];
			$fb->cache        = @$_SESSION['dFacebook'][$fb->uniqueId]['Cache'];
		}
		
		self::$instances[$id] = $fb;
		return $fb;
	}
	public Function setConfig     ($clientId, $clientSecret, $defaultSettings=Array(), $loginMode='direct'){
		$this->clientId        = $clientId;
		$this->clientSecret    = $clientSecret;
		$this->defaultSettings = $defaultSettings;
		$this->loginMode       = $loginMode;
	}
	public Function setAccessToken($accessToken){
		$this->accessToken = $accessToken;
	}
	public Function isLogged      (){
		return (bool)$this->accessToken;
	}
	public Function isLoggedOrRequestLogin(){
		if(!$this->isLogged()){
			return $this->requestLogin();
		}
		return true;
	}
	public Function destroySession(){
		$this->accessToken  = false;
		$this->tokenExpires = false;
		$_SESSION['dFacebook'][$this->uniqueId] = Array();
	}
	public Function logOut(){
		$this->destroySession();
	}
	
	// Login rápido (aquisição de AccessToken automaticamente), com redirecionamento automático.
	public Function requestLogin           ($step='auto'){
		return ($this->loginMode=='webservice')?
			$this->requestLogin_WebService($step):
			$this->requestLogin_Direct    ($step);
	}
	public Function requestLogin_WebService($step='auto'){
		// Step1: Conecta ao WebService para iniciar uma sessão (preparar o sistema para uma resposta do Facebook),
		//        redirecionar o usuário para autorizar o aplicativo no facebook. Armazena temporariamente a sessão
		//        'BrowserSessionId', a ser utilizado no passo 2.
		// 
		// Step2: Quando o WebService redireciona de volta para cá, temos a sessão 'BrowserSessionId' e o ?step=2.
		//        Neste momento, conectamos novamente ao WebService para pegar o AccessToken que o Facebook liberou.
		// 
		$step = (isset($_SESSION['dFacebook'][$this->uniqueId]['BrowserSessionId']) && isset($_GET['step']))?2:1;
		# echo "->requestLogin_WebService(step={$step})<br />";
		
		$b = new dBrowser2;
		$b->debug = $this->debug;
		if($step == 1){
			$settings = $this->defaultSettings + Array(
				'client_id'    =>$this->clientId,
				'client_secret'=>$this->clientSecret,
				'redirect_uri' =>(@$_SERVER['HTTPS']?"https":"http")."://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?".(isset($_SERVER['QUERY_STRING'])?"{$_SERVER['QUERY_STRING']}&":"")."step=2",
			);
			$b->addPost("settings", base64_encode(serialize($settings)));
			$b->go("https://ws.imaginacom.com/facebook/api.php?v=1&action=requestLogin");
			
			$_SESSION['dFacebook'][$this->uniqueId]['BrowserSessionId'] = $b->getCookie('PHPSESSID', true);
			
			if($this->debug){
				echo "<div style='border-radius: 15px; background: yellow; padding: 25px'>";
				echo "<b>Retorno do WebService (direcionando usuário para...):</b><br />";
				echo $b->getBody();
				echo "</div>";
				die;
			}
			
			dHelper2::redirectTo($b->getBody());
			die;
		}
		if($step == 2){
			$b->addCookie('PHPSESSID', $_SESSION['dFacebook'][$this->uniqueId]['BrowserSessionId']);
			$b->go("https://ws.imaginacom.com/facebook/api.php?v=1&action=getAccessToken");
			$result = json_decode($b->getBody(), true);
			$this->accessToken  = @$result['AccessToken'];
			$this->tokenExpires = @$result['Expires'];
			$this->lastError    = @$result['Error'];
			$this->sessionUpdate();
			return $this->isLogged();
		}
		return false;
	}
	public Function requestLogin_Direct    ($step='auto'){
		// Step1: Redireciona o usuário diretamente para o Facebook. Armazena temporariamente a sessão 
		//        'LastResponseUri', pois ela precisará ser idêntica no step2.
		// 
		// Step2: Quando o Facebook redireciona de volta com ?code=xxxx, o sistema se conecta ao Facebook
		//        para obtenção do AccessToken com base nesse código.
		// 
		$step = (isset($_SESSION['dFacebook'][$this->uniqueId]['LastResponseUri']) && isset($_GET['code']))?2:1;
		if($step == 1){
			if(!isset($this->defaultSettings)){
				die("dFacebook: requestLogin_Direct() chamado sem 'redirect_uri' em defaultSettings.");
			}
			$_SESSION['dFacebook'][$this->uniqueId]['LastResponseUri'] = $this->defaultSettings['redirect_uri'];
			dHelper2::redirectTo($this->getFacebookLink());
			die;
		}
		if($step == 2){
			$this->requestAccessToken($_GET['code'], $_SESSION['dFacebook'][$this->uniqueId]['LastResponseUri']);
			unset($_SESSION['dFacebook'][$this->uniqueId]['LastResponseUri']);
			$this->sessionUpdate();
		}
	}
	
	// Low-Level:
	public Function getFacebookLink($settings=Array()){
		$settings += $this->defaultSettings + Array(
			'client_id'    =>$this->clientId,
			'redirect_uri' =>'',
			'response_type'=>'code',
		);
		# echo "<b>getFacebookLink:</b><br />";
		# dDbRow3::dump($settings);
		return "https://www.facebook.com/dialog/oauth?".http_build_query($settings);
	}
	public Function requestAccessToken($code, $redirectUri, $state){
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$b->addGet('client_id',         $this->clientId);
		$b->addGet('client_secret',     $this->clientSecret);
		$b->addGet('code',              $code);
		$b->addGet('redirect_uri',      $redirectUri);
		$b->addGet('state',             $state);
		$b->go("https://graph.facebook.com/oauth/access_token");
		
		$body   = $b->getBody();
		$result = json_decode($body, true);
		if($result['error'] || !$result){
			$this->accessToken  = false;
			$this->tokenExpires = false;
			$this->lastError    = $result?$result['error']:'Invalid response';
			return false;
		}
			
		$this->accessToken  = $result['access_token'];
		$this->tokenExpires = time()+$result['expires_in'];
		$this->lastError    = false;
		return true;
	}
	public Function export(){
		return Array(
			'AccessToken'=>$this->accessToken,
			'Expires'    =>$this->tokenExpires,
			'Error'      =>$this->lastError,
		);
	}
	
	public Function sessionUpdate(){
		$_SESSION['dFacebook'][$this->uniqueId]['AccessToken'] = $this->accessToken;
		$_SESSION['dFacebook'][$this->uniqueId]['Expires']     = $this->tokenExpires;
		$_SESSION['dFacebook'][$this->uniqueId]['Cache']       = $this->cache;
	}
	public Function sessionDestroy(){
		unset($_SESSION['dFacebook'][$this->uniqueId]);
		if(!sizeof($_SESSION['dFacebook']))
			unset($_SESSION['dFacebook']);
	}
	
	public Function debugToken($accessToken){
		if(!$this->clientId || !$this->clientSecret){
			// Não tenho como checar se o token é correto, sem os dados do aplicativo.
			$this->lastError    = 'You need to inform app_id and app_secret before validating an access token.';
			return false;
		}
		$b = new dBrowser2;
		$b->debug = $this->debug;
		$b->addGet('input_token',  $accessToken);
		$b->addGet('access_token', "{$this->clientId}|{$this->clientSecret}");
		$b->go("https://graph.facebook.com/debug_token");
		$body   = $b->getBody();
		$result = json_decode($body, true);
		return $result;
	}
	public Function graphRequest($what, $ignoreCache=false){
		if(!$this->accessToken){
			return false;
		}
		
		$what = ltrim($what, '/');
		
		if(!$ignoreCache && $this->cache && isset($this->cache[$what])){
			if($this->debug){
				echo "<i>dFacebook</i> - Retornando o comando '/{$what}' com  resultado existente em cache.<br />";
			}
			return $this->cache[$what];
		}
		
		$br       = new dBrowser2;
		$br->debug = $this->debug;
		$parts = explode("?", $what, 2);
		$br->go("https://graph.facebook.com/v3.2/{$parts[0]}?access_token={$this->accessToken}".(isset($parts[1])?"&{$parts[1]}":""));
		$ret = json_decode($br->getBody(), true);
		if(isset($ret['error'])){
			$this->setError($ret['error']);
			return false;
		}
		
		if(!$ignoreCache){
			$this->cache[$what] = $ret;
			$this->sessionUpdate();
		}
		
		$this->setError(false);
		return $ret;
	}
	public Function setError($errorInfo){
		if($errorInfo['type'] == 'OAuthException'){
			// [message] Error validating access token: The user has not authorized application 640224262715250.
			// [type]    OAuthException
			// [code]    190
			// [error_subcode] 458
			$this->destroySession();
		}
		$this->lastError = $errorInfo;
	}
	
	public Function __dump($maxDepth=10){
		echo "<div style='background: #CCFFCC; border: 1px solid #080; box-shadow: -1px 1px 3px #888888; display: inline-block'>";
		echo "	<b>dFacebook object</b><br />";
		echo "	<table>";
		echo "		<tr valign='top'>";
		echo "			<td>";
		echo "				<i>Private properties:</i><br />";
		echo "				<table>";
		echo "					<tr><td>uniqueId</td><td>{$this->uniqueId}</td></tr>";
		echo "					<tr><td>clientId</td><td>{$this->clientId}</td></tr>";
		echo "					<tr><td>clientSecret</td><td>{$this->clientSecret}</td></tr>";
		echo "					<tr><td>accessToken</td><td><div style='width: 180px; text-overflow:ellipsis; white-space: nowrap; overflow: hidden' ondblclick=\"this.style.overflow=(this.style.overflow=='hidden')?'inherit':'hidden';\">{$this->accessToken}</div></td></tr>";
		echo "					<tr><td>tokenExpires</td><td>{$this->tokenExpires} <i style='color: #00F'>".(date('d/m/Y H:i:s', $this->tokenExpires))."</i></td></tr>";
		echo "					<tr><td>defaultSettings</td><td>";
		dHelper2::dump($this->defaultSettings);
		echo "					</td></tr>";
		echo "					<tr><td>loginMode</td><td>{$this->loginMode}</td></tr>";
		echo "				</table>";
		echo "			</td>";
		echo "		</tr>";
		echo "	</table>";
		echo "</div>";
	}
}

