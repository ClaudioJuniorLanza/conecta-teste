/**
 * Base.js: Oferece soluções rápidas para problemas comuns do dia-a-dia.
 * Após o deployment, é opcional remover alguns ou todos os códigos aqui presentes.
 **/
imaginacom = {
    viewport: { // Retorna valores compatíveis com @media query
        width: function () {
            return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth
        },
        height: function () {
            return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight
        },
    },
    isMobile: function () { // Retorna se é mobile ou não.
        return (typeof window.orientation !== 'undefined');
    }
};

$(function () {
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
            vpW + "x" + vpH + "<br />"
        );

    };
    $(window).resize(_onResize);
    _onResize();
});

$(function () {
    // Formata o SlideSwitch
    $("input:checkbox.sliderSwitch").each(function () {
        // Antes: input:checked.sliderSwitch
        // Depois:
        // .sliderSwitch(input:checkbox, span.slider)
        $(this)
            .removeClass('sliderSwitch')
            .wrap("<b class='sliderSwitch'>")
            .after("<span class='slider'></span>");
    });

    // Ativa/Desativa ToggleNext
    var _toggleNext = function (jqoRef, skipAnimation) {
        var isCbox = (jqoRef.tagName.toLowerCase() == 'input' && jqoRef.type.toLowerCase() == 'checkbox');
        var jqoNext = _findNext($(jqoRef));
        var jqoPare = $(jqoRef).parent();
        while (!jqoNext.length && jqoPare.length) {
            jqoNext = _findNext(jqoPare);
            jqoPare = jqoPare.parent();
        }
        if (!jqoNext.length) {
            return false;
        }

        if (isCbox) {
            if (skipAnimation) {
                jqoRef.checked ?
                    jqoNext.show() :
                    jqoNext.hide();
            } else {
                jqoRef.checked ?
                    jqoNext.slideDown() :
                    jqoNext.slideUp();
            }

            jqoRef.checked ?
                jqoNext.addClass('shown') :
                jqoNext.removeClass('shown');

            return true;
        }

        if (jqoNext.is(":hidden")) {
            $(jqoRef).addClass('shown');
            skipAnimation ? jqoNext.show() : jqoNext.slideDown();
        } else {
            $(jqoRef).removeClass('shown');
            skipAnimation ? jqoNext.hide() : jqoNext.slideUp();
        }

        return false;
    };
    var _findNext = function (jqoRef) {
        var jqoNext = jqoRef.next();
        while (jqoNext.is("br,label,span") && jqoNext.length) {
            jqoNext = jqoNext.next();
        }

        return jqoNext;
    };
    $("input:checkbox.toggleNext").each(function () {
        // Set initial state.
        _toggleNext(this, true);
    });
    $(".toggleNext").click(function () {
        // Click effect.
        _toggleNext(this);
        return false;
    });

    // Aplica funcionalidade nos dropFilterBy
    var _setGetParameter = function (paramName, paramValue) {
        var url = window.location.href;
        var hash = location.hash;
        url = url.replace(hash, '');
        if (url.indexOf(paramName + "=") >= 0) {
            var prefix = url.substring(0, url.indexOf(paramName + '='));
            var suffix = url.substring(url.indexOf(paramName + '='));
            suffix = suffix.substring(suffix.indexOf("=") + 1);
            suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
            url = prefix + paramName + "=" + paramValue + suffix;
        } else {
            if (url.indexOf("?") < 0) {
                url += "?" + paramName + "=" + paramValue;
            } else {
                url += "&" + paramName + "=" + paramValue;
            }
        }

        window.location.href = url + hash;
    };

    $("#dropFilterBy").change(function () {
        _setGetParameter("f", this.value);
    });
    $("#dropFilterByVarie").change(function () {
        _setGetParameter("v", this.value);
    });
})