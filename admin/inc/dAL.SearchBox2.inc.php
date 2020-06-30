<?php
require_once dirname(__FILE__) . "/../config.php";

// Novo sistema de listagem e buscas.
if (isset($includeContext)) {
    if (!$params || !sizeof($params)) {
        echo "<b>SearchBox2 Usage:</b><br />";
        echo "<pre>";
        echo "\$callBacks = Array(\r\n";
        echo "	'onPreSearchObj'  => function(dDbSearch \$s){\r\n";
        echo "		// Será chamado logo após \$tmpS->setTable() e \$tmpS->setFields().\r\n";
        echo "		// Não precisa de retorno.\r\n";
        echo "		// addFTable, addFField, addField, etc., devem vir aqui.\r\n";
        echo "	},\r\n";
        echo "	'onPostSearchObj' => function(dDbSearch \$s){\r\n";
        echo "		// Será chamado logo antes de \$tmpS->perform();\r\n";
        echo "		// Não precisa de retorno.\r\n";
        echo "		// addModifier, addWhere, setOrdem, setGroupBy, etc., devem vir aqui.\r\n";
        echo "	},\r\n";
        echo "	'setFilter'       => function(dDbSearch \$s, \$searchStr, \$searchCols){\r\n";
        echo "		// Este método pode:\r\n";
        echo "		// - Substituir o método setFilter(), e retornar TRUE ou FALSE.\r\n";
        echo "		// - Retornar uma string para substituir \$searchStr.\r\n";
        echo "		// - Retornar Array('searchStr'=>'Nova string', 'searchCols'=>'Novas colunas', 'matchPhrase'=>true/false).\r\n";
        echo "		return \$searchStr;\r\n";
        echo "	},\r\n";
        echo "	'showLineBg'      => function(\$row, \$rowIdx){\r\n";
        echo "		// Muda a cor de fundo de determinada linha.\r\n";
        echo "		// Pode retornar:\r\n";
        echo "		//     (string) '#FCC'\r\n";
        echo "		//     (array)  Array('bgColor'=>'#000', 'textColor'=>'#FFF')\r\n";
        echo "		//     (bool)   false; (cor automática)\r\n";
        echo "		return false;\r\n";
        echo "	},\r\n";
        echo "	'showColumn'      => function(\$row, \$column, \$rowIdx){\r\n";
        echo "		// Pode customizar os valores de cada coluna.\r\n";
        echo "		// Retorno deve ser o valor da coluna.\r\n";
        echo "		return \$row[\$column];\r\n";
        echo "	},\r\n";
        echo "	'showOptions'     => function(\$row, \$options){\r\n";
        echo "		// Modifica as opções e/ou os ícones das opções\r\n";
        echo "		// \$options[edit]   = Array('onclick'=>'', 'link'=>'', 'texto'=>'', 'iconHtml'=>''))\r\n";
        echo "		// \$options[delete] = Array('onclick'=>'', 'link'=>'', 'texto'=>'', 'iconHtml'=>''))\r\n";
        echo "		return \$options;\r\n";
        echo "	},\r\n";
        echo "	'showLastLine'    => function(\$s, \$cols, \$colsVisible){\r\n";
        echo "		// Cria uma linha no final da tabela, mas acima da paginação.\r\n";
        echo "		// Se retornar STRING, essa string será exibida sem padding.\r\n";
        echo "		// Se retornar ARRAY, o Array será exibido integralmente.\r\n";
        echo "		// Exemplo:\r\n";
        echo "		//     return Array('&lt;td&gt;Coluna1&lt;/td&gt;', '&lt;td&gt;Coluna2&lt;/td&gt;')\r\n";
        echo "		return false;\r\n";
        echo "	}\r\n";
        echo ");\r\n";
        echo "<hr size='1' />";
        echo "dHelper2::includePage('inc/dAL.SearchBox2.inc.php', Array(\r\n";
        echo "		'className'=>'dObjeto',       // Classe a ser buscada\r\n";
        echo "		'colTitles'=>'',\r\n";
        echo "		'colFields'=>'',\r\n";
        echo "		'inlineEdit'=>'titulo,data',  // Permite QuickEdit (dEip) nos seguintes campos\r\n";
        echo "		'allowSetOrdem'=>true,        // Detecta a coluna 'ordem' e permite definir a posição dos registros.\r\n";
        echo "		'allowSearch'  =>true,        // FALSE, TRUE ou 'coluna1,coluna2...'\r\n";
        echo "		'allowSorting' =>true,        // Permite que o usuário ordene os resultados pelas colunas\r\n";
        echo "		'ajaxDelete'   =>true,        // Libera o botão 'x', que exclui via AJAX o resultado\r\n";
        echo "		'optionsModel' =>'icons',     // Haverá botões na direita? Qual modelo? 'none', 'options' ou 'icons'\r\n";
        echo "		'dblClickEdit' =>true,        // Edita se houver um duplo-clique\r\n";
        echo "		'tableWidth'   =>'100%',      \r\n";
        echo "		'resPerPage'   =>250,         \r\n";
        echo "		'callBacks'    =>\$callBacks, \r\n";
        echo "		'usePerformObj'=>false,       // Informe a classe para usear performObj. Nos callbacks, \$row receberá um objeto.\r\n";
        echo "	)\r\n";
        echo ");\r\n";
        echo "</pre>";
        return false;
    }

    // Acrescenta automaticamente parâmetros mais recentes:
    $params = dHelper2::addDefaultToArray($params, array(
        'usePerformObj' => false,   // Criado em 11/03/2013
    ));
    extract($params);
}


