$(function(){
	var jqoForm       = $("form.novoAnuncio");
	var jqoWarning    = $(".chooseCultura .warningMessage");
	var jqoConfirmBtn = $("#btnConfirmar");
	var jqoWarningBox = $(".statusMessage");
	var _isLoading    = false;
	
	// Vamos fazer funcionar ambos os chooseCultura (.initial e .onTroca)
	var varieId            = false;
	var trocaVarieId       = false;
	var _initChooseCultura = function(jqoChooseCultura, isTroca){
		var jqoVariedInp = $(".inpCultivar", jqoChooseCultura);
		var jqoSearchBtn = $(".actionBtn a", jqoChooseCultura);
		
		var _searchTimer      = false;
		var _delayedSearch    = function(){
			if(_searchTimer){
				clearTimeout(_searchTimer);
			}
			_searchTimer = setTimeout(_searchVariedade, 75);
		};
		var _disableVariedade = function(yesno){
//			console.log("Desativando variedade. Yesno=",yesno);
			if(yesno){
				jqoVariedInp.prop('readonly', true).css('background', '#DDD').blur();
				jqoSearchBtn
					.css('display', 'inline-block')
					.outerWidth(jqoSearchBtn.outerWidth())
					.css('text-align', 'center')
					.html("<i class='fa fa-spinner fa-spin'></i>");
			}
			else{
				jqoVariedInp.prop('readonly', false).css('background', '');
				jqoSearchBtn
					.css('display', 'inline-block')
					.outerWidth(jqoSearchBtn.outerWidth())
					.css('text-align', 'center')
					.html("Buscar");
			}
			
			$(".itemFound", jqoChooseCultura).slideUp();
			if(!isTroca){
				varieId = false;
				$(".moreInfo").slideUp();
			}
			else{
				trocaVarieId = false;
			}
		};
		var _searchVariedade  = function(){
			_isLoading = true;
			_disableVariedade(true);
			jqoWarning.hide();
			$.post("ajax.newauction.php", {
				action:    'searchVariedade',
				variedade: jqoVariedInp.val(),
			}, function(ret){
				$("#debug").html(ret);
				ret = $.parseJSON(ret.substr(ret.indexOf("JSON=") + 5));
				
				if(typeof ret == 'object'){
					if(ret.error){
						jqoWarning.html(ret.error).fadeIn(function(){
							_isLoading = false;
							_disableVariedade(false);
						});
						return false;
					}
					
					if(isTroca){
						trocaVarieId = ret.id;
					}
					else{
						varieId = ret.id;
					}
					jqoVariedInp.val(ret.variedade);
					$(".itemFound .txtCultura", jqoChooseCultura).html(ret.cultura);
					$(".itemFound .txtTecnologia", jqoChooseCultura).html(ret.tecnologia);
					$(".itemFound", jqoChooseCultura).slideDown(function(){
						if(!isTroca){
							$(".moreInfo").slideDown(function(){
								_isLoading = false;
							});
						}
						else{
							_isLoading = false;
						}
					});
					jqoSearchBtn
						.css('width', '')
						.html("alterar");
				}
			});
			return true;
		};
		jqoVariedInp.on('change', _delayedSearch);
		jqoSearchBtn.click(function(){
			if(_isLoading){
				return false;
			}
			
			if(isTroca){
				if(trocaVarieId){
					trocaVarieId = false;
					_disableVariedade(false);
					return false;
				}
			}
			else{
				if(varieId){
					varieId = false;
					_disableVariedade(false);
					return false;
				}
			}
			
			_delayedSearch();
			return false;
		});
		
		
	};
	_initChooseCultura($(".chooseCultura.initial"), false);
	_initChooseCultura($(".chooseCultura.onTroca"), true);
	
	var _doCreate = function(){
		if(_isLoading){
			return false;
		}
		_isLoading = true;
		if(!confirm("Você revisou os dados?\nNão será possível alterar após a confirmação.")){
			_isLoading = false;
			return false;
		}
		
		var theForm = jqoForm.serializeArray();
		$(".moreInfo").find("select,input")
			.prop('disabled', true)
			.css('background-color', '#CCC');
		
		jqoConfirmBtn.html("<i class='fa fa-spinner fa-spin'></i>");
		
		$.post("ajax.newauction.php", {
			action:   'doCreate',
			varie_id: varieId,
			troca_id: trocaVarieId,
			theform:  theForm,
		}, function(ret){
			if(ret != 'OK'){
				jqoWarningBox.addClass('error').html(
					'<div><img src="images/icon-red-failed.png" /></div>' +
					'<span>' + (ret) + '</span>'
				).slideDown(function(){
					$(".moreInfo").find("select,input")
						.prop('disabled', false)
						.css('background-color', '');
					
					jqoConfirmBtn.html("Confirmar Anúncio");
					_isLoading = false;
				});
				
				return false;
			}
			jqoWarningBox.addClass('success').html(
				'<div><img src="images/icon-green-checked.png" /></div>' +
				'<span>Sucesso!<br /><i class="fa fa-spinner fa-spin"></i> Carregando...</span>'
			).slideDown(function(){
				location.href = 'newauction.php?show=sucesso';
			});
		});
	};
	jqoConfirmBtn.click(_doCreate);
	jqoForm.submit(function(){
		if(!varieId){
			_searchVariedade();
			return false;
		}
		
		_doCreate();
		return false;
	});
	
	// Vamos transformar o "Valor por Embalagem" e "Valor dos Royalties" em "Moeda".
	$("input[name=valor_por_kg],input[name=valor_royalties]", jqoForm).blur(function(){
		var curVal = $(this).val();
		var newVal = curVal?dHelper2.moeda(curVal):false;
		$(this).val(newVal?newVal:'');
	});
	
	// Vamos fazer o dropdown de Tratamento Industrial liberar/ocultar o campo "Descreva o tratamento"
	var _jqoTratInd  = jqoForm.find("select[name='tratam_indust']");
	var _tratRefresh = function(setFocus){
		var needTexto = (_jqoTratInd.val() == 'Sim');
		needTexto?
		jqoForm.find(".txtTratamento").slideDown(function(){
			if(setFocus){
				$(".txtTratamento input").focus();
			}
		}):
		jqoForm.find(".txtTratamento").slideUp();
		
		$(".txtTratamento input").prop('disabled', !needTexto);
		return needTexto;
	};
	_tratRefresh();
	_jqoTratInd.change(function(){
		_tratRefresh(true);
	});
	
	// Vamos processar o copyFrom (que será definido no corpo do newauction)
	if(copyFrom){
		$(".chooseCultura.initial input").val(copyFrom.variedade);
		$(".chooseCultura.initial .actionBtn a").click();
		$.each(copyFrom, function(key, value){
			$(".novoAnuncio [name="+key+"]").val(value);
			_tratRefresh(false);
			_embRefresh();
		});
		
		if(copyFrom.trocaVariedade){
			$(".chooseCultura.onTroca .inpCultivar").val(copyFrom.trocaVariedade);
			$(".chooseCultura.onTroca .actionBtn a").click();
		}
	}
});

