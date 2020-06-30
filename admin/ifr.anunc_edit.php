<?php
require_once "config.php";

$usuarObj = dUsuario::isLoggedOrRedirect();
$propoObj = cProposta::load($_GET['propoId'], 'usuarObj;anuncObj;anuncObj.usuarObj') or die("Proposta não encontrada.");
$anuncObj = $propoObj->v('anuncObj');

$succMsg = array();
$errMsg = array();

if (@$_GET['delAnexo']) {
    $anexoObj = dAnexo::load($_GET['delAnexo']);
    if ($anexoObj) {
        $anexoObj->delete();
        $succMsg[] = "Anexo removido.";
    }
}
if (@$_FILES['novoAnexo']) {
    foreach ($_FILES['novoAnexo']['tmp_name'] as $anexoIdx => $tmpName) {
        if (!$tmpName) {
            continue;
        }

        $origName = $_FILES['novoAnexo']['name'][$anexoIdx];
        $anexoObj = new dAnexo;
        $anexoObj->v('rel', 'cProposta');
        $anexoObj->v('rel_id', $propoObj->v('id'));
        if ($anexoObj->save()) {
            if ($anexoObj->setFile($tmpName, $origName)) {
                $succMsg[] = "Arquivo {$origName} recebido com sucesso!";
            } else {
                $errMsg[] = "Arquivo {$origName} não pode ser salvo. Contate o suporte.";
                $anexoObj->delete();
            }
        } else {
            $errMsg[] = "Impossível salvar o registro do anexo {$origName}. Contate o suporte.";
        }
    }
}

$anexos = dAnexo::multiLoad("where rel='cProposta' and rel_id='{$propoObj->v('id')}'");

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Gerenciar anexos</title>
    <?= dResLoader::writeInclude('jquery', 'jquery-1.8.3', '../'); ?>
    <?= dResLoader::writeRenderBlock('css', 'https://fonts.googleapis.com/css?family=Roboto:400,400i,700'); ?>
    <? if (dSystem::getGlobal('localHosted')): ?>
        <?= dResLoader::writeInclude('jquery', 'jquery-dEasyRefresh', '../'); ?>
        <script> $(function () {
                dEasyRefresh.relPath = '../';
            }); </script>
    <? endif ?>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: Roboto;
            font-size: 16px;
        }
    </style>
</head>

<body>
<div style='font-size: 32px; font-weight: bold;'>Anexos do anúncio #<?= $anuncObj->v('codigo') ?></div>
<hr size='1'/>
<table>
    <tr>
        <td>Anunciante:</td>
        <td><a href="cliente_edit.php?id=<?= $anuncObj->v('usuarObj')->v('id') ?>"
               target='_blank'><?= $anuncObj->v('usuarObj')->v('nome'); ?></a></td>
    </tr>
    <tr>
        <td>Proponente:</td>
        <td><a href="cliente_edit.php?id=<?= $propoObj->v('usuarObj')->v('id') ?>"
               target='_blank'><?= $propoObj->v('usuarObj')->v('nome'); ?></a></td>
    </tr>
    <tr>
        <td>Situação do anúncio:</td>
        <td><?= $anuncObj->v('status') ?></td>
    </tr>
    <tr>
        <td>Situação da proposta:</td>
        <td><?= $propoObj->v('status') ?></td>
    </tr>
</table>
<hr size='1'/>
<? if ($errMsg || $succMsg): ?>
    <? if ($errMsg): ?>
        <div style="color: #F00; background: #FCC; padding: 8px; margin-bottom: 12px">
            <?= implode("<br />", $errMsg); ?>
        </div>
    <? endif ?>
    <? if ($succMsg): ?>
        <div style="color: #030; background: #CFC; padding: 8px; margin-bottom: 12px">
            <?= implode("<br />", $succMsg); ?>
        </div>
    <? endif ?>
<? endif ?>
<? if (!$anexos): ?>
    <div style='color: #666; font-style: italic;'>Não há nenhum anexo aqui..</div>
<? else: ?>
    <div style="line-height: 1">
        <b>Anexos:</b><br/>
        <? foreach ($anexos as $anexoObj) {
            echo "<li style='margin-top: 12px'>";
            echo $anexoObj->embedFile("../", array('forceDownload' => true));
            echo " &nbsp; &nbsp; &nbsp; ";
            echo "<a href='ifr.anunc_edit.php?propoId={$propoObj->v('id')}&delAnexo={$anexoObj->v('id')}' onclick=\"return confirm('Deseja excluir este anexo? Essa é uma operação irreversível.');\" style='color: #F00; text-decoration: none; font-size: small'>Excluir</a><br />";
            echo "<small style='font-style: italic; color: #444'>Enviado em {$anexoObj->v('data_add')}</small><br />";
            echo "</li>";
        }
        ?>
    </div>
<? endif ?>
<br/>
<form method="post" enctype="multipart/form-data">
    <div style='padding: 8px; background: #EEE; border: 1px solid #AAA'>
        <b>Enviar novo(s) anexo(s):</b><br/>
        <input type='file' name='novoAnexo[]' multiple='multiple'
               style='border: 1px solid #CCC; padding: 6px; background: #FFF'/>
        <input type='submit' value='Enviar'
               style='background: #61C56F; border: 0; color: #FFF; font-size: 16px; padding: 8px'/><br/>
    </div>
</form>
</body>
</html>