<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLoggedOrRedirect();
$usuarObj->checkPermsOrDie('USER_MANAGE');

$showTrashBin = (isset($_GET['trashBin']) && $usuarObj->checkPerms('USER_TRASHBIN'));
$callBacks = array(
    'onPreSearchObj' => function (dDbSearch $s) {
        // Será chamado logo após $tmpS->setTable() e $tmpS->setFields().
        // Não precisa de retorno.
        // addFTable, addFField, addField, etc., devem vir aqui.
        $s->addField('facebook_id,facebook_invite,disabled,deleted');
    },
    'onPostSearchObj' => function (dDbSearch $s) use ($usuarObj, $showTrashBin) {
        // Será chamado logo antes de $tmpS->perform();
        // Não precisa de retorno.
        // addModifier, addWhere, setOrdem, setGroupBy, etc., devem vir aqui.
        $s->addModifier('data_cadastro,data_ult_login', 'date=d/m/y');
        if (!$usuarObj->checkPerms('USER_MANAGE_ALL')) {
            $s->addWhere("{$usuarObj->getPrimaryValue()} IN (usuar_id, id)");
        }
        $s->addWhere("deleted", ($showTrashBin) ? "1" : "0");
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
        if ($column == 'status') {
            return $row['disabled'] ? "Desativado" : "Normal";
        }
        return $row[$column];
    },
    'showOptions' => function ($row, $options) use ($usuarObj, $showTrashBin) {
        // Modifica as opções e/ou os ícones das opções
        if ($row['id'] == $usuarObj->getPrimaryValue()) {
            unset($options['delete']);
        } elseif (!$showTrashBin) {
            if ($usuarObj->checkPerms('USER_DELETE')) {
                $options['delete']['onclick'] = "if(confirm('Excluir?')) $.post('ajax.usuario_list.php', { id: {$row['id']}, action: 'delete' }, function(ret){ if(ret=='OK') window.location.reload(); else alert(ret); }); return false;";
            } else {
                unset($options['delete']);
            }
        } else {
            if ($usuarObj->checkPerms('USER_WIPE')) {
                $options['delete']['onclick'] = "if(confirm('Excluir definitivamente?')) $.post('ajax.usuario_list.php', { id: {$row['id']}, action: 'full_wipe' }, function(ret){ if(ret=='OK') window.location.reload(); else alert(ret); }); return false;";
            } else {
                unset($options['delete']);
            }
            $options['restore'] = array(
                'onclick' => "if(confirm('Restaurar item excluído?')) $.post('ajax.usuario_list.php', { id: {$row['id']}, action: 'undelete' }, function(ret){ if(ret=='OK') window.location.reload(); else alert(ret); }); return false;",
                'link' => 'go',
                'texto' => "Restaurar",
                'iconHtml' => "Restaurar",
            );
        }

        $_tmp = ($row['facebook_invite']) ?
            array("Aguardando aceite do convite", "#900") :
            array("Autenticado pelo facebook", "#009");

        $options['facebook'] = array(
            'onclick' => "return false",
            'link' => false,
            'texto' => "Facebook",
            'iconHtml' => "<span class='fa fa-facebook-square' style='color: {$_tmp[1]}' title='{$_tmp[0]}'></span>"
        );

        if ($usuarObj->checkPerms('USER_AUDITORIA')) {
            $options['auditoria'] = array(
                'onclick' => false,
                'link' => "usuario_audit.php?id={$row['id']}",
                'texto' => "Auditoria",
                'iconHtml' => "Auditoria"
            );
        }

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

dAL::layTop(array('bodyTitle' => "Gerenciar usuários" . ($showTrashBin ? " excluídos" : ""), 'saveGoBack' => true));
dAL::goBack(false, "<a href='usuario_edit.php'>Cadastrar novo usuário</a>");

dHelper2::includePage('inc/dAL.SearchBox2.inc.php', array(
        'className' => 'dUsuario',       // Classe a ser buscada
        'colTitles' => 'Cód, Cadastro, Últ. Login,     Username, Status, Disabled, Deleted',
        'colFields' => '!id, data_cadastro, data_ult_login, username, *status,!disabled,!deleted',
        'inlineEdit' => false,       // Permite QuickEdit (dEip) nos seguintes campos
        'allowSetOrdem' => true,        // Detecta a coluna 'ordem' e permite definir a posição dos registros.
        'allowSearch' => true,        // FALSE, TRUE ou 'coluna1,coluna2...'
        'allowSorting' => true,        // Permite que o usuário ordene os resultados pelas colunas
        'ajaxDelete' => true,        // Libera o botão 'x', que exclui via AJAX o item em questão
        'optionsModel' => 'icons',     // Haverá botões na direita? Qual modelo? 'none', 'options' ou 'icons'
        'dblClickEdit' => true,        // Edita se houver um duplo-clique
        'tableWidth' => '100%',
        'resPerPage' => 250,
        'callBacks' => $callBacks,
    )
);

if ($usuarObj->checkPerms('USER_TRASHBIN')) {
    echo "<br />";
    echo ($showTrashBin) ?
        "<a href='{$_SERVER['PHP_SELF']}'><img src='images/icons/trash.gif' border='0'> Clique aqui para <b>sair</b> da lixeira.</a>" :
        "<a href='{$_SERVER['PHP_SELF']}?trashBin=yes'><img src='images/icons/trash.gif' border='0'> Clique aqui para <b>ver</b> a lixeira.</a>";
}

dAL::layBottom();

