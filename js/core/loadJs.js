/**
	loadJs.js - IMAGINACOM 2017
	--------------------------------------------
	- Método loadJs     permite carregar javascripts de forma async (e utilizar callback)
	- Método loadJquery permite carregar o jQuery e seus módulos de forma async.
	- Em desenvolvimento, utilize <scrip t src='js/core/loadJs.js'></scrip t> para obter debug
	- Em produção, escreva inline o conteúdo de js/core/loadJs.min.js para evitar render-blocking.
	- Estes métodos *não suportam* scripts em outros domínios (CDN), pois usam o XMLHttpRequest.
	- Insira "$.noConflict(); if(!$) $ = jQuery;" imediatamente no final do jQuery-Core e jQuery-Core.min.
	
	loadJs(filename, callbackAfter, callbackBefore)
		@param string   filename
		@param callback callbackAfter (filename, isBefore);
		@param callback callbackBefore(filename, isBefore);
		
		Carrega o script em questão via XMLHttpRequest e executa-o utilizando o EVAL().
	
	loadJquery(filename)
		@param string   filename
		
		Carrega o jQuery-Core e todos os módulos necessários para o bom funcionamento das páginas.
		A primeira chamada de loadJquery **deve** conter o jQuery-Core (ex: jquery-1.8.2.min.js).
		Essa versão do jQuery-Core **deve** terminar, obrigatóriamente, com "$.noConflict(); ...".
		O jQuery-Core pode ou não estar minified ou mesclado com outros plugins.		
		
		A primeira chamada a loadJquery será carregada imediatamente, de forma Async.
		As demais chamadas ao loadJquery só começarão a ser chamada assim que o jQuery-Core
		estiver carregado (a primeira chamada for concluída) e houver um document.ready.
		
		Enquanto não forem carregados o jQuery-Core, todos os módulos e houver document.ready,
		todos os chamados ao "$()" serão armazenados numa fila, para execução posterior. Isso
		garantirá que os chamados ao "$" só serão executados após todos os módulos carregados.
		
		Plugins que se declaram com "$.fn." sem aguardar o document.ready apresentarão
		problemas e deverão ser envolvidos em $(function(){ $.fn.... });
		
		Fluxograma:
		1. $          --> Todos os chamados no padrão $(function(){}) serão queued, para depois
		2. loadJquery --> No primeiro chamado, carregará a biblioteca principal de forma async.
		3. loadJquery --> Nos demais chamados, carregará módulos relacionados.
		4. document.ready --> Quando ocorrer, aguardará até que o jQuery-Core e os plugins estejam
		   totalmente carregados.
		5. Todos os eventos chamados com "$(function(){})" serão executados conforme o esperado.
		6. Futuros chamados a $() já serão direcionados diretamente ao jQuery.
	
	Atualizações:
		2017-01-02: Initial Release
	
	Dificuldades/bugs ao desenvolver este script:
		1. Ocasionalmente, o _onLoadMain era chamado mesmo antes do <body> terminar de ser carregado.
		Isso fazia com que o _callQueued fosse chamado mesmo antes de alguns comandos loadJquery(),
		causando que alguns plugins fossem chamados antes de estarem carregados. Isso foi resolvido
		movendo o conteúdo de _onLoadMain (ou seja, os plugins e a _queue) para o document.ready. Dessa
		forma, só vamos carregar os plugins e rodar a fila de comandos depois de ter certeza que o HTML
		foi inteiramente carregado e processado.
		
		2. Ao se mesclar o jquery.js e plugins no mesmo script, a variável $() executado por plugins
		utilizava efetivamente o próprio jQuery, associando chamadas ao document.ready. No entanto,
		ocasionalmente o document.ready ocorria *antes* do término de carregamento dos plugins. Nesse
		caso, se aquele plugin tentasse declarar uma função ($.fn.), ele teria um erro, pois enquanto
		os plugins não são carregados, $ sempre será um alias para a fila. Isso foi resolvido adicionando
		$.noConflict() no final do jquery.js e jquery.min.js.
	
	Como minificar este script:
 		1. Remove the first block that defines dJQLLog
		2. Remove all dJQLLog calls
		3. Remove whole method '_loadDebug' and the first line of '_load'
		4. Use the following replace table
			_dJqueryLoader	--> _
			_priority		--> a
			_queue1			--> b
			_queue2			--> c
			_toLoad			--> d
			_nToLoad		--> e
			_loaded			--> f
			_onLoadMain		--> g
			_onLoadModules	--> h
			_callQueued		--> i
			_load			--> j
		5. Copy n paste to https://jscompress.com
**/

