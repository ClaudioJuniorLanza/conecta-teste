// Last review: 2013-03-20
/**
	jquery-dTextAlign.js
	@author Alexandre Tedeschi
	@email  alexandrebr at gmail dot com

	dTextAlign(options)
		
		options:
			int maxLines;
	
**/
(function( $ ) {
	var _splitNodeValue = function(nodeEl){
		var $nodeEl        = $(nodeEl);
		var startWithSpace = nodeEl.nodeValue.match(/^[ \r\n\t]/);
		var endWithSpace   = nodeEl.nodeValue.match(/[ \r\n\t]$/);
		var nodeWords      = nodeEl.nodeValue
			.replace(/^[ \r\n\t]+/, "")
			.replace(/[ \r\n\t]+$/, "")
			.split(" ");
		
		// console.log("Reconstruindo frase: '"+nodeEl.nodeValue+"'");
		for(var i = 0; i < nodeWords.length; i++){
			var isFirst = (i==0);
			var isLast  = (i+1==nodeWords.length);
			var newWord = ((isFirst&&startWithSpace)?' ':'')+nodeWords[i]+((!isLast||endWithSpace)?' ':'');
			if(!newWord.length){
				continue;
			}
			
			var newNode = $("<dText>").html(newWord);
			
			$nodeEl.before(newNode);
			nodeEl.nodeValue = nodeEl.nodeValue.substr(newWord.length);
		}
		$(nodeEl).remove();
	}
	var _splitNodes     = function(jqoContainer){
		jqoContainer.contents().each(function(){
			if(this.nodeType == 3){
				_splitNodeValue(this);
			}
			else{
				if($(this).css('display') == 'inline' || $(this).css('display') == 'inline-block'){
					_splitNodes($(this));
				}
			}
		});
		return true;
	}
	
	var _getLines       = function(jqoContainer){
		var contents = $("dText", jqoContainer).not(":hidden").add($("br", jqoContainer));
		if(!contents.length)
			return false;
		
		var toRestore   = [];
		var lastHeight  = jqoContainer.innerHeight();
		var curHeight   = false;
		var lines       = [];
		var currentLine = 0;
		for(var i = contents.length-1; i >= 0; i--){
			if(!lines[currentLine]){
				lines.push([]);
			}
			
			lines[currentLine].push(contents[i]);
			$(contents[i]).hide();
			toRestore.push(contents[i]);
			
			curHeight = jqoContainer.innerHeight();
			if(lastHeight != curHeight || i == 0){
				lines[currentLine] = $(lines[currentLine].reverse());
				lastHeight = curHeight;
				currentLine++;
			}
		}
		lines = lines.reverse();
		$(toRestore).show();
		return lines;
	}
	var _limitLines     = function(jqoContainer, nLines){
		var appendStr = '...';
		_splitNodes(jqoContainer);
		
		$(".hideByLimitLines", jqoContainer).removeClass('hideByLimitLines').show();
		$("[oInnerHtml]", jqoContainer).each(function(){
			$(this).html($(this).attr('oInnerHtml')).removeAttr('oInnerHtml');
		});
		var lines = _getLines(jqoContainer);
		if(lines.length <= nLines){
			return true;
		}
		
		$(lines.slice(nLines)).each(function(){ this.addClass('hideByLimitLines').hide(); });
		
		var tries = 3;
		do{
			lines = _getLines(jqoContainer);
			var lastLine = $(lines[lines.length-1]).filter('dText');
			var lastWord = lastLine.last();
			var oHtml    = lastWord.html();
			lastWord.attr('oInnerHtml', oHtml);
			lastWord.html($.trim(oHtml)+appendStr);
			lines = _getLines(jqoContainer);
			if(lines.length > nLines){
				lastWord.hide();
				lastWord.html(lastWord.attr('oInnerHtml'));
			}
		}
		while(lines.length > nLines && tries--);
	}
	
	$.fn.dTextAlign = function(options){
		options = $.extend({
			maxLines: false
		}, options);
		
		$(this).each(function(){
			if(options.maxLines){
				_limitLines($(this), options.maxLines);
			}
		});
	};
}) (jQuery);

