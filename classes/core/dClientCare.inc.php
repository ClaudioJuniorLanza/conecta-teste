<?php
class dClientCare{
	var $token;
	Function __construct($token){
		$this->token = $token;
		$this->wsUrl = dSystem::getGlobal('localHosted')?
			"http://192.168.25.9/im/clientcare/site/api":
			"https://www.clientcare.com.br/api";
	}
	
	Function sendCampanha($dados){
		// Dados:
		// --> token, policy, data_send, campanha, subject, message, paused
		// --> toList[] { to_email, to_name, replaces: {} }
		
		$br = new dBrowser2;
		$br->setTimeout(30);
		foreach($dados as $key=>$value){
			$br->addPost($key, $value);
		}
		$br->addPost('token',  $this->token);
		$br->go("{$this->wsUrl}/sendCampanha.php");
		
		// Retorno esperado:
		// OK:ID_DA_CAMPANHA (ou) (String) ErrorMessage.
		$ret = $br->getBody();
		
		if(substr($ret, 0, 3) == 'OK:'){
			return Array(
				'ok'      => true,
				'campaId' => substr($ret, 3),
			);
		}
		else{
			return Array(
				'ok'       => false,
				'errorMsg' => $ret
			);
		}
	}
	Function getCampanhaStatus($campaId){
		$br = new dBrowser2;
		$br->setTimeout(5);
		$br->addPost('token',  $this->token);
		$br->addPost('campaId', $campaId);
		$body = $br->go("{$this->wsUrl}/getCampanhaStatus.php");
		return @json_decode($body, true);
	}
	Function getCampanhaStatusLink($campaId){
		$br = new dBrowser2;
		$br->setTimeout(5);
		$br->addPost('token',  $this->token);
		$br->addPost('campaId', $campaId);
		$br->addPost('getLink', '1');
		$body = $br->go("{$this->wsUrl}/getCampanhaStatus.php");
		return $body;
	}
}
