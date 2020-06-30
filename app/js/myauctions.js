$(function(){
	$(".btMaisInfo").click(function(){
		var jqoBox      = $(this).closest('.anuncioBox');
		var jqoMaisInfo = jqoBox.find(".maisInformacoes");
		jqoMaisInfo.slideToggle();
		return false;
	});
	
	var _isLoading = false;
	
	// Fazer proposta (proponente)
	$(".anuncioBox .makeProposta input[name=valor]").blur(function(){
		$(this).val(dHelper2.moeda($(this).val()));
	});
	$(".btFazerProposta").click(function(){
		var jqoMakeProposta = $(this).closest(".anuncioBox").find(".makeProposta");
		if(jqoMakeProposta.is(":hidden")){
			jqoMakeProposta.slideDown();
			return false;
		}
		if(_isLoading){
			return false;
		}
		
		var jqoAnuncio = $(this).closest('.anuncioBox');
		if(jqoAnuncio.data('alreadySent')){
			// Já mandou essa proposta.
			return false;
		}
		
		var jqoMakePro = jqoAnuncio.find(".makeProposta");
		var anuncCod   = jqoAnuncio.data('codigo')
		var propoId    = jqoAnuncio.attr('data-propoid');
		var jqoValor   = jqoMakePro.find("input[name=valor]");
		var jqoRegiao  = jqoMakePro.find("select[name=regiao]");
		var jqoTermos  = jqoMakePro.find(".acceptTerms input:checkbox");
		var jqoStatusM = jqoAnuncio.find(".statusMessage");
		var _valor     = dHelper2.forceFloat(jqoValor.val());
		var _regiao    = $.trim(jqoRegiao.val());
		var _termos    = jqoTermos.prop('checked');
		if(!_valor || _valor < 0 || !_regiao){
			alert("Preencha a proposta corretamente.");
			return false;
		}
		if(!_termos){
			alert("Você precisa concordar com os termos antes de prosseguir.");
			return false;
		}
		
		_isLoading = true;
		jqoMakePro.find("input,select").prop('disabled', true);
		jqoAnuncio.find(".btFazerProposta").html("<i class='fa fa-spinner fa-spin'></i> Enviando...");
		
		jqoStatusM.slideUp(function(){
			$.post("ajax.listauctions.php", {
				anuncCod:anuncCod,
				propoId: propoId,
				action:  'setProposta',
				regiao:  _regiao,
				valor:   _valor,
			}, function(ret){
				if(ret == 'OK'){
					jqoAnuncio
						.find(".status")
						.removeClass('red')
						.addClass('lightgreen')
						.html("Já Enviada");
					
					jqoAnuncio.find(".acceptTerms").slideUp();
					
					jqoStatusM.addClass('success');
					jqoStatusM.html(
						"<div><img src='images/icon-green-checked.png' /></div>" +
						"<span>" +
						"Parabéns! Sua proposta foi enviada.<br />" +
						"Você receberá uma notificação assim que ela for aceita." +
						"</span>"
					).slideDown();
					
					_isLoading = false;
					jqoAnuncio.data('alreadySent', 'yes');
					jqoAnuncio.find(".btFazerProposta").html("Proposta enviada.").remove();
				}
				else{
					$("#debug").html(ret);
					alert(ret);
				}
			});
		});
		
		return false;
	});
	
	// Apenas "Toggle" para ver propostas:
	
	// Aceitar propostas (anunciante)
	$(".anuncioBox .btVerPropostas").each(function(){
		var jqoBtn     = $(this);
		var jqoAnuncio = jqoBtn.closest('.anuncioBox');
		var jqoReject  = jqoAnuncio.find(".btRejectAll");
		var jqoPropost = jqoAnuncio.find('.verPropostas');
		
		if(jqoAnuncio.attr('data-status') == 'Ag. Aceite'){
			// Funcionalidade para "Aguardando aceite..."
			var jqoStatusM   = jqoAnuncio.find(".statusMessage");
			var jqoOpcoes    = jqoAnuncio.find("input[name='aceitar[]']");
			var jqoTermos    = jqoAnuncio.find("input[name='acceptTerms']");
			var anuncCodig   = jqoAnuncio.attr('data-codigo');
			
			var _refreshBtns = function(){
				jqoOpcoes.each(function(){
					if(this.checked){
						$(this).closest('.propostaLine').css('background', '#CFC');
					}
					else{
						$(this).closest('.propostaLine').css('background', '');
					}
				});
				if(jqoPropost.is(":hidden")){
					jqoBtn.removeClass('green red yellow').addClass('green');
					jqoBtn.html("Ver propostas");
					return;
				}
				
				var _clen = jqoAnuncio.find("input[name='aceitar[]']").filter(":checked").length;
				if(!_clen){
					jqoBtn.removeClass('green red yellow').addClass('green');
					jqoBtn.html("Aceitar todas");
					jqoReject.show();
					return;
				}
				
				jqoBtn.removeClass('green red yellow').addClass('green');
				jqoBtn.html("Aceitar proposta" + (_clen > 1?"s":"") + " acima");
				jqoReject.hide();
			};
			_refreshBtns();
			
			var _submitAceite = function(){
				if(!jqoTermos.is(":checked")){
					alert("Você precisa aceitar os termos antes de continuar.");
					_isLoading = false;
					return false;
				}
				
				jqoOpcoes.prop('disabled', true);
				jqoTermos.prop('disabled', true);
				jqoBtn.data('initHtml', jqoBtn.html());
				jqoBtn.html("<i class='fa fa-spinner fa-spin'></i> Enviando respostas...");
				
				var acceptedIds = [];
				jqoOpcoes.filter(":checked").each(function(){
					acceptedIds.push($(this).val());
				});
				
				// Enviando re spostas...
				$.post("ajax.listauctions.php", {
						action:    'acceptPropostas',
						anuncCod:  anuncCodig,
						acceptIds: acceptedIds.join(","),
					},
					function(ret){
						if(ret == 'OK'){
							jqoAnuncio.find(".status").removeClass('red');
							if(!acceptedIds.length){
								jqoAnuncio.find(".status").addClass('lightgray').html("Encerrado").attr('title', 'Nenhuma proposta aceita');
								jqoPropost.slideUp();
								
								var message =
									    "Que pena! Ficou pra próxima...<br />" +
									    "Você não aceitou nenhuma das propostas.";
							}
							else{
								jqoAnuncio.find(".status").addClass('lightgreen').html("Ag. Intermediação");
								jqoOpcoes.not(":checked").each(function(){
									$(this).closest('.propostaLine').slideUp();
								});
								
								var message =
									    "Parabéns pelo negócio!<br />" +
									    "Entraremos em contato assim que possível para dar continuidade.";
							}
							
							jqoPropost.find("h2").slideUp();
							jqoAnuncio.find(".acceptTerms").slideUp();
							jqoStatusM.addClass('success');
							jqoStatusM.html(
								"<div><img src='images/icon-green-checked.png' /></div>" +
								"<span>" + message + "</span>"
							).slideDown();
							
							_isLoading = false;
							jqoAnuncio.data('alreadySent', 'yes');
							jqoBtn.remove();
						}
						else{
							// alert(ret);
							$("#debug").html(ret);
							
							_isLoading = false;
							jqoOpcoes.prop('disabled', false);
							jqoTermos.prop('disabled', false);
							jqoBtn.html(jqoBtn.data('initHtml'));
						}
					}
				);
			};
			
			jqoOpcoes.click(function(){
				_refreshBtns()
			});
			jqoBtn.click(function(){
				if(jqoAnuncio.data('alreadySent')){
					return false;
				}
				if(_isLoading){
					return false;
				}
				_isLoading = true;
				
				// 1º Clique: SlideDown mostrando as propostas
				if(jqoPropost.is(":hidden")){
					jqoPropost.slideDown(function(){
						_refreshBtns();
						_isLoading = false;
					});
					return false;
				}
				
				// 2º Clique: Seleciona todas as propostas
				if(!jqoOpcoes.filter(":checked").length){
					jqoOpcoes.prop('checked', true);
					_refreshBtns();
					_isLoading = false;
					return false;
				}
				
				// 3º Clique: Confirma o envio.
				_submitAceite();
				return false;
			});
			jqoReject.click(function(){
				if(!confirm("Você vai encerrar esse anúncio sem aceitar nenhuma proposta. Continuar?")){
					return false;
				}
				_submitAceite();
				return false;
			});
		}
		else{
			// Funcionalidade para "Ag. Intermediação" e "Concluído"
			jqoBtn.click(function(){
				jqoPropost.slideToggle();
				return false;
			});
		}
	});
	
	// Ver negócio
	$(".anuncioBox .btVerNegocio").click(function(){
		var jqoBtn     = $(this);
		var jqoAnuncio = jqoBtn.closest('.anuncioBox');
		var jqoPropost = jqoAnuncio.find('.verPropostas');
		jqoPropost.slideToggle();
		return false;
	});
	
	// Ativar o filtro.
	$("#dropFilterBy").change(function(){
		var _setGetParameter(paramName, paramValue){
			var url  = window.location.href;
			var hash = location.hash;
			url      = url.replace(hash, '');
			if(url.indexOf(paramName + "=") >= 0){
				var prefix = url.substring(0, url.indexOf(paramName));
				var suffix = url.substring(url.indexOf(paramName));
				suffix     = suffix.substring(suffix.indexOf("=") + 1);
				suffix     = (suffix.indexOf("&") >= 0)?suffix.substring(suffix.indexOf("&")):"";
				url        = prefix + paramName + "=" + paramValue + suffix;
			}
			else{
				if(url.indexOf("?") < 0){
					url += "?" + paramName + "=" + paramValue;
				}
				else{
					url += "&" + paramName + "=" + paramValue;
				}
			}
			
			window.location.href = url + hash;
		}
		_setGetParameter("f", this.value);
	});
});

