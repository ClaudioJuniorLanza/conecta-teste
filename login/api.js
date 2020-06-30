/**
	API - Login With IMAGINACOM
**/

var loginWithImaginacom = function(){
	this.callId     = 0;
	this.callQueue  = [];
	this._threads   = [];
	this.iframe     = false;
	this.loaded     = false;
	this.options    = {};
	this.providersLoaded = { // Quais providers estão prontos?
		facebook:   false,
		google:     false,
	};
	
	this.request       = function(provider, method, params, callback){
		if(params instanceof Function && !callback){
			// Parâmetro "params" é opcional.
			callback = params;
			params   = [];
		}
		
		var _uid  = ++this.callId;
		var _data = {
			loginWithImaginacom: 'Request',
			provider: provider,
			method:   method,
			params:   params,
			uid:      _uid,
		};
		if(!callback){
			callback = function(){};
		}
		if(this.providersLoaded[provider]){
			this._execute(_uid, _data, callback);
		}
		else{
			this.callQueue.push({ provider: provider, uid: _uid, data: _data, callback: callback });
		}
	};
	this.setChecked    = function(provider, yesno){
		var data = {
			loginWithImaginacom: 'SetChecked',
			provider:            provider,
			yesno:               yesno, // true | false | 'loading'
		};
		this.iframe.contentWindow.postMessage(data, 'https://www.conectasementes.com.br');
	},
	this.init          = function(iframeId, options){
		// iframeId: ID do iFrame que aponta para http://www.conectasementes.com.br/
		// options:  Os mesmos parâmetros de .create()
		var domIframe = document.getElementById(iframeId);
		this.iframe   = domIframe;
		if(domIframe){
			var iframeDoc = domIframe.contentDocument;
			if(iframeDoc && iframeDoc.readyState  == 'complete'){
				this.loaded = true;
			}
		}
		
		this.options  = options;
		loginWithImaginacom.instances[iframeId] = this;
	};
	this.handleMessage = function(data){
	//	console.log("Received message: ", data);
		if(data.loginWithImaginacom == 'Ready'){
			// O Frame está pronto;
			// O Facebook está pronto;
			// O Google está pronto.
			if(data.provider){
				this.providersLoaded[data.provider] = true;
			}
			this.loaded = true;
			this.runQueue();
			return;
		}
		if(data.loginWithImaginacom == 'Response'){
			// Recebemos uma resposta de uma requisição (.request)
			if(data.provider == 'facebook'){
				this._threads[data.uid].call(this, data.response);
				delete this._threads[data.uid];
				return;
			}
			if(data.provider == 'google'){
				this._threads[data.uid].call(this, data.response);
				delete this._threads[data.uid];
				return;
			}
			return;
		}
		if(data.loginWithImaginacom == 'LoginOk'){
			// O botão de Login foi clicado e o login foi realizado com sucesso!
			var stopLoading = this.options['onLogin'].call(this, data.provider, data.token, data.response);
			if(stopLoading && this.options['autoLoading']){
				this.setChecked(data.provider, this.options.autoCheck?true:false);
			}
			return;
		}
		if(data.loginWithImaginacom == 'LoginFail'){
			// O botão de Login foi clicado e o login foi realizado com sucesso!
			var stopLoading = this.options['onLoginFail'].call(this, data.provider, data.response);
			if(stopLoading && this.options['autoLoading']){
				this.setChecked(data.provider, false);
			}
			return;
		}
		if(data.loginWithImaginacom == 'Uncheck'){
			// O botão de Login foi clicado e o login foi realizado com sucesso!
			var stopLoading = this.options['onUncheck'].call(this, data.provider);
			if(stopLoading && this.options['autoLoading']){
				this.setChecked(data.provider, false);
			}
			return;
		}
	};
	this.runQueue      = function(){
		var _queue    = this.callQueue;
		var _newQueue = [];
		for(var i = 0; i < _queue.length; i++){
			var _item = _queue[i];
			if(this.providersLoaded[_item.provider]){
				this._execute(_item.uid, _item.data, _item.callback);
				// console.log("Executando ", _item.provider, this.providersLoaded);
			}
			else{
				// Provider não está pronto, mantenha na fila.
				// console.log("Postergando ", _item.provider, this.providersLoaded);
				_newQueue.push(_item);
			}
		}
		
		this.callQueue = _newQueue;
	};
	this._execute      = function(uid, data, callback){
		this._threads[uid] = callback;
		this.iframe.contentWindow.postMessage(data, 'https://www.conectasementes.com.br');
	};
};