// 1. Permitir setup rápido ---> OK
// 2. Suporte a setOrdem    ---> OK (To-do: Se nem todos os registros estiverem aparecendo?)
// 3. Suporte a startUpdate ---> OK
// 4. Suporte a paginação   ---> OK
// 5. Suporte a busca       ---> 
// 5. Suporte a novos cadastros in-line
// 6. Suporte a novos cadastros in iframe
// 7. Suporte a opções diversas
// 8. Suporte a edição rápida (lápis)
// 9. Proteção na exclusão

// Helper:
if (!@$colTitles) {
    // Ajuda o programador, criando os campos iniciais.
    $obj = new $className;
    $exKeys = array_keys($obj->fieldValues);

    $fields = array();
    $fieldTitle = array();
    $fieldAlias = array();
    $alertas = array();

    $n = 1;

    foreach ($exKeys as $key) {
        if ($key == 'id') {
            $fieldTitle[] = "Cód";
            $fieldAlias[] = "!id";
            continue;
        }
        if (substr($key, -3) == '_id') {
            $alertas[] = "Tabela externa: {$key}";
            continue;
        }

        $useTitle = ucfirst($key);
        if ($useTitle == 'Titulo') {
            $useTitle = "Título";
        }

        $fieldTitle[] = " {$useTitle}";
        $fieldAlias[] = ($n++ > 5) ? "!{$key}" : " {$key}";
    }
    echo "<b>Personalize e copie as seguintes configurações:</b><br />";
    echo "<pre>";
    echo "		'colTitles' => '" . implode(",", $fieldTitle) . "',\r\n";
    echo "		'colFields' => '" . implode(",", $fieldAlias) . "',\r\n";
    echo "\r\n";
    echo "---------------------------------------------\r\n";
    echo implode("\r\n", $alertas) . "\r\n";
    echo "---------------------------------------------\r\n";
    echo "* Não é um campo real. Deve ser tratado pelo callback showColumn.\r\n";
    echo "! Ocultar a exibição, mas permitir busca nesse campo.\r\n";
    die;
}
if (!in_array($optionsModel, array('none', 'options', 'icons'))) {
    echo "<b>optionsModel</b> deve ser: none, options ou icons.<br />";
    echo "Recomendamos: icons<br />";
    die;
}

// Código que não pode ser alterado:
if (!isset($dblClickEdit)) {
    $dblClickEdit = true;
}

// Inicializa a classe principal, para uso geral.
$tmpObject = new $className;

if ($allowSetOrdem) {
    $allowSetOrdem = method_exists($className, 'sortingIsEnabled') ?
        $tmpObject->sortingIsEnabled() :
        method_exists($className, 'sortingMoveTo');

    if ($allowSetOrdem) {
        // Se pode definir a ordem, não pode ter busca nem paginação.
        $allowSearch = false;
        $resPerPage = false;
        $allowSorting = false;
    }
}

