// Last review: 2012-12-07
/**
	jquery-clickOutside.js
	@author Alexandre Tedeschi
	@email  alexandrebr at gmail dot com

	dClickOutside(options, callback)
	dClickOutside(callback, options)
		
		options:
			bool     onlyWhenVisible
			selector ignoreList
		
		callback(clickedOn)
			Don't need to return anything.
**/
(function( $ ) {
	$.fn.dClickOutside = function(options, cb){
		if(typeof options == 'function'){
			var tmp = options;
			options = cb;
			cb      = tmp;
			delete tmp;
		}
		var options = $.extend({
			onlyWhenVisible: true,
			ignoreList:      false
		}, options);
		
		this.each(function(){
			var mainEl = $(this);
			
			// Unbind existing handler....
			if(mainEl.data('cbClickOutsideTurnOff')){
				mainEl.data('cbClickOutsideTurnOff').call();
				mainEl.removeData('cbClickOutsideTurnOff');
			}
			if(!cb){
				// Disable handler, already done that.
				return true;
			}
			
			var _handleClick = function(ev){
				// If clicked in the same element, do nothing.
				// if clicked on a child of that element, do nothing.
				var clickedOn = $(ev.target);
				if(!clickedOn.is(mainEl) && clickedOn.parents().index(mainEl) == -1){
					if(options.onlyWhenVisible && !mainEl.is(":visible")){
						// I am not visible, ignore.
						return true;
					}
					if(options.ignoreList && (clickedOn.is(options.ignoreList)||clickedOn.closest(options.ignoreList).length)){
						// Clicked on something in the ignore list.
						return true;
					}
					
					cb.call(mainEl, clickedOn, ev);
				}
			};
			var _disableClick = function(){
				$(document).off('mousedown', _handleClick);
			};
			
			$(document).on('mousedown', _handleClick);
			mainEl.data('cbClickOutsideTurnOff',  _disableClick);
			
			return mainEl;
		});
		
		return this;
	};
	
	var oldRemove = $.fn.remove;
	$.fn.remove  = function(){
		$(this).dClickOutside(false);
		return oldRemove.apply(this, arguments);
	}
}) (jQuery);

