<?php
// 09/11/2019
// + Reformulado o método ->download, permitindo auto-resume;
// + setTimeout agora permite array como parametro
// 16/Dez/2014 v2.2
// + New parameter $onlyFirst on getCookie($name)
// * getCookie now returns only VALUES, not Array(key, value) anymore, as keys are always the same.
// * Default charset now is utf-8 instead of iso-8859-1 (Require compatibility check)
// * Method ParseForm is now removed.
// 01/Jan/2014 v2.112
// * Imported cookies now overwrites old ones.
// 25/Ago/2013 v2.111
// * Debug now shows also 'text' return
// 17/Fev/2013 v2.11
// + New methods: ->ping and ->download
// + New methods: ->setHeader and ->makeCodeFromChromeNetwork($chromeDebug)
// * Debugging is now more useful
// * Class was totally rewritten.
// * Class is now UTF-8 by default.

// Usage:
//   $b = new dBrowser2;
//   $b->debug = true;
//   $b->followLocation = true;
//   $b->go("http://www.google.com/");
//   $b->addPost("campo",     "valor");
//   $b->addFile("anexo.zip", "/anexo.zip");
//   $b->addCookie("cookie", "valor_do_cookie");
//   $b->setHeader("custom-header", "valor do header");
//   $b->go("http://site.com.br/", "debug-file.html", $expireAfterSeconds)
//   $b->ping($host, $timeout=3)
//   $b->download($url, $cbOrFilename=false, $bufferSize=1024)

/**
	Public methods:
		
		Browser settings (Not affected by restart):
		->setUserAgent($agent) Default is IE8
		->setCharset($charset) Useful when you're submitting data (->addPost)
		->setTimeout($timeout) Max time to connect, and max time to wait for response
		* Total execution time may be 2*$timeout
		
		Session and form management:
		->restart() Clears all cookies and session cookies;
		->addGet($name, $value)    ->removeGet($name)
		->addPost($name, $value)   ->removePost($name)
		->addFile($name, $file)    ->removeFile($name)
		->addCookie($name, $value) ->removeCookie($name)
		
		Header and Cookie management:
		->getCookie($name[, $onlyFirst=false])
		->clearCookies()
		->setHeader($name, $value)
		->removeHeader($name)
		->checkHeader($name)
		->clearHeaders()
		->setReferer($url) Shortcut to ->setHeader
		->setAuthentication($username, $password) Shortcut to ->setHeader
		->setHost($host) Shortcut to ->setHeader
		
		Connection control:
		->go($url[, $cacheFile[, $cacheExpires]])
		->getHeader()
		->getStatus()
		->getBody()
		->ping($host[, $timeout])
		->download($url, $cbOrFilename[, $bufferSize])
		
		Debugging and extended class control:
		->makeCodeFromChromeNetwork($str)
		->errors = Array(); Default is empty.
		->debug = true; Default is false.
		->followLocation = true; Default is false.
		->clearCookiesBeforeImport = true; Default is false.
**/


class dBrowser2{
	var $getVars;    // Not persistent
	var $postVars;   // Not persistent
	var $fileVars;   // Not persistent
	var $cookieVars; // Persistent
	var $headerVars; // Persistent
	
	var $cache;
	var $errors;
	var $debug;
	
	var $followLocation           = false;
	var $clearCookiesBeforeImport = false;
	
	var $timeout = 15;
	var $charset = 'utf-8';
	var $agent   = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36";
	
	static Function getVersion(){
		return 2.2;
	}
	
	public Function __construct(){
		$this->restart();
	}
	public Function restart(){
		$this->cache = Array(
			'status'=>false,
			'header'=>false,
			'body'  =>false,
		);
		
		$this->getVars    = 
		$this->postVars   = 
		$this->fileVars   = 
		$this->cookieVars = 
		$this->headerVars = 
		$this->cache      = 
		$this->errors     = Array();
	}
	public Function reset(){
		return $this->restart();
	}
	
	public Function addGet($name, $value){
		$this->getVars[] = Array($name, $value);
	}
	public Function removeGet($name){
		foreach($this->getVars as $idx=>$item){
			if($item[0] == $name)
				unset($this->getVars[$idx]);
		}
	}
	
	public Function setPost   ($string){
		$this->postVars = $string;
	}
	public Function addPost   ($name, $value){
		$this->postVars[] = Array($name, $value);
	}
	public Function removePost($name){
		foreach($this->postVars as $idx=>$item){
			if($item[0] == $name)
				unset($this->postVars[$idx]);
		}
	}
	
