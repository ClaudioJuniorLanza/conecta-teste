$(function(){
	function showLoading(scheme){
		if(!scheme){
			scheme = 'black';
		}
		
		$(".dEipWaitEl").each(function(){
			// Remove os avisos anteriores
			$(this).css('margin-bottom', 64*2);
			hideLoading($(this));
		});
		
		var scheme = {
			boxShadow:  (scheme == 'black')?'#FFF':'#000',
			background: (scheme == 'black')?'#000':'#FFF',
			color:      (scheme == 'black')?'#FFF':'#000',
		};
		
		var _waitEl   = $("<div>")
			.addClass('dEipWaitEl')
			.css('transition', 'margin-bottom 0.5s, background-color 0.5s, color 0.5s')
			.css('padding', '16px')
			.css('display', 'inline-block')
			.css('box-shadow', "-2px -2px 2px "+scheme.boxShadow+", -2px 2px 2px "+scheme.boxShadow)
			.css('background', scheme.background)
			.css('color',      scheme.color)
			.css('opacity', 0)
			.css('white-space', 'nowrap')
			.css('position', 'fixed')
			.css('right', 0)
			.css('bottom', 64)
			.css('z-index', 200)
			.css('width', '1')
			.html("<i class='fa fa-spinner fa-spin'></i> Salvando...")
			.prop('scheme', scheme)
			.prependTo($("body"))
			.animate({ opacity: 1, width: 200 });
		
		return _waitEl;
	}
	function hideLoading(_waitEl){
		if(!_waitEl){
			return false;
		}
		
		_waitEl.fadeOut(500, function(){
			setTimeout(function(){
				_waitEl.remove();
				_waitEl = null;
			}, 10);
		});
		
	}
	function endLoading (_waitEl, message){
		if(!_waitEl){
			return false;
		}
		
		var scheme = _waitEl.prop('scheme');
		if(message == 'OK'){
			_waitEl.html("<i class='fa fa-check'></i> Sucesso!");
			setTimeout(function(){
				hideLoading(_waitEl);
			}, 1500);
		}
		else{
			_waitEl.stop().css({ width: 'auto', opacity: 1, backgroundColor: '#FCC', color: '#000' }).html(
				(message?message:"Erro: Sem resposta")+
				"<div style='font: 11px Arial; font-style: italic; border-top: 1px solid #000; margin-top: 8px; padding-top: 8px'>Clique para desconsiderar</div>"
			);
			_waitEl.attr('title', 'Clique para desconsiderar').click(function(){
				hideLoading(_waitEl);
			});
		}
	}
	
	// Gerencia a exibição das exceções em "Variedade/Cultivar"
	$(".wannaGroup input[name*='[tudo]']").click(function(){
		var group = $(this).closest('.subGroups').attr('rel');
		_refreshVisible(group);
	});
	var _refreshVisible    = function(group){
		if(!group){
			_refreshVisible('comprar');
			_refreshVisible('vender');
			return;
		}
		
		jqoGroup  = $(".subGroups[rel="+group+"]");
		var jqoCb = $("input[name*='[tudo]']", jqoGroup);
		if(jqoCb.is(":checked")){
			$(".showAll",  jqoGroup).show();
			$(".showOnly", jqoGroup).hide();
		}
		else{
			$(".showOnly", jqoGroup).show();
			$(".showAll",  jqoGroup).hide();
		}
	};
	var _refreshSeparators = function(){
		// Quando as listas estiverem vazias, os separators não precisam ser mostrados.
		$(".rowsHolder").each(function(){
			var jqoSeparator = $(this).prev('.separator');
			($(this).find(".row").length)?
				jqoSeparator.show():
				jqoSeparator.hide();
		});
	};
	_refreshVisible();
	_refreshSeparators();
	
	// Vamos gerenciar os botões de "delete" de .rowsHolder
	//   Group: compra, venda
	var _deleteRow = function(){
		var jqoRow = $(this).closest('.row');
		if(jqoRow.data('isDeleted')){
			return false;
		}
		
		jqoRow.data('isDeleted', '1');
		jqoRow.css('background', '#EEE');
		jqoRow.find(".fa-times").removeClass('fa-times').addClass('fa-spinner').addClass('fa-spin');
		
		$.post("ajax.meus-interesses.php", {
			action: 'deleteRow',
			group:  jqoRow.closest('.subGroups').attr('rel'),  // comprar | vender
			type:   jqoRow.closest('.rowsHolder').attr('rel'), // only | exceto
			what:   jqoRow.attr('rel'),                        // Ex: "ID:123" ou "STR:Soja"
		}, function(ret){
			// Debug only:
			//			$(".boxDestaque").html(ret);
			jqoRow.slideUp('fast', function(){
				$(this).remove();
				_refreshSeparators();
			});
		});
		
		return false;
	};
	$(".rowsHolder .deleteBtn").click(_deleteRow);
	
	// Funções para adicionar interesse:
	var _isLoading   = false;
	var _submitForm  = function(group){
		if(_isLoading){
			// Não aceita dois itens ao mesmo tempo...
			return;
		}
		
		var jqoGroup  = $(".subGroups[rel="+group+"]");
		var jqoInput  = $("input[name=interesseAdd]", jqoGroup);
		var jqoButton = $(".addInteresse button", jqoGroup);
		var strInteresse = $.trim(jqoInput.val());
		if(strInteresse.length < 3){
			// Ignorar.
			return;
		}
		
		_isLoading = true;
		jqoInput.prop('disabled', true);
		jqoButton.prop('disabled', true);
		jqoButton.find(".fa-check").removeClass('fa-check').addClass('fa-spinner').addClass('fa-spin');
		var _releaseForm = function(){
			_isLoading = false;
			jqoInput .prop('disabled', false);
			jqoInput.val('');
			jqoInput.focus();
			jqoButton.prop('disabled', false);
			jqoButton.find(".fa-spin").removeClass('fa-spin').removeClass('fa-spinner').addClass('fa-check');
		};
		
		
		var _waitEl = showLoading();
		var jqoNew  = $(
			"<div class='row'>"+
				"<a class='left deleteBtn' href='#' onclick='return false'><i class='fa fa-fw fa-spin fa-spinner'></i></a>"+
				"<div class='middle'><small>Adicionando...</small><span></span></div>"+
			"</div>"
		).hide();
		jqoNew.prependTo($(".rowsHolder[rel=only]", jqoGroup));
		jqoNew.css('background', '#CFC');
		jqoNew.find(".middle span").text(strInteresse);
		jqoNew.slideDown();
		_refreshSeparators();
		
		$.post("ajax.meus-interesses.php", {
			action:    'addOnly',
			group:     group,
			interesse: strInteresse,
		}, function(ret){
			// RET:
			// OK:STR|alfafa|linha1|linha2
			setTimeout(function(){
				// Adiciona um timer pra poder realizar a animação decentemente...
				if(ret.substr(0, 3) == 'OK:'){
					jqoNew.css('background', '');
					jqoNew.find(".fa-spin").removeClass('fa-spin').removeClass('fa-spinner').addClass('fa-times');
					jqoNew.find(".deleteBtn").click(_deleteRow);
					
					var parts = ret.substr(3).split("|");
					jqoNew.attr('rel', parts[0]+":"+parts[1]);
					
					(parts[2].length)?
						jqoNew.find("small").text(parts[2]):
						jqoNew.find("small").slideUp();
					
					jqoNew.find(".middle span").text(parts[3]);
					
					endLoading(_waitEl, 'OK');
				}
				else{
					jqoNew.slideUp(function(){
						jqoNew.remove();
						_refreshSeparators();
					});
					
					endLoading(_waitEl, ret);
				}
				
				_releaseForm();
			}, 300);
		}).fail(function(){
			endLoading(_waitEl, "Falha de conexão");
			_releaseForm();
		});
	};
	var jqoForm      = $(".addInteresse");
	var jqoAddInput  = $("input[name=interesseAdd]", jqoForm);
	var jqoAddButton = $("button", jqoForm);
	jqoForm.on('submit', function(){
		_submitForm($(this).closest('.subGroups').attr('rel'));
		return false;
	});
	jqoAddButton.click(function(){
		jqoForm.submit();
		return false;
	});
	
	// Vamos evitar conflitos entre o usuário digitar algum item
	// ou selecionar alguma coisa no DataList.
	// Ordem dos eventos: keypress, input|change, keyup
	var _lastString = false;
	var _isKeyPress = false;
	$("input[name=interesseAdd]")
		.on('keypress', function(ev){
//			console.log("KeyPress", ev.keyCode);
			_isKeyPress = (ev.keyCode != 13);
//			console.log("isKeyPress: ", _isKeyPress);
			return true;
		})
		.on('change',   function(){
//			console.log("Change triggered.");
			if(_isKeyPress){
				// Digitou algo, só aceite.
//				console.log("Digitou algo, só aceite.");
				return true;
			}
			if(!$.trim($(this).val())){
				return true;
			}
			_isKeyPress = false;
			
			// Selecionou algo no dropdown.
			var jqoGroup = $(this).closest('.subGroups');
			$(this).blur();
			$(".addInteresse button", jqoGroup).click();
		});
	
	// Salva todos os checkbox automaticamente
	$(".wannaGroup input:checkbox").click(function(){
		var jqoCbox = $(this);
		var myName  = jqoCbox.attr('name');
		var _waitEl = showLoading();
		jqoCbox.prop('disabled', true);
		
		$.post("ajax.meus-interesses.php", {
			action: 'toggle',
			setting: myName,
			value:   jqoCbox.get(0).value,
			setAs:   jqoCbox.get(0).checked?'1':'0',
		}, function(ret){
			endLoading(_waitEl, ret);
			jqoCbox.prop('disabled', false).focus();
		}).fail(function(ret){
			endLoading(_waitEl, "Falhou: "+ret.status);
			jqoCbox.prop('disabled', false).focus();
		});
		return true;
	});
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
