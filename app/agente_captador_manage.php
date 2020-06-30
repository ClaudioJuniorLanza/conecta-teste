<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if (!$usuarObj->v('agente_captador')) {
    // Sem permissões.
    dHelper2::redirectTo("agente_central.php");
    die;
}

$dropClientes = $db->singleIndexV("select id,nome from c_usuarios where agente_id='{$usuarObj->v('id')}' order by nome");
$dropStatus = ['Ag. Propostas', 'Concluído', 'Cancelado'];

$filterCliente = @$_GET['clien_id'];
$filterStatus = @$_GET['status'];
if (!$filterCliente || !array_key_exists($filterCliente, $dropClientes)) {
    // Tentou filtrar por um cliente inexistente.
    $filterCliente = false;
}
if (!$filterStatus || !in_array($filterStatus, $dropStatus)) {
    // Tentou filtrar por um status não permitido.
    $filterStatus = "Ag. Propostas";
}

$lista = call_user_func(function () use ($usuarObj, $filterCliente, $filterStatus) {
    $s = new dDbSearch3('cAnuncio', ['loadExt' => 'usuarObj,varieObj']);
    $filterCliente ?
        $s->addWhere("usuar_id = '" . intval($filterCliente) . "'") :
        $s->addWhere("usuar_id IN (select id from c_usuarios where agente_id='{$usuarObj->v('id')}')");

    $s->addWhere("status = '" . addslashes($filterStatus) . "'");
    $s->addWhere("negocio = 'Venda'");
    $s->setOrderBy('data_anuncio');
    return $s->perform();
});

layCima("Enviar Anúncios", [
    'menuSel' => 'agente_captador',
    'extraCss' => 'agente'
]); ?>
    <div class="barTop searchBar">
        <b>Filtrar por:</b>
        <span>
			Cliente: <?= dInput2::select("name='clien_id'", $dropClientes, $filterCliente, false, "Todos"); ?>
		</span>
        <span>
			Status: <?= dInput2::select("name='status'", $dropStatus, $filterStatus); ?>
		</span>
    </div>
    <div class="agente">
        <h1>
            Anúncios: <?= $filterStatus ?>
            <?= $filterCliente ? "<small>({$dropClientes[$filterCliente]})</small>" : "" ?>
        </h1>
        <table width='100%' style='border-collapse: collapse;' border='1' cellpadding='4' class='smallTable'>
            <thead>
            <tr>
                <? if (!$filterCliente): ?>
                    <th><b>Cliente</b></th>
                <? endif ?>
                <th><b>Código</b></th>
                <th><b><i class='fa fa-calendar'></i> Cadastro</b></th>
                <th><b><i class='fa fa-calendar'></i> Expira</b></th>
                <th><b>Variedade</b></th>
                <th><b>Local de Origem</b></th>
                <th><b>Embalagem</b></th>
                <th><b>Quantidade</b></th>
                <th><b>Preço</b></th>
                <th>Ações...</th>
            </tr>
            </thead>
            <tbody>
            <? foreach ($lista as $anuncObj):
                $isPerKg = ($anuncObj->v('valor_por_kg'));
                ?>
                <tr rel="<?= $anuncObj->v('id') ?>" data-codigo="<?= $anuncObj->v('codigo') ?>"
                    data-clien_id="<?= $anuncObj->v('usuar_id') ?>">
                    <? if (!$filterCliente): ?>
                        <td><?= $anuncObj->v('usuarObj')->v('nome') ?></td>
                    <? endif ?>
                    <td><?= $anuncObj->v('codigo') ?></td>
                    <td><?= substr($anuncObj->v('data_anuncio'), 0, 16) ?></td>
                    <td><?= $anuncObj->v('autoexpire_data') ? substr($anuncObj->v('autoexpire_data'), 0,
                            10) : "<i>Nunca</i>" ?></td>
                    <td><?= $anuncObj->v('varieObj')->v('variedade') ?></td>
                    <td><?php
                        echo ($anuncObj->v('cidade') && $anuncObj->v('uf')) ?
                            "{$anuncObj->v('cidade')}, {$anuncObj->v('uf')}" :
                            "{$anuncObj->v('regiao')}";
                        ?></td>
                    <td><?= $anuncObj->v('embalagem') ?></td>
                    <td><?= dHelper2::moeda($anuncObj->v('quantidade'), 0) . ($isPerKg ? "kg" : "un.") ?></td>
                    <td><?= dHelper2::moeda($anuncObj->v($isPerKg ? 'valor_por_kg' : 'valor_por_embalagem'),
                            2) . ($isPerKg ? "/kg" : "/unidade") ?></td>
                    <td>
                        <? if ($anuncObj->v('status') != 'Cancelado'): ?>
                            <a href="#" onclick="return false" class='roundBotao menor excluir'>Excluir</a>
                        <? endif ?>
                        <a href="agente_cliente_act_as.php?id=<?= $anuncObj->v('usuar_id') ?>&gotoAnuncio=<?= $anuncObj->v('codigo') ?>"
                           target='_blank' class='roundBotao menor manage'>Gerenciar</a>
                    </td>
                </tr>
            <? endforeach; ?>
            </tbody>
        </table>
    </div>
<? if (dSystem::getGlobal('localHosted')): ?>
    <div id='debug'></div>
<? endif ?>
    <script>
        $(function () {
            // Vamos fazer funcionar a barTop.
            $(".barTop select").on('change', function () {
                var changeKey = $(this).attr('name');
                var changeTo = $(this).val();
                dHelper2.changeUrl(changeKey, changeTo);
            });

            // Vamos fazer funcionarem as ações:
            var nDeleted = 0;
            $(".smallTable a.excluir").click(function () {
                if (!nDeleted) {
                    if (!confirm("Tem certeza que deseja cancelar este anúncio?\nVocê não receberá mais essa mensagem nos próximos cliques.")) {
                        return false;
                    }
                }
                nDeleted++;

                var jqoThis = $(this);
                var jqoTr = jqoThis.closest('tr');
                var anunc_id = jqoTr.attr('rel');

                jqoThis.html("<i class='fa fa-spinner fa-spin'></i> Excluindo");
                $.post("agente_captador_manage.ajax.php", {action: 'delete', anunc_id: anunc_id}, function (ret) {
                    $("#debug").html(ret);
                    if (ret != "OK") {
                        alert(ret);
                        jqoThis.html("Excluir");
                        return false;
                    }

                    var _isDone = false;
                    jqoThis.closest('tr').find("td").slideDown(function () {
                        if (_isDone) {
                            return;
                        }
                        _isDone = true;
                        jqoTr.remove();
                    });
                });
                return false;
            });
        });
    </script>
<?php
layBaixo();