	public Function addFile   ($name, $filename){
		$fileEx = @file_exists($filename);
		$this->_debugMsg("addFile({$name}): Definida como '{$filename}'. Arquivo ".($fileEx?'encontrado':'não encontrado!'));
		
		// Todo:
		//   If(!$fileEx)
		//     setError and return false.
		$this->fileVars[] = Array($name, $filename);
	}
	public Function removeFile($name){
		foreach($this->fileVars as $idx=>$item){
			if($item[0] == $name)
				unset($this->fileVars[$idx]);
		}
	}
	
	public Function addCookie ($name, $value){
		$this->cookieVars[] = Array($name, $value);
	}
	public Function getCookie ($name, $onlyFirst=false){
		$ret = Array();
		foreach($this->cookieVars as $idx=>$item){
			if($item[0] == $name)
				$ret[] = $item[1];
		}
		if($onlyFirst && sizeof($ret) >= 1){
			return $ret[0];
		}
		
		return $ret;
	}
	public Function removeCookie($name){
		foreach($this->cookieVars as $idx=>$item){
			if($item[0] == $name)
				unset($this->cookieVars[$idx]);
		}
	}
	public Function clearCookies(){
		$this->cookieVars = Array();
	}
	
	public Function setHeader   ($name, $value){
		$name = explode("-", strtolower($name));
		$name = implode("-", array_map('ucfirst', $name));
		$this->headerVars[] = Array($name, $value);
	}
	public Function checkHeader ($name){
		$ret = Array();
		foreach($this->headerVars as $idx=>$item){
			if($item[0] == $name)
				$ret[] = $item[1];
		}
		return $ret;
	}
	public Function removeHeader($name){
		foreach($this->headerVars as $idx=>$item){
			if($item[0] == $name)
				unset($this->headerVars[$idx]);
		}
	}
	public Function clearHeaders(){
		$this->headerVars = Array();
	}
	
	// Shortcuts to headers:
	public Function setReferer($url){
		$url?
			$this->setHeader('Referer', $url):
			$this->removeHeader('Referer');
	}
	public Function setAuthentication($username, $password){
		($username || $password)?
			$this->setHeader("Authentication", "Basic ".base64_encode("{$username}:{$password}")):
			$this->removeHeader("Authentication");
	}
	public Function setHost   ($host){
		$host?
			$this->setHeader('Host', $host):
			$this->removeHeader('Host');
	}
	
	// Browser settings (not affected by ->restart())
	public Function setUserAgent($agent){
		$this->agent = $agent;
	}
	public Function setCharset($charset){
		$this->charset = $charset;
	}
	public Function setTimeout($timeout){
		// int $timeout (TIMEOUT*), or...
		// array(CONNECTTIMEOUT, TIMEOUT*)
		// * Timeout global, considerando conexão + tempo conectado.
		$this->timeout = $timeout;
	}
	
