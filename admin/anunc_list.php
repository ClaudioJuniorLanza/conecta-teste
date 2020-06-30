<?php
require_once "config.php";
require_once "template.php";

dAL::layTop(array('bodyTitle' => "Gerenciar os anúncios", 'saveGoBack' => true));
$callBacks = array(
    'onPreSearchObj' => function (dDbSearch $s) {
        // Será chamado logo após $tmpS->setTable() e $tmpS->setFields().
        // Não precisa de retorno.
        // addFTable, addFField, addField, etc., devem vir aqui.
        $s->addField('codigo');

        $s->addFTable('c_usuarios', 'usuar_id', 'id');
        $s->addFField('c_usuarios', 'cliente,renasem', 'nome,renasem');

        $s->addFTable('c_ref_variedades', 'varie_id', 'id');
        $s->addFField('c_ref_variedades', 'cultura,variedade');

        $s->addFTable("(select id,anunc_id from c_propostas where status='Enviada' and  isnull(data_revisado)) as paadm",
            'id', 'anunc_id');
        $s->addFTable("(select id,anunc_id from c_propostas where status='Enviada' and !isnull(data_revisado)) as paanu",
            'id', 'anunc_id');
        $s->addFTable("(select id,anunc_id from c_propostas where status='Aceita'                            ) as paint",
            'id', 'anunc_id');
        $s->addFTable("(select id,anunc_id from c_propostas where status like 'Rejeitada%'                   ) as preje",
            'id', 'anunc_id');
        $s->addFTable("(select id,anunc_id from c_propostas where status like 'Negócio%'                     ) as pconc",
            'id', 'anunc_id');
        $s->addFTable('(select id,anunc_id from c_propostas where !isnull(status)) as poutr', 'id', 'anunc_id',
            "and poutr.id NOT IN(paadm.id, paanu.id, paint.id, preje.id, pconc.id)");

        $s->addRawSelect('propoAgAdmin', "count(paadm.anunc_id)");
        $s->addRawSelect('propoAgAnunc', "count(paanu.anunc_id)");
        $s->addRawSelect('propoAgInter', "count(paint.anunc_id)");
        $s->addRawSelect('propoRejeit', "count(preje.anunc_id)");
        $s->addRawSelect('propoConclu', "count(pconc.anunc_id)");
        $s->addRawSelect('propoOutros', "count(poutr.anunc_id)");
        $s->addRawSelect('propoTotal',
            "(count(paadm.anunc_id) + count(paanu.anunc_id) + count(paint.anunc_id) + count(preje.anunc_id) + count(pconc.anunc_id) + count(poutr.anunc_id))");
        $s->setGroupBy('c_anuncios.id');

        if (@$_GET['status'] == 'Em Análise') {
            $s->setOrderBy("data_anuncio desc");
        } else {
            $s->setOrderBy("propoAgAdmin desc,propoAgInter desc,propoTotal desc,data_anuncio desc");
        }
    },
    'onPostSearchObj' => function (dDbSearch $s) {
        // Será chamado logo antes de $tmpS->perform();
        // Não precisa de retorno.
        // addModifier, addWhere, setOrdem, setGroupBy, etc., devem vir aqui.
        $s->addModifier('cliente', 'limit=30');
        $s->addModifier('variedade', 'limit=35');

        if (@$_GET['status'] == 'Concluído') {
            $s->addWhere("status IN ('Concluído', 'Cancelado')");
        } elseif (@$_GET['status']) {
            $s->addWhere("status", $_GET['status']);
        } elseif (@$_GET['codigo']) {
            $s->addWhere('codigo', $_GET['codigo']);
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
        if ($column == 'cliente') {
            return
                "<b>" . $row['renasem'] . "</b><br />" .
                "<small>" . ucfirst(mb_strtolower($row[$column])) . "</small>";
        }
        if ($column == 'negocio') {
            return
                "<b>{$row['negocio']}</b>: {$row['cultura']}<br />" .
                "<small><i>{$row['variedade']}</i></small>";
        }
        if ($column == 'data_anuncio') {
            return
                date('d/m/y H\hi\m', strtotime($row[$column])) . "<br />" .
                "<small><i>Cód. #{$row['codigo']}</i></small>";
        }
        if ($column == 'status') {
            if ($row[$column] == 'Em Análise') {
                return "<span style='color: #F00'>Ag. Revisão</span>";
            }
            if ($row[$column] == 'Ag. Propostas') {
                return "Publicado";
            }
            return $row[$column];
        }
        if ($column == 'propoTotal') {
            // $s->addRawSelect('propoTotal',   "count(pt.anunc_id)");
            // $s->addRawSelect('propoAgAdmin', "count(paadm.anunc_id)");
            // $s->addRawSelect('propoAgAnunc', "count(paanu.anunc_id)");
            // $s->addRawSelect('propoAgInter', "count(paint.anunc_id)");
            if (!$row['propoTotal']) {
                return "<div align='center'>---</div>";
            }

            $ret = "<div style='display: flex; flex-direction: column; font-size: small; border: 1px solid #999'>";
            $nOutros = $row['propoTotal'];
            if ($row['propoAgAdmin']) {
                $ret .= "<div align='center' style='padding: 2px 4px; background: #F99'>Ag. Admin: <b>{$row['propoAgAdmin']}</b></div>";
                $nOutros -= $row['propoAgAdmin'];
            }
            if ($row['propoAgAnunc']) {
                $ret .= "<div align='center' style='padding: 2px 4px; background: #CFC'>Ag. Aceite: <b>{$row['propoAgAnunc']}</b></div>";
                $nOutros -= $row['propoAgAnunc'];
            }
            if ($row['propoAgInter']) {
                $ret .= "<div align='center' style='padding: 2px 4px; background: #9bffd8'>Intermediação: <b>{$row['propoAgInter']}</b></div>";
                $nOutros -= $row['propoAgInter'];
            }
            if ($row['propoRejeit']) {
                $ret .= "<div align='center' style='padding: 2px 4px; background: #CCC'>Rejeitada: <b>{$row['propoRejeit']}</b></div>";
                $nOutros -= $row['propoRejeit'];
            }
            if ($row['propoConclu']) {
                $ret .= "<div align='center' style='padding: 2px 4px; background: #CCC'>Concluídas: <b>{$row['propoConclu']}</b></div>";
                $nOutros -= $row['propoConclu'];
            }
            if ($nOutros) {
                $ret .= "<div align='center' style='font-size: small; background: #CCC'>Outras: <b>{$nOutros}</b></div>";
            }
            $ret .= "</div>";


            return $ret;
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

echo "<form method='get'>";
echo "  Buscar por código: " . dInput2::input("name='codigo'", @$_GET['codigo']);
echo "  <input type='submit' value='Buscar' />";
echo "</form>";
echo "<br />";

dHelper2::includePage('inc/dAL.SearchBox2.inc.php', array(
        'className' => 'cAnuncio',
        'colTitles' => 'Cód, Data e Código, Negócio, Cliente, Status, Propostas',
        'colFields' => '!id, data_anuncio,  negocio, cliente, status, propoTotal',
        'inlineEdit' => '',
        // Permite QuickEdit (dEip) nos seguintes campos
        'allowSetOrdem' => true,
        // Detecta a coluna 'ordem' e permite definir a posição dos registros.
        'allowSearch' => false,
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
        'resPerPage' => false,
        'callBacks' => $callBacks,
        'usePerformObj' => false,
        // Informe a classe para usear performObj. Nos callbacks, $row receberá um objeto.
    )
);

dAL::layBottom();

