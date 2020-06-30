/**
	dSmartMap --> 01/02/2015
	
	27/11/2015
	* Bugfix: MouseOver não funciona no iPhone
	
	01/02/2015
	+ Novos eventos 'dSmartMap.mouseover' e 'dSmartMap.mouseout' (use $('area').on(evento)).
	* Eventos de 'click' agora são encaminhados ao <area> correspondente.
	* Removido suporte ao cbOnClick, pois o mesmo nunca funcionou corretamente.
	* Bugfix do highlight não apagando quando o mouse passava muito rápido
	
	29/03/2013
	* Bug no HTML, se <img> tivesse display inline (padrão), a posição relativa do DIV não era
	  obedecida com a realidade. Por padrão agora, se for inline, vai ser convertida para inline-block.
	* Bug sério com o IE. Sempre que um elemento for excluído dentro de um <DIV>, todos os
	  elementos após aquele terão suas propriedades top/left retornadas a 0,0.
	  
	  Descrição técnica:
	  - Temos um DIV contendo várias vezes a mesma imagem.
	  - A primeira imagem é armazenada na memória. As demais são apenas reutilizadas.
	  - Se a primeira imagem é excluída, então a segunda (agora primeira) é recarregada.
	  - Esse recarregamento força com que as propriedades scrolltop,scrollleft voltem para 0,0.
	  - Quando um novo elemento é criado ANTES da primeira imagem, o problema não ocorre.
	  - Para resolver isso, pré-carregamos dois elementos invisíveis de cada <div relative>.
	
	17/02/2013
	* Ao aplicar zoom no Chrome, a sobreposição saía do lugar;
	* Erro com variável autoInit ao iniciar uma imagem;
	
	Usage:
		(img) .dSmartMap(srcOver[, srcSel[, jqoAreaSel]])           --> OK! (Inicia o map e a imagem ao mesmo tempo)
		(img) .dSmartMap(srcOver[, srcSel])                         --> OK! (Inicia o map e a imagem ao mesmo tempo)
		(img) .dSmartMap(jqoArea[, multiple=false]);                --> OK! (Define qual AREA deve estar selecionada)
		(img) .dSmartMap(false);                                    --> OK! (Des-seleciona tudo)
		(img) .dSmartMap();
		(img) .dSmartMap('destroy');                                --> OK! (Remove a funcionalidade da imagem)
		
		(map) .dSmartMap(srcOver[, srcSelected])                    --> OK! (Inicia ou reinicia o dSmartMap num map)
		(map) .dSmartMap(jqoAreaSelected)                           --> OK! (Seleciona a AREA em todas as imagens relacionadas)
		(map) .dSmartMap([false])                                   --> OK! (Des-seleciona em todas as imagens relacionadas)
		(map) .dSmartMap('destroy')                                 --> OK! (Desativa o dSmartMap no map e em todas as imagens relacionadas)
		(map) .dSmartMap()                                          --> 
		
		(area).dSmartMap(jqoImg[, true])                            --> OK! (Seleciona a área em questão na imagem desejada)
		(area).dSmartMap(jqoImg, false)                             --> OK! (Des-seleciona ...)
		(area).dSmartMap([true])                                    --> OK! (Seleciona a área em questão em todas as imagens relacionadas)
		(area).dSmartMap(false)                                     --> OK! (Des-seleciona)
	
	Example:
		$("img").dSmartMap('images/menu_over.png', 'images/menu_sel.png', $("area[href='index.php']"));
	
	Funcionamento técnico:
		- Os seguintes elementos são iniciados: jqoMap, jqoArea e jqoImg (.data('dSmartMap'))
		- jqoMap .data('dSmartMap'): { isLoaded, cbOnLoad, jqoImgOver, jqoImgSel, autoInitImages }
		- jqoArea.data('dSmartMap'): { jqoMap,   offset,   size }
		- jqoImg .data('dSmartMap'): { 
		
		- Inicialização do jqoMap:
			1. Faz preload das imagens relacionadas;
		
		- Inicialização do jqoArea:
			1. Faz um parse em "coords" para extrair 'offset' e 'size'
			2. Adiciona os listeners _areaOver e _areaOut ao <area>
		
		- Inicialização do jqoImg:
			1. Adiciona um "<div style='position: relative z-index: 9'>" ao redor da imagem em questão
			2. Modifica a imagem em questão para 'inline-block'
		
		- Como funciona o overlay (método _buildHighlight)
			1. Chama _clearHighlight para desativar qualquer outro item aceso;
			2. Cria os elementos <div><a>(jqoImg)</a></div>
			3. jqoLink (<a>):  Copia os atributos href e target, e delega o evento 'click'.
			4. jqoDiv (<div>): 
			   1. É posicionado no final do <div> criado na inicialização do jqoImg;
			   2. Tem seu tamanho exato e posições definidas
			   3. Tem sua transparência definida (opacity: 0.01)
			   4. É movido com scrollTop e scrollLeft para exibir a porção final da imagem
			   5. Inicia a sua exibição animada (animate: opacity: 1)
			   6. Se for criado com "isSel", attach os eventos '_hookOver' e '_hookOut'
			   7. Se for criado sem "isSel", attach eventos _hlOver e _hlOut.
**/

