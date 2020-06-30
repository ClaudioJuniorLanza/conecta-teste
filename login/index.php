<?php
require_once "config.php";

$use = isset($_GET['providers'])?$_GET['providers']:'google,facebook';
$use = explode(",", $use);

$checked = isset($_GET['checked'])?$_GET['checked']:'';
$checked = explode(",", $checked);

?><!DOCTYPE html>
<html>
	<head>
		<title>Conecta Sementes - Login with Social Media</title>
		<meta charset="UTF-8">
		<style>
			* { box-sizing: border-box }
			body {
				margin:     0;
				padding:    0;
				background: transparent;
			}
			
			.social {
				display:         flex;
				justify-content: center;
				align-items:     center;
				padding-left:    37px;
				padding-right:   8px;
				width:           189px;
				height:          32px;
				margin-top:      16px;
				text-decoration: none;
				color:           #FFF;
			}
			.social:first-of-type{
				margin-top: 0;
			}
			
			.social.facebook {
				background-image: url(images/facebook-login.png);
			}
			.social.google {
				background-image: url(images/google-login.png);
			}
			
			.social.checked {
				background-position-x: -189px;
			}
			.social.empty {
				background-position-x: -378px;
			}
			
			.lds-ring {
				display: inline-block;
				position: relative;
				width: 28px;
				height: 28px;
			}
			.lds-ring div {
				box-sizing: border-box;
				display: block;
				position: absolute;
				width: 20px;
				height: 20px;
				margin: 3px;
				border: 3px solid #fff;
				border-radius: 50%;
				animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
				border-color: #fff transparent transparent transparent;
			}
			.lds-ring div:nth-child(1) {
				animation-delay: -0.45s;
			}
			.lds-ring div:nth-child(2) {
				animation-delay: -0.3s;
			}
			.lds-ring div:nth-child(3) {
				animation-delay: -0.15s;
			}
			@keyframes lds-ring {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}
		</style>
	</head>
	
	<body>
		<? if(@$_GET['display'] != 'none'): ?>
			<? foreach($use as $_use): ?>
				<? if($_use == 'facebook'): ?>
					<a href="#" onclick="doFacebookLogin(); return false" class='social facebook <?=in_array('facebook', $checked)?'checked':''?>' id="btnFacebook"></a>
				<? elseif($_use == 'google'): ?>
					<a href="#" onclick="doGoogleLogin(); return false" class='social google <?=in_array('google', $checked)?'checked':''?>' id="btnGoogle"></a>
				<? endif ?>
			<? endforeach; ?>
		<? endif ?>

<script type="text/javascript">
	var autoCheck   = <?=@$_GET['autoCheck']?'true':'false'?>;
	var autoLoading = <?=@$_GET['autoLoading']?'true':'false'?>;
	var cbUncheck   = <?=@$_GET['cbUncheck']?'true':'false'?>;
	var iframeId    = "<?=@$_GET['instanceId']?>";
</script>
<script>
window.addEventListener('message', function(event){
	var data = event.data;
	if(typeof data != 'object'){
		// Não é objeto.
		return;
	}
	if(typeof data.loginWithImaginacom == 'undefined'){
		// Não tem ".loginWithImaginacom"
		return;
	}
	if(!data.params){
		data.params = [];
	}
	
	if(data.loginWithImaginacom == 'Init'){
		iframeId = data.iframeId;
	}
	if(data.loginWithImaginacom == 'SetChecked'){
		setChecked(data.provider, data.yesno);
	}
	if(data.loginWithImaginacom == 'Request'){
		var source = data.provider;   // 'facebook', 'google'
		var action = data.method;     // 'FB.getLoginStatus', 'FB.api' ou qualquer outro 
		var params = data.params;     // 'me?fields=id,name,email'
		if(source == 'facebook'){
			var _handleFbResponse = function(response){
				var _postData = {
					loginWithImaginacom: 'Response',
					provider:            'facebook',
					iframeId:            iframeId,
					response:            response,
					uid:                 data.uid,
				};
				
				// console.log("Subindo a resposta ", _postData);
				parent.postMessage(_postData, '*');
			};
			params.push(_handleFbResponse);
			eval(data.method).apply(null, params);
		}
	}
});

