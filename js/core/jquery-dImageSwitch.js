// Last review: 2016-11-27
/**
		Usage:
			<div id='banners'>
				<div>conteúdo 1</div>
				<div>conteúdo 2</div>
				<div>conteúdo 3</div>
			</div>
			
			var disBanner = $("#banners").dImageSwitch(options);
				.go(n)
				.goPrev()
				.goNext()
				.pause()
				.resume()
				.setOption(key, value)
				.getMax()
				.getN()
				.setButtons(jqoDiv, imgSel, imgNsel)
			
			Options: {
				slideShow:      true,
				fadeInFadeOut:  'auto',   # Apaga uma enquanto acende outro. Não funciona bem com imagens de tamanho diferente ou com transparência. O 'auto' detecta o problema com o tamanho.
				vAlign:         'middle', # Caso as imagens tenham tamanhos diferentes, padronizar o alinhamento vertical.
				speed:          1500, # Velocidade de transição
				cbBeforeChange: function(oldN, newN, maxN){},
				cbAfterChange:  function(oldN, newN, maxN){},
				jqoNot:         jqoToIgnore,
				loop:           true,
				interval:       5000,
				setTaller:      true,
			}
		}
**/
var dImageSwitch = function(jqoMain, options){
	var t   = this;
	options = $.extend({
		slideShow:      true,
		cbBeforeChange: false,
		cbAfterChange:  false,
		interval:       5000,
		speed:          1500,
		fadeInFadeOut:  'auto',
		vAlign:         'middle',
		jqoNot:         false,
		loop:           true,
		setTaller:      true,
	}, options);
	
	t.inited = false;
	var jqoDivs = jqoMain.children();
	if(options.jqoNot){
		jqoDivs = jqoDivs.not(options.jqoNot);
	}
	var _selN   = 1;
	var _maxN   = jqoDivs.length;
	var _paused = true;
	var _timer  = false;
	var _inTrans= false;
	var _queue  = false;
	
	t.init      = function(){
		if(t.inited)
			return;
		
		$("body").load(_setTaller);
		$(_setTaller);
		jqoDivs.find("img").load(_setTaller);
		jqoMain.css('position', 'relative');
		jqoDivs.each(function(idx){
			$(this)
				.css('position', 'absolute')
				.css('left',     '0px')
				.css('top',      '0px')
				.css('z-index',  (idx==0)?1:0)
				.css('display',  (idx==0)?'block':'none');
		});
		
		t.inited = true;
	}
	t.destroy   = function(){
		// To-do.
	}
	t.go        = function(newN, dontTriggerBC){
		if(newN > _maxN) newN = _maxN;
		if(newN < 1)     newN = 1;
		if(_inTrans){
			// Se já estava em andamento, vamos acelerar a animação atual
			// e agendar o novo item para ser executado na sequencia.
			// 
			if(_queue){
				if(_queue[1] == newN){
					// Já estava na fila, ignore.
					return false;
				}
				if(options.cbAfterChange){
					options.cbAfterChange(_queue[0], _queue[1], _maxN);
				}
				if(options.cbBeforeChange){
					options.cbBeforeChange(_queue[1], newN, _maxN);
				}
				
				_queue = [_queue[1], newN];
			}
			else{
				if(_inTrans[1] == newN){
					// O destino da transição já era o próprio newN, ignore.
					return false;
				}
				
				if(options.cbBeforeChange){
					options.cbBeforeChange(_inTrans[1], newN, _maxN);
				}
				_queue = [_selN, newN];
			}
			
			return false;
		}

		var oldN = _selN;
		if(newN == oldN){
			// Não tem pra onde ir... Ignore esse pedido.
			return false;
		}
		
		_selN = newN;
		clearTimeout(_timer);
		_timer = false;

		if(options.cbBeforeChange && !dontTriggerBC){
			options.cbBeforeChange(oldN, newN, _maxN);
		}
		
		
		_inTrans = [oldN, newN];
		var _fadeInFadeOut = options.fadeInFadeOut;
		if(_fadeInFadeOut == 'auto'){
			// Se o objeto que vai aparecer for menor do que o objeto que está sendo
			// exibido, utilize 'fadeInFadeOut', pois o objeto que está sumindo não
			// será sobreposto pelo que está aparecendo.
			if($(jqoDivs[newN-1]).height() < $(jqoDivs[oldN-1]).height()
			|| $(jqoDivs[newN-1]).width()  < $(jqoDivs[oldN-1]).width()){
				_fadeInFadeOut = true;
			}
			else{
				_fadeInFadeOut = false;
			}
		}
		if(_fadeInFadeOut){
			$(jqoDivs[oldN-1]).fadeOut(options.speed);
		}
		
		$(jqoDivs[oldN-1]).css('z-index', 0);
		$(jqoDivs[newN-1]).css('z-index', 1).fadeIn(options.speed, function(){
			if(!_fadeInFadeOut){
				$(jqoDivs[oldN-1]).hide();
			}
			
			if(options.cbAfterChange){
				options.cbAfterChange(oldN, newN, _maxN);
			}
			if(!_paused)
				_startTimer();
			
			_inTrans = false;
			if(_queue){
				t.go(_queue[1], true);
				_queue = false;
			}
		});
	}
	t.goPrev    = function(){
		var newN = (_queue?_queue[1]:_selN) - 1;
		if(newN < 1){
			if(!options.loop){
				t.pause();
				return false;
			}
			newN = _maxN;
		}
		t.go(newN);
	}
	t.goNext    = function(){
		var newN = (_queue?_queue[1]:_selN) + 1;
		if(newN > _maxN){
			if(!options.loop){
				t.pause();
				return false;
			}
			newN = 1;
		}
		t.go(newN);
	}
	t.pause     = function(){
		_paused = true;
		if(_timer){
			clearInterval(_timer)
			_timer  = false;
		}
	}
	t.resume    = function(){
		_paused = false;
		_startTimer();
	}
	t.setOption = function(key, value){
		options[key] = value;
	}
	t.getMax    = function(){
		return _maxN;
	}
	t.getN      = function(){
		return _selN;
	}
	
	t.setButtons = function(jqoDiv, imgSel, imgNsel){
		var jqoDiv = $(jqoDiv);
		if(!jqoDiv.length)
			return;
		
		if(jqoDiv.length > 1){
			jqoDiv.each(function(){
				t.setButtons($(this), imgSel, imgNsel);
			});
			return;
		}
		
		var cbBefore = options.cbBeforeChange;
		options.cbBeforeChange = function(oldN, newN, maxN){
			if(jqoDiv.prop('dIS-Button-Started')){
				$("img", jqoDiv).each(function(idx){
					$(this).attr('src', ((idx+1)==newN)?imgSel:imgNsel);
				});
			}
			else{
				jqoDiv.prop('dIS-Button-Started', true);
				jqoDiv.empty();
				for(var n = 1; n <= _maxN; n++){
					(function(n){
						var tmpImg = $("<img>").attr('border', '0').attr('src', (n==newN)?imgSel:imgNsel);
						var tmpA   = $("<a>").click(function(){
							t.go(n);
							return false;
						}).append(tmpImg).appendTo(jqoDiv);
					})(n);
				}
			}
			if(cbBefore)
				return cbBefore(oldN, newN, maxN);
			
			return true;
		}
		options.cbBeforeChange(jqoDiv, _selN, _maxN);
	};
	
	var _startTimer = function(){
		if(_timer)
			clearTimeout(_timer);
		
		_timer = setTimeout(_hitTimer, options.interval);
	}
	var _hitTimer   = function(){
		t.goNext();
	}
	var _setTaller  = function(){
		if(!options.setTaller){
			return false;
		}
		var maxHeight = 1;
		var maxWidth  = 1;
		$(jqoDivs).each(function(){
			if($(this).width()  > maxWidth)  maxWidth  = $(this).width();
			if($(this).height() > maxHeight) maxHeight = $(this).height();
		});
		jqoMain.css('width',  maxWidth);
		jqoMain.css('height', maxHeight);
		if(options.vAlign == 'middle'){
			$(jqoDivs).each(function(){
				if($(this).height() != maxHeight){
					$(this).css('top', (maxHeight/2)-$(this).height()/2+"px");
				}
				else{
					$(this).css('top', "0px");
				}
			});
		}
	}
	
	t.init();
	if(options.slideShow){
		t.resume();
	}
};

(function($){
	$.fn.dImageSwitch = function(options){
		var ret = false;
		$(this).each(function(){
			if($(this).prop('dImageSwitch')){
				ret = $(this).prop('dImageSwitch');
				return;
			}
			ret = new dImageSwitch($(this), options)
			$(this).prop('dImageSwitch', ret);
		});
		return ret;
	}
})(jQuery);