{ // Vamos criar o objeto dDbSearch:
    $tmpS = new dDbSearch(dDatabase::start());
    $tmpS->setTable($tmpObject->mainTable['tableName']);
    if (isset($tmpObject->fieldValues['id'])) {
        $tmpS->addField('id');
    }
    if ($allowSetOrdem) {
        $tmpS->addField('ordem');
        $tmpS->setOrderBy('ordem');
    }
    if ($callBacks['onPreSearchObj']) {
        call_user_func($callBacks['onPreSearchObj'], $tmpS);
    }
}
{ // Paginação e Sorting:
    if ($resPerPage) {
        $tmpS->activatePagination($resPerPage);
    }
    if ($allowSorting) {
        $tmpS->activateSorting();
    }
}
{ // Criamos $colsVisible (numero de colunas), e $cols, contendo:
    //     aliasName --> ...
    //     isReal    --> É ou não real (*)
    //     ignore    --> Campo oculto  (!)
    //     text      --> Título da coluna
    //     orderLink --> ->writeSortLink()
    $cols = array();
    $inlineEdit = explode(",", $inlineEdit);
    $colTitles = explode(",", $colTitles);
    $colFields = explode(",", $colFields);
    $colsVisible = 1;
    foreach ($colFields as $idx => $aliasName) {
        $aliasText = trim($colTitles[$idx]);
        $aliasName = trim($aliasName);

        $ignore = false;
        $isReal = true;
        if ($aliasName[0] == '!') {
            $ignore = true;
            $aliasName = substr($aliasName, 1);
        }
        if ($aliasName[0] == '*') {
            $isReal = false;
            $aliasName = substr($aliasName, 1);
        }

        // Aceita as customizações enviadas pela busca.
        if (@$_GET['searchCols'] && is_array($_GET['searchCols'])) {
            $ignore = !in_array($aliasName, $_GET['searchCols']);
        }

        if (!$ignore) {
            if ($isReal && !isset($tmpS->fieldProps[$aliasName]['sqlField'])) {
                $tmpS->addField($aliasName);
            }
            $colsVisible++;
        }

        $info['aliasName'] = $aliasName;
        $info['isReal'] = $isReal;
        $info['ignore'] = $ignore;
        $info['text'] = $aliasText;
        $info['orderLink'] = $isReal ? ($info['aliasName'] ? $tmpS->writeSortLink($aliasName) : false) : false;
        if ($info['orderLink'] == '#') {
            $info['orderLink'] = false;
        }
        $cols[] = $info;
    }

    if ($optionsModel != 'none') {
        $colsVisible++;
    }
    if ($allowSetOrdem) {
        $colsVisible++;
    }
}
{ // Vamos processar a busca:
    if ($allowSearch && isset($_GET['q']) && $_GET['q']) {
        // Se houver um modifier tipo 'date' ou 'datetime', podemos converter automaticamente.
        // Caso contrário, temos dois tipos de filtro:
        //
        // Buscar por: [_____________________________]
        // ( ) sequencia exata               ( ) todos os termos
        // ( ) Apenas nos campos visíveis    ( ) Em todas as colunas
        // [ Buscar ]
        //
        // Campos visíveis:
        //   [ ] id       [ ] nome      [ ] email
        //   [ ] telefone [ ] cpf       [ ] rg

        // Exemplo:
        # $s->addModifier('destaque', function($yn){ return $yn?"Sim":"Não" });
        # $s->addModifier('data',     'datetime');
        # $s->addModifier('nome',     'limit=80');

        // A seguinte query teria que ser gerada:
        # $str = '10/08/20120


        // b) Vamos pesquisar em todos os campos disponíveis, ou seja, se addField for chamado, vai ser utilizado
        // c) Se tiver callback para setFilter, utilizá-lo


        $searchOnCols = array();
        foreach ($cols as $item) {
            if (!$item['ignore'] && $item['isReal']) {
                $searchOnCols[] = $item['aliasName'];
            }
        }

        if ($callBacks['setFilter']) {
            $tmpReturn = call_user_func($callBacks['setFilter'], $tmpS, $_GET['q'], $searchOnCols);
            if (is_bool($tmpReturn)) {
                // Substituiu o método setFilter automático, não fazer nada.
            } elseif (is_string($tmpReturn)) {
                // Substituiu a string a ser pesquisada.
                $tmpS->setFilter($tmpReturn, $searchOnCols, false);
            } elseif (is_array($tmpReturn)) {
                // Substitui os parâmetros que seriam utilizados no setFilter.
                $tmpSOC = isset($tmpReturn['searchOnCols']) ? $tmpReturn['searchOnCols'] : $searchOnCols;
                $tmpSTR = isset($tmpReturn['searchStr']) ? $tmpReturn['searchStr'] : $searchStr;
                $tmpMP = isset($tmpReturn['matchPhrase']) ? $tmpReturn['matchPhrase'] : false;
                $tmpS->setFilter($tmpReturn, $searchOnCols, $tmpMP);
            }
        } else {
            $tmpS->setFilter($_GET['q'], $searchOnCols, false);
        }
    }
}
{ // Vamos gerar $lista (perform())
    if ($callBacks['onPostSearchObj']) {
        call_user_func($callBacks['onPostSearchObj'], $tmpS);
    }
    $lista = $usePerformObj ?
        $tmpS->performObj($usePerformObj) :
        $tmpS->perform();
}
{ // Vamos montar o formulário da busca
    if ($allowSearch) {
        dHelper2::includePage(dirname(__FILE__) . '/inc.dAL.SearchBox2.Search.inc.php');
    }
}

