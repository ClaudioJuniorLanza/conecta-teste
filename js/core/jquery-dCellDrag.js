/**
	dCellDrag
		$("table").dCellDrag(settings);
		$("table").dCellDrag('disable');
		
		settings:
			mouseOffset: { left: 7, top: 0 },
			cbCanMove:   (jqoCell)
			cbOnStart:   (info)
			cbOnMove:    (oldInfo, newInfo)
			cbOnDrop:    (oldInfo, newInfo)
		
		return {
			jqoTable: jqoObject
			jqoCell:  jqoObject
			left:     jqoObject or FALSE
			right:    jqoObject or FALSE
			top:      jqoObject or FALSE
			bottom:   jqoObject or FALSE
			prev:     jqoObject or FALSE
			next:     jqoObject or FALSE
			index:    {
				rowIdx:  int (0-n)
				cellIdx: int (0-n)
				nCells:  int (cells per row),
				index:   int (0-n) (rowIdx*nCells)+cellIdx,
			}
		};
**/
(function( $ ) {
	$.fn.dCellDrag = function(settings){
		var dcd = dCellDrag.start();
		$(this).each(function(){
			if(settings != 'disable'){
				dcd.initTable($(this), settings);
			}
			else{
				dcd.removeTable($(this));
			}
		});
	};
}) (jQuery);