(function($){
	var __jqoAreaOver  = false;
	var __jqoImages    = false;
	
	// Data:
	// - dMap:  jqoImgOver, jqoImgSel, isLoaded, cbOnLoad[], autoInitImages
	// - dImg:  jqoMap, jqoDiv, jqoSel[]
	// - dArea: jqoMap, offset, size
	var _initMap    = function(jqoMap, srcOver, srcSel, autoInitImages){
		if(_inited(jqoMap)){
			//~ console.log("_initMap (ignored, already intialized)");
			return;
		}
		//~ console.log("_initMap");
		
		// 1. Initialize myself!
		var dMap   = {};
		jqoMap.data('dSmartMap', dMap);
		
		// 2. Initialize areas (data and events)
		var jqoAreas = $("area", jqoMap);
		jqoAreas.each(function(){
			var coords = $($(this).attr("coords").split(/, */)).map(function(){ return parseInt(this) });
			$(this)
				.data('dSmartMap', {
					jqoMap:  jqoMap,
					offset:  { left: coords[0], top: coords[1] },
					size:    { w: coords[2] - coords[0] + 1, h: coords[3] - coords[1] + 1 }
				})
				.mouseout (_areaOut)
				.mouseover(_areaOver);
		});
		
		// 3. Preload images, and try to find correct image size
		var jqoImg = $("img[usemap='#"+jqoMap.attr('name')+"']").first(),
		    jqoImgOver = $("<img border='0' />").attr('border', '0'),
		    jqoImgSel  = jqoImgOver.clone();
		
		// 3.1. Do we know the image size, or will we have to wait for it to load?
		dMap.isLoaded = false;
		if(jqoImg.width() && jqoImg.height()){
			//~ console.log("_initMap: Size was implicit in document: ", jqoImg[0].width + "x" + jqoImg[0].height);
			jqoImgOver.css('width',  jqoImg[0].width);
			jqoImgSel .css('width',  jqoImg[0].width);
			jqoImgOver.css('height', jqoImg[0].height);
			jqoImgSel .css('height', jqoImg[0].height);
			dMap.isLoaded  = true;
		}
		else{
			//~ console.log("_initMap: Waiting to catch the correct size....");
			jqoImgOver.load(function(){
				//~ console.log("_initMap: Got it! Image size is ", jqoImgOver[0].width + "x" + jqoImgOver[0].height);
				jqoImgOver.css('width', jqoImgOver[0].width);
				jqoImgSel .css('width', jqoImgOver[0].width);
				jqoImgOver.css('height', jqoImgOver[0].height);
				jqoImgSel .css('height', jqoImgOver[0].height);
				jqoImgOver.off('load');
				dMap.isLoaded     = true;
				
				while(dMap.cbOnLoad.length){
					(dMap.cbOnLoad.shift())();
				}
			});
		}
		jqoImgOver.attr('src', srcOver);
		jqoImgSel.attr ('src', srcSel?srcSel:srcOver);
		
		// 4. Save data the map
		dMap.cbOnLoad    = [];
		dMap.jqoImgOver  = jqoImgOver;
		dMap.jqoImgSel   = jqoImgSel;
		dMap.autoInitImages = autoInitImages;
	}
	var _destroyMap = function(jqoMap){
		//~ console.log("_destroyMap");
		if(!_inited(jqoMap)){
			return;
		}
		
		// 1. Desativa todos os eventos de mouseover e mouseout
		$("area", jqoMap)
			.removeData('dSmartMap')
			.off('mouseover', _areaOver)
			.off('mouseout',  _areaOut);
		
		// 2. Remove qualquer seleção existente (mouseover)
		var dMap = jqoMap.data('dSmartMap');
		if(__jqoAreaOver && __jqoAreaOver.jqoMap.is(jqoMap)){
			// Tem mouseover something of this map?
			_clearHighlight();
		}
		
		// 3. Destrói as imagens já inicializadas (remove o <div> delas)
		$("img[usemap='#"+(jqoMap.attr('name'))+"']").dSmartMap('destroy');
		
		// 4. Exclui todas as informações ainda existentes.
		jqoMap.removeData('dSmartMap');
	}
	
	var _initImg    = function(jqoImg){
		if(_inited(jqoImg)){
			//~ console.log("_initImg (ignored, already intialized)");
			return;
		}
		//~ console.log("_initImg");
		
		var dImg = {};
		jqoImg.data('dSmartMap', dImg);
		
		// Fixes bug in Firefox and IE9:
		if(jqoImg.css('display')=='inline')
			jqoImg.css('display', 'inline-block');
		
		dImg.jqoMap     = $("map[name='"+(jqoImg.attr('usemap').substr(1))+"']").first();
		dImg.jqoSel     = new $;
		dImg.jqoDiv     = $("<div>")
			.css('display', jqoImg.css('display'))
			.css('position', 'relative')
			.css('z-index', 9)
			.insertBefore(jqoImg)
			.append(jqoImg);
		
		if($.browser.msie){
			//~ console.log("_initImg: IE's memory handling bugfix");
			dImg.jqoDiv
				.append(dImg.jqoMap.data('dSmartMap').jqoImgSel.clone().css('display', 'none'))
				.append(dImg.jqoMap.data('dSmartMap').jqoImgOver.clone().css('display', 'none'))
		}
		if(!__jqoImages){
			__jqoImages = jqoImg;
			_listenResize(true);
		}
		else{
			__jqoImages = __jqoImages.add(jqoImg);
		}
	}
	var _destroyImg = function(jqoImg){
		//~ console.log("_destroyImg ("+jqoImg.length+")");
		if(!_inited(jqoImg)){
			//~ console.log("_destroyImg: Not inited!");
			return;
		}
		
		var dImg = jqoImg.data('dSmartMap');
		var dMap = dImg.jqoMap.data('dSmartMap');
		
		// Se tiver mouse sobre algo nesta imagem, vamos des-selecionar.
		if(__jqoAreaOver && __jqoAreaOver.jqoImg.is(jqoImg)){
			_clearHighlight(jqoImg);
		}
		
		// Se tiver algo selecionado, remova.
		dImg.jqoSel.remove();
		
		// Remove o <div> que estava encapsulando a imagem
		// NOVO:
		// jqoImg.insertAfter(dImg.jqoDiv);
		// dImg.jqoDiv.remove();
		// ANTES:
		// jqoImg.unwrap(dImg.jqoDiv);
		var jqoDiv = dImg.jqoDiv;
		jqoImg.insertAfter(jqoDiv);
		jqoDiv.remove();
		jqoImg.removeData('dSmartMap');
		
		__jqoImages = __jqoImages.not(jqoImg);
		if(!__jqoImages.length){
			__jqoImages = false;
			_listenResize(false);
		}
	}
	
	var _inited   = function(jqoEl){
		return !!(jqoEl.data('dSmartMap'));
	}
	var _delayed  = function(jqoMap, cbAction){
		// Código a ser executado apenas depois que a imagem do jqoMap esteja carregada.
		var dMap = jqoMap.data('dSmartMap');
		if(!dMap.isLoaded){
			dMap.cbOnLoad.push(cbAction);
			return true;
		}
		
		cbAction();
		return false;
	}
	
	var _listenResize = function(yesno){
		//~ console.log("_listenResize: Listening resize: "+(yesno?"Yes":"No"));
		yesno?
			$(window).on ('resize', _onResize):
			$(window).off('resize', _onResize);
	}
	
	// Hook events:
	var _hookOver = function(ev, jqoArea, from){
		//~ console.log("_hookOver (from="+from+")");
		jqoArea.trigger('dSmartMap.mouseover');
	};
	var _hookOut  = function(ev, jqoArea, from){
		//~ console.log("_hookOut (from="+from+")")
		jqoArea.trigger('dSmartMap.mouseout');
		_clearHighlight();
	};
	
	// Handle events:
	// --> There are two events that happens here:
	//     1. Area MouseOver. This happens when mouse is over the <area>.
	//     2. HL MouseOver.   This happens when mouse is over the hightlight element (usually, <img>)
	// --> The _hookOver is meant to unify both of these events into one single place, so you can
	//     manipulate mouseOver and mouseOut without having to handle different elements.
	// 
	// Usually, browser threats events in the following order:
	// _areaOver, _areaOut, _hlOver, _hlOut
	// * When mouse moves too fast, _hlOver and _hlOut may not be called.
	// 
	// _areaOver: Always trigger _hookOver, no matter what.
	// _hlOver:   Only trigger   _hookOver if _areaOver has not been called.
	// _hlOut:    Always trigger _hookOut,  no matter what.
	// _areaOut:  Only trigger   _hookOut  if _hlOut    has not been called.
	var _areaOver = function(ev){
		if(/iPad|iPhone|iPod/.test(navigator.platform)){
			return false;
		}
		
		// _areaOver:
		// --> Area is not highlighted, so we need to build the highlight element.
		// --> Always throw _hookOver(ev).
		//~ console.log("_areaOver");
		
		// Call _buildHighlight
		var jqoArea = $(ev.target);
		var jqoImg  = _findRelatedImage(jqoArea, ev);
		if(!jqoImg){
			return false;
		}
		_buildHighlight(jqoImg, jqoArea);
		
		// Throw _hookOver.
		_hookOver(ev, jqoArea, 'areaOver');
	};
	var _areaOut  = function(ev){
		// If leaving <area> to enter jqoOver, then we don't need
		// to trigger the _hookOut, as it will be triggered by the
		// _hlOut event.
		//~ console.log("_areaOut");
		if($(ev.toElement).closest('div').is(__jqoAreaOver.jqoOver)){
			//~ console.log("_areaOut: Delegating out event to _hlOut.");
			// Do nothing.
		}
		else{
			//~ console.log("_areaOut: _hlOut not found, calling _hookOut.");
			_hookOut(ev, $(ev.target), 'areaOut');
		}
	}
	var _hlOver   = function(ev){
		var jqoDivOver = $(this);
		if(!_inited(jqoDivOver)){
			//~ console.log("_hlOver --> Nao iniciado!");
			return;
		}
		
		var dDiv = jqoDivOver.data('dSmartMap');
		jqoDivOver
			.stop()
			.animate({ opacity: 1 });
		
		if(__jqoAreaOver){
			_clearHighlight();
		}
		
		__jqoAreaOver = {
			jqoOver: jqoDivOver,
			jqoMap:  dDiv.jqoMap,
			jqoImg:  dDiv.jqoImg,
			jqoArea: dDiv.jqoArea
		};
		
		if(ev.relatedTarget.tagName == 'AREA'){
			// Over event is beign called right after _areaOver.
			// So, we need to ignore it, as _hookOver was already called.
		}
		else{
			_hookOver(ev, __jqoAreaOver.jqoArea, 'hlOver');
		}
	}
	var _hlOut    = function(ev){
		//~ console.log("_hlOut");
		if(__jqoAreaOver.jqoArea)
			_hookOut(ev, __jqoAreaOver.jqoArea, 'hlOut');
	};
	var _onResize = function(){
		$(__jqoImages).each(function(){
			var jqoImg = $(this);
			var jqoSel = $(this).data('dSmartMap').jqoSel;
			jqoSel.each(function(){
				var jqoAreaSel = $(this).data('dSmartMap').jqoArea;
				jqoImg.dSmartMap(false).dSmartMap(jqoAreaSel);
				
			});
		});
	}
	
	// Do actions:
	var _buildHighlight   = function(jqoImg, jqoArea, isSel){
		if(!_inited(jqoArea)){
			//~ console.log("_buildHighlight - Área (e consequentemente, o map) não inicializados.");
			return false;
		}
		if(!isSel){
			//~ console.log("_buildHighlight", jqoImg[0]);
		}
		
		var dArea  = jqoArea.data('dSmartMap'),
		    jqoMap = dArea.jqoMap,
		    dMap   = dArea.jqoMap.data('dSmartMap'),
			dImg   = jqoImg.data('dSmartMap');
		
		if(!dImg && !dMap.autoInitImages){
			//~ console.log("_buildHighlight: Image is not initialized");
			return;
		}
		else if(!dImg){
			//~ console.log("_buildHighlight: Auto initializing image");
			_initImg(jqoImg);
			return _buildHighlight(jqoImg, jqoArea, isSel);
		}
		
		if(__jqoAreaOver){
			// Mouse over happened before a mouseout..
			// Let's assume that THERE WAS a mouseout missed by the browser.
			_clearHighlight();
		}
		
		var jqoLink = $("<a>")
			.append(isSel?dMap.jqoImgSel.clone():dMap.jqoImgOver.clone())
			.attr('href',   jqoArea.attr('href'  ))
			.attr('target', jqoArea.attr('target'))
			.bind("click",  function(ev){
				jqoArea.trigger('click', ev);
				return false;
			});
		
		var jqoDivOver = $("<div>")
			.appendTo(dImg.jqoDiv) // insertAfter(jqoImg) melhora uma parte do problema
			.append  (jqoLink)
			.css('overflow', 'hidden')
			.css('width',  dArea.size.w)
			.css('height', dArea.size.h)
			.css('left',   dArea.offset.left)
			.css('top',    dArea.offset.top )
			.css('opacity', 0.01)
			.show()
			.css('position', 'absolute')
			.scrollLeft(dArea.offset.left)
			.scrollTop (dArea.offset.top);
		
		if(!isSel){
			//~ console.log("_buildHighlight --> Is mouse over event");
			jqoDivOver
				.css('z-index', 10)
				.mouseout  (_hlOut)
				.mouseover(_hlOver)
				.animate({ opacity: 1 }, 'fast', function(){
					$(this).data('dSmartMap', {
						// Initialize only on end of animation.
						jqoImg:  jqoImg,
						jqoMap:  jqoMap,
						jqoArea: jqoArea
					})
				});
			
			__jqoAreaOver = {
				jqoOver: jqoDivOver,
				jqoMap:  jqoMap,
				jqoImg:  jqoImg,
				jqoArea: jqoArea
			};
		}
		else{
			//~ console.log("_buildHighlight --> Is permanent (selected)");
			jqoDivOver
				.css('z-index', 11)
				.data('dSmartMap', {
					// Initialize immediatly, because _clearHighlight depends on it (even if we put some animation here)
					jqoImg:  jqoImg,
					jqoMap:  jqoMap,
					jqoArea: jqoArea
				})
				.css('opacity', 0.01)
				.animate({ opacity: 1 }, 'fast')
				.mouseover(function(ev){ _hookOver(ev, jqoArea, 'permSel.mouseOver'); })
				.mouseout(function(ev){  _hookOut(ev,  jqoArea, 'permSel.mouseOut'); });
			
			var lbef = (dImg.jqoSel.length);
			dImg.jqoSel = dImg.jqoSel.add(jqoDivOver);
			//~ console.log("_buildHighlight: Just created, my length is "+(dImg.jqoSel.length)+", was "+lbef+" before.");
		}
	}
	var _clearHighlight   = function(jqoImg, jqoArea){
		// false,  false   happens when mouse is out. Persistent, if any, should continue.
		// jqoImg, false   happens when a persistent hightlight is placed
		// jqoImg, jqoArea happens when deselecting some specific area.
		if(!jqoImg){
			//~ console.log("_clearHighlight --> Attempting to clear everything from the last active image");
			if(__jqoAreaOver){
				__jqoAreaOver.jqoOver.animate({ opacity: 0.1 }, function(){
					//~ console.log("_clearHighlight:afterHide --> Clearing jqoAreaOver (which was a "+this.tagName+")");
					$(this).remove();
				});
				__jqoAreaOver = false;
			}
			return;
		}
		
		var dImg = jqoImg.data('dSmartMap');
		if(!jqoArea || !jqoArea.length){
			//~ console.log("_clearHighlight --> Attempting to clear all highlights for that image", _inited(jqoImg));
			// dImg.jqoSel.fadeOut('fast', function(){ $(this).remove(); });
			dImg.jqoSel.remove();
			dImg.jqoSel = new $;
		}
		else if(dImg.jqoSel.length){
			//~ console.log("_clearHighlight --> Clearing all highlights, EXCEPT the permanent-selected.");
			dImg.jqoSel.each(function(){
				if(!_inited($(this))){
					//~ console.log("_clearHighlight: I should have been initialized on _buildHighlight!!");
					return;
				}
				if($(this).data('dSmartMap').jqoArea.is(jqoArea)){
					//~ console.log("_clearHighlight (Removing object)");
					dImg.jqoSel = dImg.jqoSel.not($(this));
					// $(this).fadeOut('fast', function(){ $(this).remove(); });
					$(this).remove();
					return false;
				}
				return;
			});
		}
		return false;
	}
	var _select           = function(jqoImg, jqoArea){
		if(jqoArea){
			var jqoMap = jqoArea.data('dSmartMap').jqoMap;
			var dMap   = jqoMap.data('dSmartMap');
			_buildHighlight(jqoImg, jqoArea, true);
		}
		else{
			_clearHighlight(jqoImg);
		}
		return false;
	}
	
	var _findRelatedImage = function(jqoArea, ev){
		// 1. Existe ev.relatedTarget, e é a imagem correta?
		// 2. Procure pela localização.
		var evR    = ev.relatedTarget;
		if(evR && evR.tagName == 'IMG'){
			var $evR = $(evR);
			if($evR.attr('usemap') == "#"+jqoArea.closest('map').attr('name') && _inited($evR)){
				return $evR;
			}
		}
		
		var jqoImg = false;
		$("img[usemap='#"+jqoArea.closest('map').attr('name')+"']").each(function(){
			var myPos  = $(this).offset();
			if(ev.pageX >= myPos.left && ev.pageX <= myPos.left + $(this).width()){
				if(ev.pageY >= myPos.top && ev.pageY <= myPos.top + $(this).height()){
					jqoImg = $(this);
					return false;
				}
			}
		});
		return jqoImg;
	}
	$.fn.dSmartMap = function(param1, param2, param3, param4){
		// Variações aceitas:
		// $("<map>") .dSmartMap(imgOver[, jqoAreasSelected])
		// $("<map>") .dSmartMap(imgOver[, imgSel[, jqoAreasSelected]])
		// $("<area>").dSmartMap([jqoImg, ]true/false)
		// $("<area>").dSmartMap(true/false)
		
		$(this).each(function(){
			var t  = this;
			var $t = $(t);
			if(t.tagName == 'IMG'){
				if(!$t.attr('usemap'))
					return;
				
				var jqoMap   = $("map[name='"+($t.attr('usemap').substr(1))+"']").first();
				if((typeof param1) == 'string'){  // 1, 4
					if(param1 == 'destroy'){
						if(!_inited($t)){
							return;
						}
						
						_destroyImg($t);
						return;
					}
					
					_initMap(jqoMap, param1, param2, false); // FALSE = autoInitImages
					_initImg($t);
					
					if((typeof param3) == 'boolean'){
						$t.dSmartMap(param3);
					}
					else if((typeof param3) == 'function'){
						jqoMap.dSmartMap(param3);
					}
					if((typeof param4) == 'function'){
						jqoMap.dSmartMap(param4);
					}
				}
				if((typeof param1) == 'object'){  // 2
					var autoInit = jqoMap.data('dSmartMap').autoInitImages;
					if(!_inited($t)){
						if(autoInit)
							_initImg($t);
						else
							return;
					}
					
					var jqoArea   = param1;
					var setActive = ((typeof param2)=='undefined')?true:param2;
					var multiple  = ((typeof param3)=='undefined')?false:param3;
					_delayed(jqoMap, function(){
						if(setActive){
							if(!multiple){
								_clearHighlight($t);
							}
							_select($t, jqoArea);
						}
						else{
							_clearHighlight($t, jqoArea);
						}
					});
				}
				if((typeof param1) == 'boolean' && !param1){ // 3
					if(!_inited($t)){
						if(!_inited(jqoMap))
							return;
						
						var autoInit = jqoMap.data('dSmartMap').autoInitImages;
						if(autoInit)
							_initImg($t);
						else
							return;
					}
					
					_delayed(jqoMap, function(){
						_select($t, false);
					});
				}
			}
			if(t.tagName == 'MAP'){
				var jqoImg = $("img[usemap='#"+$t.attr('name')+"']");
				if((typeof param1) == 'string'){ // 5, 1
					if(param1 == 'destroy'){
						if(!_inited($t)){
							return;
						}
						_destroyMap($t);
						return;
					}
					if(_inited($t)){
						_destroyMap($t);
					}
					
					_initMap($t, param1, param2, true);
				}
				if((typeof param1) == 'object' || (typeof param1) == 'boolean'){ // 2, 3
					jqoImg.dSmartMap(param1);
				}
			}
			if(t.tagName == 'AREA'){
				if((typeof param1) == 'object'){
					var jqoImg = param1;
					param1 = ((typeof param2)=='undefined')?true :param2; // true/false
					param2 = ((typeof param3)=='undefined')?false:param3; // multiple?
				}
				else{
					var jqoImg = $("img[usemap='#"+$t.closest('map').attr('name')+"']");
					param1 = ((typeof param1)=='undefined')?true :param1; // true/false
					param2 = ((typeof param2)=='undefined')?false:param2; // multiple?
				}
				
				// Select it!
				jqoImg.dSmartMap($t, param1, param2);
			}
		});
		
		return this;
	};
})(jQuery);
