// Last review: 22/01/2014
/**
	Documentação:
		$("input, textarea").dEip(settings);
		$("span, div").dEip(settings);
	
	Ajax quick code:
		.dEipAjax("ajax.rename.php?id=xxx", 'title', settings)
		// --> Shorthand for: $.post('ajax.rename.php?id=xxx', { name: 'title', value: 'new-value' });
		// --> Write "OK" to display "Success". Write anything else to display it as error.
		// --> Settings:
		//         popup: 'black' | 'white' | false
		//         post:     your own postData
		//         postName:  Key for 'name'.  default: 'name'.
		//         postValue: Key for 'value'. default: 'value'.
		//         * You can also extend all the default settings.
	
	dEipObject --> Encontre em $("input").data("dEip")
		.htmlEl
		.overEl
		.editEl
		.oldValue
		.settings
		.editorOpen();
		.editorOk();
		.editorCancel();
		
	Settings:
		useTextarea:   false, // Se for inserido num <input>, sempre false. Se num <textarea>, sempre true.
		onChangeCb:    false, // callback(dEipObject, me.oldValue, newValue)
		htmlToValueCb: false, // callback(dEipObject, plainValue, returnCallback), (Se retonar FALSE ou NULL, abre o editor enquanto carrega.)
		valueToHtmlCb: false, // callback(dEipObject, htmlValue,  returnCallback), (Se retornar TRUE, o próprio callback terá que abrir o editor.)
		convert_nl2br: true,  // true | false
*/
	
	

