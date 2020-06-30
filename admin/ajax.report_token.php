<?php
require_once "config.php";

$usuarObj = dUsuario::isLogged() or die("Can't proceed.");
$tokenObj = false; // Destaque neste objeto:

$currentQS = isset($includeContext) ?
    $_SERVER['QUERY_STRING'] :
    $_POST['QUERY_STRING'];

$currentTable = isset($includeContext) ?
    @$_GET['t'] :
    @$_POST['currTable'];

if (@$_POST['action'] == 'save') {
    $titulo = $_POST['title'];
    $table = $_POST['table'];
    $uri = $_POST['url'];

    $tokenObj = new cToken;
    $tokenObj
        ->v('usuar_id', $usuarObj->v('id'))
        ->v('dbtable', $table)
        ->v('token', md5(microtime(true)))
        ->v('data_create', date('d/m/Y H:i:s'))
        ->v('titulo', $titulo)
        ->v('query_string', $uri ? $uri : "#")
        ->save();
}
if (@$_POST['action'] == 'remove') {
    $tokenObj = cToken::load($_POST['tokenId']);
    $tokenObj->delete();
    $tokenObj = false;
}

$showAddNew = true;
?>
<? if (isset($includeContext)): ?>
    <style> #tokenList * {
            box-sizing: border-box
        } </style>
    <div id="tokenList">
        <? endif ?>

        <ul style="margin-top: 0">
            <? foreach (cToken::multiLoad("where dbtable='{$currentTable}' order by titulo") as $_tokenObj):
                $isCurrent = (rtrim($_tokenObj->v('query_string'), '#') == $currentQS);
                $searchUrl = "db_reports.php?" . rtrim($_tokenObj->v('query_string'), '#');
                $tokenUrl = (dSystem::getGlobal('baseUrlSSL') ? dSystem::getGlobal('baseUrlSSL') : dSystem::getGlobal('baseUrl')) .
                    "admin/db_reports.php?token={$_tokenObj->v('token')}";

                if ($isCurrent) {
                    $showAddNew = false;
                }
                ?>
                <li>
                    <? if ($isCurrent): ?>
                    <b>
                        <? endif ?>
                        <a href="<?= $searchUrl ?>" <?= $isCurrent ? "style='color: #00F'" : "" ?>><?= $_tokenObj->v('titulo') ?></a>
                        <? if ($isCurrent): ?>
                    </b>
                <? endif ?>
                    <small>
                        <a href="#" onclick="deleteToken(<?= $_tokenObj->v('id') ?>); return false;">(excluir)</a> |
                        <a href="#" onclick="$(this).next().slideToggle(); return false">(ver token)</a>
                        <div style="display: none">
                            <?= dInput2::input("style='white-space: nowrap; padding: 4px 8px; width: 100%'",
                                $tokenUrl); ?>
                        </div>
                    </small>
                </li>
            <? endforeach; ?>

            <? if ($showAddNew): ?>
                <a href="#" onclick="addToken(); return false"
                   style='background: #EEE; display: inline-block; padding: 8px; border-radius: 4px; border: 0; color: #00F; text-decoration: none; margin-top: 12px'>
                    <i class='fa fa-floppy-o'></i> Salvar busca atual para uso posterior - ou uso no Excel via Token
                </a>
            <? endif ?>
        </ul>

        <? if (isset($includeContext)): ?>
    </div>
<? endif ?>

<? if (isset($includeContext)): ?>
    <script>
        var table = "<?=@$_GET['t']?>";
        var currQs = "<?=$currentQS?>";

        function addToken() {
            var title = prompt("Informe um nome/título para esta busca/token", "");
            $.post("ajax.report_token.php", {
                action: 'save',
                table: table,
                url: currQs,
                title: title,
                QUERY_STRING: "<?=$currentQS?>",
                currTable: "<?=$currentTable?>",
            }, function (ret) {
                $("#tokenList").html(ret);
            });
            return false;
        }

        function deleteToken(id) {
            if (!confirm("Se você excluir um token, planilhas do Excel que dependem dele não poderão mais ser atualizadas.\n\nContinuar?")) {
                return false;
            }

            $.post("ajax.report_token.php", {
                action: 'remove',
                tokenId: id,
                QUERY_STRING: "<?=$currentQS?>",
                currTipo: "<?=$currentTable?>",
            }, function (ret) {
                $("#tokenList").html(ret);
            });
        }

        function openXlsHelp() {
            $("#excelTutorial a").first().click();
            return false;
        }

        $(function () {
            $("#excelTutorial a").fancybox();
        });
    </script>
    <div id="excelTutorial" style="display: none">
        <a href="images/tutorial-token-1.png" rel='xlstutorial' title="1º Passo: Copie a URL final do Token">Tutorial
            1</a>
        <a href="images/tutorial-token-2.png" rel='xlstutorial'
           title="2º Passo: No Excel, acesse a aba Dados - Obter Dados - Da Web, e insira a URL do  Token">Tutorial
            2</a>
        <a href="images/tutorial-token-3.png" rel='xlstutorial'
           title="3º Passo: Confirme a requisição Anônima no Excel">Tutorial 3</a>
        <a href="images/tutorial-token-4.png" rel='xlstutorial'
           title="4º Passo: Selecione o item 'Resultados do Seu Token' e clique em Carregar">Tutorial 4</a>
        <a href="images/tutorial-token-5.png" rel='xlstutorial'
           title="A qualquer momento, clique em 'Atualizar Tudo' para sincronizar novamente.">Tutorial 5</a>
    </div>
<? endif ?>