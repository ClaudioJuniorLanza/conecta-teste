function dEasyRefresh(){}
dEasyRefresh.timer=!1;dEasyRefresh.paused='unset';dEasyRefresh.lastRet=!1;dEasyRefresh.jqoWidget=!1;dEasyRefresh.relPath='';dEasyRefresh.toHerePath='';dEasyRefresh.interval=1000;dEasyRefresh.toggleStatus=function(){if(dEasyRefresh.paused)
dEasyRefresh.pause(!1);else dEasyRefresh.pause(!0);dEasyRefresh.updateStatus();return!1};dEasyRefresh.cookiePlugin=function(){if($.cookie)
return!0;var pluses=/\+/g;function raw(s){return s}
function decoded(s){return decodeURIComponent(s.replace(pluses,' '))}
var config=$.cookie=function(key,value,options){if(value!==undefined){options=$.extend({},config.defaults,options);if(value===null){options.expires=-1}
if(typeof options.expires==='number'){var days=options.expires,t=options.expires=new Date();t.setDate(t.getDate()+days)}
value=config.json?JSON.stringify(value):String(value);return(document.cookie=[encodeURIComponent(key),'=',config.raw?value:encodeURIComponent(value),options.expires?'; expires='+options.expires.toUTCString():'',options.path?'; path='+options.path:'',options.domain?'; domain='+options.domain:'',options.secure?'; secure':''].join(''))}
var decode=config.raw?raw:decoded;var cookies=document.cookie.split('; ');for(var i=0,l=cookies.length;i<l;i++){var parts=cookies[i].split('=');if(decode(parts.shift())===key){var cookie=decode(parts.join('='));return config.json?JSON.parse(cookie):cookie}}
return null};config.defaults={};$.removeCookie=function(key,options){if($.cookie(key)!==null){$.cookie(key,null,options);return!0}
return!1}};dEasyRefresh.hitTimer=function(){var cssList=[];var jqoCssList=$("link[rel=stylesheet][data-filelist]").each(function(){cssList.push({file:$(this).data('filelist'),curFile:$(this).attr('href'),params:$(this).data('params')})});var _relPath=(dEasyRefresh.relPath?dEasyRefresh.relPath:'');var _postUrl=_relPath+"_dEasyRefresh.php?seed="+((new Date).getTime());$.post(_postUrl,{'cssList':cssList,relPath:_relPath,toHerePath:dEasyRefresh.toHerePath},function(ret){if(ret.css&&Object.keys(ret.css).length){for(var file in ret.css){var jqoEx=$("link[rel=stylesheet][data-filelist='"+file+"']");if(jqoEx.length){if(jqoEx.attr('href')!=ret.css[file]){console.log("Atualizando CSS de "+file+" para "+ret.css[file]);jqoEx.attr('href',ret.css[file]);}}}}
if(dEasyRefresh.lastRet&&ret.checksum!=dEasyRefresh.lastRet){setTimeout(function(){location.reload()},100)}
dEasyRefresh.lastRet=ret.checksum;if(dEasyRefresh.timer){clearTimeout(dEasyRefresh.timer)}
dEasyRefresh.timer=setTimeout(dEasyRefresh.hitTimer,dEasyRefresh.interval)})};dEasyRefresh.pause=function(yesno,ignoreCookie){dEasyRefresh.paused=yesno;clearTimeout(dEasyRefresh.timer);if(yesno){if(!ignoreCookie)
$.cookie("dEasyRefresh-autoPause",'yes')}
else{dEasyRefresh.timer=setTimeout(dEasyRefresh.hitTimer,dEasyRefresh.interval);if(!ignoreCookie)
$.cookie("dEasyRefresh-autoPause",'no')}
dEasyRefresh.updateStatus()};dEasyRefresh.updateStatus=function(){if(!dEasyRefresh.jqoWidget){var _findIt=$("#dEasyRefresh-Widget");if(!_findIt.length){setTimeout(dEasyRefresh.updateStatus,500);return}
dEasyRefresh.jqoWidget=_findIt}
if(dEasyRefresh.paused){dEasyRefresh.jqoWidget.attr('title','dEasyRefresh: Paused').css('background','#F00')}
else{dEasyRefresh.jqoWidget.attr('title','dEasyRefresh: Running').css('background','#080')}}
$(function(){dEasyRefresh.cookiePlugin();dEasyRefresh.jqoWidget=$("<a>").css('display','block').css('position','fixed').css('right',0).css('top',0).css('width',16).css('height',16).css('background','#F00').css('z-index',100).attr('title','dEasyRefresh').appendTo("body").css('opacity',0.1).click(dEasyRefresh.toggleStatus).mouseover(function(){$(this).animate({height:32,width:32,opacity:1})}).mouseout(function(){$(this).animate({height:16,width:16,opacity:.1})});if(!dEasyRefresh.relPath){var _jqoScript=$("script[src*='jquery-dEasyRefresh.js'][relPath]");if(_jqoScript.length){dEasyRefresh.relPath=_jqoScript.attr('relPath')}}
if(dEasyRefresh.paused=='unset'){dEasyRefresh.pause($.cookie("dEasyRefresh-autoPause")=='yes')}
dEasyRefresh.updateStatus()})