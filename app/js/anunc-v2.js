$(function(){
	var _reloadCounter = function(newCounter){
		if(typeof newCounter != 'object'){
			return;
		}
		
		var oCompra = parseInt(newCounter.oportunidades.Compra);
		var oVenda = parseInt(newCounter.oportunidades.Venda);
		var oTroca = parseInt(newCounter.oportunidades.Troca);
		var oTotal = oCompra + oVenda + oTroca;
		
		$("a[rel=oportunidades-comprar] .count").html(oVenda?oVenda:'');
		$("a[rel=oportunidades-vender] .count").html(oCompra?oCompra:'');
		$("a[rel=oportunidades-trocar] .count").html(oTroca?oTroca:'');
		$("a[rel=oportunidades-comprar]").closest(".submenu").prev().find(".count").html(oTotal?oTotal:'');
	};
	var _markAsRead = function(jqoRow){
		var jqoNew = $(".new", jqoRow);
		
		if(!jqoNew.length){ // Não estou marcado como new.
			return;
		}
		
		jqoNew.fadeOut(function(){
			jqoNew.remove();
		});
		$.post("ajax.listauctions.php", {
			action:   'markAsRead',
			anuncCod: jqoRow.attr('rel'),
		}, _reloadCounter);
	};
	
	// Detalhes (Mais informações)
	$('.btnMaisInformacoes').click(function(){
		var jqoBtn    = $(this);
		var jqoText   = jqoBtn.find('span');
		var jqoRow    = jqoBtn.closest('.anuncRow');
		var jqoExpand = jqoBtn.closest('.moreAbout').find('.expand');
		var jqoOffer  = jqoBtn.closest('.actions').find(".offerHolder");
		
		if(!jqoBtn.data('wfixed')){
			jqoText.width(jqoText.width() + 1);
			jqoBtn.data('wfixed', '1');
		}
		if(jqoExpand.is(":hidden")){
			_markAsRead(jqoRow);
			jqoExpand.slideDown();
			jqoBtn.addClass('btnClose');
			jqoText.html('Fechar detalhes');
			jqoOffer.fadeIn();
		}
		else{
			jqoExpand.slideUp();
			jqoBtn.removeClass('btnClose');
			jqoText.html('Mais informações');
			jqoOffer.fadeOut();
		}
		
		return false;
	});
	
	// Não estou interessado
	var _dcoLoaded = false;
	$('.btnNotInterested').click(function(){
		var jqoRow = $(this).closest('.anuncRow');
		var jqoExp = $('.expandNi', jqoRow);
		jqoExp.slideToggle();
		
		_markAsRead(jqoRow);
		
		if(!$(this).data('dcoLoaded')){
			$(this).data('dcoLoaded', '1');
			$(this).dClickOutside(function(){
				jqoExp.fadeOut();
			}, { ignoreList: jqoExp });
		}
		return false;
	});
	$(".expandNi a").click(function(){
		if($(this).hasClass('managePrefs')){
			return true;
		}
		
		var jqoRow = $(this).closest('.anuncRow');
		var jqoExp = $(this).closest('.expandNi');
		var what   = $(this).attr('rel');
		
		// Funcionamento:
		// --> Neste anuncio? Remove apenas ele.
		// --> O restante? Vai para "Minhas Preferências"
		
		// Não tenho interesse em:
		// 	 what: this, cultura, variedade, embalagem, regiao
		if(what == 'this'){
			$.post("ajax.listauctions.php", {
				action:   'notInterested',
				anuncCod: jqoRow.attr('rel'),
			});
			jqoRow.css('background', '#EEE').slideUp('slow', function(){ $(this).remove(); })
		}
		else{
			// cultura, variedade, embalagem, regiao
			var value = jqoRow.data(what);
			$.post("ajax.meus-interesses.php", {
				action: 'notInterested',
				group:  jqoRow.data('negocio'),
				what:   what,
				target: value,
			}, function(ret){
				// For debug:
				// $(".boxDestaque").html(ret);
			});
			
			jqoRow.closest(".anuncListV2")
				.find(".anuncRow[data-"+what+"='"+value+"']")
				.css('background', '#EEE')
				.slideUp('slow', function(){ $(this).remove(); });
		}
		jqoExp.fadeOut();
		
		return false;
	});
	
	// Quero fazer uma oferta
	$(".offerExpand input[name=valor]").blur(function(){
		var valor = $(this).val();
		if(!dHelper2.forceFloat(valor)){
			$(this).val('');
			return;
		}
		
		$(this).val(dHelper2.moeda(valor));
	});
	$('.btnToggleOffer').click(function(){
		var jqoExpand = $(this).closest('.makeOfferGroup').find('.offerExpand,.introText');
		var jqoArrow  = $(this).find('.fa');
		if(jqoExpand.is(':hidden')){
			jqoExpand.slideDown();
			jqoArrow.css('transform', 'rotate(90deg)');
			
		}
		else{
			jqoExpand.slideUp();
			jqoArrow.css('transform', '');
		}
		return false;
	});
	$('.btnCloseDetails').click(function(){
		$(this).closest('.makeOfferGroup').find('.btnToggleOffer').click();
		return false;
	});
	$('.btnCloseIntro').click(function(){
		$('.introText').slideUp(function(){
			$(this).remove();
		});
		return false;
	});
	$('.btnConfirmOffer').click(function(){
		if($(this).data('in-progress')){
			return false;
		}
		
		// Init Loading
		var jqoBtn = $(this);
		if(!jqoBtn.data('set-width')){
			jqoBtn.outerWidth(jqoBtn.outerWidth());
			jqoBtn.data('set-width', 'yes');
		}
		jqoBtn.data('in-progress', $(this).get(0).innerHTML);
		jqoBtn.html("<i class='fa fa-spinner fa-spin'></i> Aguarde...");
		
		// End Loading
		var _endLoading = function(success){
			if(!success){
				jqoBtn.html(jqoBtn.data('in-progress'));
				jqoBtn.removeData('in-progress');
			}
			else{
				jqoBtn.html("<i class='fa fa-check'></i> Sucesso!");
			}
		};
		
		
		var jqoAnunc  = $(this).closest('.anuncRow');
		var jqoOffer  = $(".offerExpand", jqoAnunc);
		var isTroca   = (jqoAnunc.data('negocio')=='Troca');
		var formData  = { };
		var errorList = [];
		$.each(jqoOffer.serializeArray(), function() {
		    formData[this.name] = this.value;
		});
		
		// Validação client-side:
		if(!isTroca && ((!formData.valor.length || dHelper2.forceFloat(formData.valor) < 1))){
			errorList.push("Faça uma oferta com valor válido.");
		}
		if(!formData.regiao.length){
			errorList.push("Informe a região");
		}
		if(!formData.acceptTerms){
			errorList.push("Você precisa concordar com os termos do contrato");
		}
		
		if(errorList.length){
			alert(errorList.join("\n"));
			_endLoading(false);
			return false;
		}
		
		$.post("ajax.listauctions.php", {
			action:   'setProposta',
			anuncCod: jqoAnunc.attr('rel'),
			valor:    isTroca?null:formData.valor,
			regiao:   formData.regiao,
			justificativa: formData.justifique,
		}, function(ret){
			// To-do: This is ONLY for debug purposes.
			//     $(".introText").html(ret);
			if(ret == 'OK'){
				$([document.documentElement, document.body]).animate({
			        scrollTop: jqoAnunc.offset().top - 64
			    });
				$(".offerReceived", jqoAnunc).slideDown(function(){ // Show success message.
					$(".introText").hide();                     // Hide ALL intro text (helpers).
					$(".offerExpand", jqoAnunc).remove();       // Destroy offer placement.
					$(".btnToggleOffer", jqoAnunc).remove();    // Destroy offer placement.
				});
				$(".btnNotInterested", jqoAnunc).fadeOut(); // Remove 'not interested' button
				$(".btnMaisInformacoes", jqoAnunc).removeClass('green').click();
				_endLoading(true);
			}
			else{
				alert(ret);
				_endLoading(false);
			}
		}).fail(function(ret){
			alert("Não foi possível enviar sua proposta. Erro desconhecido.");
			_endLoading(false);
		});
		
		return false;
	});
	$("select[name=regiao]").change(function(){
		// Se mudar a região em um item, muda em todos.
		$("select[name=regiao]").not(this).val($(this).val());
	});
	
	// Encerrar ou Reativar anúncio
	$(".btnRemoverAnunc").click(function(){
		if(confirm("Deseja deixar de receber propostas para este anúncio?")){
			var jqoBtn    = $(this);
			var jqoAnunc  = $(this).closest('.anuncRow');
			if(!jqoBtn.data('fixedsize')){
				jqoBtn.outerWidth(jqoBtn.outerWidth());
				jqoBtn.outerHeight(jqoBtn.outerHeight());
			}
			if(jqoBtn.data('loading')){
				return false;
			}
			
			jqoBtn.data('loading', 'yes');
			jqoBtn.data('origContent', jqoBtn.html());
			jqoBtn.css('padding-top', '12px')
			jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
			
			var _reEnable = function(){
				jqoBtn.html(jqoBtn.data('origContent'));
				jqoBtn.css('padding-top', '');
				jqoBtn.removeData('loading');
			};
			
			$.post("ajax.listauctions.php", {
				action:  'encerrarAnuncio',
				anuncCod: jqoAnunc.attr('rel'),
			}, function(ret){
				if(ret == 'OK'){
					jqoBtn.html("<b>Anúncio encerrado.</b>");
					setTimeout(function(){
						jqoAnunc.slideUp(function(){
							$(this).remove();
							if(!$(".anuncRow").length){
								location.reload();
							}
						});
					}, 750);
				}
				else{
					alert(ret);
					_reEnable();
				}
			}).fail(_reEnable);
		}
		return false;
	});
	$(".btnReactivateAnunc").click(function(){
		var jqoAnunc = $(this).closest('.anuncRow');
		location.href = "newauction.php?type="+(jqoAnunc.data('negocio').toLowerCase()+"&copyfrom="+jqoAnunc.attr('rel'));
		return false;
	});
	
	$(".listOffers .offerRow").each(function(){
		var propoId   = $(this).attr('rel');
		var jqoAnunc  = $(this).closest('.anuncRow');
		var cbTermos  = $(this).find("input:checkbox[name=acceptTerms]");
		var btnAccept = $(this).find('.btnPropoAccept');
		var btnPropoSemInteresse = $(this).find('.btnPropoSemInteresse');
		
		btnAccept.click(function(){
			if(!cbTermos.is(":checked")){
				alert("Você precisa aceitar os termos e condições do sistema antes de prosseguir");
				return false;
			}
			
			var jqoBtn = $(this);
			if(!jqoBtn.data('fixedsize')){
				jqoBtn.outerWidth(jqoBtn.outerWidth());
				jqoBtn.outerHeight(jqoBtn.outerHeight());
			}
			if(jqoBtn.data('loading')){
				return false;
			}
			
			jqoBtn.data('loading', 'yes');
			jqoBtn.data('origContent', jqoBtn.html());
			jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
			var _reEnable = function(){
				jqoBtn.html(jqoBtn.data('origContent'));
				jqoBtn.removeData('loading');
			};
			
			$.post("ajax.listauctions.php", {
				action:  'acceptProposta',
				anuncCod: jqoAnunc.attr('rel'),
				propoId:  propoId,
			}, function(ret){
				if(ret == 'OK'){
					location.reload();
				}
				else{
					alert(ret);
					_reEnable();
				}
			}).fail(_reEnable);
			
			return false;
		});
		btnPropoSemInteresse.click(function(){
			var jqoBtn = $(this);
			if(!jqoBtn.data('fixedsize')){
				jqoBtn.outerWidth(jqoBtn.outerWidth());
				jqoBtn.outerHeight(jqoBtn.outerHeight());
			}
			if(jqoBtn.data('loading')){
				return false;
			}
			
			jqoBtn.data('loading', 'yes');
			jqoBtn.data('origContent', jqoBtn.html());
			jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
			var _reEnable = function(){
				jqoBtn.html(jqoBtn.data('origContent'));
				jqoBtn.removeData('loading');
			};
			
			$.post("ajax.listauctions.php", {
				action:  'rejectProposta',
				anuncCod: jqoAnunc.attr('rel'),
				propoId:  propoId,
			}, function(ret){
				if(ret == 'OK'){
					location.reload();
				}
				else{
					alert(ret);
					_reEnable();
				}
			}).fail(_reEnable);
			
			return false;

		});
	});
	
	
//	$.fx.off = true;
//	$('.btnMaisInformacoes:first').click();
//	$('.btnToggleOffer:first').click();
//	$('input[name=valor]:first').val("47,00");
//	$('select[name=regiao]:first').val("AL");
//	$('input[name=acceptTerms]:first').prop('checked', true);
//	$.fx.off = false;
});
