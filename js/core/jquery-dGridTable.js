// Last review: 2012-12-07
/**
	To-do na sequencia:
		Fixed header when scrolling;
		
	
	dGridTable - Documentação:
		
		Requisitos:
		- jQuery
		- Plugin: dClickOutside  --> Apenas para .enableChooseColumn(true)
		- Plugin: dTableDragDrop --> Apenas para .enableMovable(...)
	
		new dGridTable(constructorOptions)
		.addColumn(columnOptions)
		.build(jqoWhere)
		.refresh()
		
		.enableMovable(cbOnDrop, cbOnMove, cbOnDrag)
		.getRowsN()
		
		.dataClear()
		.dataAddRow     (arrRow,   newRowOptions)
		.dataImportTable(jqoTable, importTableOptions)
		.dataBlur(yesno)
		
		.enableSelectable(yesno, cbOnSelect)
		.selectAdd(id)
		.selectRemove(id)
		.selectOnly(id)
		.selectAll(yesno)
		.selectGetIds()
	
	constructorOptions:
		id: Opcional, ID único da tabela em questão. Se não enviado, um ID será gerado automaticamente.
	
	columnOptions:
		key:         Índice da coluna
		title:       Título visível,
		visible:     Será exibida logo de início, ou começará escondida
		choosable:   Pode ser selecionada para exibição pelo enableChooseColumn()?
		cbWriteCell: Callback que será chamado sempre que uma linha for escrita
	
	newRowOptions:
		id:       false,
		asHtml:   false,
	
	importTableOptions:
		
	
	callback cbOnSelect(dGridTable t)
		@param t: Útil para chamar .selectGetIds().
		@return:  void
	
	callback cbWriteCell(arrAssocRow, key, options, dGridTable t)
		@param arrAssocRow: A linha inteira, em forma de Array.
		@param key:         Qual índice está sendo escrito.
		@param options:     As mesmas opções em newRowOptions
		@param t:           O objeto controlador.
		@return:            Nova jQuery <td>, já montada e formatada da maneira desejada.
	
	callback cbOnGenerateAction(dGridTable t)
		@param t:           Útil para chamar .selectGetIds() e .getElements();
		@return:            string to go between <select> ... </select>
	
	callback cbOnSubmitAction(strAction, dGridTable t)
		@param strAction: O valor da opção selecionada no <select>
		@param t:         Útil para chamar .selectGetIds() e .getElements();
		@return:          void
	
	callback cbOnMove(oldInfo, newInfo, dGridTable t)
		@param oldInfo: { jqoTable, jqoRow, jqoRowAbove, jqoRowBelow } // Antes de mover
		@param newInfo: { jqoTable, jqoRow, jqoRowAbove, jqoRowBelow } // Após mover
		@param t:       Objeto dGridTable
		@return:        void
	
	callback cbWriteOptions  (jqoRow, jqoRowOpt, dGridTable t)
	callback cbRefreshOptions(jqoRow, jqoRowOpt, dGridTable t)
		@param jqoRow:    Linha que foi escrita
		@param jqoRowOpt: Coluna onde os elementos deverão ser gerados.
		@param t:         Objeto dGridTable
		@return:          void
	
	callback cbWriteFooter(info, jqoFooterTd, dGridTable t)
		@param info:        { nVisible, nSelected }
		@param jqoFooterTd: Elemento onde você deverá escrever o rodapé
		@param t:           Objeto dGridTable
		@return:            void
	
	Classes:
		dgt-ignore-sel-click:
			Havendo isso dentro de qualquer row ou célula, o sistema vai
			ignorar qualquer clique dentro de determinada área (clicar para selecionar
			ou duplo-clique para acessar objeto)
	
	Ajuda na programação:
		Se você adicionar dados utilizando dataAddRow (ou addRow),
		é possível definir o ID da linha de duas formas:
		--> A primeira é não fazer nada. A primeira coluna será o id.
		--> A segunda é definir options { id: xxxxx }.
		
		Se você for adicionar dados utilizando dataLoadTable,
		então a primeira coluna será o ID. Garanta que ela seja
		única e texto puro.

**/
function dGridTable(settings){
	var settings = $.extend({
		id:             false,
		
		supportColumnChoose: true, // To-do: Future.
		supportActionBar:    true, // To-do: Future.
		supportMoving:       true, // To-do: Future.
		supportOptions:      true, // To-do: Future.
		
		isSelectable:   false, // Não deve ser definida diretamente, apenas pelo .enableSelectable
		cbOnSelect:     false, // Não deve ser definida diretamente, apenas pelo .enableSelectable
		
		isColumnChoosable:  false, // Não deve ser definida diretamente, apenas pelo .enableChooseColumn
		cbOnGenerateAction: false, // Não deve ser definida diretamente, apenas pelo .enableActions
		cbOnSubmitAction:   false, // Não deve ser definida diretamente, apenas pelo .enableActions
		
		cbOnMove:       false, // Não deve ser definida diretamente, apenas pelo .enableMovable
		
		cbWriteOptions:   false, // Não deve ser definida diretamente, apenas pelo .enableOptions
		cbRefreshOptions: false, // Não deve ser definida diretamente, apenas pelo .enableOptions
		
		cbWriteFooter:    false, // Não deve ser definida diretamente, apenas pelo .enableFooter
	}, settings);
	var t        = this;
	var columns  = [];
	var rows     = [];
	var cache    = {
		nCols:        0,
		jqoTable:     false,
		jqoActionEls: {
			bar:          false,
			barTd:        false, 
			table:        false, 
			tBody:        false, 
			row:          false, 
			colLeft:      false, 
			colRight:     false, 
			actionText:   false, 
			actionSelect: false,
			actionButton: false,
			chooseColumnLink: false, 
			chooseColumnDiv:  false
		},
		jqoHeaderBar:  false,
		jqoContents:   false,
		jqoCheckboxes: (new $),
		jqoFooterBar:  false,
		jqoFooterTd:   false,
		lastActionBar: false
	};
	if(!settings.id){
		if(!dGridTable.uniqueId)
			dGridTable.uniqueId = 0;
		
		settings.id = 'dGridUid-'+(dGridTable.uniqueId++);
	}
	
	t.addColumn = function(objColumn){
		objColumn = $.extend({
			key:         false,
			title:       false,
			visible:     true,
			choosable:   true,
			cbWriteCell: false
		}, objColumn);
		
		columns.push(objColumn);
		cache.nCols = columns.length;
	};
	t.build     = function(jqObj){
		if(cache.jqoTable)
			return false;
		
		_buildTable();
		_buildActionBar();
		_buildHeaderBar();
		_buildContents();
		_buildFooter();
		
		// Inverter as linhas abaixo dá problema...
		// Por que?
		$(jqObj).html(cache.jqoTable);
		t.refresh();
	}
	t.refresh   = function(){
		// Por que refresh o conteúdo primeiro?
		// Pois pode haver rows não consolidadas.
		_refreshData();
		
		_refreshTable();
		_refreshActionBar();
		_refreshHeaderBar();
		_refreshFooter();
	}
	
	t.enableSelectable   = function(yesno, cbOnSelect){
		if(!yesno){
			t.selectAll(false);
		}
		
		settings.cbOnSelect   = cbOnSelect;
		settings.isSelectable = yesno;
		t.refresh();
	}
	t.enableMovable      = function(cbOnMove){
		if(cbOnMove == false){
			cache.jqoTable.dRowDrag('disable');
			settings.cbOnMove = false;
		}
		else{
			cache.jqoTable.dRowDrag({
				cbCanMove: function(ev){
					return $(ev.target).hasClass('dgt-col-movable');
				},
				cbOnDrop:  function(oldInfo, newInfo){
					t.refresh();
					cbOnMove(oldInfo, newInfo, t);
				}
			});
			settings.cbOnMove  = cbOnMove;
		}
		t.refresh();
	}
	t.enableChooseColumn = function(yesno){
		settings.isColumnChoosable = yesno;
		t.refresh();
	}
	t.enableActions      = function(cbOnGenerateAction, cbOnSubmitAction){
		settings.cbOnGenerateAction = cbOnGenerateAction;
		settings.cbOnSubmitAction   = cbOnSubmitAction;
		_refreshActionBar();
	}
	t.enableOptions      = function(cbWriteOptions, cbRefreshOptions){
		if(cbWriteOptions == false && cbRefreshOptions == false){
			t.refresh();
			return true;
		}
		settings.cbWriteOptions   = cbWriteOptions  ?cbWriteOptions  :false;
		settings.cbRefreshOptions = cbRefreshOptions?cbRefreshOptions:false;
		
		if(settings.cbWriteOptions){
			// Vamos escrever/re-escrever as opções...
			$(".dgt-row-content", cache.jqoTable).each(function(idx){
				var jqoRow    = $(this);
				var jqoRowOpt = $(".dgt-col-options", jqoRow);
				
				settings.cbWriteOptions(jqoRow, jqoRowOpt.empty(), t);
				jqoRowOpt.prop('dgt-options-built', true);
			});
		}
		
		t.refresh();
	}
	t.enableFooter       = function(cbWriteFooter){
		settings.cbWriteFooter = cbWriteFooter;
		t.refresh();
	}
	
	t.getRowsN      = function(){
		return $(".dgt-row-content", cache.jqoTable).length;
	}
	t.getRowById    = function(id){
		var jqo = $("#"+settings.id + '-' + id);
		return jqo.length?
			jqo:
			false;
	}
	
	t.dataClear       = function(){
		// Limpa o conteúdo.
		$(".dgt-b", cache.jqoTable).empty();
		rows = [];
	}
	t.dataBlur        = function(yesno, cbOnBlur, cbOnUnblur){
		// Escurece ou recupera o conteúdo.
		// 1. Cria um DIV escuro em toda a dimensão da tabela;
		// 2. Após escurecer o DIV, chama cbOnBlur e aguarda um objeto ou HTML
		//    como resultado, que vai aparecer centralizado na tela do usuário, dentro da área blurred.
		// 3. Se o usuário clicar na área preta (fora da área criada por ele), o cbOnUnblur será chamado.
	}
	t.dataImportTable = function(jqoTable, options){
		if(jqoTable.is(".dgt-table")){
			return false;
		}
		
		options = $.extend({
			hasHeader:     false,
			replaceFooter: false,
			
			jqoOwnRowHeader: false,
			jqoOwnRowFooter: false,
			
			appendHeaderWhere: 'above',
			appendFooterWhere: 'below'
		});
		
		var jqoTHead = jqoTable.children("thead");
		var jqoTBody = jqoTable.children("tbody");
		var jqoTFoot = jqoTable.children("tfoot");
		
		var jqoHeaderBar = jqoTHead.children("tr");
		var jqoFooterBar = jqoTFoot.children("tr");
		
		cache.jqoTable     = jqoTable;
		cache.jqoHeaderBar = jqoHeaderBar;
		cache.jqoContents  = jqoTBody;
		cache.jqoFooterBar = jqoFooterBar;
		
		// To-do:
		// --> Verificar footer
		// --> Definir cache.jqoFooterTd
		
		var needToExtendHeader = true;
		if(jqoHeaderBar.length > 1){
			alert("Não sei como processar mais de uma linha no cabeçalho.");
			return false;
		}
		else if(jqoHeaderBar.length == 1){
			// Existe cabeçalho na tabela, vamos importá-lo.
			var allCols = $("td", jqoHeaderBar);
			
			if(columns.length && allCols.length != columns.length){
				alert("Estava esperando "+(columns.length)+", mas recebi "+(allCols.length)+", não sei como continuar.");
				return false;
			}
			if(!columns.length){
				allCols.each(function(idx){
					var $t = $(this);
					var useKey    = $t.attr('dgt-key');
					var useTitle  = $t.attr('dgt-title');
					var visible   = $t.attr('dgt-visible');
					var choosable = $t.attr('dgt-choosable');
					
					$t.addClass('dgt-col-header');
					
					if(!useKey){
						useKey = 'key-'+idx;
					}
					if(!useTitle){
						useTitle = $t.text();
					}
					if(!visible){
						visible = '1';
					}
					if(!choosable){
						choosable = '1';
					}
					
					t.addColumn({
						key: useKey,
						title: useTitle,
						visible:   (visible=='1' || visible=='true'),
						choosable: (choosable=='1' || choosable=='true')
					});
				});
			}
		}
		else{
			if(!jqoTHead.length){
				jqoTHead = $("<thead>").prependTo(jqoTable);
				cache.jqoHeaderBar = $("<tr class='dgt-row-header'>").appendTo(jqoTHead);
			}
			
			// Quantas colunas devemos gerar?
			for(var i = 1; i <= jqoTBody.children('tr').first().children('td').length; i++){
				t.addColumn({ key: 'autokey-'+i, title: 'Coluna #'+i });
			}
			
			needToExtendHeader = false;
			_buildHeaderBar();
		}
		
		// Neste ponto, sabemos que temos uma tabela válida. 
		// Vamos extender as linhas e colunas.
		if(needToExtendHeader)
			_extendHeaderBar(jqoHeaderBar);
		_buildActionBar();
		jqoTBody.children("tr").each(function(){
			$(this).addClass('dgt-row-content');
			$(this).children("td").addClass('dgt-col-content');
			_extendContentRow($(this));
		});
		
		t.refresh();
		
		// 1. Detectar pré-existencia de cabeçalho.
		// 2. Detectar pré-existencia de footer.
		// 3. Processar o cabeçalho e o conteúdo.
		// 4. Adicionar o ActionBar e o Header, se necessário.
		// 5. Analisar o refresh do footer também.
		
		
	}
	t.dataAddRow      = function(arrRow, options){
		if(arrRow.length != cache.nCols){
			alert("Erro inserindo row, esperava "+(t.cache.nCols)+" colunas, mas recebi "+arrRow.length);
			return false;
		}
		options = $.extend({
			id:       false,
			asHtml:   false
		}, options);
		
		var jqoRow  = $("<tr class='dgt-row-content'>");
		var valueId = settings.id?settings.id:arrRow[0];
		jqoRow
			.attr('id', settings.id + '-' + valueId)
			.data('user-id', valueId);
		
		for(var idx in arrRow){
			if(columns[idx].cbWriteCell){
				// Vamos chamar o callback cbWriteCell.
				// Deve retornar um único objeto jQuery <td>, ou uma string em HTML a ser posicionada aqui.
				
				// cbWriteCell(arrAssocRow, key, options)
				// Retorno esperado: jQuery <td>
				var newCell = columns[idx].cbWriteCell(_mountRowAssoc(arrRow), columns[idx].key, options, t);
				if(newCell.get(0).tagName.toUpperCase() != "TD"){
					alert("Falha critica - cbWriteCell não retornou objeto jQuery <td>, conforme esperado.");
					return false;
				}
				
				jqoRow.append(newCell.addClass('dgt-col-content'));
			}
			else{
				// Não temos callback, então temos que adicionar nós mesmos.
				if(options.asHtml){
					$("<td>").html(arrRow[idx]).addClass('dgt-col-content').appendTo(jqoRow);
				}
				else{
					$("<td>").text(arrRow[idx]).addClass('dgt-col-content').appendTo(jqoRow);
				}
			}
		}
		rows.push(jqoRow);
	}
	
	t.selectAdd    = function(id){
		if(!settings.isSelectable)
			return false;
		
		cache.jqoCheckboxes.not(":checked").removeAttr('checked').closest('.dgt-row-content').each(function(){
			_handleOnSelect($(this));
		});
	}
	t.selectRemove = function(id){
		if(!settings.isSelectable)
			return false;
		
		cache.jqoCheckboxes.filter(":checked").removeAttr('checked').closest('.dgt-row-content').each(function(){
			_handleOnSelect($(this));
		});
	}
	t.selectOnly   = function(id){
		if(!settings.isSelectable)
			return false;
		
		t.selectAll(false);
		t.selectAdd(id);
	}
	t.selectAll    = function(yesno){
		if(!settings.isSelectable)
			return false;
		
		// To-do:
		// --> Ao invés de percorrer _handleOnSelect um por um,
		// --> podemos otimizar isso e aplicar todas as mudanças
		// --> de uma só vez, pensando na performance.
		
		cache.jqoCheckboxes.each(function(){
			yesno?
				$(this).attr('checked', 'checked'):
				$(this).removeAttr('checked');
			
			_handleOnSelect($(this).closest('.dgt-row-content'));
		});
	}
	t.selectGetIds = function(){
		if(!settings.isSelectable)
			return false;
		
		var selIds = cache.jqoCheckboxes.filter(":checked").map(function(){ return $(this).val(); }).get();
		if(!selIds.length)
			return false;
		
		return selIds;
	}
	
	t.columnShow   = function(key, yesno){
		for(var idx in columns){
			if(columns[idx].key == key){
				columns[idx].visible = yesno;
				break;
			}
		}
		
		if(settings.isColumnChoosable)
			$("[value='"+key+"']", cache.jqoActionEls.chooseColumnDiv)[0].checked = yesno;
			
		t.refresh();
	}
	t.getElements  = function(){
		return cache;
	}
	
	// Shortcuts:
	t.addRow      = t.dataAddRow;
	
	var _handleOnSelect     = function(jqoRow, ev){
		if(!settings.isSelectable)
			return false;
		
		if(jqoRow.length > 1)
			jqoRow.each(handleOnSelect);
		
		var jqoRowSelCbox = jqoRow.children(".dgt-col-selectable").children(".dgt-obj-selectable");
		var isSelected    = jqoRowSelCbox.is(":checked");
		if(ev && !$(ev.target).is(jqoRowSelCbox)){
			// Se não clicou no checkbox, vamos sincronizá-lo.
			// Caso contrário, deixe-o como está.
			jqoRowSelCbox.get(0).checked = !jqoRowSelCbox.get(0).checked;
			isSelected    = jqoRowSelCbox.get(0).checked;
		}
		
		isSelected?
			jqoRow.addClass('sel'):
			jqoRow.removeClass('sel');
		
		if(settings.cbOnSelect){
			settings.cbOnSelect(t);
		}
		_refreshActionBar();
		
		// Todos os checkboxes estão selecionados?
		var isEverythingSelected = !(cache.jqoCheckboxes.not(":checked").length);
		$(cache.jqoHeaderBar).find("input:checkbox").get(0).checked = isEverythingSelected;
	}
	var _addRowClickHandler = function(jqoRow){
		jqoRow.on('click dblclick', function(ev){
			if($(ev.target).closest('.dgt-ignore-sel-click').length){
				// console.log("Checkbox: Clicou num elemento que não queremos que o clique seja processado, vou ignorar o clique.");
				return true;
			}
			if($(ev.target).filter("a,select,input,textarea,label[for]").not(".dgt-obj-selectable").length){
				// console.log("Checkbox: Clicou num link, vou ignorar o clique");
				return true;
			}
			if(ev.target.onclick){
				// console.log("Checkbox: Clicou num elemento que tem um .onclick. Vou ignorar o clique.");
				return true;
			}
			if(!$(ev.target).closest(".dgt-row-content").is(jqoRow)){
				// Se for uma dGridTable dentro de outra?
				return true;
			}
			
			if(ev.type == 'click'){
				_handleOnSelect(jqoRow, ev);
			}
			if(ev.type == 'dblclick'){
				// console.log("Double click, going anywhere...");
				return false;
			}
			
			return true;
		});
	}
	
	var _refreshTable     = function(){
		var tb      = cache.jqoTable;
		var jqoRows = cache.jqoContents.children(".dgt-row-content");
		var jqoHead = cache.jqoHeaderBar;
		settings.isSelectable?
			jqoRows.add(jqoHead).children(".dgt-col-selectable").show():
			jqoRows.add(jqoHead).children(".dgt-col-selectable").hide();
		
		(settings.cbOnMove && (t.getRowsN() > 1))?
			jqoRows.add(jqoHead).children(".dgt-col-movable").show():
			jqoRows.add(jqoHead).children(".dgt-col-movable").hide();
		
		((settings.cbWriteOptions || settings.cbRefreshOptions) && (t.getRowsN() > 1))?
			jqoRows.add(jqoHead).children(".dgt-col-options").show():
			jqoRows.add(jqoHead).children(".dgt-col-options").hide();
		
		(settings.cbWriteFooter)?
			cache.jqoFooterBar.show():
			cache.jqoFooterBar.hide();
		
		// Exibe/oculta colunas selecionadas
		var cacheToEval = "";
		for(idx in columns){
			cacheToEval = cacheToEval + "myColumns.slice("+idx+", "+(parseInt(idx)+1)+")."+(columns[idx].visible?'show':'hide')+"(); // "+(columns[idx].visible)+"\r\n";
		}
		
		var myColumns = cache.jqoHeaderBar.children(".dgt-col-header");
		eval(cacheToEval);
		
		jqoRows.each(function(){
			var myColumns = $(this).children(".dgt-col-content");
			eval(cacheToEval);
		});
	}
	var _refreshActionBar = function(){
		var jqoBar = cache.jqoActionEls.bar;
		var jqoCl  = cache.jqoActionEls.colLeft;
		var jqoCr  = cache.jqoActionEls.colRight;
		
		// 1. Atualiza o <select> com as novas ações:
		var cbGa   = settings.cbOnGenerateAction;
		if(cbGa){
			var jqoSe      = cache.jqoActionEls.actionSelect;
			var newActions = cbGa(t);
			jqoCr.show();
			
			if(newActions == cache.lastActionBar){
				// Do nothing.
			}
			else{
				cache.lastActionBar = newActions;
				// console.log("actionBar isVisible: ", jqoCr.prop('isVisible'));
				if(newActions && !jqoCr.prop('isVisible')){
					jqoSe.empty().html(newActions);
					jqoCr.css('visibility', 'inherit').animate({ opacity: 1 }, 'fast').prop('isVisible', true);
				}
				else if(jqoCr.prop('isVisible')){
					jqoCr.animate({ opacity: 0 }, 'fast', function(){
						if(!jqoCr.prop('isVisible'))
							$(this).css('visibility', 'hidden');
					}).prop('isVisible', false);
				}
			}
		}
		
		// 2. Exibe/oculta o botão de exibir colunas
		if(settings.isColumnChoosable){
			if(!jqoCl.prop('isVisible')){
				jqoCl.css('visibility', 'inherit').animate({ opacity: 1 }, 'fast').prop('isVisible', true);
				_buildChooseCol();
			}
		}
		else{
			if(jqoCl.prop('isVisible')){
				jqoCl.animate({ opacity: 0 }, 'fast', function(){
					if(!jqoCl.prop('isVisible')){
						$(this).css('visibility', 'hidden');
						cache.jqoActionEls.chooseColumnDiv.remove();
						cache.jqoActionEls.chooseColumnDiv = false;
					}
				}).prop('isVisible', false);
			}
		}
		
		// 3. Se tiver ação ou exibir colunas ativado, exibe a barra.
		if(settings.isColumnChoosable || (cbGa && settings.isSelectable)){
			jqoBar.show();
			settings.isColumnChoosable ?jqoCl.show():jqoCl.hide();
			cbGa&&settings.isSelectable?jqoCr.show():jqoCr.hide();
		}
		else{
			jqoBar.hide();
		}
		
		// To-do:
		// 4. Define o colspan do jqoBar.
		// $('td', jqoBar).first().attr('colspan', 10);
	}
	var _refreshHeaderBar = function(){
		// To-do:
		// --> Implementar no futuro, junto com o sistema de sorting().
	}
	var _refreshData      = function(){
		if(rows){
			// Tem alguma ROW no buffer para adicionar?
			// Vamos adicionar, remover do buffer e esconder as colunas invisíveis.
			for(var idx in rows){
				_buildContentRow(rows[idx]);
			}
			rows = [];
		}
		
		// To-do:
		//     Aqui, vamos atualizar os conteúdos selecionados ou não.
		//     Ou seja, mudar a classe e conferir se o "checkbox" está de acordo.
		
		// Formata com a classe 'Even / Odd'
		// To-do:
		// --> Conferir se realmente precisa fazer isso aqui, pois é uma tarefa pesada.
		cache.jqoContents.children(".dgt-row-content").each(function(idx){
			var jqoRow    = $(this);
			var shouldBe  = (((idx+1)%2)?'odd':'even');
			var shouldNBe = (((idx+1)%2)?'even':'odd');
			
			if(jqoRow.hasClass(shouldNBe)){
				jqoRow.removeClass(shouldNBe);
				jqoRow.addClass(shouldBe);
			}
			
			// Tem um callback para atualizar as opções?
			if(settings.cbRefreshOptions){
				settings.cbRefreshOptions(jqoRow, jqoRow.children(".dgt-col-options"), t);
			}
		});
	}
	var _refreshFooter    = function(){
		if(settings.cbWriteFooter){
			console.log("Updating footer.");
			var info = {};
			info.nVisible = $(".dgt-row-content", cache.jqoContents).length;
			settings.cbWriteFooter(info, cache.jqoFooterTd.attr('colspan', 10), t);
		}
	}
	
	var _buildTable      = function(){
		var tb = cache.jqoTable  = $(
			"<table border='1' cellpadding='1' cellspacing='0' class='dgt-table'>"+
				"<thead></thead>"+
				"<tbody></tbody>"+
				"<tfoot></tfoot>"+
			"</table>"
		).css('border-collapse', 'collapse');
		
		cache.jqoHeaderBar = $("<tr class='dgt-row-headerbar'>").appendTo($("thead", tb));
		cache.jqoContents  = tb.find("tbody", tb);
		cache.jqoFooterBar = $("<tr class='dgt-row-footerbar'>").appendTo($('tfoot', tb));
		cache.jqoFooterTd  = $("<td>").appendTo(cache.jqoFooterBar);
	}
	var _buildActionBar  = function(){
		if(!settings.supportActionBar)
			return false;
		
		var jqoEls         =
		cache.jqoActionEls = {};
		jqoEls.bar              = $("<tr>").prependTo(cache.jqoTable.children("thead")).addClass('dgt-row-actionbar');
		jqoEls.barTd            = $("<td>").appendTo(jqoEls.bar).css('padding', 5);
		jqoEls.table            = $("<table width='100%' border='0' cellspacing='0' cellspacing='0'>").appendTo(jqoEls.barTd);
		jqoEls.tBody            = $("<tbody>").appendTo(jqoEls.table);
		jqoEls.row              = $("<tr>").appendTo(jqoEls.tBody);
		jqoEls.colLeft          = $("<td>").appendTo(jqoEls.row).css('text-align', 'left') .css('opacity', 0).css('visibility', 'hidden').prop('isVisible', false).addClass('dgt-col-choosecolumns');
		jqoEls.colRight         = $("<td>").appendTo(jqoEls.row).css('text-align', 'right').css('opacity', 0).css('visibility', 'hidden').prop('isVisible', false);
		jqoEls.chooseColumnLink = $("<a href='#'>Selecionar colunas para exibir</a>").appendTo(jqoEls.colLeft);
		jqoEls.actionText       = $("<span class='dgt-action-text'>Ação:</span>")    .appendTo(jqoEls.colRight);
		jqoEls.actionSelect     = $("<select>")                                      .appendTo(jqoEls.colRight);
		jqoEls.actionButton     = $("<input type='submit'>")                         .appendTo(jqoEls.colRight);
		
		// To-do:
		// --> Remove line below.
		jqoEls.barTd.attr('colspan', 10);
		
		// Prepara as ações:
		jqoEls.actionButton.click(function(){
			if(settings.cbOnSubmitAction && jqoEls.colRight.prop('isVisible')){
				settings.cbOnSubmitAction(jqoEls.actionSelect.val(), t);
			}
		});
		jqoEls.chooseColumnLink.click(function(){
			cache.jqoActionEls.chooseColumnDiv.fadeToggle('fast');
			return false;
		});
	}
	var _buildChooseCol  = function(){
		if(cache.jqoActionEls.chooseColumnDiv){
			return false;
		}
		
		var dv = cache.jqoActionEls.chooseColumnDiv = $("<div>").addClass('dgt-choosecolumn-box').hide();
		dv.appendTo(cache.jqoActionEls.colLeft);
		dv.css('position', 'absolute');
		
		var htmlToWrite = "<table>";
		for(var idx in columns){
			if(!columns[idx].choosable)
				continue;
			
			var tmpUid  = (settings.id+"-dgt-cc-cbox-"+columns[idx].key);
			
			htmlToWrite += "<tr>";
			htmlToWrite += "<td>";;
			htmlToWrite += "<input type='checkbox' class='dgt-choosecolumn-cbox' value='"+columns[idx].key+"' id='"+tmpUid+"'";
			if(columns[idx].visible)
				htmlToWrite += " checked='checked'";
			htmlToWrite += " />";
			htmlToWrite += "</td>";
			htmlToWrite += "<td>";
			htmlToWrite += "<label for='"+tmpUid+"'>"+columns[idx].title+"</label>";
			htmlToWrite += "</td>";
			htmlToWrite += "</tr>";
		}
		htmlToWrite += "</table>";
		dv.html(htmlToWrite);
		
		dv.dClickOutside(function(clickedWhere){
			if(dv.is(":visible") && !clickedWhere.is(cache.jqoActionEls.chooseColumnLink)){
				dv.fadeOut();
			}
		});
		$(".dgt-choosecolumn-cbox", dv).click(function(){
			t.columnShow($(this).val(), $(this).is(":checked"));
		});
	}
	var _buildHeaderBar  = function(){
		var bar = cache.jqoHeaderBar;
		for(var idx in columns){
			bar.append("<td class='dgt-col-header'>"+(columns[idx].title)+"</td>");
		}
		
		_extendHeaderBar();
	}
	var _buildContents   = function(){
		// Sempre gerado pelo _refreshData.
	}
	var _buildContentRow = function(jqoRow){
		// Vamos inicializar as funcionalidades de cada linha.
		var jqoBox    = cache.jqoContents;
		var valueId   = $("td", jqoRow).first().text();
		
		// Vamos assimilar um ID para esta row, para
		// identificá-la posteriormente.
		jqoRow.attr('id', settings.id + '-' + valueId);
		
		// Tem um callback para gerar as opções?
		if(settings.cbWriteOptions){
			settings.cbWriteOptions(jqoRow, jqoRowOpt, t);
			jqoRowOpt.prop('dgt-options-built', true);
		}
		
		// Define a cor de background
		jqoRow.addClass((($(".dgt-row-content", jqoBox).length+1)%2)?'even':'odd');
		
		_extendContentRow  (jqoRow, valueId);
	}
	var _buildFooter     = function(){
		// To-do:
	}
	
	var _extendHeaderBar = function(){
		var bar = cache.jqoHeaderBar;
		bar.prepend($(
			"<td class='dgt-col-movable'></td>"+
			"<td class='dgt-col-selectable'><input type='checkbox' /></td>"
		));
		bar.append("<td class='dgt-col-options'></td>");
		
		// Faz o "selectAll" funcionar:
		$(".dgt-col-selectable>input:checkbox", bar).click(function(){
			if($(this).is(":checked")){
				t.selectAll(true);
			}
			else{
				t.selectAll(false);
			}
			return true;
		});
	}
	var _extendContentRow = function(jqoRow, valueId){
		var jqoBox    = cache.jqoContents;
		
		// Adiciona as colunas 'Moving', 'Select' e 'Options'
		var jqoRowMov = $("<td class='dgt-col-movable'></td>");
		var jqoRowSel = $("<td class='dgt-col-selectable'></td>");
		var jqoRowOpt = $("<td class='dgt-col-options'></td>");
		jqoRow.prepend (jqoRowMov, jqoRowSel);
		jqoRow.append  (jqoRowOpt);
		jqoRow.appendTo(jqoBox);
		
		// Configura o jqoRowSel e o jqoRowMov
		jqoRowMov
			.css('width',      17)
			.css('min-height', 14)
			.css('background-image',    "url('images/move_ud.gif')")
			.css('background-repeat',   'no-repeat')
			.css('background-position', 'center center')
			.css('cursor', 'move');
		
		var jqoNewCBox = $("<input type='checkbox' class='dgt-obj-selectable' />").val(valueId).appendTo(jqoRowSel);
		cache.jqoCheckboxes = cache.jqoCheckboxes.add(jqoNewCBox);
		
		_addRowClickHandler(jqoRow);
	}
	
	var _mountRowAssoc   = function(arrRow){
		if(arrRow.length != columns.length){
			alert("Inconsistencia:\nRow="+(arrRow.length)+" colunas, esperado era "+(columns.length));
			return false;
		}
		
		var newRow = [];
		for(var idx in arrRow){
			newRow[columns[idx].key] = arrRow[idx];
		}
		
		return newRow;
	}
}