// Vamos simular o Datalist.
// (Caberá ao HTML popular a variável cultivList)
$(function(){
	// Vamos popular o Javascript.
	if('options' in document.createElement('datalist')){
	    // É suportado nativamente.
		return false;
	}
	
	// Vamos converter o dataList num Array do Javascript.
	var cultivList = [];
	$("#cultivarList option").each(function(){
		cultivList.push($(this).attr('value'));
	});
//	console.log("Lista de Cultivares atualizada. Foram importados ", cultivList.length);
	
	// Vamos processar cada datalist individualmente.
	$("input[list=cultivarList]").each(function(){
		var jqoInput    = $(this);
		var jqoDlHolder = $("<div class='dataListHolder'></div>");
		var jqoOptions  = $("<div class='dataListSimulator'></div>");
		jqoInput.removeAttr('list');
		jqoInput.wrap(jqoDlHolder);
		jqoOptions.hide().insertAfter(jqoInput);
		
		// Funcionamento:
		var _lastStr        = "";
		var _chooseOption   = function(){
//			console.log("_chooseOption: ", this.innerHTML);
			jqoInput.val(this.innerHTML);
			jqoOptions.hide();
			jqoInput.change();
		};
		var _refreshOptions = function(){
			if(jqoInput.prop('readonly')){
				jqoOptions.hide();
				return false;
			}
			
			var filterBy = $.trim(jqoInput.val()).toUpperCase();
			if(_lastStr == filterBy){
				// Do nothing (provavelmente teclas de controle, como shift, alt, etc..)
				return;
			}
			_lastStr = filterBy;
			
			if(filterBy.length < 3){
				jqoOptions.hide();
				return;
			}
			filterBy = filterBy.split(" ");
			
			var _sofFilterBy = filterBy.length;
			var _foundAny = false;
			jqoOptions.empty();
			
			for(var i = 0; i < cultivList.length; i++){
				var _found = 0;
				for(var k = 0; k < filterBy.length; k++){
					if(cultivList[i].match("( |^)" + filterBy[k])){
						_found++;
					}
				}
				if(_found == _sofFilterBy){
					_foundAny = true;
					var _jqoRow = $("<a>"+cultivList[i]+"</a>");
					jqoOptions.append(_jqoRow);
				}
			}
			
			_foundAny?
				jqoOptions.show():
				jqoOptions.hide();
		};
		
		jqoOptions.bind('click', function(ev){
			var jqoTarget = $(ev.target);
			if(!jqoTarget.is('a')){
//				console.log("Wrong target.");
				return false;
			}
			
//			console.log("Options has been clicked.");
			_chooseOption.call(ev.target);
			return false;
		});
		jqoInput.keyup(function(){
			_refreshOptions();
		});
		jqoInput.focus(_refreshOptions);
	});
});












