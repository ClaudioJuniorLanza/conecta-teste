<?php
require_once "config.php";
require_once "template.php";

$type = @$_GET['type'];
if (!$type) {
    $type = 'notAgentes';
}
$isAgente = (substr($type, 0, 2) == 'ag');

dAL::layTop(array('bodyTitle' => "Gerenciar os usuários", 'saveGoBack' => true));
dAL::goBack(false, "<a href='cliente_edit.php'>Cadastrar novo usuário</a>");

$callBacks = array(
    'onPreSearchObj' => function (dDbSearch $s) {
        // Será chamado logo após $tmpS->setTable() e $tmpS->setFields().
        // Não precisa de retorno.
        // addFTable, addFField, addField, etc., devem vir aqui.
        $s->addField('agente_pending,agente_vendedor,agente_captador');
        $s->addField('disabled');

        $s->addFTable('c_usuarios as agentes', 'c_usuarios.agente_id');
        $s->addFField('agentes', 'agente', 'nome');
    },
    'onPostSearchObj' => function (dDbSearch $s) use ($type) {
        // Será chamado logo antes de $tmpS->perform();
        // Não precisa de retorno.
        // addModifier, addWhere, setOrdem, setGroupBy, etc., devem vir aqui.

        $s->addModifier('data_cadastro', 'date=d/m/y');
        $s->addModifier('data_lastlogin', 'date=d/m/y H:i');

        if ($type == 'agPendentes') {
            $s->addWhere("'1' = c_usuarios.agente_pending");
        } else {
            if ($type == 'agAprovados') {
                $s->addWhere("'0' = c_usuarios.agente_pending and '1' IN (c_usuarios.agente_pending, c_usuarios.agente_vendedor, c_usuarios.agente_captador)");
            } else {
                $s->addWhere("(!isnull(c_usuarios.renasem) or (c_usuarios.agente_pending='0' and c_usuarios.agente_vendedor='0' and c_usuarios.agente_captador='0'))");
            }
        }
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
        if ($column == 'permissoes') {
            $perms = [];
            if ($row['agente_vendedor']) {
                $perms[] = "<span style='color: #0B0'>Vendedor</span>";
            }
            if ($row['agente_captador']) {
                $perms[] = "<span style='color: #B00'>Captador</span>";
            }
            return $perms ? implode(" + ", $perms) : "Não definido";
        }
        if ($column == 'agente_pending') {
            if ($row['disabled']) {
                return "<span style='color: #777'>Desativado</span>";
            }

            return $row[$column] ?
                "<b style='color: orange'>Pendente</b>" :
                "<b style='color: darkgreen'>Aprovado</b>";
        }
        return $row[$column];
    },
    'showOptions' => function ($row, $options) {
        // Modifica as opções e/ou os ícones das opções
        // $options[edit]   = Array('onclick'=>'', 'link'=>'', 'texto'=>'', 'iconHtml'=>''))
        // $options[delete] = Array('onclick'=>'', 'link'=>'', 'texto'=>'', 'iconHtml'=>''))
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

if ($isAgente) {
    $colTitles = 'Cód, Data cadastro, Últ. Login,     UF, Cidade,Nome, Telefone, E-mail, Permissões,  Status,         Disabled';
    $colFields = '!id, data_cadastro,!data_lastlogin, uf, cidade, nome, fone1,    email,  *permissoes, agente_pending,!disabled';

    if ($type == 'agAprovados') {
        $colFields = str_replace("!data_lastlogin", " data_lastlogin", $colFields);
    }
} else {
    $colTitles = 'Cód, Data cadastro, Últ. Login,     E-mail, Agente, Nome, Responsavel,      CPF/CNPJ, RG/IE, Atividade, Cep, Uf, Cidade, Bairro, Endereco, Numero, Complemento, Referencia, Telefone, Telefone, E-mail, Senha, Disabled';
    $colFields = '!id, data_cadastro, data_lastlogin, email, agente, nome, responsavel_nome,!cpf_cnpj,!rg_ie,!atividade,!cep, uf, cidade,!bairro,!endereco,!numero,!complemento,!referencia,fone1,!fone2,!email,!senha,!disabled';
}


dHelper2::includePage('inc/dAL.SearchBox2.inc.php', array(
        'className' => 'cUsuario',
        'colTitles' => $colTitles,
        'colFields' => $colFields,
        'inlineEdit' => 'nome_completo',
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

