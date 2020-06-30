// Last review: 2014-05-02
/**
	jquery-dProximity.js
	@author Alexandre Tedeschi
	@email  alexandrebr at gmail dot com

	$.dProximity('pause' | 'unpause' | 'destroy')
	$(selector).dProximity(options)
		options:
			minDistance:   0
			maxDistance: 100
			cbOnEvent:   callback(pcDistant*, distanceInPixels)
			* Sendo 0 quando dentro do elemento, e 100 quando acima de maxDistance;
	
	Exemplos:
		$("div").dProximity({
			minDistance:   0,
			maxDistance: 150,
			cbOnEvent:   function(pc, dist){
				$(this).css('opacity', pc/100);
			}
		});
**/
(function( $ ) {
	var _onProximity = new $;
	var _isListening = false;
	var _debug       = false;
	var _jqoMouse    = false;
	
	// Vamos assumir as seguintes informa√ß√µes:
	//   Raio da imagem:   1
	//   Centro da imagem: [  0,  0] 
	//   Posi√ß√£o do Mouse: [ 50,100] [mx,my]  (Referente ao CENTRO - MY*-1)
	//   Cateto Oposto:    100       my
	//   Cateto Adjacente:  50       mx
	//   ¬ngulo ALPHA(rad):1.107148  atan(CO/CA)  (Em radianos)
	//   ¬ngulo ALPHA(deg):63,43490  rad2deg(^^)  (Em graus)
	//   Cos(ALPHA):       0,447214  (cos(63,43490)) (Em graus)
	//   Sen(ALPHA):       0,567160  (sen(63,43490)) (Em graus)
	//   tan(alpha) = Cateto Oposto / Cateto Adjacente
	//   alpha = atan(CO/CA)
	// 
	
	var _getDistance = function(p1, p2){
		return Math.sqrt(Math.pow(p2.x-p1.x, 2)+Math.pow(p2.y-p1.y, 2));
	};
	var _catchMoves = function(e){
		var mouse = { x: e.pageX, y: e.pageY };
		if(_debug){
			if(!_jqoMouse)
				_jqoMouse = $("<div style='width: 4px; height: 4px; background: #00F; position: absolute; left: 0; top: 0; z-index: 100'></div>").appendTo("body");
			_jqoMouse.offset({ left: mouse.x-2, top: mouse.y-2 });
		}
		
		_onProximity.each(function(){
			if(_debug){
				// Debug:
				// 1. Create/update/destroy 'center' icon
				// 2. Create/update/destroy 'border' icon
				_jqoCenter = $(this).prop('dProximity-debug-center');
				_jqoBorder = $(this).prop('dProximity-debug-border');
				if(!_jqoCenter){
					_jqoCenter = $("<div style='width: 4px; height: 4px; background: #000; position: absolute; left: 0; top: 0; z-index: 100'></div>").appendTo("body");
					$(this).prop('dProximity-debug-center', _jqoCenter);
				}
				if(!_jqoBorder){
					_jqoBorder = $("<div style='width: 4px; height: 4px; background: #F00; position: absolute; left: 0; top: 0; z-index: 101'></div>").appendTo("body");
					$(this).prop('dProximity-debug-border', _jqoBorder);
				}
			}
			
			var opts = $(this).prop('dProximityOpts');
			var offs = $(this).offset();
			var box    = {
				top    : offs.top,
				left   : offs.left,
				radiusX: $(this).outerWidth() /2,
				radiusY: $(this).outerHeight()/2
			};
			var getImagePos  = function(x, y){
				// Input: PosiÁ„o na tela. Output: PosiÁ„o a partir do centro da imagem.
				// Ex: 250,250 --> 0,0 (Centro da imagem)
				return {
					x: x - box.radiusX - box.left,
					y: y - box.radiusY - box.top
				};
			};
			var getScreenPos = function(x, y){
				// Output: PosiÁ„o a partir do centro da imagem. Input: PosiÁ„o na tela.
				// Ex: 0,0 --> 250, 250 (Centro da imagem no <body>)
				return {
					x: box.left + box.radiusX + x,
					y: box.top  + box.radiusY + y
				};
			};
			var rMouse         = getImagePos(mouse.x, mouse.y);
			if(!rMouse.y && !rMouse.x){
				return;
			}
			
			var quadrante      = 1;
			var angle          = Math.atan(rMouse.y/rMouse.x);
			
			// Quadrantes:
			// 1 ( 0 at√© 90), 2 (-90 at√© 0 )
			// 3 ( 0 at√© 90), 4 (-90 at√© 0 )
			
			if(rMouse.x >= 0 && rMouse.y <= 0) quadrante = 2;
			else if(rMouse.x >= 0 && rMouse.y >= 0) quadrante = 4;
			else if(rMouse.x <= 0 && rMouse.y >= 0) quadrante = 3;
			if(quadrante == 1 || quadrante == 3){
				// Antes: 89, Depois: -91
				angle = -Math.PI + angle;
			}
			var border = {
				x: Math.cos(angle)*box.radiusX,
				y: Math.sin(angle)*box.radiusY
			};
			
			var bDistance = _getDistance({ x: 0, y: 0 }, border);                  // Dist√¢ncia entre o centro da imagem e a borda;
			var mDistance = _getDistance(getScreenPos(0,0), mouse);                // Dist√¢ncia entre o centro da imagem e o mouse;
			var fDistance = _getDistance(getScreenPos(border.x, border.y), mouse); // Dist√¢ncia entre a borda e o mouse
			if(mDistance < bDistance){
				fDistance *= -1;
			}
			
			if(_debug){
				var cPos = getScreenPos(0, 0);
				var bPos = getScreenPos(border.x, border.y);
				_jqoCenter.offset({ left: cPos.x-2, top: cPos.y-2 });
				_jqoBorder.offset({ left: bPos.x-2, top: bPos.y-2 });
			}
			
			// console.log("Centro at√© o mouse: ", parseInt(mDistance));
			// console.log("Centro at√© a borda: ", parseInt(bDistance));
		 	// console.log("Borda at√© o mouse: ", parseInt(fDistance), "Border as Screen: ", getScreenPos(border.x, border.y));
			
			var pc = 0;
			if(fDistance <= opts.minDistance){
				pc = 0;
			}
			else if(fDistance >= opts.maxDistance){
				pc = 100;
			}
			else{
				pc = 100*(fDistance-opts.minDistance)/(opts.maxDistance-opts.minDistance);
			}
			
			opts.cbOnEvent.call(this, pc, fDistance);
			return;
		});
	};
	
	$.dProximity    = function(cmd){
		if(cmd == 'pause'   &&  _isListening){
			$(document).off(_catchMoves);
			_isListening = false;
		}
		if(cmd == 'unpause' && !_isListening){
			$(document).on("mousemove", _catchMoves);
			_isListening = true;
		}
		if(cmd == 'destroy'){
			_onProximity.dProximity('destroy');
		}
		return $;
	};
	$.fn.dProximity = function(opts){
		opts = $.extend({
			minDistance: 25,
			maxDistance: 100,
			cbOnEvent:   function(pc){
				$(this).css('opacity', (100-pc)/100);
			}
		}, opts);
		
		if(opts=='destroy'){
			_onProximity = _onProximity.not(this);
			if(!_onProximity.length)
				$.dProximity('pause');
			return this;
		}
		if(this.length){
			$this = $(this).prop('dProximityOpts', opts);
			_onProximity = _onProximity.add($this);
			$.dProximity('unpause');
		}
		return this;
	};
})(jQuery);