if(!(document.cookie.indexOf("loadJs-debug=1")!=-1)){
	console.log(
		"IMAG-DEBUG: loadJs. To enable, use: ",
		"document.cookie = 'loadJs-debug=1'"
	);
	dJQLLog = function(){};
}
else{
	dJQLLog = function(){
		console.log.apply(null, arguments);
	}
}

_dJqueryLoader = {
	$: function(toQueue){
		_dJqueryLoader._priority?
			_dJqueryLoader._queue1.push(toQueue):
			_dJqueryLoader._queue2.push(toQueue);
	},
	_priority: 0, // Calls to '$' should be PREPENDED, not appended (default)
	_queue1: [],
	_queue2: [],
	_toLoad: [],
	_nToLoad: 0,
	_loaded:  0, // 0=No, -1=Loading, 1=Loaded
	_onLoadMain:    function(fn){
		// O $.noConflict() deve ter sido chamado diretamente pelo jQuery-main.
		// 
		// Vamos carregar os plugins *apenas* depois do document.ready(), para evitar
		// que a "fila" de plugins termine de carregar antes de tomarmos conhecimento
		// sobre outros plugins mais a frente, o que ocasionaria erros (queue2 sendo
		// processada antes do carregamento de plugins).
		dJQLLog("_onLoadMain: jQuery Core ("+fn+") foi carregado com sucesso.");
		jQuery(function(){
			_dJqueryLoader._loaded = 1;
			if(!_dJqueryLoader._nToLoad){
				return _dJqueryLoader._callQueued();
			}
			
			dJQLLog("_onLoadMain: Ainda preciso carregar "+_dJqueryLoader._toLoad.length+" módulos antes de processar a fila");
			var tl;
			while(tl = _dJqueryLoader._toLoad.shift()){
				_dJqueryLoader._load(tl, _dJqueryLoader._onLoadModules, _dJqueryLoader._onLoadModules);
			}
		});
	},
	_onLoadModules: function(modName, isBefore){
		_dJqueryLoader._priority = isBefore;
		if(isBefore){
			dJQLLog("_onLoadModules: Executando "+modName+"... Faltam: "+(_dJqueryLoader._nToLoad-1));
			return;
		}
		if(--_dJqueryLoader._nToLoad){
			return false;
		}
		_dJqueryLoader._callQueued();
	},
	_callQueued:    function(){
		dJQLLog("_callQueued: jQuery e módulos estão prontos. PluginQueue="+(_dJqueryLoader._queue1.length)+", UserQueue="+(_dJqueryLoader._queue2.length));
		$ = jQuery;
		var fq = _dJqueryLoader._queue1.concat(_dJqueryLoader._queue2);
		var q;
		while(q = fq.shift()){
			$(q);
		}
		dJQLLog("_callQueued: All done.");
	},
	_loadDebug:     function(filename, callbackAfter, callbackBefore){
		// Na hora de minificar, remova este método e remova a primeira linha do método _load().
		var script = document.createElement("script");
		script.onload = function(){
			if(callbackBefore){
				callbackBefore(filename, 1);
			}
			if(callbackAfter){
				callbackAfter(filename, 0);
			}
		}
		script.src = filename;
		document.head.appendChild(script);
	},
	_load:          function(filename, callbackAfter, callbackBefore){
		return _dJqueryLoader._loadDebug(filename, callbackAfter, callbackBefore);
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function(){
			if(this.readyState == 4){ // && this.status == 200){
				if(callbackBefore){
					callbackBefore(filename, 1);
				}
				try{
					(function(data){ window["eval"].call(window, data)})(this.responseText);
				}
				finally{
					if(callbackAfter){
						callbackAfter  (filename, 0);
					}
				}
			}
		};
		xhttp.open('GET', filename, true);
		xhttp.send();
	},
};
loadJquery = function(filename){
	// 1st call: Load only this request
	// 2nd..nth: Load everything else simultaneuously
	// If everything is loaded: Load immediately
	if(!_dJqueryLoader._loaded){
		_dJqueryLoader._loaded = -1;
		return _dJqueryLoader._load(filename, _dJqueryLoader._onLoadMain);
	}
	
	if(_dJqueryLoader._loaded == -1){
		_dJqueryLoader._nToLoad++;
		return _dJqueryLoader._toLoad.push(filename);
	}
	
//	else if(_dJqueryLoader._loaded == 1)
		return _dJqueryLoader._load(filename, function(){
			dJQLLog("Loaded a little late... ", filename);
		});
}
loadJs = _dJqueryLoader._load;

if(typeof $ == 'undefined') $ = _dJqueryLoader.$;
