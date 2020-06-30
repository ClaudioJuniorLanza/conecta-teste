<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLogged();
$usuarObj->checkPermsOrDie('SENTMAIL_VIEW');

$allTipos = $db->singleColumn("select distinct tipo from d_emails where !isnull(tipo)");

dAL::layTop(array('bodyTitle' => "E-mails enviados pelo sistema", 'saveGoBack' => true));
if ($db->singleResult("select count(*) from d_emails")) {
    dAL::goBack(false, "<a href='email_export.php'>Exportar e-mails em planilha</a>");
}


if (sizeof($allTipos) > 1) {
    echo "<b>Exibir apenas:</b> (ou <a href='email_list.php'>exibir todos</a>)<br />";
    foreach ($allTipos as $tipo) {
        echo "- <a href='email_list.php?tipo=" . urlencode($tipo) . "'>{$tipo}</a><br />";
    }
    echo "<hr />";
}


$callBacks = array(
    'onPreSearchObj' => function (dDbSearch $s) {
        // Será chamado logo após $tmpS->setTable() e $tmpS->setFields().
        // Não precisa de retorno.
        $s->addModifier('data_hora', 'date=d/m/y H:i');
        $s->setOrderBy('data_hora desc');
    },
    'onPostSearchObj' => function (dDbSearch $s) {
        if (@$_GET['tipo']) {
            $s->addWhere('tipo', $_GET['tipo']);
        }

        // Será chamado logo antes de $tmpS->perform();
        // Não precisa de retorno.
    },
    'setFilter' => function (dDbSearch $s, $searchStr, $searchCols) {
        // Este método pode:
        // - Substituir o método setFilter(), e retornar FALSE.
        // - Retornar uma string para substituir $searchStr.
        $fields = implode(",", $searchCols) . ",text_html";
        $s->addField($fields);
        $s->setFilter($searchStr, implode(",", $searchCols) . ",text_html");
        return false;
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
        return $row->getValue($column);
    },
    'showOptions' => function ($row, $options) {
        // Modifica as opções e/ou os ícones das opções
        // $options[] = Array('edit'  =>(link=>, texto=>, iconHtml=>))
        // $options[] = Array('delete'=>(link=>, texto=>, iconHtml=>))
        $options['edit']['link'] = "email_view.php?id={$row->v('id')}";
        return $options;
    },
    'showLastLine' => function ($s, $cols, $colsVisible) {
        // Cria uma linha no final da tabela, mas acima da paginação.
        // Se retornar STRING, essa string será exibida sem padding.
        // Se retornar ARRAY, o Array será exibido integralmente.
        // Exemplo:
        //     return Array('<td>Coluna1</td>', '<td>Coluna2</td>')
        return false;
    }
);

dHelper2::includePage('inc/dAL.SearchBox2.inc.php', array(
        'className' => 'dEmail',       // Classe a ser buscada
        'colTitles' => 'Cód, Data, Tipo, Remetente,    E-mail remetente, Destinatário, Assunto, Text_html, Dsm_object, Deleted',
        'colFields' => '!id, data_hora,!tipo,  !replyto_name, !replyto_mail,   mailto, subject,!text_html,!dsm_object,!deleted',
        'inlineEdit' => "tipo",  // Permite QuickEdit (dEip) nos seguintes campos
        'allowSetOrdem' => true,        // Detecta a coluna 'ordem' e permite definir a posição dos registros.
        'allowSearch' => true,        // FALSE, TRUE ou 'coluna1,coluna2...'
        'allowSorting' => true,        // Permite que o usuário ordene os resultados pelas colunas
        'ajaxDelete' => true,        // Libera o botão 'x', que exclui via AJAX o resultado
        'optionsModel' => 'icons',     // Haverá botões na direita? Qual modelo? 'none', 'options' ou 'icons'
        'dblClickEdit' => true,        // Edita se houver um duplo-clique
        'tableWidth' => '100%',
        'resPerPage' => 250,
        'callBacks' => $callBacks,
        'usePerformObj' => 'dEmail'
    )
);

dAL::layBottom();