	// Actions
	public Function go($url, $cacheFile=false, $cacheExpires=-1, $getCurlParams=false){
		// Se cacheExpires for -1, não adicione informações de data no arquivo (útil para jpg)
		// Se cacheExpires for  0, adicione informações de data, mas considere cache infinito.
		// Se cacheExpires for >0, após 'cacheExpires' segundos o link será re-downloaded.
		// Se $getCurlParams, retorna a lista de curl params, sem executar.
		if(!$getCurlParams && file_exists($cacheFile)){
			// file_put_contents($cacheFile, str_pad(time(), 15).$this->cache['body']);
			$tmpBody = file_get_contents($cacheFile);
			
			if($cacheExpires == -1){
				$this->_debugMsg("Retornando conteudo cached.");
				$this->setReferer($url);
				$this->cache['body'] = &$tmpBody;
				return $tmpBody;
			}
			else{
				$tmpTime = substr($tmpBody, 0, 15);
				$tmpBody = substr($tmpBody, 15   );
				
				if($cacheExpires && intval($tmpTime)+$cacheExpires < time()){
					// Cache expired! Re-downloading....
				}
				else{
					$this->_debugMsg("Retornando conteudo cached (expira em ".($tmpTime-time())."s).");
					$this->setReferer($url);
					$this->cache['body'] = $tmpBody;
					return $tmpBody;
				}
			}
		}
		
		// Atualização para suportar "PUT", "GET", "DELETE", etc..
		// $url agora pode ser "PUT $url".
		$requestMethod = false;
		if(preg_match("/^([a-z]+) (.+)$/i", $url, $out)){
			$requestMethod = strtoupper($out[1]);
			$url           = $out[2];
		}
		
		// Primeira etapa: Preparação das variáveis para conexão ($cURL_Commands)
		$url     = $this->_getInfoAboutUrl($url);
		if(!$url['scheme']){
			// URL Simplificada, continuação do request anterior.
			$lastUrl = $this->checkHeader('Referer');
			if(!$lastUrl){
				die("dBrowser2 - Impossivel dar continuidade ao request atual, sem um request anterior.");
			}
			
			$lastUrl = $this->_getInfoAboutUrl($lastUrl[0]);
			$url['scheme'] = $lastUrl['scheme'];
			$url['host']   = $lastUrl['host'];
			$url['port']   = $lastUrl['port'];
			if($url['path'][0] != '/'){
				$url['path'] = dirname($lastUrl['path'])."/".$url['path'];
			}
		}
		
		$headers = $this->headerVars;
		if(!$this->checkHeader('User-Agent') && $this->agent){
			$headers[] = Array('User-Agent', $this->agent);
		}
		
		$cURL_Commands   = Array();
		$cURL_Commands[] = Array('CURLOPT_NOPROGRESS', 1);
		$cURL_Commands[] = Array('CURLOPT_SSL_VERIFYPEER', 0);
		$cURL_Commands[] = Array('CURLOPT_SSL_VERIFYHOST', 0);
		if($requestMethod){
			$cURL_Commands[] = Array('CURLOPT_CUSTOMREQUEST', $requestMethod);
		}
		$cURL_Commands[] = Array('CURLOPT_RETURNTRANSFER', 1);
		$cURL_Commands[] = Array('CURLOPT_HEADER',         1);
		if($this->timeout){
			if(is_array($this->timeout)){
				$cURL_Commands[] = Array('CURLOPT_CONNECTTIMEOUT', $this->timeout[0]);
				$cURL_Commands[] = Array('CURLOPT_TIMEOUT',        $this->timeout[1]);
			}
			else{
				$cURL_Commands[] = Array('CURLOPT_TIMEOUT',        $this->timeout);
			}
		}
		if($this->postVars || $this->fileVars){
			if(!$this->checkHeader('Content-Type')){
				$headers[] = $this->fileVars?
					Array("Content-Type", "multipart/form-data; charset={$this->charset}"):
					Array("Content-Type", "application/x-www-form-urlencoded; charset={$this->charset}");
				
				// To-do:
				// --> Se houver fileVars, dever�amos ter "; boundary=xxxxxx" no Content-Type.
				//     No entanto, os boundaries s�o gerados automaticamente pelo CURL, e n�o temos
				//     como identific�-los. Ent�o, a solu��o seria gerar um multipart/form-data manualmente,
				//     e fazer uma codifica��o pr�pria RAW para o $_POST... Mas pelos testes efetuados,
				//     mesmo sem o Boundary as coisas ainda funcionam, ent�o vamos manter dessa forma.
			}
			
			if($this->fileVars){
				// Multipart/form-data:
				$sendVar = Array();
				foreach($this->postVars as $postInfo){
					$sendVar[$postInfo[0]] = $postInfo[1];
				}
				foreach($this->fileVars as $fileInfo){
					$sendVar[$fileInfo[0]] = "@{$fileInfo[1]}";
				}
			}
			else{
				if(is_array($this->postVars)){
					if(isset($this->postVars[0]) && is_array($this->postVars[0])){
						// Detectado padrão Array(key, value).
						$sendVar = Array();
						foreach($this->postVars as $varItem){
							$sendVar[$varItem[0]] = $varItem[1];
						}
					}
					else{
						$sendVar = $this->postVars;
					}
				}
				else{
					$sendVar = $this->postVars;
				}
			}
			
			$cURL_Commands[] = Array('CURLOPT_POST', 1);
			$cURL_Commands[] = Array('CURLOPT_POSTFIELDS', http_build_query($sendVar));
		}
		if($this->getVars){
			$tmp = Array();
			foreach($this->getVars as $item){
				$tmp[] = urlencode($item[0])."=".urlencode($item[1]);
			}
			$url['query'] .= 
				($url['query']?"&":"?").
				implode("&", $tmp);
		}
		if($this->cookieVars){
			$cURL_Commands[] = Array('CURLOPT_COOKIE', $this->_asQueryString($this->cookieVars, "=", "; "));
		}
		if($headers){
			$_useHeaders = Array();
			foreach($headers as $header){
				$_useHeaders[] = "{$header[0]}: {$header[1]}";
			}
			$cURL_Commands[] = Array('CURLOPT_HTTPHEADER', $_useHeaders);
		}
		
		$con_url = "{$url['scheme']}://{$url['host']}:{$url['port']}{$url['path']}{$url['query']}";
		$cURL_Commands[] = Array('CURLOPT_URL',     $con_url);
		
		if($getCurlParams){
			return $cURL_Commands;
		}
		
		// Segunda etapa: Enviar a requisição e obter resposta ($ret)
		$c       = curl_init();
		foreach($cURL_Commands as $cURL_Item){
			(sizeof($cURL_Item) == 2)?
				curl_setopt($c, constant($cURL_Item[0]), $cURL_Item[1]):
				curl_setopt($c, constant($cURL_Item[0]));
		}
		$ret = curl_exec($c);
		if($error = curl_error($c)){
			$this->errors[] = "Curl error: {$error}";
			return false;
		}
		
		// Terceira etapa: Tratar e separar o conteúdo do cabeçalho
		$this->errors = Array();
		$this->cache  = $this->_parseReceived($ret, false);
		$this->_importCookies($this->cache['header']);
		
		$this->removeHeader('Referer');
		$this->setHeader('Referer', $con_url);
		
		if($this->debug){
			$uid = uniqid();
			echo "<script src='http://code.jquery.com/jquery-1.9.1.min.js'></script>";
			echo "<a href='#' onclick=\"$('#main-{$uid}').slideToggle(); return false;\" style='display: inline-block; color: #FFF; border-top: 1px solid #FFF; padding: 5px; background: #9C9; font: 12px Arial'>-->go({$con_url})</a><br />";
			echo "<div id='main-{$uid}' style='display: block'>";
			echo "<table bgcolor='#CCFFCC' cellpadding='2' cellspacing='1' style='border-collapse: collapse; font: 12px Arial' border='1'>";
			echo "	<tr valign='top'>";
			echo "		<td nowrap='nowrap'>";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#cURL-{$uid}').stop().slideDown(); return false;\">cURL Parameters</a><br />";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#cURL-REP-{$uid}').stop().slideDown(); return false;\">cURL Reproduce</a><br />";
			echo "			<hr size='1' />";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#Response-Raw-{$uid}').stop().slideDown(); return false;\">Response Raw</a><br />";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#Response-Headers-{$uid}').stop().slideDown(); return false;\">Response Headers</a><br />";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#Response-Body-{$uid}').stop().slideDown(); return false;\">Response Body</a><br />";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#Response-Text-{$uid}').stop().slideDown(); return false;\">Response Text</a><br />";
			echo "			<hr size='1' />";
			echo "			<a href='#' onclick=\"$('.cURL-{$uid}').slideUp(); $('#Browser-{$uid}').stop().slideDown(); return false;\">Browser Status</a><br />";
			echo "		</td>";
			echo "		<td>";
			echo "			<div id='cURL-{$uid}' class='cURL-{$uid}' style='display: none'>";
			echo "				<table style='font: 11px Arial; border-collapse: collapse' border='1'>";
			foreach($cURL_Commands as $item){
				echo "<tr valign='top'>";
				if($item[0] == 'CURLOPT_POSTFIELDS' || $item[0] == 'CURLOPT_COOKIE'){
					echo "	<td>{$item[0]}</td>";
					echo "	<td>";
					dHelper2::dump($this->postVars);
					echo "	</td>";
				}
				elseif($item[0] == 'CURLOPT_URL'){
					echo "	<td>{$item[0]}</td>";
					echo "	<td>";
					if(!strpos($con_url, "?")){
						echo "			{$con_url}<br />";
					}
					else{
						$tmp = explode("?", $con_url, 2);
						echo "			{$tmp[0]}?<br />";
						$tmp = explode("&", $tmp[1]);
						echo "<div style='margin-left: 15px'>";
						foreach($tmp as $item){
							echo "		<b>&</b>{$item}<br />";
						}
						echo "</div>";
					}
					echo "	</td>";
				}
				elseif($item[0] == 'CURLOPT_HTTPHEADER'){
					echo "	<td>{$item[0]}</td>";
					echo "<td>";
					foreach($item[1] as $subitem){
						$subitem = explode(": ", $subitem, 2);
						echo "<b>{$subitem[0]}: </b>{$subitem[1]}<br />";
					}
					echo "</td>";
				}
				else{
					echo "	<td>{$item[0]}</td>";
					echo "	<td>{$item[1]}</td>";
				}
			}
			echo "				</table>";
			echo "			</div>";
			echo "			<div id='cURL-REP-{$uid}' class='cURL-{$uid}' style='display: none; font: 11px Courier New'>";
			echo "\$c = curl_init();<br />";
			foreach($cURL_Commands as $item){
				echo "curl_setopt(\$c, {$item[0]}, ".htmlspecialchars(var_export($item[1], true)).");<br />";
			}
			echo "\$ret = curl_exec(\$c);\r\n";
			echo "			</div>";
			echo "			<div id='Response-Raw-{$uid}' class='cURL-{$uid}' style='display: none; font: 11px Courier New'>";
			echo nl2br(htmlspecialchars($ret));
			echo "			</div>";
			echo "			<div id='Response-Headers-{$uid}' class='cURL-{$uid}' style='display: none; font: 11px Courier New'>";
			if($getCurlParams){
				echo "<b>Download mode. Headers were not parsed.</b><br />";
			}
			else{
				echo "				<b>getStatus():</b> version={$this->cache['status']['version']}, status={$this->cache['status']['status']}, string={$this->cache['status']['string']}<br />";
				echo "				<hr size='1' color='#000000' />";
				foreach($this->cache['header'] as $key=>$values){
				foreach($values as $value){
					echo "<b>{$key}:</b> {$value}<br />";
				}
			}
			}
			echo "			</div>";
			echo "			<div id='Response-Body-{$uid}' class='cURL-{$uid}' style='display: none; font: 11px Courier New'>";
			if($getCurlParams){
				echo "<b>Download mode. Body was not parsed.</b><br />";
			}
			else{
				echo nl2br(htmlspecialchars($this->cache['body']));
			}
			echo "			</div>";
			echo "			<div id='Response-Text-{$uid}' class='cURL-{$uid}' style='display: none; font: 11px Courier New'>";
			if($getCurlParams){
				echo "<b>Download mode. Body was not parsed.</b><br />";
			}
			else{
				echo nl2br(strip_tags($this->cache['body']));
			}
			echo "			</div>";
			echo "			<div id='Browser-{$uid}' class='cURL-{$uid}' style='display: block'>";
			echo "				<b>Cookies:</b><br />";
			echo "				<table bgcolor='#AADDAA' style='font: 11px Arial'>";
			foreach($this->cookieVars as $cookieItem){
				echo "					<tr>";
				echo "						<td><b>{$cookieItem[0]}</b></td>";
				echo "						<td bgcolor='#BBEEBB'>{$cookieItem[1]}</td>";
				echo "					</tr>";
			}
			echo "				</table>";
			echo "				<br />";
			echo "				<b>Headers pré-definidos:</b><br />";
			echo "				<table bgcolor='#AADDAA' style='font: 11px Arial'>";
			foreach($this->headerVars as $headerVar){
				echo "					<tr>";
				echo "						<td><b>{$headerVar[0]}</b></td>";
				echo "						<td bgcolor='#BBEEBB'>{$headerVar[1]}</td>";
				echo "					</tr>";
			}
			echo "				</table>";
			echo "				<br />";
			echo "				<b>Settings:<br />";
			echo "				<table bgcolor='#AADDAA' style='font: 11px Arial'>";
			echo "					<tr>";
			echo "						<td><b>Timeout</b></td>";
			echo "						<td bgcolor='#BBEEBB'>{$this->timeout}</td>";
			echo "					</tr>";
			echo "					<tr>";
			echo "						<td><b>Agent</b></td>";
			echo "						<td bgcolor='#BBEEBB'>{$this->agent}</td>";
			echo "					</tr>";
			echo "					<tr>";
			echo "						<td><b>Charset</b></td>";
			echo "						<td bgcolor='#BBEEBB'>{$this->charset}</td>";
			echo "					</tr>";
			echo "				</table>";
			echo "			</div>";
			echo "		</td>";
			echo "	</tr>";
			echo "</table>";
			echo "</div>";
		}
		
		$this->getVars  = 
		$this->postVars = 
		$this->fileVars = Array();
		if($this->followLocation && isset($this->cache['header']['Location'][0])){
			$this->_debugMsg("go(): Following location to: {$this->cache['header']['Location'][0]}");
			return $this->go($this->cache['header']['Location'][0]);
		}
		if($cacheFile){
			file_put_contents($cacheFile, (($cacheExpires>-1)?str_pad(time(), 15):'').$this->cache['body']);
		}
		
		return $this->cache['body'];
	}
	public Function getHeader(){
		return $this->cache['header'];
	}
	public Function getStatus(){
		return $this->cache['status'];
	}
	public Function getBody(){
		return $this->cache['body'];
	}
	