function setChecked(provider, yesno){
	// true, false or 'loading'
	// Source para o Spinner: https://loading.io/css/
	var ldsRing = "<div class='lds-ring'><div></div><div></div><div></div><div></div></div>";
	var el = document.getElementsByClassName(provider)[0];
	if(yesno === 'loading'){
		removeClass(el, 'checked');
		addClass   (el, 'empty');
		el.innerHTML = ldsRing;
	}
	else if(yesno == true){
		addClass   (el, 'checked');
		removeClass(el, 'empty');
		el.innerHTML = '';
	}
	else{
		removeClass(el, 'checked');
		removeClass(el, 'empty');
		el.innerHTML = '';
	}
}

// Helpers:
function addClass(element, className){
	var arr = element.className.split(" ");
	if(arr.indexOf(className) == -1){
		element.className += " " + className;
	}
}
function removeClass(element, className){
	element.className = element.className.replace(className, "").trim();
}
function hasClass(element, className) {
    return new RegExp('(\\s|^)' + className + '(\\s|$)').test(element.className);
}


// This API is Ready
parent.postMessage({ loginWithImaginacom: 'Ready', iframeId: iframeId }, '*'); // API Ready
</script>

<? if(in_array('facebook', $use)): ?> 
	<script type='text/javascript'>
	// Load Facebook.
	window.fbAsyncInit = function(){
		FB.init({
			appId      : '<?=$FacebookAppId?>',
			cookie     : true,
			xfbml      : true,
			version    : 'v3.2',
		});
		
		// Facebook API Ready
		parent.postMessage({ loginWithImaginacom: 'Ready', provider: 'facebook', iframeId: iframeId }, '*');
	};
	
	function doFacebookLogin(){
		var el = document.getElementsByClassName('facebook')[0];
		if(hasClass(el, 'empty')){
			console.log("Ignorando clique enquanto animação de Loading está em andamento...");
			return false;
		}
		
		var _postData = {
			provider: 'facebook',
			iframeId: iframeId,
		};
		
		if(hasClass(el, 'checked') && cbUncheck){
			// Se for "Uncheck":
			_postData.loginWithImaginacom = 'Uncheck';
			if(autoLoading){
				setChecked('facebook', 'loading');
			}
			parent.postMessage(_postData, '*');
			return;
		}
		
		// Caso contrário, tente realizar o login.
		if(autoLoading){
			setChecked('facebook', 'loading');
		}
		FB.login(function(response){
			_postData.loginWithImaginacom = 'LoginFail';
			_postData.response            = response;
			
			if(response && response.authResponse && response.authResponse.accessToken){
				_postData.loginWithImaginacom = 'LoginOk';
				_postData.token               = response.authResponse.accessToken;
			}
			
			parent.postMessage(_postData, '*');
		});
	}
	</script>
	<script src="https://connect.facebook.net/en_US/sdk.js" async defer></script>
<? endif ?> 

<? if(in_array('google', $use)): ?> 
	<script type='text/javascript'>
	// Load Google Async
	function googleInit(){
		gapi.load('auth2', function(){
			gapi.auth2.init({
				client_id: "<?=$GoogleAppId?>",
			});
			
			// Google API Ready
			parent.postMessage({ loginWithImaginacom: 'Ready', provider: 'facebook', iframeId: iframeId }, '*');
		});
	}
	
	function doGoogleLogin(){
		var el = document.getElementsByClassName('google')[0];
		if(hasClass(el, 'empty')){
			console.log("Ignorando clique enquanto animação de Loading está em andamento...");
			return false;
		}
		
		var _postData = {
			provider: 'google',
			iframeId: iframeId,
		};
		
		if(hasClass(el, 'checked') && cbUncheck){
			// Se for "Uncheck":
			if(autoLoading){
				setChecked('google', 'loading');
			}
			_postData.loginWithImaginacom = 'Uncheck';
			parent.postMessage(_postData, '*');
			return;
		}
		
		if(autoLoading){
			setChecked('google', 'loading');
		}
		
		var _okCallback = function(googleUser){
			var response  = googleUser.getAuthResponse(true);
			_postData.loginWithImaginacom = 'LoginOk';
			_postData.token               = response.id_token;
			_postData.response            = response;
			parent.postMessage(_postData, '*');
		};
		var _failCallback = function(response){
			console.log("Failing, data is: ", response);
			_postData.loginWithImaginacom = 'LoginFail';
			_postData.response            = response;
			parent.postMessage(_postData, '*');
		};
		
		var GA = gapi.auth2.getAuthInstance();
		GA.signIn().then(_okCallback, _failCallback);
	}
	</script>
	<script src="https://apis.google.com/js/platform.js?onload=googleInit" async defer></script>
<? endif ?>
	</body>
</html>