(function($){
	var dEipClass = function(someEl, settings){
		var me       = this;
		this.htmlEl   = false;
		this.overEl  = false;
		this.editEl   = false;
		this.oldValue = false;
		this.settings = $.extend({
			useTextarea:   false, // Se for inserido num <input>, sempre false. Se num <textarea>, sempre true.
			onChangeCb:    false, // callback(dEipObject, me.oldValue, newValue)
			htmlToValueCb: false, // callback(dEipObject, plainValue, returnCallback), (Se retonar FALSE ou NULL, abre o editor enquanto carrega.)
			valueToHtmlCb: false, // callback(dEipObject, htmlValue,  returnCallback), (Se retornar TRUE, o próprio callback terá que abrir o editor.)
			convert_nl2br: true,  // true | false
			placeHolder:   "<i>Clique aqui para editar</i>"
		}, settings);
		
		me.initialize = function(){
			if(someEl.data('dEip')){
				return false;
			}
			switch(someEl.get(0).tagName.toLowerCase()){
				case 'input':
					if(someEl.get(0).attributes.value)
						someEl.val(someEl.get(0).attributes.value.value);
					
					me.editEl = someEl;
					me.editEl.css('resize',   'none');
					me.editEl.css('border',   '0');
					
					me.htmlEl  = $("<span style='white-space: nowrap; visibility: hidden'></span>").insertBefore(someEl);
					if(me.editEl.get(0).style.width || me.editEl.get(0).style.height){
						if(me.editEl.get(0).style.width)
							me.htmlEl.css('width', me.editEl.css('width'));
						
						if(me.editEl.get(0).style.height)
							me.htmlEl.css('height', me.editEl.css('height'));
					}
					me._syncValues(me.editEl.val(), true);
					
					break;
					
					
				case 'textarea':
					me.editEl = someEl;
					me.editEl.css('resize',   'none');
					me.editEl.css('border',   '0');
						
					me.htmlEl  = $("<div style='visibility: hidden'></div>").insertBefore(someEl);
					if(me.editEl.get(0).style.width || me.editEl.get(0).style.height){
						if(me.editEl.get(0).style.width)
							me.htmlEl.css('width', me.editEl.css('width'));
						
						if(me.editEl.get(0).style.height)
							me.htmlEl.css('height', me.editEl.css('height'));
					}
					me._syncValues(me.editEl.val(), true);
					
					// Por que não overflow:hidden?
					// --> Porque se tiver css:height definido, o texto ficará inacessível.
					// me.editEl.css('overflow', 'hidden');
					
					// Por que não override no outline?
					// --> Porque se ocorrer temos que identificar um comportamento padrão
					//     entre os navegadores.
					// me.editEl.css('outline', '1px round #FFC');
					break;
					
//				case 'select': To-do.
				
				default:
					me.htmlEl = someEl;
					break;
			}
			
			if(!me.editEl){
				//~ me.editEl  = this.settings.useTextarea?
					//~ $("<textarea style='display: none'></textarea>").insertAfter(someEl):
					//~ $("<input style='display: none' />").insertAfter(someEl);
				me.editEl  = $("<textarea style='display: none'></textarea>").prop('singleLine', this.settings.useTextarea?false:true).insertAfter(someEl);
				
				if(!$.trim(me.htmlEl.html()))
					me.htmlEl.html(this.settings.placeHolder);
				
				me._syncValues(me.htmlEl.html(), false);
			}
			if(!me.overEl){
				me.overEl = $("<div style='display: none'></div>").insertAfter(me.htmlEl);
			}
			
			me.htmlEl.data('dEip', me).mouseover(me.showOver);
			
			if(me.htmlEl.css('display') == 'inline'){
				me.htmlEl.css('display', 'inline-block');
			}
			me.overEl.data('dEip', me).click(me.editorOpen).mouseout(me.hideOver); 
			me.editEl.data('dEip', me).blur (me.editorOk)
				.keydown(function(e){
					if(e.keyCode == 9){ // TAB
						me.editorOk();
						e.preventDefault();
						return false;
					}
					return true;
				})
				.keypress(function(e){
					if(e.keyCode == 13 && (me.editEl.get(0).tagName != 'TEXTAREA' || me.editEl.prop('singleLine'))){ // ENTER
						me.editorOk();
					}
				})
				.keyup   (function(e){
					if(e.keyCode == 27 || e.charCode == 27 || e.which == 27){ // ESC
						me.editorCancel();
					}
					else{
						me._syncValues(me.editEl.val(), true);
						me._syncPosAndSize();
					}
				});
			
			me.showHtml();
		};
		
		me.showOver = function(){
			$(".dEipOver").hide();
			me._syncPosAndSize();
			me.overEl.fadeIn('fast');
		}
		me.hideOver = function(){
			me.overEl.hide();
		}
		me.showHtml = function(){
			me.overEl.hide();
			me.editEl.hide();
			me.htmlEl.css ('visibility', 'visible');
			me._syncPosAndSize();
		}
		me.showEdit = function(){
			me._syncPosAndSize();
			me.htmlEl.css ('visibility', 'hidden');
			me.overEl.hide();
			me.editEl.show().focus()
		}

		me._syncValues     = function(newValue, sourceIsPlain){
			var htmlValue = newValue;
			var editValue = newValue;
			
			if(htmlValue == me.settings.placeHolder){
				htmlValue = editValue = "";
			}
			if(me.settings.convert_nl2br){
				if(sourceIsPlain){
					htmlValue = htmlValue.replace(/\n *$/g, "<br />&nbsp;");
					htmlValue = htmlValue.replace(/\n/g, "<br />");
					htmlValue = htmlValue.replace(/  /g, " &nbsp;");
				}
				else{
					editValue = editValue.replace(/\r?\n/g, "");
					editValue = editValue.replace(/<br ?\/?>[ \t]*/ig, "\n");
					editValue = editValue.replace(/&nbsp;/ig, " ");
					editValue = $.trim(editValue);
				}
			}
			
			htmlValue = $.trim(htmlValue);
			if(!htmlValue.length){
				htmlValue = me.settings.placeHolder;
			}
			
			// Se o source é plain, então vamos sincronizar apenas o HTML, ou vice-versa.
			(sourceIsPlain)?
				me.htmlEl.html(htmlValue):
				me.editEl.val (editValue);
		};
		me._syncPosAndSize = function(){
			// Offset funciona bem quando não há position: relative.
			// Offset j� retorna o valor considerando "margin".
			// Position funciona bem em todas as situa��es de position: relative ou n�o;
			// Position retorna sem considerar o "margin"
			var pos  = me.htmlEl.position();
			var size = me.htmlEl[0].getBoundingClientRect();
			
			me.overEl
				.addClass('dEipOver')
				.css('text-shadow', '0 0 2px #FFF, 0 0 2px #FFF')
				.css('color',       '#000')
				.css('text-align',  'right')
				.html("<i class='fa fa-pencil fa-fw' style='font-size: 16px; position: relative; right: -16px; top: -16px'></i>")
				.css('position',    'absolute')
				.css('margin',      me.htmlEl.css('margin'))
				.css('border',      '1px dotted #F00')
				.css('width',       size.width)
				.css('height',      size.height)
				.css('left',        pos.left)
				.css('top',         pos.top);
				
			
			me.editEl
				.css('margin',      me.htmlEl.css('margin'))
				.css('padding',     me.htmlEl.css('padding'))
				.css('border',      me.htmlEl.css('border'))
				.css('font-family', me.htmlEl.css('font-family'))
				.css('font-size',   me.htmlEl.css('font-size'))
				.css('font-weight', me.htmlEl.css('font-weight'))
				.css('line-height', me.htmlEl.css('line-height'))
				.css('text-align',  me.htmlEl.css('text-align'))
				.css('border-radius',me.htmlEl.css('border-radius'))
				.css('color',       me.htmlEl.css('color'))
				.css('background',  'transparent')
				.css('position',    'absolute')
				.css('left',        pos.left)
				.css('top',         pos.top)
				.css('overflow',    'hidden')
				.css('width',       Math.ceil(size.width))
				.css('height',      Math.ceil(size.height));
			
			if(me.htmlEl.css('padding') == '0px'){
				me.overEl
					.css('left',        pos.left-6)
					.css('top',         pos.top-6)
					.css('width',       size.width+12)
					.css('height',      size.height+12);
				
				me.editEl
					.css('left',        pos.left - 6)
					.css('top',         pos.top  - 6)
					.css('width',       size.width+12)
					.css('height',      size.height+12)
					.css('padding',     '6px');
			}
			
		};
		
		me.editorOpen   = function(){
			if(!me.editEl.is(":hidden"))
				return false;
			
			me.htmlEl.prop('origHtml', me.htmlEl.html());
			if(me.settings.htmlToValueCb){
				var stop = me.settings.htmlToValueCb(me, me.editEl.val(), function(ret){
					me._syncValues(ret, true);
					me.oldValue = me.editEl.val();
				});
				if(stop)
					return;
			}
			else{
				me.editEl.css('min-width',   me.htmlEl.outerWidth())
				me.editEl.css('min-height',  me.htmlEl.outerHeight());
				
				me._syncValues(me.htmlEl.html(), false);
				me.oldValue = me.editEl.val();
			}
			
			me.showEdit();
		}
		me.editorOk     = function(){
			if(me.editEl.is(":hidden"))
				return false;
			
			me.htmlEl.removeProp('origHtml');
			if(me.settings.valueToHtmlCb){
				var stop = me.settings.valueToHtmlCb(me, me.editEl.val(), function(ret){ 
					me._syncValues(ret, false);
				});
				if(stop)
					return true;
			}
			else{
				me._syncValues(me.editEl.val(), true);
			}
			me.showHtml();
			
			if(me.settings.onChangeCb && me.oldValue != me.editEl.val()){
				me.settings.onChangeCb(me, me.oldValue, me.editEl.val());
			}
		}
		me.editorCancel = function(){
			if(me.editEl.is(":hidden"))
				return false;
			
			
			me.htmlEl.html(me.htmlEl.prop('origHtml'));
			me.htmlEl.removeProp('origHtml');
			me.showHtml();
		}
		me._scrollbarWidth = function(){
			var div = $('<div style="width:50px;height:50px;overflow:hidden;position:absolute;top:-200px;left:-200px;"><div style="height:100px;"></div>');
			$('body').append(div);
			var w1 = $('div', div).innerWidth();
			div.css('overflow-y', 'scroll');
			var w2 = $('div', div).innerWidth();
			$(div).remove();
			return (w1 - w2);
		}
		
		me.initialize();
	};
	
	function showLoading(scheme){
		if(!scheme){
			scheme = 'black';
		}
		
		$(".dEipWaitEl").each(function(){
			// Remove os avisos anteriores
			$(this).css('margin-bottom', 64*2);
			hideLoading($(this));
		});
		
		var scheme = {
			boxShadow:  (scheme == 'black')?'#FFF':'#000',
			background: (scheme == 'black')?'#000':'#FFF',
			color:      (scheme == 'black')?'#FFF':'#000',
		};
		
		var _waitEl   = $("<div>")
			.addClass('dEipWaitEl')
			.css('transition', 'margin-bottom 0.5s, background-color 0.5s, color 0.5s')
			.css('padding', '16px')
			.css('display', 'inline-block')
			.css('box-shadow', "-2px -2px 2px "+scheme.boxShadow+", -2px 2px 2px "+scheme.boxShadow)
			.css('background', scheme.background)
			.css('color',      scheme.color)
			.css('opacity', 0)
			.css('white-space', 'nowrap')
			.css('position', 'fixed')
			.css('right', 0)
			.css('bottom', 64)
			.css('z-index', 200)
			.css('width', '1')
			.html("<i class='fa fa-spinner fa-spin'></i> Salvando...")
			.prop('scheme', scheme)
			.prependTo($("body"))
			.animate({ opacity: 1, width: 200 });
		
		return _waitEl;
	}
	function hideLoading(_waitEl){
		if(!_waitEl){
			return false;
		}
		
		_waitEl.fadeOut(500, function(){
			setTimeout(function(){
				_waitEl.remove();
				_waitEl = null;
			}, 10);
		});
		
	}
	function endLoading (_waitEl, message){
		if(!_waitEl){
			return false;
		}
		
		var scheme = _waitEl.prop('scheme');
		if(message == 'OK'){
			_waitEl.html("<i class='fa fa-check'></i> Sucesso!");
			setTimeout(function(){
				hideLoading(_waitEl);
			}, 1500);
		}
		else{
			_waitEl.stop().css({ width: 'auto', opacity: 1, backgroundColor: '#FCC', color: '#000' }).html(
				(message?message:"Erro: Sem resposta")+
				"<div style='font: 11px Arial; font-style: italic; border-top: 1px solid #000; margin-top: 8px; padding-top: 8px'>Clique para desconsiderar</div>"
			);
			_waitEl.attr('title', 'Clique para desconsiderar').click(function(){
				hideLoading(_waitEl);
			});
		}
	}
			
	$.fn.dEip     = function(settings){
		$(this).each(function(){
			$(this).data('dEip', new dEipClass($(this), settings));
		});
	};
	$.fn.dEipAjax = function(url, fieldName, settings){
		if(typeof settings != 'object'){
			settings = {
				useTextarea: settings?true:false,
			};
		}
		settings = $.extend({
			popup:       'black',
			post:        {},
			postName:    'name',
			postValue:   'value',
			onChangeCb:  false,
		}, settings);
		
		var _extraCb = settings.onChangeCb;
		settings.onChangeCb = function(dEip, ov, nv){
			var _post      = settings.post;
			var _fieldName = ((typeof fieldName)=="string")?fieldName:fieldName.call(dEip.htmlEl);
			eval("_post."+settings.postName +" = _fieldName;");
			eval("_post."+settings.postValue+" = nv;");
			
			_waitEl = showLoading(settings.popup);
			$.post(url, _post, function(ret){
				endLoading(_waitEl, ret);
			}).fail(function(details){
				endLoading(_waitEl, "Erro: "+details.statusText);
			});
			
			if(_extraCb){
				_extraCb(dEip, ov, nv);
			}
		};
		
		$(this).dEip(settings);
	};
	
	$.dEip = {
		showLoading: showLoading,
		hideLoading: hideLoading,
		endLoading:  endLoading,
	};
})(jQuery);

