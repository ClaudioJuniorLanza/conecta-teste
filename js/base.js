/**
 * Base.js: Oferece soluções rápidas para problemas comuns do dia-a-dia.
 * Após o deployment, é opcional remover alguns ou todos os códigos aqui presentes.
**/
imaginacom = {
    viewport: { // Retorna valores compatíveis com @media query
        width:  function(){ return window.innerWidth  || document.documentElement.clientWidth  || document.body.clientWidth  },
        height: function(){ return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight },
    },
    isMobile: function(){ // Retorna se é mobile ou não.
        return (typeof window.orientation !== 'undefined');
    }
};

// Acender automaticamente menuSel, se houver.
$(function(){
	if(menuSel){
		$("header a[href^='"+menuSel+"'],header a[rel='"+menuSel+"']").addClass('sel');
	}
});

$(function(){
	// Mobile Scrollbar Issues Fix:
	//   --> Resolve problema com banners <div height="100%">
	//   --> Quando utilizar a classe '.fixedHeight'?
	//       Apenas na exibição de banners/conteúdo que você deseje
	//       que ocupe toda a altura da tela, e sabe que haverá conteúdo
	//       abaixo disso. Por exemplo com a frase "Role para baixo para continuar".
	//   --> Como utilizar:
	//       Utilize style="height: 100vh + 60px" class="fitScreen"
	//
	//   * Veja a documentação em:
	//     https://docs.google.com/document/d/15WmWsfU9xyGwbIYF3Fij8Gthda-V-vumkVQcyjRKsqc
	//
    if(!imaginacom.isMobile())
        return;

    var _lastWidth = $(window).width();
    $(".fitScreen").each(function(){
        $(this).css({
            'min-height': $(this).height(),
            'height': '',
        });
    });
    $(window).resize(function(){
        var newWidth = $(window).width();
        if(_lastWidth == newWidth) {
            return;
        }
        _lastWidth = newWidth;

        $(".fitScreen").each(function(){
            if($(window).scrollTop() > $(this).position().top){
                return;
            }
            $(this).css({
                'min-height': $(window).height(),
                'height': '',
            });
        });
    })
});

$(function(){
	// MediaPort Helper
    var _onResize = function () {
        // Media Viewport (Janela inteira, ignora a existência de ScrollBar)
        // Deve ser utilizado para consistência com @media query
        var vpW = imaginacom.viewport.width();
        var vpH = imaginacom.viewport.height();

        // Available Viewport (Área disponível p/ conteúdo, considerando a ScrollBar)
        // Deve ser utilizado para definir tamanho de elementos na tela.
        var avW = $(window).width();
        var avH = $(window).height();

        $("#debugResponsive").css('text-align', 'right').html(
//            "Window Resolution:   "+(screen.availWidth)+"x"+(screen.availHeight)+"<br />"+
//            "Viewport(available): "+avW+"x"+avH+"<br />"+
            vpW+"x"+vpH+"<br />"
        );

    };
    $(window).resize(_onResize);
    _onResize();
});

/*
// Banner com dImageSwitch e 100% de largura
// --> Defina um RATIO (ex: 1900/800)
// --> Ajuste o nome do elemento em getElementById
(function(){
	var obj   = document.getElementById('mainBanner');
	var ratio = 1320/600;
	var ubh = function(){
		obj.style.height = obj.clientWidth/ratio;
	};
	window.addEventListener('resize', ubh)
	ubh();
	
	// Opcional:
	$(function(){
		var dis = $("#mainBanner").dImageSwitch({
			setTaller: false,
		});;
	});
})();
*/