// Last review: 2015-12-23 * No final de _endMoving:fadeOut, o elemento não era destruído
// Last review: 2013-06-05 * Problemas com falta de ';', quando usado com um js loader.
// Last review: 2013-03-10 * cbOnDrop e cbOnMove são chamados sempre, mesmo se oldInfo=newInfo
// Last review: 2012-12-12
/**
	dRowDrag
		$("table").dRowDrag(settings);
		$("table").dRowDrag('disable');
		
		settings:
			mouseOffset: { left: 7, top: 0 },
			cbCanMove:   (ev)
			cbOnStart:   (info)
			cbOnMove:    (oldInfo, newInfo)
			cbOnDrop:    (oldInfo, newInfo)
		
		info.
			jqoTable
			jqoRow
			jqoRowAbove
			jqoRowBelow
			rowIndex    --> Sempre relativo ao tbody
	
	To-do:
		$("tr").dRowDrag('moveTo',    5);
		$("tr").dRowDrag('moveTo',    oldInfo)
		$("tr").dRowDrag('moveBelow', $("tr"))
		$("tr").dRowDrag('moveAbove', $("tr"))
		$("tr").dRowDrag('moveTop'   )
		$("tr").dRowDrag('moveBottom')
**/

(function( $ ) {
	$.fn.dRowDrag = function(settings){
		var drd = dRowDrag.start();
		$(this).each(function(){
			if(settings != 'disable'){
				drd.initTable($(this), settings);
			}
			else{
				drd.removeTable($(this));
			}
		});
	};
}) (jQuery);

