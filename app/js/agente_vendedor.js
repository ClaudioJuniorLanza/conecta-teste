$(function(){
	$(".cotacaoForm").on('submit', function(){
		var jqoUf      = $(".cotacaoForm input[name=uf]");
		var jqoCidade  = $(".cotacaoForm input[name=cidade]");
		var jqoProduto = $(".cotacaoForm input[name^=produto]");
		
		// Validação rápida:
		if(jqoUf.val().length != 2){
			alert("Favor informar o UF com 2 caracteres.");
			return false;
		}
		if(!jqoCidade.val().length){
			alert("Favor informar a cidade");
			return false;
		}
		if(!jqoProduto.val()){
			alert("Você não selecionou o produto");
			return false;
		}
		
		return true;
	});
});

// Vamos simular o Datalist.
// (Caberá ao HTML popular a variável cultivList)
// --> Não altere essa parte do código, sem antes reproduzir *exatamente* o que está em newauction.js.
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