function dCellDrag(){
	var t             = this;
	var _settings     = false;
	var _jqoFakeTable = false; // DIV que flutuará com o mouse, com a tabela falsas
	var _jqoShadow    = false; // DIV que flutuará com a sombra sobre o objeto inicial
	var _jqoTable     = false; // jqoTable that is being worked
	var _jqoCell      = false; // A célula que está sendo movida (a real, não a flutuante)
	var _mapCellPos   = [];    // { top, left, bottom, right, jqoElement }
	var _oldInfo      = false; // Valor inicial de _makeReturn
	var _isMoving     = false; // True/False
	var _curOffset    = false;
	var _curPos       = false;
	var _oldPos       = false;
	
	// Public:
	t.initTable   = function(jqoTable, settings){
		settings = $.extend({
			mouseOffset: { left: 7, top: 0 },
			cbCanMove:   false,
			cbOnStart:   false,
			cbOnMove:    false,
			cbOnDrop:    false
		}, settings);
		
		if(jqoTable.prop('dcd-settings')){
			jqoTable.prop('dcd-settings', settings);
			return true;
		}
		
		_jqoTable = jqoTable
			.on  ('mousedown',    _onPress)
			.prop('dcd-settings', settings);
		return true;
	};
	t.removeTable = function(jqoTable){
		jqoTable.off('mousedown', _onPress);
		jqoTable.removeProp('dcd-settings');
	};
	
	var _loadCellsPosition = function(){
		// Carrega a posição absoluta dos elementos na tabela.
		// Retorno:
		//   [] { top:, left:, bottom:, right:, jqoElement: }
		_mapCellPos = [];
		_jqoTable.children("tbody").children("tr").children("td").each(function(){
			var tmpInfo        = $(this).offset();
			tmpInfo.bottom     = tmpInfo.top  + $(this).outerHeight();
			tmpInfo.right      = tmpInfo.left + $(this).outerWidth();
			tmpInfo.jqoElement = $(this);
			_mapCellPos.push(tmpInfo);
		});
	};
	var _getCellFromPos    = function(pos){
		// Retorna a célula que está ocupando determinada posição x,y do mouse.
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
	var _buildCloneCell  = function(){
		if(_jqoFakeTable && _jqoFakeTable.length){
			_jqoFakeTable.remove();
		}
		var jqoCell     = _jqoCell;
		var cloneTable = _jqoFakeTable  = $("<table>").hide().appendTo("body").css('z-index', 2);
		var cloneTBody = $("<tbody>").appendTo(cloneTable);
		var cloneRow   = $("<tr>").appendTo(cloneTBody);
		var cloneCell  = jqoCell.clone().appendTo(cloneRow);
		
		_copyAttributes(_jqoTable, cloneTable, "Table");
		_copyAttributes(_jqoTable.children("tbody").first(), cloneTBody);
		_copyAttributes(jqoCell, cloneCell, "Cell");
		
		_jqoFakeTable = cloneTable;
		_jqoFakeTable
			.css('position', 'absolute')
			.css('box-shadow', '2px 2px 5px #888888')
			.css('border', '1px solid #808080')
			.css('z-index', 2)
			.css('opacity', 0.8)
			.attr('unselectable', 'on')
			.css('user-select', 'none')
			.on('selectstart', false);
		
		_moveCloneCell(true);
	};
	var _moveCloneCell   = function(firstMove){
		var oCell    = _jqoCell.offset();
		var newLeft  = _curPos.x - _curOffset.left;
		var newTop   = _curPos.y - _curOffset.top;
		if(firstMove){
			_jqoFakeTable.fadeIn();
		}
		_jqoFakeTable.offset ({ left: newLeft, top:  newTop });
	};
	var _buildShadowDrop = function(){
		if(_jqoShadow && _jqoShadow.length)
			_jqoShadow.remove();
		
		_jqoShadow = $("<div>")
			.appendTo("body")
			.width (_jqoCell.outerWidth())
			.height(_jqoCell.outerHeight())
			.css('background', '#FFF')
			.css('position', 'absolute')
			.css('text-align', 'center')
			.css('opacity', 0)
			.css('z-index', 1)
			.html("");
		
		_moveShadowDrop(true);
	};
	var _moveShadowDrop  = function(firstMove){
		if(firstMove){
			_jqoShadow.offset( _jqoCell.offset() );
			_jqoShadow.animate({ opacity: 0.9 }, 'fast');
			_jqoShadow.show();
		}
		else{
			var offset    = _jqoCell.offset();
			offset.width  = _jqoCell.outerWidth();
			offset.height = _jqoCell.outerHeight();
			_jqoShadow.stop().animate(offset, 'fast');
		}
	};
	var _copyAttributes  = function(from, to, what){
		to.addClass(from[0].className);
		for(var idx in cloneCss){ to.css (cloneCss[idx], from.css(cloneCss[idx]));  };
		for(var idx in cloneAtt){ to.attr(cloneAtt[idx], from.attr(cloneAtt[idx])); };
		if(what == 'Cell'){
			to.width(from.width());
			to.height(from.height());
		}
	};
	
	var _makeReturn        = function(){
		// Retorno esperado:
		//   _getCellsAround() + jqoTable + jqoCell
		var ret       = _getCellsAround(_jqoCell);
		var cellIndex = _getCellIndex  (_jqoCell);
		ret.jqoTable = _jqoTable;
		ret.jqoCell  = _jqoCell;
		ret.index    = cellIndex;
		
		return ret;
	};
	var _getTableFromEvent = function(ev){
		var jqoCell  = $(ev.target).closest('td');
		var jqoTable = jqoCell.closest('table');
		while(jqoTable.length && !jqoTable.prop('dcd-settings')){
			jqoCell  = jqoCell.parent().closest('td');
			jqoTable = jqoCell.closest('table');
		}
		return { jqoTable: jqoTable, jqoCell: jqoCell };
	};
	var _getCellsAround    = function(jqoCell){
		var table     = _jqoTable[0];
		var jqoLeft   = jqoCell.prev();
		var jqoRight  = jqoCell.next();
		var myIndex   = _getCellIndex(jqoCell); // { rowIdx, cellIdx, index, nCells }
		var jqoTop    = _getCellByIndex([myIndex.rowIdx-1, myIndex.cellIdx]);
		var jqoBottom = _getCellByIndex([myIndex.rowIdx+1, myIndex.cellIdx]);
		var jqoPrev   = _getCellByIndex(myIndex.index-1);
		var jqoNext   = _getCellByIndex(myIndex.index+1);
		
		return {
			left:   jqoLeft .length?jqoLeft :false,
			right:  jqoRight.length?jqoRight:false,
			top:    jqoTop,
			bottom: jqoBottom,
			prev:   jqoPrev.length?jqoPrev:false,
			next:   jqoNext.length?jqoNext:false,
		};
	};
	var _getCellIndex      = function(jqoCell){
		var table   = _jqoTable[0];
		var cellIdx = jqoCell.index();
		var rowIdx  = jqoCell.closest('tr')[0].rowIndex;
		var nCells  = table.rows[rowIdx].cells.length;
		return {
			rowIdx:  rowIdx,
			cellIdx: cellIdx,
			index:   (rowIdx*nCells)+cellIdx,
			nCells:  nCells
		};
	};
	var _getCellByIndex    = function(index){
		var table   = _jqoTable[0];
		var nCells  = table.rows[0].cells.length;
		var rowIdx  = false;
		var cellIdx = false;
		if(typeof index == 'object'){
			rowIdx  = index[0];
			cellIdx = index[1];
		}
		else{
			rowIdx  = parseInt(index/nCells);
			cellIdx = index%nCells;
		}
		
		if(!table.rows[rowIdx] || !table.rows[rowIdx].cells[cellIdx]){
			return false;
		}
		
		return $(table.rows[rowIdx].cells[cellIdx]);
	};
	
	// Moving handling...
	var _initMoving  = function(){
		// Devo começar já com as seguintes variáveis:
		//   _jqoTable, _jqoCell, _curPos, _curOffset
		
		_loadCellsPosition();
		_isMoving       = true;
		_oldInfo        = _makeReturn();
		
		_buildCloneCell();
		_buildShadowDrop();
		
		if(_settings.cbOnStart)
			_settings.cbOnStart(_oldInfo);
	};
	var _whileMoving = function(){
		_moveCloneCell();
		
		var movingInfo    = _jqoCell.offset();
		movingInfo.bottom = movingInfo.top  + _jqoCell.outerHeight();
		movingInfo.right  = movingInfo.left + _jqoCell.outerWidth();
		
		var overInfo   = _getCellFromPos(_curPos);
		if(!overInfo){
			// Estou sobre uma borda ou um espaçamento... Ignore.
			return false;
		}
		
		var jqoOver   = overInfo.jqoElement;
		var jqoMoving = _jqoCell;
		if(jqoOver.is(jqoMoving)){
			// Estou com o mouse sobre o elemento que está sendo movido.
			// Nada a fazer.
			return true;
		}
		if(_settings.cbCanMove && !_settings.cbCanMove(jqoOver)){
			return false;
		}
		
		var moveTo = false;
		     if(_curPos.x < movingInfo.left   && overInfo.closestX == 'left'){
			moveTo = 'prev';
		}
		else if(_curPos.x > movingInfo.right  && overInfo.closestX == 'right'){
			moveTo = 'next';
		}
		else if(_curPos.y < movingInfo.top    && overInfo.closestY == 'top'){
			moveTo = 'prev';
		}
		else if(_curPos.y > movingInfo.bottom && overInfo.closestY == 'bottom'){
			moveTo = 'next';
		}
		if(!moveTo){
			return;
		}
		
		var _movePrev = function(){
			var jqoFrom = jqoMoving;
			var from    = _getCellIndex(jqoFrom);
			var jqoTo   = _getCellByIndex(from.index-1);
			var to      = _getCellIndex(jqoTo);
			
			if(from.cellIdx > 0){
				jqoFrom.insertBefore(jqoTo);
			}
			else{
				var jqoTemp = _getCellByIndex(from.index+1);
				jqoFrom.insertBefore(jqoTo);
				jqoTo.insertBefore(jqoTemp);
			}
		};
		var _moveNext = function(){
			var jqoFrom = jqoMoving;
			var from    = _getCellIndex(jqoFrom);
			var jqoTo   = _getCellByIndex(from.index+1);
			var to      = _getCellIndex(jqoTo);
			
			if(from.cellIdx < from.nCells-1){
				jqoFrom.insertAfter(jqoTo);
			}
			else{
				var jqoTemp = _getCellByIndex(from.index-1);
				jqoFrom.insertAfter(jqoTo);
				jqoTo.insertAfter(jqoTemp);
			}
		};
		
		var from = _getCellIndex(jqoMoving).index;
		var to   = _getCellIndex(jqoOver).index;
		var dist = Math.abs(from-to);
		for(var i = 0; i < dist; i++){
			if(moveTo == 'prev')
				_movePrev();
			else
				_moveNext();
		}
		
		_loadCellsPosition();
		_moveShadowDrop();
			
		if(_settings.cbOnMove)
			_settings.cbOnMove(_oldInfo, _makeReturn());
		
		return false;
	};
	var _abortMoving = function(){
		// To-do:
		// --> ESC pressed?
		// --> Restore object to original location.
	};
	var _endMoving   = function(){
		_isMoving = false;
		var newInfo = _makeReturn();
		if(_settings.cbOnDrop)
			_settings.cbOnDrop(_oldInfo, newInfo);
		
		var newProps    = _jqoCell.offset();
		_jqoFakeTable.find("td").first().animate({
			width:  _jqoCell.css('width'),
			height: _jqoCell.css('height')
		});
		
		_jqoFakeTable.animate(newProps, function(){
			if(!_isMoving){
				_jqoShadow   .fadeOut(function(){ $(this).remove() });
				_jqoFakeTable.fadeOut(function(){ $(this).remove() });
			}
		});
	};
	
	// Event handling...
	// _onPress():
	// --> Salva a posição inicial
	// --> Registra _onMove e _onRelease
	
	// _onMove():
	// --> Se moveu o mais mais do que 1px:
	//     --> Chame _initMoving()
	//     --> Chame _whileMoving()
	
	// _onRelease():
	// --> Se estiver movendo:
	//     --> Chame _endMoving()
	//     --> Chame event.stopPropagation()
	//     --> Retorne FALSE
	// --> Retorne TRUE
	
	var _onPress     = function(ev){
		var fromEvent = _getTableFromEvent(ev);
		_jqoTable    = fromEvent.jqoTable;
		_jqoCell     = fromEvent.jqoCell;
		_settings    = _jqoTable.prop('dcd-settings');
		
		if(!_jqoCell.length){
			// Clicou em algo entre as células...
			return true;
		}
		
		var canMove = true;
		if(canMove && _settings.cbCanMove){
			canMove = _settings.cbCanMove(_jqoCell);
		}
		if(!canMove){
			// Accepts the click.
			return true;
		}
		
		// Refuses the click event and consider dragging...
		// --> Why to refuse click?
		//     Because it is used for links and text/image selection and dragging.
		$(document)
			.on('mousemove', _onMove)
			.on('mouseup',   _onRelease);
		
		_isMoving   = false;
		_oldPos     = _getMousePos(ev);
		
		var cellOffset = _jqoCell.offset();
		_curOffset = {
			left: _oldPos.x - cellOffset.left + _settings.mouseOffset.left,
			top:  _oldPos.y - cellOffset.top  + _settings.mouseOffset.top
		};
		
		ev.stopPropagation();
		return false;
	};
	var _onMove      = function(ev){
		_curPos = _getMousePos(ev);
		if(!_isMoving && (Math.abs(_curPos.x - _oldPos.x) > 1 || Math.abs(_curPos.y - _oldPos.y) > 1)){
			_initMoving();
		}
		if(_isMoving){
			_whileMoving();
		}
	};
	var _onRelease   = function(ev){
		$(document).off('mousemove', _onMove);
		$(document).off('mouseup',   _onRelease);
		
		if(!_isMoving)
			return true;
		
		_endMoving();
	};
	var _getMousePos = function(ev){
		return { x: ev.pageX, y: ev.pageY }
	};
};
dCellDrag.start = function(){
	if(!dCellDrag.isStarted){
		dCellDrag.isStarted = true;
		dCellDrag.instance  = new dCellDrag;
	}
	return dCellDrag.instance;
};
