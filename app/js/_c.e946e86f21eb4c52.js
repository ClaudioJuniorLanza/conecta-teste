$(function(){$(".cotacaoForm").on('submit',function(){var jqoUf=$(".cotacaoForm input[name=uf]");var jqoCidade=$(".cotacaoForm input[name=cidade]");var jqoProduto=$(".cotacaoForm input[name^=produto]");if(jqoUf.val().length!=2){alert("Favor informar o UF com 2 caracteres.");return!1}
if(!jqoCidade.val().length){alert("Favor informar a cidade");return!1}
if(!jqoProduto.val()){alert("Você não selecionou o produto");return!1}
return!0})});$(function(){if('options' in document.createElement('datalist')){return!1}
var cultivList=[];$("#cultivarList option").each(function(){cultivList.push($(this).attr('value'))});$("input[list=cultivarList]").each(function(){var jqoInput=$(this);var jqoDlHolder=$("<div class='dataListHolder'></div>");var jqoOptions=$("<div class='dataListSimulator'></div>");jqoInput.removeAttr('list');jqoInput.wrap(jqoDlHolder);jqoOptions.hide().insertAfter(jqoInput);var _lastStr="";var _chooseOption=function(){jqoInput.val(this.innerHTML);jqoOptions.hide();jqoInput.change()};var _refreshOptions=function(){if(jqoInput.prop('readonly')){jqoOptions.hide();return!1}
var filterBy=$.trim(jqoInput.val()).toUpperCase();if(_lastStr==filterBy){return}
_lastStr=filterBy;if(filterBy.length<3){jqoOptions.hide();return}
filterBy=filterBy.split(" ");var _sofFilterBy=filterBy.length;var _foundAny=!1;jqoOptions.empty();for(var i=0;i<cultivList.length;i++){var _found=0;for(var k=0;k<filterBy.length;k++){if(cultivList[i].match("( |^)"+filterBy[k])){_found++}}
if(_found==_sofFilterBy){_foundAny=!0;var _jqoRow=$("<a>"+cultivList[i]+"</a>");jqoOptions.append(_jqoRow)}}
_foundAny?jqoOptions.show():jqoOptions.hide()};jqoOptions.bind('click',function(ev){var jqoTarget=$(ev.target);if(!jqoTarget.is('a')){return!1}
_chooseOption.call(ev.target);return!1});jqoInput.keyup(function(){_refreshOptions()});jqoInput.focus(_refreshOptions)})})