/**
	Tal como dRowDrag e dCellDrag, no entanto aceita qualquer conjunto de elementos
	em qualquer lugar do DOM. Os itens ser„o vari·veis dentro do prÛprio conjunto.
	
	Ainda est· em est·gios iniciais de desenvolvimento.
	
	dAnyDrag
		$("div").dAnyDrag(settings);
		$("div").dAnyDrag('disable'); --> To be implemented.
		
		settings:
			mouseOffset: { left: 7, top: 0 },
			cbCanMove:   (ev)
			cbOnStart:   (info)
			cbOnMove:    (oldInfo, newInfo)
			cbOnDrop:    (oldInfo, newInfo)
		
		info.
			jqo   --> Elemento que estava sendo movido
			index --> N˙mero
			prev  --> jqoElement
			next  --> jqoElement
**/
(function( $ ) {
	var _jqoAllList= [];    // [id] = { list: $(...), settings: {} }
	
	var _isMoving  = false;
	var _curOffset = false;
	var _curPos    = false;
	var _oldPos    = false;
	var _oldInfo   = false;
	
	var _jqoDiv    = false; // These only exists once something is dragged.
	var _jqoList   = false; // These only exists once something is dragged.
	var _settings  = {};    // These only exists once something is dragged.
	
	var _jqoFake    = false;
	var _jqoShadow  = false;
	var _mapCellPos = [];
	
	var _makeReturn        = function(){
		var index = _getCellIndex(_jqoDiv);
		return {
			jqo:   _jqoDiv,
			index: _getCellIndex(_jqoDiv),
			prev:  _getCellByIndex(index-1),
			next:  _getCellByIndex(index+1)
		};
	};
	var _loadCellsPosition = function(){
		// Carrega a posi√ß√£o absoluta dos elementos na tabela.
		// Retorno:
		//   [] { top:, left:, bottom:, right:, jqoElement: }
		_mapCellPos = [];
		_jqoList.each(function(){
			var tmpInfo        = $(this).offset();
			tmpInfo.bottom     = tmpInfo.top  + $(this).outerHeight();
			tmpInfo.right      = tmpInfo.left + $(this).outerWidth();
			tmpInfo.jqoElement = $(this);
			_mapCellPos.push(tmpInfo);
		});
	};
	var _getCellFromPos    = function(pos){
		// Retorna a c√©lula que est√° ocupando determinada posi√ß√£o x,y do mouse.
		for(var i = 0; i < _mapCellPos.length; i++){
			var tmp = _mapCellPos[i];
			if(pos.x >= tmp.left && pos.x <= tmp.right && pos.y >= tmp.top && pos.y <= tmp.bottom){
				var width  = tmp.right  - tmp.left;
				var height = tmp.bottom - tmp.top;
				tmp.closestX = false;
				tmp.closestY = false;
				if((pos.x - tmp.left)/width <= 0.5)
					tmp.closestX = 'left';
				else if((pos.x - tmp.left)/width >= 0.5)
					tmp.closestX = 'right';
				
				if((pos.y - tmp.top)/height <= 0.5)
					tmp.closestY = 'top';
				else if((pos.y - tmp.top)/height >= 0.5)
					tmp.closestY = 'bottom';
				
				return tmp;
			}
		}
		return false;
	};
	
	var cloneAtt   = ['border', 'cellspacing', 'cellpadding'];
	var cloneCss   = ['border', 'padding', 'margin', 'border-collapse', 'background'];
	var _buildClone      = function(){
		if(_jqoFake && _jqoFake.length){
			_jqoFake.remove();
		}
		
		_jqoFake = $("<div>")
			.append(_jqoDiv.clone())
			.appendTo('body')
			.css('position',   'absolute')
			.css('box-shadow', '2px 2px 5px #888888')
			.css('border',     '1px solid #808080')
			.css('z-index',    2)
			.css('opacity',  0.8)
			.attr('unselectable', 'on')
			.css('user-select',   'none')
			.on ('selectstart',   false);
		
		_copyAttributes(_jqoDiv, _jqoFake);
		_moveClone(true);
	};
	var _buildShadowDrop = function(){
		if(_jqoShadow && _jqoShadow.length)
			_jqoShadow.remove();
		
		_jqoShadow = $("<div>")
			.appendTo("body")
			.width (_jqoDiv.outerWidth())
			.height(_jqoDiv.outerHeight())
			.css('background', '#FFF')
			.css('position', 'absolute')
			.css('text-align', 'center')
			.css('opacity', 0)
			.css('z-index', 1)
			.html("");
		
		_moveShadowDrop(true);
	};
	
	var _moveClone       = function(firstMove){
		var newLeft  = _curPos.x - _curOffset.left;
		var newTop   = _curPos.y - _curOffset.top;
		if(firstMove){
			_jqoFake.fadeIn();
		}
		_jqoFake.offset({ left: newLeft, top:  newTop });
	};
	var _moveShadowDrop  = function(firstMove){
		if(firstMove){
			_jqoShadow.offset( _jqoDiv.offset() );
			_jqoShadow.animate({ opacity: 0.9 }, 'fast');
			_jqoShadow.show();
		}
		else{
			var offset    = _jqoDiv.offset();
			offset.width  = _jqoDiv.outerWidth();
			offset.height = _jqoDiv.outerHeight();
			_jqoShadow.stop().animate(offset, 'fast');
		}
	};
	var _copyAttributes  = function(from, to){
		to.addClass(from[0].className);
		for(var idx in cloneCss){ to.css (cloneCss[idx], from.css(cloneCss[idx]));  };
		for(var idx in cloneAtt){ to.attr(cloneAtt[idx], from.attr(cloneAtt[idx])); };
		to.width(from.width());
		to.height(from.height());
	};
	
	// Handling moves...
	var _initMoving  = function(){
		_loadCellsPosition();
		_oldInfo        = _makeReturn();
		_isMoving       = true;
		
		_buildClone();
		_buildShadowDrop();
		
		if(_settings.cbOnStart)
			_settings.cbOnStart(_oldInfo);
		
	};
	var _whileMoving = function(){
		_moveClone();
		
		var movingInfo    = _jqoDiv.offset();
		movingInfo.bottom = movingInfo.top  + _jqoDiv.outerHeight();
		movingInfo.right  = movingInfo.left + _jqoDiv.outerWidth();
		
		var overInfo   = _getCellFromPos(_curPos);
		if(!overInfo){
			// Estou sobre uma borda ou um espa√ßamento... Ignore.
			return false;
		}
		
		var jqoOver   = overInfo.jqoElement;
		var jqoMoving = _jqoDiv;
		if(jqoOver.is(jqoMoving)){
			// Estou com o mouse sobre o elemento que est√° sendo movido.
			// Nada a fazer.
			return true;
		}
		if(_settings.cbCanMove && !_settings.cbCanMove(jqoOver)){
			return false;
		}
		
		var from = _getCellIndex(jqoMoving);
		var to   = _getCellIndex(jqoOver);
		if(to<from && (overInfo.closestX == 'left' || overInfo.closestY == 'top')){
		}
		else if(to>from && (overInfo.closestX == 'right' || overInfo.closestY == 'bottom')){
		}
		else{
			// Don't do a t hing.
			return;
		}
		
		var dist = Math.abs(from-to);
		for(var i = 0; i < dist; i++){
			// From: jqoMoving
			// To:   _getCellByIndex(from-1 || from+1)
			
			// Invertendo posi√ß√µes:
			var jqoTo    = _getCellByIndex(from+((from>to)?-1:+1));
			var _jqoTemp = $("<span>");
			jqoMoving.after(_jqoTemp);
			jqoTo.after(jqoMoving);
			jqoTo.insertAfter(_jqoTemp);
			_jqoTemp.remove();
			
			_jqoList = _jqoAllList[_jqoListId].list = $(_jqoList.selector);
			_loadCellsPosition();
		}
		
		_moveShadowDrop();
		
		if(_settings.cbOnMove)
			_settings.cbOnMove(_oldInfo, _makeReturn());
		
		return false;
	};
	var _endMoving   = function(){
		_isMoving = false;
		
		var newInfo = _makeReturn();
		if(_settings.cbOnDrop)
			_settings.cbOnDrop(_oldInfo, newInfo);
		
		var newProps    = _jqoDiv.offset();
		_jqoFake.animate({
			width:  _jqoDiv.css('width'),
			height: _jqoDiv.css('height')
		}, { queue: false });
		
		_jqoFake.animate(newProps, function(){
			if(_isMoving == false){
				_jqoShadow.fadeOut(function(){ $(this).remove() });
				_jqoFake  .fadeOut(function(){ $(this).remove() });
			}
		});
	};
	
	var _getCellIndex      = function(jqoDiv){
		return _jqoList.index(jqoDiv);
	};
	var _getCellByIndex    = function(index){
		if(index<0)                 return false;
		if(index+1>_jqoList.length) return false;
		return $(_jqoList[index]);
	};
	
	// Handling events...
	var _onMove    = function(ev){
		_curPos = _getMousePos(ev);
		if(_isMoving == 'almost' && (Math.abs(_curPos.x - _oldPos.x) > 1 || Math.abs(_curPos.y - _oldPos.y) > 1)){
			_initMoving();
		}
		if(_isMoving === true){
			_whileMoving();
		}
	};
	var _onPress   = function(ev){
		// Refuses the click event and consider dragging...
		// --> Why to refuse click?
		//     Because it is used for links and text/image selection and dragging.
		_isMoving  = 'almost';
		_oldPos    = _getMousePos(ev);
		_jqoDiv    = $(this);
		_jqoListId = $(this).prop('dad-list-id');
		_settings  = _jqoAllList[_jqoListId].settings;
		_jqoList   = _jqoAllList[_jqoListId].list;
		
		var cellOffset = _jqoDiv.offset();
		_curOffset = {
		 	left: _oldPos.x - cellOffset.left + _settings.mouseOffset.left,
		 	top:  _oldPos.y - cellOffset.top  + _settings.mouseOffset.top
		};
		
		$(document).on('mousemove', _onMove);
		$(document).on('mouseup',   _onRelease);
		ev.stopPropagation();
		return false;
	};
	var _onClick   = function(ev){
		if(_isMoving){
			ev.stopPropagation();
			return false;
		}
	};
	var _onRelease = function(ev){
		if(_isMoving == false)
			return true;
		
		$(document).off('mousemove', _onMove);
		$(document).off('mouseup',   _onRelease);
		
		if(_isMoving == 'almost'){
			_isMoving = false;
			return true;
		}
		
		// Why timeout?
		// --> _onRelease is called before _onClick.
		// --> We need to invert it, so _onClick will know that it WAS being moved.
		// --> That only happens in the end of _endMoving
		setTimeout(_endMoving, 5);
	};
	
	var _getMousePos = function(ev){
		return { x: ev.pageX, y: ev.pageY }
	};
	
	$.fn.dAnyDrag  = function(settings){
		settings = $.extend({
			mouseOffset: { left: 7, top: 0 },
			cbCanMove:   false,
			cbOnStart:   false,
			cbOnMove:    false,
			cbOnDrop:    false
		}, settings);
		
		var listId = _jqoAllList.length;
		_jqoAllList[listId] = {
			list:    this,
			settings: settings
		}
		this.prop('dad-list-id', listId)
		    .bind('mousedown',         _onPress)
			.bind('click',             _onClick);
	};
}) (jQuery);