	public Function makeCodeFromChromeNetwork($str, $n=0){
		// Deve começar com "Request URL:"
		// e terminar logo antes do "Response Headers:".
		// Antes, não esquecer de selecionar "Show source" em tudo.
		$n++;
		$url       = false;
		$postVar   = Array();
		$append    = false;
		
		$inside = "Main Header";
		$lines  = explode("\n", $str);
		$lines  = array_map('trim', $lines);
		for($x = 0; $x < sizeof($lines); $x++){
			if(substr($lines[$x], 0, 12) == "Request URL:" && $inside != "Main Header"){
				$append = self::makeCodeFromChromeNetwork(implode("\n", array_slice($lines, $x)), $n);
				break;
			}
			
			if($lines[$x] == "Request Headersview parsed"){
				$inside = "Request Headers - Source";
				continue;
			}
			if($lines[$x] == "Form Dataview parsed"){
				$inside = "Form Dataview - Source";
				continue;
			}
			
			if($inside == "Main Header"){
				$parts = explode(":", $lines[$x], 2);
				if($parts[0] == 'Request URL'){
					$url = $parts[1];
				}
				continue;
			}
			if($inside == "Request Headers - Source"){
				$parts = explode(":", $lines[$x], 2);
				// User-agent!?
				// Pragma?
				// Host?
				continue;
			}
			if($inside == "Form Dataview - Source"){
				if(!strlen($lines[$x]))
					continue;
				
				$params = explode("&", $lines[$x]);
				foreach($params as $param){
					$postVar[] = explode("=", $param, 2);;
				}
			}
		}
		
		$maxLen = 0;
		array_map(function($str) use (&$maxLen){
			$s = strlen($str[0]);
			if($s > $maxLen)
				$maxLen = $s;
		}, $postVar);
		
		$finalCode = "";
		foreach($postVar as $item){
			$finalCode .= "\$b->addPost(".str_pad("'{$item[0]}',", $maxLen+3)." '".addslashes($item[1])."');\r\n";
		}
		$finalCode .= "\$b->go('{$url}', 'custom-{$n}.html');\r\n";
		if($append){
			$finalCode .= "\r\n";
			$finalCode .= $append['code'];
		}
		return Array(
			'code'=>$finalCode,
			'cmds'=>false
		);
	}
	
