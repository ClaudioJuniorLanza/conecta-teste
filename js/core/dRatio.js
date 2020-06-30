/**
 * .dRatio Handler
 *
 * <div class='dRatio' data-ratio='(ratio)' data-ratio_keep='w'>
 *     (ratio):        Width/Height. Ex: 400/200 ou 4:2 ou 2.
 *     (ratio_change): What css property to change. Ex: w, h | max-w, max-h | min-w | max-w.
 *
 *  Opcional:
 *      dRatio.enable()  --> Re-ativa o dRatio. --> Enable by default
 *      dRatio.disable() --> Desativa o dRatio.
 *      dRatio.render()  --> Render immediately.
 *
 *  And more:
 *      Add    ".dRatio" at any time. Use "dRatio.render()" after.
 *      Remove ".dRatio" at any time.
 *      Modify "data-ratio" or "data-ratio_change" at any time. Use "dRatio.render()" after.
 *      Combine with css "min/max-width" and "min-max-height".
 *
 *  Note:
 *      If there's no ".dRatio" element found, dRatio will auto disable itself.
 *      Use "dRatio_Disable = true" before including this file to disable auto enabling.
 */
dRatio = {
	render:  function(){
		if(this.disable){
			return false;
		}
		
		var objs = document.getElementsByClassName('dRatio');
		if(!objs.length){
			dRatio.disable();
			return false;
		}
		for(var i = 0; i < objs.length; i++){
			var _obj          = objs[i];
			var _data_ratio   = _obj.dataset.ratio;        // 4:2 or 4/2 or 2.
			var _change_prop  = _obj.dataset.ratio_change; // w | h | min-w | min-h | max-w | max-h
			var _ratio        = eval(_data_ratio.replace(':', '/'));
			if(!_ratio){
//				console.log("dRatio: Failed to render. Unknown ratio " + _data_ratio + " for ", _obj);
				continue;
			}
			if(!_change_prop){
				_change_prop = 'height';
			}
			
			var _len        = _change_prop.length;
			if(_len == 1 || _len == 5){
				_change_prop += (_change_prop.substr(-1)=='w')?'idth':'eight';
			}
			
			if(_change_prop.indexOf('height') != -1){
				// Keep width
				// console.log("Keep width. New height: ", (_obj.clientWidth / _ratio));
				_obj.style[_change_prop] = (_obj.clientWidth / _ratio) + "px";
			}
			else{
				// Keep height
				_obj.style[_change_prop] = (_obj.clientHeight * _ratio) + "px";
			}
		}
	},
	disable: function(){
		window.removeEventListener("resize", dRatio.render);
		window.removeEventListener('DOMContentLoaded', dRatio.render, false);
		document.body.removeEventListener("load", dRatio.render, false);
	},
	enable:  function(){
		window.addEventListener("resize", dRatio.render);
		window.addEventListener('DOMContentLoaded', dRatio.render, false);
		document.body.addEventListener("load", dRatio.render, false);
	},
};
if(!('dRatio_Disable' in window)){
	dRatio.enable();
}