// Static
loginWithImaginacom.instances  = {};
loginWithImaginacom.instanceId = 1;
loginWithImaginacom.create = function(divId, options){
	var divEl = divId;
	if(typeof divId == 'string'){
		divEl = document.getElementById(divId);
	}
	else if(divId && divId.tagName){
		divEl = divId;
	}
	
	if(!options){
		alert("Login with IMAGINACOM: Options not provided on .create()");
		return false;
	}
	if(options instanceof Function){
		options = {
			onLogin: options,
		};
	}
	
	if(!options.onLogin){
		options.onLogin = function(provider, token, rawData){
			alert("Login with IMAGINACOM: .create({ onLogin: function(...){ Not implemented! } });");
			return true;
		};
	}
	if(!options.onLoginFail){
		options.onLoginFail = function(provider, rawData){
			if(provider == 'google'){
				if(rawData.error == 'popup_closed_by_user'){
					alert("Você fechou a popup sem autenticar no Google");
					return true;
				}
				if(rawData.error == 'access_denied'){
					alert("Você não autorizou o login pelo Google.");
					return true;
				}
				if(rawData.error == 'immediate_failed'){
					alert("Você bloqueou o acesso pela conta do Google.\nAcesse sua conta do Google e remova o bloqueio.");
					return true;
				}
				alert("Acesso negado pela conta do Google");
				return true;
			}
			if(provider == 'facebook'){
				alert("Login negado pelo Facebook");
				return true;
			}
			return true;
		};
	}
	if(!options.onUncheck){
		options.onUncheck = false;
	}
	if(!options.providers) options.providers = 'google,facebook';
	if(!options.checked)   options.checked   = '';
	if(!options.display)   options.display   = false;
	if(!options.token)     options.token     = false;
	if(options.autoCheck   == null) options.autoCheck   = true;
	if(options.autoLoading == null) options.autoLoading = true;
	
	var _providers = options.providers.split(",");
	var _setHeight = 32 + ((_providers.length-1) * 48);
	var _iframeId  = "login-with-imaginacom-"+(loginWithImaginacom.instanceId++);
	var _theUrl    = "https://www.conectasementes.com.br/login/";
	var _style     = ((options.display=='none')?" style='display: none'":"");
	
	var _params    = [];
	if(options.token){
		_params.push("token="+options.token);
	}
	if(options.providers){
		_params.push("providers="+options.providers);
	}
	if(options.checked){
		_params.push("checked="+options.checked);
	}
	_params.push("autoLoading="+(options.autoLoading?'1':'0'));
	_params.push("cbUncheck="+(options.onUncheck?'1':'0'));
	_params.push("instanceId="+_iframeId);
	_theUrl += "?"+_params.join("&");
	
	var writeIframe = "<iframe id='"+_iframeId+"' src='"+_theUrl+"' "+_style+" frameborder='0' allowtransparency='true' scrolling='no' width='189' height='"+_setHeight+"'></iframe>";
	if(divEl){
		divEl.innerHTML = writeIframe;
	}
	else{
		document.write(writeIframe);
	}
	
	var lwi = new loginWithImaginacom;
	lwi.init(_iframeId, options);
	
	return lwi;
};


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
	
	loginWithImaginacom
		.instances[data.iframeId]
		.handleMessage(data);
});
