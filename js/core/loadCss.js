/**
	loadJs.js - IMAGINACOM 2017
	--------------------------------------------
		Versão original: https://github.com/filamentgroup/loadCSS/blob/master/src/loadCSS.js
		Minified por:    jscompress.com
		Última revisão:  2017-01-02
		
	Alterações realizadas sobre o código original:
	- Cleanup (removidos parâmetros before, media e as partes relevantes no código)
	- Renamed (método renomeado de loadCSS para loadCss)
**/
function loadCss( href ){
	// Arguments explained:
	// `href` [REQUIRED] is the URL for your CSS file.
	var w    = window;
	var doc  = w.document;
	var ss   = doc.createElement( "link" );
	var refs = ( doc.body || doc.getElementsByTagName( "head" )[ 0 ] ).childNodes;
	var ref  = refs[ refs.length - 1];

	var sheets = doc.styleSheets;
	ss.rel = "stylesheet";
	ss.href = href;
	ss.media = "only x";

	// wait until body is defined before injecting link. This ensures a non-blocking load in IE11.
	function ready( cb ){
		if( doc.body ){
			return cb();
		}
		setTimeout(function(){
			ready( cb );
		});
	}
	
	// Inject link
	// Note: the ternary preserves the existing behavior of "before" argument, but we could choose to change the argument to "after" in a later release and standardize on ref.nextSibling for all refs
	// Note: `insertBefore` is used instead of `appendChild`, for safety re: http://www.paulirish.com/2011/surefire-dom-element-insertion/
	ready( function(){
		ref.parentNode.insertBefore(ss, ref.nextSibling);
	});
	
	// A method (exposed on return object for external use) that mimics onload by polling document.styleSheets until it includes the new sheet.
	var onloadcssdefined = function( cb ){
		var resolvedHref = ss.href;
		var i = sheets.length;
		while( i-- ){
			if( sheets[ i ].href === resolvedHref ){
				return cb();
			}
		}
		setTimeout(function() {
			onloadcssdefined( cb );
		});
	};

	function loadCB(){
		if( ss.addEventListener ){
			ss.removeEventListener( "load", loadCB );
		}
		ss.media = "all";
	}

	// once loaded, set link's media back to `all` so that the stylesheet applies once it loads
	if( ss.addEventListener ){
		ss.addEventListener( "load", loadCB);
	}
	
	ss.onloadcssdefined = onloadcssdefined;
	onloadcssdefined( loadCB );
	return ss;
};

