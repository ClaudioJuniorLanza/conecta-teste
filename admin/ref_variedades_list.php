<?php
require_once "config.php";
require_once "template.php";

dAL::layTop(array('bodyTitle' => "Gerenciar as variedades", 'saveGoBack' => true));
dAL::goBack(false, "<a href='ref_variedades_edit.php'>Cadastrar nova variedade</a>");

$callBacks = array(
    'onPreSearchObj' => function (dDbSearch $s) {
        // Será chamado logo após $tmpS->setTable() e $tmpS->setFields().
        // Não precisa de retorno.
        // addFTable, addFField, addField, etc., devem vir aqui.
    },
    'onPostSearchObj' => function (dDbSearch $s) {
        // Será chamado logo antes de $tmpS->perform();
        // Não precisa de retorno.
        // addModifier, addWhere, setOrdem, setGroupBy, etc., devem vir aqui.
    },
    'setFilter' => function (dDbSearch $s, $searchStr, $searchCols) {
        // Este método pode:
        // - Substituir o método setFilter(), e retornar TRUE ou FALSE.
        // - Retornar uma string para substituir $searchStr.
        // - Retornar Array('searchStr'=>'Nova string', 'searchCols'=>'Novas colunas', 'matchPhrase'=>true/false).
        return $searchStr;
    },
    'showLineBg' => function ($row, $rowIdx) {
        // Muda a cor de fundo de determinada linha.
        // Pode retornar:
        //     (string) '#FCC'
        //     (array)  Array('bgColor'=>'#000', 'textColor'=>'#FFF')
        //     (bool)   false; (cor automática)
        return false;
    },
    'showColumn' => function ($row, $column, $rowIdx) {
        // Pode customizar os valores de cada coluna.
        // Retorno deve ser o valor da coluna.
        return $row[$column];
    },
    'showOptions' => function ($row, $options) {
        // Modifica as opções e/ou os ícones das opções
        // $options[edit]   = Array('onclick'=>'', 'link'=>'', 'texto'=>'', 'iconHtml'=>''))
        // $options[delete] = Array('onclick'=>'', 'link'=>'', 'texto'=>'', 'iconHtml'=>''))
        unset($options['delete']);
        return $options;
    },
    'showLastLine' => function ($s, $cols, $colsVisible) {
        // Cria uma linha no final da tabela, mas acima da paginação.
        // Se retornar STRING, essa string será exibida sem padding.
        // Se retornar ARRAY, o Array será exibido integralmente.
        // Exemplo:
        //     return Array('&lt;td&gt;Coluna1&lt;/td&gt;', '&lt;td&gt;Coluna2&lt;/td&gt;')
        return false;
    }
);

dHelper2::includePage('inc/dAL.SearchBox2.inc.php', array(
        'className' => 'cRefVariedade',
        'colTitles' => 'Cód, Variedade, Cultura, Tecnologia',
        'colFields' => '!id, variedade, cultura, tecnologia',
        'inlineEdit' => '',
        // Permite QuickEdit (dEip) nos seguintes campos
        'allowSetOrdem' => true,
        // Detecta a coluna 'ordem' e permite definir a posição dos registros.
        'allowSearch' => true,
        // FALSE, TRUE ou 'coluna1,coluna2...'
        'allowSorting' => true,
        // Permite que o usuário ordene os resultados pelas colunas
        'ajaxDelete' => true,
        // Libera o botão 'x', que exclui via AJAX o resultado
        'optionsModel' => 'icons',
        // Haverá botões na direita? Qual modelo? 'none', 'options' ou 'icons'
        'dblClickEdit' => true,
        // Edita se houver um duplo-clique
        'tableWidth' => '100%',
        'resPerPage' => 250,
        'callBacks' => $callBacks,
        'usePerformObj' => false,
        // Informe a classe para usear performObj. Nos callbacks, $row receberá um objeto.
    )
);

dAL::layBottom();

