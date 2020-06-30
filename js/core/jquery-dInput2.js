/**
	dInput2:
		iframeControl:
		------------------------------------------------------------------
			dInput2.iframeControl.open(url, cbOnReturn, options);
				cbOnReturn(ret): Sempre que houver um clickOutside, ret será FALSE.
				options:
					content:    Para ignorar a URL e informar uma string ou um jqo a ser utilizado.
					            Padrão: false (ou string, ou jqo que será utilizado e excluído depois)
					modal:      Padrão: false (ou true)
					width, height: Força altura e/ou largura do item em questão.
					            Padrão: false (ou seja, automático)
			
			Exemplo funcional:
				dInput2.iframeControl.open("iframe.teste.php", function(ret){ console.log("Retorno foi: ", ret) });
				
				// iframe.teste.php:
				$(parent.dInput2.iframeControlresize);
				$("body").load(parent.dInput2.iframeControl.resize);
				
				// Ao clicar:
				parent.dInput2.iframeControl.close("Mensagem de retorno");
**/


dInput2               = {};
dInput2.iframeControl = {
	_queue:     [],
	_realClose:  false,
	_timer:      false,
	open:       function(url, onReturn, options){
		var t = this;
		options = $.extend({
			content:    false,
			modal:      false,
			width:      false,
			height:     false,
			padding:    null,
		}, options);
		
		if(!onReturn)
			onReturn = function(){};
		
		t._queue.push({
			onReturn: onReturn,
			options:  options
		});
		
		var IE        = navigator.userAgent.match(/msie/i);
		var jqoIframe = options.content?
			$("<div class='fancybox-iframeControl'></div>").html(options.content):
			$('<iframe class="fancybox-iframeControl" style="display: block" frameborder="0" vspace="0" hspace="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen' + (IE ? ' allowtransparency="true"' : '') + '></iframe>');
		
		if(options.width)
			jqoIframe.width(options.width);
		if(options.height)
			jqoIframe.height(options.height);
		
		if(!$.fancybox.inner){
			$.fancybox.open({
				type:      'inner',
				scrolling: 'auto',
				content:   jqoIframe,
				padding:   options.padding,
				afterLoad:   function(){
					// $.fancybox.inner.css('overflow', 'auto');
				},
				beforeClose: function(){
					if(!t._realClose){
						t.close(false);
						return false;
					}
				},
				modal: options.modal,
			});
		}
		else{
			// Já estava aberto, vamos esconder o anterior e adicionar o novo.
			$.fancybox.inner.find(".fancybox-iframeControl").hide();
			$.fancybox.inner.append(jqoIframe);
		}
		if(!options.content)
			jqoIframe.attr('src', url);
	},
	fakeClose: function(retMessage){
		// Roda o callback como se estivesse fechando, mas não fecha!
		$(this._queue).get(-1).onReturn(retMessage);
	},
	close:      function(retMessage){
		var _item = this._queue.pop();
		_item.onReturn(retMessage);
		
		if(this._queue.length){
			var jqoQueue = $(".fancybox-iframeControl", "#onQueue");
			$.fancybox.inner.find(".fancybox-iframeControl").last().remove();
			$.fancybox.inner.find(".fancybox-iframeControl").show();
			$.fancybox.update();
		}
		else{
			this._realClose = true;
			$.fancybox.close();
			this._realClose = false;
		}
	},
	setSize:     function(width, height){
		var t = dInput2.iframeControl;
		if(!$.fancybox.inner || !t._queue.length)
			return;
		
		// Para DIVs e qualquer outro tipo de "Content", o resize apenas atualiza
		// o $.fancybox. Para iframe, obedece-se o width e height informado no options.
		var lastQueue = t._queue.slice(-1).pop();
		if(!lastQueue.options.content){
			var jqoIfr = $.fancybox.inner.find('.fancybox-iframeControl').last();
			if(width)  jqoIfr.width(width);
			if(height) jqoIfr.height(height);
		}
		$.fancybox.update();
	},
	
	alert:      function(htmlStr, cbReturn){
		dInput2.iframeControl.open(false, cbReturn, {
			content:
				htmlStr+"<br />"+
				"<div align='center'>"+
				"	<button onclick=\"parent.dInput2.iframeControl.close(true); return false;\">OK</button> "+
				"</div>",
			modal:      true,
		});
	},
	confirm:    function(htmlStr, cbReturn){
		dInput2.iframeControl.open(false, cbReturn, {
			content:
				htmlStr+"<br />"+
				"<div align='center'>"+
				"	<button onclick=\"parent.dInput2.iframeControl.close(true); return false;\">Sim</button> "+
				"	<button onclick=\"parent.dInput2.iframeControl.close(true); return false;\">Não</button> "+
				"</div>",
			modal:      true,
		});
	},
};
dInput2.getCaretPos = function(obj){
	var iCaretPos = 0;
	if(document.selection){ // IE Support
		// Set focus on the element
		obj.focus();
		
		// To get cursor position, get empty selection range
		// Move selection start to 0 position
		var oSel = document.selection.createRange();
		oSel.moveStart('character', -obj.value.length);
		iCaretPos = oSel.text.length;
	}
	else if(obj.selectionStart || obj.selectionStart == '0'){ // Firefox support
		iCaretPos = obj.selectionStart;
	}
	
	return iCaretPos;
};
dInput2.numberMask = {
	masks:      {
		'numeric'  : {
			cbInit:    function(){
				$(this).attr('placeholder', $(this).attr('dmask-format').replace(/#/g, 'x'));
				$(this).attr('maxlength',   $(this).attr('dmask-format').length);
			},
			applyMask: function(when){
				var nv      = $(this).val().replace(/[^0-9]/g, '');
				var theMask = $(this).attr('dmask-format');
				var needN   = nv.length;
				var newStr  = '';
				for(var i = 0; i < theMask.length; i++){
					if(theMask[i] == '#'){
						if(!nv.length)
							break;
						
						newStr += nv[0];
						nv = nv.substr(1);
					}
					else{
						newStr += theMask[i];
					}
				}
				
				$(this).val(newStr);
			},
		},
		'fone'     : {
			settings: {
				placeHolder: '(xx) xxxx-xxxx',
				maxLength:   15,
				applyOnBackspace: [10],  // Only if length equals to....
			},
			applyMask: function(when){
				var nv      = $(this).val().replace(/[^0-9]/g, '');
				var theMask = (nv.length>10)?'(##) #####-####':'(##) ####-####';
				
				var newStr  = '';
				for(var i = 0; i < theMask.length; i++){
					if(theMask[i] == '#'){
						if(!nv.length)
							break;
						
						newStr += nv[0];
						nv = nv.substr(1);
					}
					else{
						newStr += theMask[i];
					}
				}
				
				$(this).val(newStr);
			},
		},
		'cpf_cnpj' : {
			settings: {
				placeHolder:   'CPF ou CNPJ',
				maxLength:     18,
				applyOnChange: false,
			},
			applyMask: function(when){
				var newValue = $(this).val();
				var nv       = $(this).val().replace(/[^0-9]/g, '');
				if(nv.length == 11){
					newValue = nv.substr(0, 3)+"."+nv.substr(3, 3)+"."+nv.substr(6, 3)+"-"+nv.substr(9, 2);
				}
				else if(nv.length == 14){
					newValue = nv.substr(0, 2)+"."+nv.substr(2, 3)+"."+nv.substr(5, 3)+"/"+nv.substr(8, 4)+"-"+nv.substr(12);
				}
				
				$(this).val(newValue);
			},
		},
		'moeda'    : {
			settings:  {
				applyOnChange: false,
			},
			applyMask: function(when){
				var newValue = $(this).val().replace(/[^0-9,\.]/g, '');
				if(!newValue.length){
					$(this).val('');
					return;
				}
				
				var decPart  = false;
				newValue  = newValue.replace(/,/g, '.');
				newValue  = newValue.split('.');
				lastBlock = (newValue.length==1)?'0':newValue.pop();
				
				var _addThousSep = function(n){
					var finalStr = [];
					while(n.length > 3){
						finalStr.push(n.substr(n.length-3));
						n = n.substr(0, n.length-3);
					}
					finalStr.push(n);
					finalStr = finalStr.reverse();
					finalStr = finalStr.join('.');
					return (finalStr.length)?
					       finalStr:
					       '0';
				};
				if(lastBlock.length <= 2){ // Ex: 255.1 ou 255.10, considera-se como centavos.
					if(lastBlock == 0)      lastBlock = '00';
					else if(lastBlock < 10) lastBlock = (lastBlock*10);
					newValue  = _addThousSep(newValue.join(''));
					newValue  = newValue+","+lastBlock;
				}
				else{ // Ex: 255.100, considera-se como 255.100,00
					newValue.push(lastBlock);
					newValue  = _addThousSep(newValue.join(''));
					newValue  = newValue+",00";
				}
				$(this).val(newValue);
			},
		},
	},
	initialize: function(){
		var _masks       = this.masks;
		var _getCaretPos = dInput2.getCaretPos;
		$("input[dmask]").each(function(){
			// Apply onload
			var dMask    = $(this).attr('dmask');
			if(dMask == 'date'){
				dMask = 'numeric';
				$(this).attr('dmask-format', '##/##/####');
			}
			if(dMask == 'datetime'){
				dMask = 'numeric';
				$(this).attr('dmask-format', '##/##/#### ##:##');
			}
			if(dMask == 'cep'){
				dMask = 'numeric';
				$(this).attr('dmask-format', '#####-###');
			}
			if(dMask == 'cpf'){
				dMask = 'numeric';
				$(this).attr('dmask-format', '###.###.###-##');
			}
			if(dMask == 'cnpj'){
				dMask = 'numeric';
				$(this).attr('dmask-format', '##.###.###/####-##');
			}
			
			if(!_masks[dMask]){
				//	console.log("Can't handle: "+dMask);
				return;
			}
			
			if($(this).prop('dInput2-mask-loaded')){
				return;
			}
			$(this).prop('dInput2-mask-loaded', true);
			
			var settings = $.extend({
				placeHolder:      false,
				maxLength:        false,
				filterRegexp:     /[^0-9]/g,
				applyOnBackspace: false,
				applyOnChange:    true,
				onlyEndCaret:     true
			}, _masks[dMask].settings);
			
			if(settings.maxLength   && !$(this).attr('maxlength')){
				$(this).attr('maxlength', settings.maxLength);
			}
			if(settings.placeHolder && !$(this).attr('placeholder')){
				$(this).attr('placeholder', settings.placeHolder);
			}
			
			if(_masks[dMask].cbInit){
				_masks[dMask].cbInit.call(this);
			}
			if($(this).val().length){
				// Formata os campos que já vieram preenchidos?
				// --> Apenas se eles não estiverem pré-formatados.
				// --> Ou seja, se tiverem apenas números, formate-os.
				var nv = $(this).val().replace(settings.filterRegexp, "");
				if(nv.length == $(this).val().length){
					// O campo é apenas numérico, vamos formatá-lo.
					_masks[dMask].applyMask.call(this, 'onload');
				}
			}
			$(this).focus(function(){
				if(!$.trim($(this).val()).length && settings.applyOnChange){
					_masks[dMask].applyMask.call(this, 'onfocus');
				}
			});
			$(this).blur(function(){
				var nv = $(this).val().replace(settings.filterRegexp, "");
				if(!nv.length){
					$(this).val('');
					return;
				}
				
				_masks[dMask].applyMask.call(this, 'onblur');
			});
			
			if(settings.applyOnChange){
				var _oldStr = false;
				$(this).keydown(function(){
					_oldStr = $(this).val();
				});
				$(this).keypress(function(e){
					// Chrome ignores keypress for tab, backspace, arrows, ctrl+v, etc..
					// Firefox considers them, but leaves charCode empty.
					var _t = this;
					if(e.ctrlKey || !e.charCode){
						return true;
					}
					
					// Chrome considers ENTER as a keypress, and ignores it.
					// Let's make it consider ENTER as a valid key to a form submit.
					if(e.keyCode == 13){
						return true;
					}
					
					// Digamos que o próximo caractere no mapa é "-", e o usuário digitou "-"...
					// Teria que ser aceito, né? Vamos providenciar.
					var _char = String.fromCharCode(e.charCode);
					if(_char.match(settings.filterRegexp)){
						var myMask = $(_t).attr('dmask-format');
						if(myMask && myMask[_getCaretPos(_t)] == _char){
							return true;
						}
						
						return false;
					}
					return true;
				});
				$(this).keyup(function(){
					var _newStr = $(this).val();
					if(_oldStr == _newStr){
						return;
					}
					
					var nv = $.trim($(this).val()).replace(settings.filterRegexp, '');
					if(settings.onlyEndCaret){
						var cp = _getCaretPos(this);
						var le = $(this).val().length;
						if(cp != le){
							// Não digitou no último caractere... Não aplique a máscara.
							// * A mask só será aplicada no "onblur".
							return;
						}
					}
					if((settings.applyOnBackspace === false || typeof settings.applyOnBackspace == 'object') && _oldStr.length > $(this).val().length){
						// Devemos ignorar backspace, ou considerá-lo apenas
						// em situações específicas (length da parte numérica [nv]).
						// Backspace detectado.
						if(settings.applyOnBackspace === false){
							// Pare de executar.
							return;
						}
						if($.inArray(nv.length, settings.applyOnBackspace) == -1){
							// Não estou na White List.
							return;
						}
					}
					
					_masks[dMask].applyMask.call(this);
				});
			}
		});
	},
};

$(function(){
	dInput2.numberMask.initialize();
});