// Vamos montar a exibição dos resultados:
$SB2_UID = uniqid();
echo "<div id='{$SB2_UID}'>";
echo "<table border='0' cellpadding='2' cellspacing='0' width='{$tableWidth}' class='dalLista" . ($allowSetOrdem ? " searchResults" : "") . ($dblClickEdit ? " dblClickEdit" : "") . "' style='border-collapse: collapse' usingClass='{$className}'>\r\n";
{ // Cabeçalho (header):
    echo "<thead>";
    echo "	<tr class='header nodrag'>";
    echo "		<td valign='top' style='padding: 0px'><img border='0' src='images/box_tl.gif'></td>";
    foreach ($cols as $item) {
        if ($item['ignore']) {
            continue;
        }

        echo "		<td nowrap='nowrap'>";
        echo ($item['orderLink']) ? "<a href='{$item['orderLink']}'>" : "";
        echo $item['text'];
        echo ($item['orderLink']) ? "</a>" : "";
        echo ($tmpS->getOrderBy() == "{$item['aliasName']}") ? "<img border='0' src='images/seta_down.gif' />" : "";
        echo ($tmpS->getOrderBy() == "{$item['aliasName']} desc") ? "<img border='0' src='images/seta_up.gif'   />" : "";
        echo "		</td>\r\n";
    }
    if ($optionsModel != 'none') {
        echo "		<td>&nbsp;</td>";
    }
    echo "	</tr>";
    echo "</thead>";
}
{ // Conteúdo - Resultados
    if ($lista) {
        echo "<tbody>";
        foreach ($lista as $idx => $item) {
            if ($usePerformObj) {
                $itemObj = $item;
                $item = &$item->fieldValues;
            }
            $rowClass = ($idx % 2) ? 'row2' : 'row1';
            $rowBg = false;

            if (array_key_exists('showLineBg', $callBacks)) {
                $rowBg = call_user_func($callBacks['showLineBg'], $usePerformObj ? $itemObj : $item, $idx);
            }

            echo "<tr id='item-{$SB2_UID}-{$item['id']}' class='{$rowClass}' " . ($rowBg ? " style='background: {$rowBg}'" : "") . ($allowSetOrdem ? " ordem='{$item['ordem']}'" : "") . " usingId='{$item['id']}'>";
            if ($allowSetOrdem) {
                echo "	<td title='Clique e arraste para cima ou para baixo para mover este item' style='width: 25px; height: 25px; background-image: url(images/move_ud.png); background-repeat: no-repeat; background-position: center; cursor: move' class='movable'></td>";
            } else {
                echo "	<td style='border-left: 1px solid #DDD; border-right: 0'></td>";
            }
            foreach ($cols as $colInfo) {
                if ($colInfo['ignore']) {
                    continue;
                }

                // O conteudo de cada célula será escrito aqui:
                $colNoWrap = false;
                $colAlign = 'left';
                if ($callBacks['showColumn']) {
                    // 'showColumn'      => function($row, $column, $rowIdx){
                    $colText = call_user_func($callBacks['showColumn'], $usePerformObj ? $itemObj : $item,
                        $colInfo['aliasName'], $idx);
                    if (is_array($colText)) {
                        $colAlign = @$colText['align'];
                        $colNoWrap = @$colText['nowrap'];
                        $colText = @$colText['value'];
                    }
                } else {
                    $colText = $item[$colInfo['aliasName']];
                }

                if (in_array($colInfo['aliasName'], $inlineEdit)) {
                    $colText = dInput2::inputRead("name='{$className}:{$item['id']}:{$colInfo['aliasName']}'",
                        $item[$colInfo['aliasName']], 'dAL_SearchBox2_InLineEdit');
                }

                echo "<td align='{$colAlign}'" . ($colNoWrap ? " nowrap='nowrap'" : "") . ">";
                echo $colText;
                echo "</td>";
            }

            // Opções:
            if ($optionsModel != 'none') {
                $editLink = str_replace("_list", "_edit", $_SERVER['PHP_SELF']);
                $editLink .= (stripos($editLink, "?") === false) ?
                    "?id={$item['id']}" :
                    "&id={$item['id']}";

                $rowOptions = array();
                $rowOptions['edit'] = array(
                    'onclick' => false,
                    'link' => $editLink,
                    'texto' => 'Editar',
                    'iconHtml' => "<i class='fa fa-pencil' style='color: #080'></i>"
                );
                $rowOptions['delete'] = array(
                    'onclick' => false,
                    'link' => 'EXCLUIR',
                    'texto' => 'Excluir',
                    'iconHtml' => "<i class='fa fa-times-circle' style='color: #F00'></i>"
                );
                if ($callBacks['showOptions']) {
                    $rowOptions = $callBacks['showOptions']($usePerformObj ? $itemObj : $item, $rowOptions);
                }

                if ($ajaxDelete && array_key_exists('delete', $rowOptions) && !$rowOptions['delete']['onclick']) {
                    $rowOptions['delete']['link'] = '#';
                    $rowOptions['delete']['onclick'] = "if(confirm('Excluir este item?')) dAL_SearchBox2_AjaxDelete('{$className}', '{$item['id']}', 'item-{$SB2_UID}-{$item['id']}'); return false;";
                }

                if ($optionsModel == 'icons') {
                    echo "<td align='left' nowrap='nowrap'>";
                    foreach ($rowOptions as $optionKey => $optionItem) {
                        echo "<a href='{$optionItem['link']}' " . ($optionItem['onclick'] ? "onclick=\"{$optionItem['onclick']}\" " : "") . " rel=\"{$optionKey}\">{$optionItem['iconHtml']}</a> ";
                    }
                    echo "</td>";
                } elseif ($optionsModel == 'options') {
                    echo "<td align='center' nowrap='nowrap'>";
                    echo "	<a href='#' onclick=''><img src='images/opcoes.jpg' border='0' /></a>";
                    echo "	<div style='position: absolute; left: 200px; width: 150px; background: #FFF; border: 2px dotted red; text-align: left; display: none'>";
                    foreach ($rowOptions as $optionKey => $optionItem) {
                        echo "- <a href='{$optionItem['link']}' " . ($optionItem['onclick'] ? "onclick=\"{$optionItem['onclick']}\" " : "") . "rel=\"{$optionKey}\">{$optionItem['texto']}</a><br />";
                    }
                    echo "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</tbody>";
        echo "<tfoot>";
        echo "<tr class='nodrag'>\r\n";
        echo "	<td colspan='{$colsVisible}' align='right' class='footer'>";
        echo ($tmpS->getPagesTotal() > 1) ?
            $tmpS->writeResultsStr("Exibindo <b>#-#</b> de <b>#</b> resultados") :
            "Foram encontrados <b>" . sizeof($lista) . "</b> resultados.";
        echo "	</td>";
        echo "</tr>";

        if ($callBacks['showLastLine']) {
            $lastLineStr = $callBacks['showLastLine']($tmpS, $cols, $colsVisible);
            if (is_string($lastLineStr)) {
                echo '<tr class="nodrag">';
                echo "	<td colspan='{$colsVisible}' class='footer' style='padding: 0'>{$lastLineStr}</td>";
                echo '</tr>';
            } elseif (is_array($lastLineStr)) {
                // Esperado:
                //     [] = <td....></td>
                //     [] = <td....></td>
                //     [] = <td....></td>
                echo '<tr class="nodrag">';
                echo implode("", $lastLineStr);
                echo '</tr>';
            }
        }
        if ($tmpS->getPagesTotal() > 1) {
            echo "<tr class='nodrag'>";
            echo "	<td colspan='{$colsVisible}'' align='right' class='footer'>";
            echo "		Página: " . $tmpS->writePagination(5, 5, array('', ''), array('<b>(', ')</b>'), false,
                    ' &nbsp; ');
            echo "	</td>";
            echo "</tr>";
        }
        echo "</tfoot>";
    } else {
        // Nenhum resultado encontrado.
        echo '<tr class="nodrag">';
        echo "	<td colspan='{$colsVisible}' align='center' class='footer'>";
        echo "		Nenhum resultado encontrado.";
        echo "	</td>";
        echo '</tr>';
        if ($callBacks['showLastLine']) {
            $lastLineStr = $callBacks['showLastLine']($tmpS, $cols, $colsVisible);
            if (is_string($lastLineStr)) {
                echo '<tr class="nodrag">';
                echo "	<td colspan='{$colsVisible}' class='footer' style='padding: 0'>{$lastLineStr}</td>";
                echo '</tr>';
            } elseif (is_array($lastLineStr)) {
                // Esperado:
                //     [] = <td....></td>
                //     [] = <td....></td>
                //     [] = <td....></td>
                echo '<tr class="nodrag">';
                echo implode("", $lastLineStr);
                echo '</tr>';
            }
        }
    }
}
echo "</table>";
echo "</div>";

if (!@$_dAL_SearchBox2_JsWritten):
    $_dAL_SearchBox2_JsWritten = true;
    ?>
    <script type='text/javascript'>
        var allowSetOrdem = <?=$allowSetOrdem ? 'true' : 'false'?>;
    </script>
    <script type='text/javascript'>
        function dAL_SearchBox2_AjaxDelete(className, id, rowId) {
            $.post("inc/ajax.dAL.SearchBox2.inc.php", {
                action: 'ajaxDelete',
                className: className,
                id: id
            }, function (ret) {
                if (ret != 'OK') {
                    alert(ret);
                } else {
                    var table = $("#" + rowId).closest('table');
                    $("#" + rowId).remove();
                    table.find("tr.row1,tr.row2").each(function (idx) {
                        $(this).attr('class', (idx % 2) ? 'row2' : 'row1');
                    });
                }
            });
        }

        function dAL_SearchBox2_InLineEdit(uid, name, nv, ov) {
            var parts = name.split(':');
            $.post("inc/ajax.dAL.SearchBox2.inc.php", {
                action: 'inlineEdit',
                className: parts[0],
                id: parts[1],
                key: parts[2],
                ov: ov,
                nv: nv
            }, function (ret) {
                if (ret.substr(0, 6) == "ERROR=") {
                    alert("Valor não foi atualizado.\n" + ret.substr(6));
                    document.getElementById('dIROSpan' + uid).innerHTML = ov;
                    document.getElementById('dIROInput' + uid).value = ov;
                } else {
                    document.getElementById('dIROSpan' + uid).innerHTML = ret;
                }
            });
            return true;
        }

        function dAL_SearchBox2_OnMove(oldInfo, newInfo) {
            if (oldInfo.rowIndex == newInfo.rowIndex)
                return;

            newInfo.jqoTable.find("tbody>tr").each(function (idx) {
                $(this).attr('class', "row" + ((idx % 2) ? '2' : '1'));
            });

            $.post("inc/ajax.dAL.SearchBox2.inc.php", {
                    id: newInfo.jqoRow.attr('usingid'),
                    className: newInfo.jqoTable.attr('usingclass'),
                    action: (newInfo.jqoRowAbove.length) ? 'moveAfter' : 'moveBefore',
                    otherId: (newInfo.jqoRowAbove.length ? newInfo.jqoRowAbove : newInfo.jqoRowBelow).attr('usingid')
                },
                function (ret) {
                    if (ret != 'OK') {
                        alert(ret);
                    }
                });
        }

        if (allowSetOrdem) {
            $(function () {
                $("#<?=$SB2_UID?>>table").dRowDrag({
                    mouseOffset: {left: 0, top: 0},
                    cbCanMove: function (ev) {
                        return $(ev.target).closest(".movable").length;
                    },
                    cbOnStart: function (info) {
                    },
                    cbOnMove: function (oldInfo, newInfo) {
                        newInfo.jqoTable.find("tbody>tr").each(function (idx) {
                            $(this).attr('class', "row" + ((idx % 2) ? '2' : '1'));
                        });
                    },
                    cbOnDrop: dAL_SearchBox2_OnMove
                });
            });
        }

        $(function () {
            // Libera o double-click to edit, se aplicável.
            $("table.dblClickEdit").find("tr").each(function () {
                var tr = $(this);
                tr.dblclick(function (e) {
                    // Chrome: e.srcElement
                    // Firefox: e.target
                    // IE: e.srcElement

                    var focus = (e.srcElement) ? e.srcElement : (e.target ? e.target : false);
                    if (focus && (focus.tagName.toLowerCase() == 'input' || focus.tagName.toLowerCase() == 'a')) {
                        return true;
                    }

                    var link = tr.find("a[rel='edit']").last();
                    if (!link)
                        return true;

                    if (link.get(0).onclick) {
                        return link.get(0).onclick();
                    }

                    location.assign(link.attr('href'));
                    return false;
                });
            });
        });
    </script>
<? endif;