	// More browser common functions:
	public static Function ping($host, $timeout=3){
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket  = socket_create(AF_INET, SOCK_RAW, 1);
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
		socket_connect($socket, $host, null);
		
		$ts = microtime(true);
		
		socket_send($socket, $package, strLen($package), 0);
		$result = socket_read($socket, 255)?
			(microtime(true) - $ts):
			false;
		
		socket_close($socket);
		return $result;
	}
	public Function download($url, $filename, $bufferSize=1024*1000, $resume=false, $cbOnProgress=false){
		// $cbOnProgress: Se FALSE, ignora. Se TRUE, usa o "NOPROGRESS=FALSE" (mostra tabela padrão no CLI)
		// $cbOnProgress: function($bytesDownloaded, $bytesTotal);
		//    * bytesTotal pode ser false, se não tivermos "Content-Length".
		$allHeaders      = Array();
		$resumeOffset    = 0;
		
		$_headerIdx      = 0;
		$_handleHeaders  = function($ch, $headerLine) use (&$_headerIdx, &$allHeaders){
			// HTTP/1.1 206 Partial Content
			// Content-Range: bytes 0-3567060/3567061
			// Content-Length: 3567061
			
			// HTTP/1.1 200 OK
			// Accept-Ranges: bytes
			// Content-Length: 3567061
			if($_headerIdx === 0){
				// Ex: Array(HTTP/1.1, 206, Partial Content
				$allHeaders['HTTP'] = explode(" ", $headerLine, 3);
				// echo "Salvando headerline.. Original: {$headerLine}...\r\n";
				// dHelper2::dump($allHeaders['HTTP']);
				
				// Validação!
				if($allHeaders['HTTP'][1] != 200 && $allHeaders['HTTP'][1] != 206){
					// Interrompe a execução.
					// echo "Interrompendo a execução no header. {$allHeaders['HTTP'][1]} não é 200 nem 206.<br />";
					return false;
				}
			}
			elseif(strpos($headerLine, ": ")){
				$parts = explode(": ", $headerLine);
				$allHeaders[$parts[0]] = rtrim($parts[1], "\r\n");
			}
			elseif(trim($headerLine)){
				// Não sei o que fazer com este conteúdo...
				// echo "Não sei o que fazer com este headerLine: {$headerLine}<br />";
			}
			
			$_headerIdx++;
			return strlen($headerLine);
		};
		// Headers:
		//   [HTTP] = ['HTTP/1.1', '200'|'206', 'OK'|'Partial Content']
		//   [Content-Range]  = '0-3567060/3567061'
		//   [Content-Length] = 3567061
		//   [Accept-Ranges] = bytes
		
		$_curBytesDownloaded = 0;
		$_handleProgress = function() use (&$allHeaders, &$_curBytesDownloaded, $filename, $resumeOffset, $cbOnProgress){
			if(is_bool($cbOnProgress)){
				// Não faz nada.
				return;
			}
			// dHelper2::dump($allHeaders);
			$bytesDownloaded = $_curBytesDownloaded;
			$bytesTotal      = isset($allHeaders['Content-Length'])?
				($allHeaders['Content-Length']+$resumeOffset):
				false;
			
			if($cbOnProgress){
				$cbOnProgress($bytesDownloaded, $bytesTotal);
			}
		};
		$_writeFile      = function($data, $dataLen) use (&$_curBytesDownloaded, $filename, $_handleProgress){
			$fh = fopen($filename, "a");
			fwrite($fh, $data);
			fclose($fh);
			$_curBytesDownloaded += $dataLen;
			$_handleProgress();
		};
		
		$_buffContent    = '';
		$_buffLen        = 0;
		$_initWrite      = function(&$cURL_Commands) use (&$resumeOffset, $filename, $resume){
			// Arquivo já existe. Overwrite or Resume?
			if($resume && file_exists($filename)){
				$resumeOffset = filesize($filename);
			}
			if($resumeOffset){
				$cURL_Commands[] = Array('CURLOPT_RANGE', "{$resumeOffset}-");
			}
			else{
				// Cria um novo arquivo em branco...
				fclose(fopen($filename, $resumeOffset?"a":"w"));
			}
		};
		$_handleWrite    = function($ch, $data) use (&$_buffContent, &$_buffLen, $_writeFile, $bufferSize){
			$dataLen = strlen($data);
			// echo "Received {$dataLen} bytes of data...<br />";
			
			// Adding to buffer...
			$_buffContent .= $data;
			$_buffLen     += $dataLen;
			if($_buffLen >= $bufferSize){
				// Estourou o limite do buffer. Vamos publicar o buffer atual.
				// echo "Publicando buffer...<br />";
				$_writeFile(substr($_buffContent, 0, $bufferSize), $bufferSize);
				$_buffContent = substr($_buffContent, $bufferSize);
				$_buffLen    -= $bufferSize;
			}
			
			return $dataLen;
		};
		$_endWrite       = function() use (&$_buffContent, &$_buffLen, $_writeFile){
			// echo "Publicando endWrite...<br />";
			if($_buffLen){
				// echo "Tinha coisa, publiquei.\r\n";
				$_writeFile($_buffContent, $_buffLen);
				$_buffContent = '';
				$_buffLen     = 0;
			}
		};
		
		$cURL_Commands = $this->go($url, false, false, true);
		$cURL_Commands[] = Array('CURLOPT_HEADER',           false);
		$cURL_Commands[] = Array('CURLOPT_RETURNTRANSFER',   $filename?false:true);
		$cURL_Commands[] = Array('CURLOPT_NOPROGRESS',       (is_bool($cbOnProgress)&&$cbOnProgress===true)?false:true);
		$cURL_Commands[] = Array('CURLOPT_BUFFERSIZE',       $bufferSize);
		$cURL_Commands[] = Array('CURLOPT_BINARYTRANSFER',   true);
		$cURL_Commands[] = Array('CURLOPT_HEADERFUNCTION',   $_handleHeaders);
		$cURL_Commands[] = Array('CURLOPT_WRITEFUNCTION',    $_handleWrite);
		$_initWrite($cURL_Commands);
		
		$cURL_Handler  = curl_init();
		foreach($cURL_Commands as $cURL_Item){
			(sizeof($cURL_Item) == 2)?
				curl_setopt($cURL_Handler, constant($cURL_Item[0]), $cURL_Item[1]):
				curl_setopt($cURL_Handler, constant($cURL_Item[0]));
		}
		
		$ret = curl_exec($cURL_Handler);
		$_endWrite();
		
		return $ret;
	}
	