function dRowDrag(){
	var _settings     = false;
	var _jqoFakeTable = false;
	var _jqoFakeRow   = false;
	var _jqoTable     = false;
	var _isMoving     = false;
	var _mapRowPos    = false;
	var _oldInfo      = false;
	var _moving       = {
		oPos:     false, // Original mouse position
		row:      false, // Row that is being moved around (tr)
		curPos:   false  // Current mouse position
	};
	var t = this;
	
	// Public:
	t.initTable   = function(jqoTable, settings){
		settings = $.extend({
			mouseOffset: { left: 7, top: 0 },
			cbCanMove:   false,
			cbOnStart:   false,
			cbOnMove:    false,
			cbOnDrop:    false
		}, settings);
		
		if(jqoTable.prop('drd-settings')){
			jqoTable.prop('drd-settings', settings);
			return true;
		}
		jqoTable = jqoTable.on('mousedown', _onPress);
		jqoTable.prop('drd-settings', settings);
		return true;
	};
	t.removeTable = function(jqoTable){
		jqoTable.off('mousedown', _onPress);
		jqoTable.removeProp('drd-settings');
	};
	
	// Private, singleTon.
	var cloneAtt   = ['border', 'cellspacing', 'cellpadding'];
	var cloneCss   = ['border', 'padding', 'margin', 'border-collapse', 'background'];
	var _loadRowsPosition = function(){
		var allRows    = [];
		var headerRows = _jqoTable.children("thead").children("tr").length;
		
		_mapRowPos = [];
		
		// To-do:
		// .not('to-ignore');
		
		_jqoTable.children("tbody").children("tr").each(function(){
			var tmpInfo = $(this).offset();
			tmpInfo.height     = $(this).outerHeight();
			tmpInfo.jqoElement = $(this);
			_mapRowPos.push(tmpInfo);
		});
	};
	var _getRowFromPos    = function(pos){
		var foundIdx = 0;
		for(var idx in _mapRowPos){
			if(pos.top >= _mapRowPos[idx].top){
				foundIdx = idx;
				continue;
			}
			break;
		}
		
		var ret = _mapRowPos[foundIdx];
		ret.closerTo = 'top';
		if(pos.top > ret.top + (ret.height/2)){
			ret.closerTo = 'bottom';
		}
		
		return ret;
	};
	
	var _buildCloneRow = function(){
		if(_jqoFakeTable){
			_jqoFakeTable.remove();
			_jqoFakeTable = false;
		}
		
		var jqoRow     = _moving.row.jqoElement;
		var cloneTable = _jqoFakeTable  = $("<table>").hide().appendTo("body").css('z-index', 2);
		var cloneTBody = $("<tbody>").appendTo(cloneTable);
		var cloneRow   = jqoRow.clone().appendTo(cloneTBody);
		var cells      = jqoRow[0].cells;
		var cloneCells = cloneRow[0].cells;
		
		_copyAttributes(_jqoTable, cloneTable, "Table");
		_copyAttributes(_jqoTable.children("tbody").first(), cloneTBody);
		_copyAttributes(jqoRow, cloneRow);
		for(var idx = 0; idx < cells.length; idx++){
			_copyAttributes($(cells[idx]), $(cloneCells[idx]), "Cell");
		}
		
		_jqoFakeTable = cloneTable;
		_jqoFakeTable
			.css('position', 'absolute')
			.css('box-shadow', '2px 2px 5px #888888')
			.css('z-index', 1)
			.css('opacity', 0.8);
		
		_moveCloneRow(true);
	};
	var _buildShadowDrop = function(){
		_jqoFakeRow = $("<div>")
			.appendTo("body")
			.width (_moving.row.jqoElement.outerWidth())
			.height(_moving.row.jqoElement.outerHeight())
			.css('background', '#FFF')
			.css('position', 'absolute')
			.css('text-align', 'center')
			.css('opacity', 0)
			.html("");
		
		_moveShadowDrop(true);
	};
	
	var _moveCloneRow   = function(firstMove){
		// Position floater....
		var oRow     = _moving.row;
		var newLeft  = _moving.curPos.left - 
		(_moving.oPos.left - oRow.left) + 
		_settings.mouseOffset.left;
		var newTop   = _moving.curPos.top  - (_moving.oPos.top  - oRow.top)  + _settings.mouseOffset.top;
		if(firstMove){
			_jqoFakeTable.fadeIn();
		}
		_jqoFakeTable.offset ({ left: newLeft, top:  newTop });
	};
	var _moveShadowDrop = function(firstMove){
		var oRow = _moving.row;
		if(firstMove){
			_jqoFakeRow.offset( oRow.jqoElement.offset() );
			_jqoFakeRow.animate({ opacity: 0.9 }, 'fast');
		}
		else{
			// _jqoFakeRow.clearQueue().animate( oRow.jqoElement.offset(), 'fast' );
			_jqoFakeRow.offset( oRow.jqoElement.offset() );
		}
	};
	var _makeReturn  = function(){
		return {
			jqoTable:    _jqoTable,
			jqoRow:      _moving.row.jqoElement,
			jqoRowAbove: _moving.row.jqoElement.prev("tr"), // not...?
			jqoRowBelow: _moving.row.jqoElement.next("tr"), // not...?
			rowIndex:    (_moving.row.jqoElement[0].rowIndex - $("thead>tr", _jqoTable).length)
		};
	};
	
	// Moving handling...
	var _initMoving  = function(){
		_isMoving = true;
		_loadRowsPosition();
		
		_moving.row = _getRowFromPos(_moving.oPos);
		_oldInfo    = _makeReturn();
		
		_buildCloneRow();
		_buildShadowDrop();
		
		if(_settings.cbOnStart)
			_settings.cbOnStart(_oldInfo);
	};
	var _whileMoving = function(){
		_moveCloneRow();
		
		var overTr   = _getRowFromPos(_moving.curPos);
		var movingTr = _moving.row.jqoElement;
		
		if(movingTr.is(overTr.jqoElement)){
			return true;
		}
		
		if(overTr.closerTo == 'top'){
			if(overTr.jqoElement.prev().is(_moving.row.jqoElement))
				return true;
			overTr.jqoElement.before(_moving.row.jqoElement);
		}
		else{
			if(overTr.jqoElement.next().is(_moving.row.jqoElement))
				return true;
			overTr.jqoElement.after (_moving.row.jqoElement);
		}
		
		if(_settings.cbOnOver)
			_settings.cbOnOver(_oldInfo, _makeReturn());
		
		_loadRowsPosition();
		_moveShadowDrop();
		
		if(_settings.cbOnMove)
			_settings.cbOnMove(_oldInfo, _makeReturn());
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
		
		var localJqoFakeTable = _jqoFakeTable;
		var localJqoFakeRow   = _jqoFakeRow;
		
		_jqoFakeTable.animate( _jqoFakeRow.offset(), function(){
			if(!_isMoving){
				localJqoFakeTable.fadeOut(function(){
					if($(this).is(":hidden"))
						$(this).remove();
				});
			}
			localJqoFakeRow.remove();
		});
	};
	
	// Event handling...
	var _onPress     = function(ev){
		_jqoTable = $(ev.currentTarget);
		_settings = _jqoTable.prop('drd-settings');
		
		var canMove = $(ev.target).closest('tbody').closest(_jqoTable).length;
		if(canMove && _settings.cbCanMove){
			canMove = _settings.cbCanMove(ev);
		}
		
		if(!canMove){
			// Accepts the click.
			return true;
		}
		
		// Refuses the click and consider dragging.
		$(document)
			.on('mousemove', _onMove)
			.on('mouseup',   _onRelease);
		
		_isMoving     = false;
		_moving.oPos  = _getMousePos(ev);
		
		return false;
	};
	var _onMove      = function(ev){
		var pos = _getMousePos(ev);
		if(!_isMoving && Math.abs(pos.top - _moving.oPos.top) > 1)
			_initMoving();
		
		if(_isMoving){
			_moving.curPos  = pos;
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
		return { left: ev.pageX, top: ev.pageY }
	};
	
	// Helpers...
	var _copyAttributes = function(from, to, what){
		to.addClass(from[0].className);
		for(var idx in cloneCss){ to.css (cloneCss[idx], from.css(cloneCss[idx]));  };
		for(var idx in cloneAtt){ to.attr(cloneAtt[idx], from.attr(cloneAtt[idx])); };
		if(what == 'Table'){
			to.width(from.outerWidth());
		}
		if(what == 'Cell'){
			to.width(from.width());
		}
	};
};
dRowDrag.start = function(){
	if(!dRowDrag.isStarted){
		dRowDrag.isStarted = true;
		dRowDrag.instance  = new dRowDrag;
	}
	return dRowDrag.instance;
};