	// Private:
	private Function _asSingleArray($array, $qs=": "){
		if(!$array)
			return false;
		
		$ret = Array();
		foreach($array as $item){
			$ret[] = "{$item[0]}{$qs}".urlencode($item[1]);
		}
		return $ret;
	}
	private Function _asQueryString($array, $qs="=", $implode="&"){
		return implode($implode, $this->_asSingleArray($array, $qs));
	}
	
	Function _debugMsg($message, $long_data=false){
		if($this->debug){
			$uid = uniqid();
			echo "<b style='font: 11px Arial'>dBrowser2 - Debug: ".htmlspecialchars($message)."</b> ";
			if($long_data){
				echo "<small><a href='#' onclick=\"document.getElementById('dbr2_{$uid}').style.display = (document.getElementById('dbr2_{$uid}').style.display=='none')?'block':'none'; return false;\">(Mais)</a></small>";
				echo "<br />";
				echo "<pre style='display: none; background: #EEE; border: 1px solid #777; padding: 10px' id='dbr2_{$uid}'>";
				echo htmlspecialchars($long_data);
				echo "</pre>";
			}
			else{
				echo "<br />";
			}
		}
	}
	Function _getInfoAboutUrl($url){
		$ret  = Array();
		$info = parse_url($url);
		
		$ret['scheme'] = isset($info['scheme'])?(($info['scheme']=='https')?"https":"http"):false;
		$ret['host']   = isset($info['host'])  ?$info['host']:false;
		$ret['port']   = isset($info['port'])  ?$info['port']:(($ret['scheme']=='http')?80:443);
		$ret['path']   = isset($info['path'])  ?$info['path']:"";
		$ret['query']  = isset($info['query']) ?"?{$info['query']}":"";
		
		if(!$ret['path'])
			$ret['path'] = "/";
		
		return $ret;
	}
	Function _parseReceived($data, $parseChunked=true){
		$logto = "header";
		$toget = 0;
		$strlog = "";
		$header = Array();
		
		$data = explode("\n", $data);
		for($x = 0; $x < sizeof($data); $x++){
			if($x == 0){
				$parts  = explode(" ", trim($data[$x]));
				$status = Array("version"=>$parts[0], "status"=>$parts[1], "string"=>join(" ", array_slice($parts, 2)));
				continue;
			}
			if($logto == "header"){
				$data[$x] = trim($data[$x]);
				if($data[$x] == ""){
					$logto = "body";
					$body  = join("\n", array_slice($data, $x+1));
					continue;
				}
				$parts = explode(": ", $data[$x]);
				$header[$parts[0]][] = $parts[1];
			}
		}
		
		if(isset($header["Transfer-Encoding"]) && $header["Transfer-Encoding"][0] == "chunked" && $parseChunked)
			$body = $this->_parseChunked($body);
		
		if($status['status'] == 100 && strtolower($status['string']) == 'continue'){
			return $this->_parseReceived($body);
		}
		
		return Array(
			"status"=>$status,
			"header"=>$header,
			"body"  =>$body
		);
	}
	Function _parseChunked ($data){
	    if (!preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', trim($data))) {
	        return $data;
	    }
	    
	    $decoded = '';
	    $encoded = $data;
	    while (true) {
	        $is_chunked = (bool) preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', $encoded, $matches);
	        if (!$is_chunked) {
	            // Looks like it's not chunked after all
	            return $data;
	        }
	
	        $length = hexdec(trim($matches[1]));
	        if($length === 0) {
	            // Ignore trailer headers
	            return $decoded;
	        }
	
	        $chunk_length = strlen($matches[0]);
	        $decoded .= substr($encoded, $chunk_length, $length);
	        $encoded = substr($encoded, $chunk_length + $length + 2);
	
	        if(trim($encoded) === '0' || empty($encoded)) {
	            return $decoded;
	        }
	    }
	    // We'll never actually get down here
	    // @codeCoverageIgnoreStart
	}
	Function _importCookies($headers){
		if($this->clearCookiesBeforeImport){
			$this->go("_importCookies(): Clearing cookies before importing...");
			$this->clearCookies();
		}
		
		$newCookies = Array();
		if(isset($headers['Set-Cookie'])) foreach($headers['Set-Cookie'] as $cookie){
			$tmp = explode("=", $cookie, 2);
			$pos = strpos($tmp[1], ";");
			
			if($pos === 0)
				$tmp[1] = '';
			elseif($pos !== false)
				$tmp[1] = substr($tmp[1], 0, ($pos?$pos:-1));
			
			$this->removeCookie(urldecode($tmp[0]));
			$newCookies[] = Array(urldecode($tmp[0]), trim(urldecode($tmp[1])));
			$this->addCookie(urldecode($tmp[0]), trim(urldecode($tmp[1])));
		}
		
		return $newCookies;
	}
}
