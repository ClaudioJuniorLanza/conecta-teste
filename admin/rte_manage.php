<?php
require_once "config.php";
header("Content-Type: text/html; charset=utf8");

$type = @$_GET['type'];
$onlyImages = ($type != 'files');
$maxWidth = intval(@$_GET['maxWidth']) ? intval($_GET['maxWidth']) : dConfiguracao::getConfig('TEMPLATE/BODY_WIDTH');
$_RteConfig = array(
    'maxWidth' => $maxWidth,
    'maxHeight' => false,
    'allowResizer' => true,
    'allowAttachments' => !dConfiguracao::getConfig('TEMPLATE/RTE_ONLY_IMAGES'),
    'allowScripts' => false,
    'rtePath' => '-- to do --',
);
$_RteConfig['rtePath'] = ($onlyImages) ?
    "{$_BaseDir}/fotos/rte/" :
    "{$_BaseDir}/fotos/rte/files/";

if (!method_exists('dHelper2', 'removeAccents')) {
    dSystem::notifyAdmin('HIGH', "Erro na configuração RTE",
        "Função dHelper2::removeAccents() não foi encontrada.",
        true
    );
    die;
}
if (!is_writable($_RteConfig['rtePath'])) {
    dSystem::notifyAdmin('HIGH', "Erro na configuração RTE",
        "Sem permissão de escrita, ou pastas não existente:\r\n" .
        "{$_RteConfig['rtePath']}",
        true
    );
    die;
}

if(isset($_GET['upload'])): ?><!DOCTYPE html>
<html>
<head>
    <script type='text/javascript' src='../js/core/jquery-1.8.3.min.js'></script>
</head>

<body>
<script type='text/javascript'>
    <?php
    if (isset($_GET['delorig'])) {
        $fileName = dHelper2::removeAccents($_GET['delorig']);
        $fileName = preg_replace("/[^0-9a-zA-Z\.\(\)_]/", "", $fileName);
        $target = "{$_RteConfig['rtePath']}/{$fileName}";
        unlink($target);
        echo "alert('Imagem grande removida.');\r\n";
        # echo "history.go(-2);\r\n";
    } else {
        $handle = handleUpload($onlyImages);
        if ($handle) {
            echo "(function(){ \r\n";
            if ($_GET['upload'] == 1) {
                echo "	var p = window.parent;\r\n";
            } else {
                echo "	var p = window.parent.opener;\r\n";
            }
            echo "	var cke = p.CKEDITOR;\r\n";
            echo "	var CKEditorFuncNum = '{$_GET['CKEditorFuncNum']}';\r\n";
            if ($onlyImages && $handle['doResample']) {
                echo "	if(!confirm('Você enviou uma imagem grande, que foi redimensionada para caber na tela.\\nDeseja exibir a imagem em tamanho real quando clicada?')){\r\n";
                echo "		$.post('{$_SERVER['PHP_SELF']}?upload=1&delorig=" . basename($handle['relPathO']) . "', '', function(ret){});\r\n";
                echo "	}\r\n";
                echo "	else{\r\n";
                echo "		cke.dialog.getCurrent().setValueOf('info',     'txtBorder',   '0');\r\n";
                echo "		cke.dialog.getCurrent().setValueOf('Link',     'txtUrl',      '../fotos/rte/{$handle['relPathO']}');\r\n";
                echo "		cke.dialog.getCurrent().setValueOf('Link',     'cmbTarget',   '_blank');\r\n";
                echo "		cke.dialog.getCurrent().setValueOf('advanced', 'txtGenClass', 'img_lightbox');\r\n";
                echo "	}\r\n";
            }
            if ($onlyImages) {
                echo "	cke.dialog.getCurrent().setValueOf('info', 'txtAlt', '" . addslashes($handle['strFileName']) . "');\r\n";
                echo "	cke.tools.callFunction( CKEditorFuncNum, '../fotos/rte/{$handle['relPath']}', '');\r\n";
            } else {
                echo "	cke.tools.callFunction( CKEditorFuncNum, '../fotos/rte/files/{$handle['relPath']}', '');\r\n";
            }
            echo "	self.close();\r\n";
            echo "})()\r\n";
        } else {
            echo "location.href='rte_manage.php?" . time() . "'";
        }
    }
    ?>
</script>
</body>
</html>
<? else:
$allFiles = array();

if (isset($_GET['delete'])) {
    $fileName = dHelper2::removeAccents($_GET['delete']);
    $fileName = preg_replace("/[^0-9a-zA-Z\.\(\)_]/", "", $fileName);
    $target = "{$_RteConfig['rtePath']}/{$fileName}";
    $targetO = "{$_RteConfig['rtePath']}/full_{$fileName}";
    @unlink($target);
    @unlink($targetO);
    echo "<script> alert('Arquivo excluído.'); </script>\r\n";
}

$d = dir($_RteConfig['rtePath']);
while ($e = $d->read()) {
    if ($e == '.' || $e == '..') {
        continue;
    }

    if ($e == 'index.php' || is_dir($e)) {
        continue;
    }

    $isFull = (substr($e, 0, 5) == 'full_');
    $fullFile = $isFull ? $e : "full_{$e}";
    $realFile = $isFull ? substr($e, 5) : $e;

    if (is_dir("{$_RteConfig['rtePath']}/{$realFile}")) {
        continue;
    }

    if (isset($allFiles[$realFile])) {
        // Já foi definido
        continue;
    }

    if ($isFull && !file_exists("{$_RteConfig['rtePath']}/{$realFile}")) {
        // É um "Full"
        $isFull = false;
        $fullFile = false;
        $realFile = $e;
    } elseif (!file_exists("{$_RteConfig['rtePath']}/{$fullFile}")) {
        // É um arquivo pequeno, e não possui o FULL.
        $fullFile = false;
    }

    $allFiles[$realFile] = array(
        'full' => $fullFile,
        'real' => $realFile,
    );
}
?><!DOCTYPE html>
<html>
<head>
    <title>Navegando entre <?= $onlyImages ? "as imagens" : "os arquivos" ?> existentes no servidor...</title>
    <script type='text/javascript' src='../js/core/jquery-1.8.3.min.js'></script>
    <style>
        html, body {
            border: 0;
            padding: 0px;
            margin: 0px;
            height: 100%;
            font: 12px Arial
        }

        body, td {
            font: 12px Arial
        }

        h1 {
            margin: 0
        }
    </style>
</head>

<body>

<table width="100%" height="100%" cellpadding="5" cellspacing="0" border="0">
    <tr>
        <td>
            <table cellspacing='0' cellpadding='0' width='100%' border='0'>
                <tr>
                    <td><h1>Arquivos disponíveis no servidor...</h1></td>
                    <td align='right'><img src="images/logo_full.gif" border="0"/></td>
                </tr>
            </table>
            <div style="margin-top: 5px; border-top: 1px solid #DDD; padding-top: 5px; margin-bottom: 5px">
                <div style="float: left; display: block; width: 230px">
                    <b>Buscar arquivo:</b><br/>
                    <input type='text' id='fileFilter' size='30'/><br/>
                </div>
                <div style="float: left; display: block; border-left: 1px solid #CCC; padding-left: 20px">
                    <form method="post" enctype="multipart/form-data"
                          action="rte_manage.php?type=<?= $type ?>&upload=2&CKEditorFuncNum=<?= @$_GET['CKEditorFuncNum'] ?>&maxWidth=<?= $maxWidth ?>">
                        <b>Enviar novo arquivo:</b><br/>
                        <input type='file' name='upload'/>
                        <input type='submit' value='Enviar...'/>
                    </form>
                </div>
                <br style="clear: both"/>
            </div>
        </td>
    </tr>
    <tr height="100%">
        <td>
            <table cellspacing='0' cellpadding='0' width='100%' height='100%'>
                <tr>
                    <td valign='top' id='listaCell' style='padding-right: 5px'>
                        <div style="height: 100%; background: #FFF; width: 100%; overflow-Y: auto" id="listaDiv">
                            <? if ($allFiles): ?>
                                <table width='100%' bgcolor='#EEEEEE' cellspacing="0" cellpadding='5' id="listaFiles">
                                    <tr bgcolor="#DDDDDD">
                                        <td><b>Arquivo:</b></td>
                                        <td><b>Arquivo maior:</b></td>
                                        <td><b>Opções:</b></td>
                                    </tr>
                                    <? foreach ($allFiles as $item): ?>
                                        <tr>
                                            <td><a href="../fotos/rte/<?= $item['real'] ?>"
                                                   target='_blank'><?= $item['real'] ?></a></td>
                                            <td><?= $item['full'] ? "<b><a href='../fotos/rte/{$item['full']}' target='_blank'>Sim</a></b>" : "Não" ?></td>
                                            <td>
                                                <a href="#"
                                                   onclick="useThis('<?= $item['real'] ?>', '<?= $item['full'] ?>')">Utilizar</a>
                                                |
                                                <a href="<?php
                                                echo
                                                    'rte_manage.php?browse=1' .
                                                    '&type=' . $type .
                                                    '&delete=' . $item['real'] .
                                                    '&CKEditor=' . (@$_GET['CKEditor']) .
                                                    '&CKEditorFuncNum=' . (@$_GET['CKEditorFuncNum']) .
                                                    '&maxWidth=' . $maxWidth .
                                                    '&langCode=' . (@$_GET['langCode']);

                                                ?>"
                                                   onclick="return confirm('Tem certeza? Não recomendamos a exclusão de arquivos.\nSó exclua arquivos se você tiver CERTEZA de que eles nã estão sendo utilizados.\nNo caso de dúvidas, acione o suporte IMAGINACOM.\n\nConfirmar a exclusão?');">Excluir</a>
                                            </td>
                                        </tr>
                                    <? endforeach ?>
                                </table>
                            <? else: ?>
                                <div style="font: 14px Arial; text-align: center; margin-top: 50px">
                                    <b>Nenhum arquivo disponível atualmente.</b><br/>
                                </div>
                            <? endif ?>
                        </div>
                    </td>
                    <td width="30%" align='center' valign='center' style='border: 1px solid #777'>
                        <div style="height: 100%; background: #FFF; width: 100%; overflow-Y: auto">
                            <table height="100%" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align='center' valign='center' id="prevDiv">
                                        <b>Preview</b><br/>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<script> var CKEditorFuncNum = '<?=@$_GET['CKEditorFuncNum'] ?>'; </script>
<script>
    $(function () {
        $("#listaFiles>tbody>tr").slice(1).each(function () {
            $(this).mouseover(function () {
                var lnk = $(this).find("td>a").attr('href');

                if (lnk.match(/\.(jpg|gif|png|bmp)$/i)) {
                    $("#prevDiv").html("<img src='" + lnk + "' width='100%' />");
                } else {
                    $("#prevCell>b").html("N??magem");
                }
                $(this).css('background', '#FFC');
            });
            $(this).mouseout(function () {
                $(this).css('background', '');
            });
        });
        $("#fileFilter").keyup(function () {
            var q = $(this).val().toLowerCase();
            $("#listaFiles>tbody>tr").slice(1).each(function () {
                var row = $(this);
                var fn = row.find("td").first().find("a").html().toLowerCase();
                if (fn.indexOf(q) != -1)
                    row.show();
                else
                    row.hide();
            });
        });
    });

    function useThis(real, full) {
        var cke = window.parent.opener.CKEDITOR;
        var dia = cke.dialog.getCurrent();

        <? if($onlyImages): ?>
        if (full && confirm('Deseja adicionar o link para a imagem ampliada também?')) {
            dia.setValueOf('Link', 'txtUrl', '../fotos/rte/' + full);
            dia.setValueOf('info', 'txtBorder', '0');
            dia.setValueOf('Link', 'cmbTarget', '_blank');
            dia.setValueOf('advanced', 'txtGenClass', 'img_lightbox');
        }
        dia.setValueOf('info', 'txtAlt', '');
        cke.tools.callFunction(CKEditorFuncNum, '../fotos/rte/' + real, '');
        <? else: ?>
        cke.tools.callFunction(CKEditorFuncNum, '../fotos/rte/files/' + real, '');
        <? endif ?>
        self.close();
    }
</script>
</body>
</html>
<? endif;


function handleUpload($onlyImages)
{
    // Retorna FALSE no caso de erros, e j� alerta o motivo.
    // Retorna relPath, relPathO, doResample, strFileName

    global $_RteConfig;

    $rtePath = $_RteConfig['rtePath'];
    $tmpName = @$_FILES['upload']['tmp_name'];
    $fileName = @$_FILES['upload']['name'];

    if (!$tmpName) {
        $errorCode = isset($_FILES['upload']['error']) ? isset($_FILES['upload']['error']) : false;
        if ($errorCode && $errorCode != 4) {
            echo "alert('Ocorreu um erro no seu upload. Código {$errorCode}.\\nVerifique o tamanho do arquivo e tente novamente.');\r\n";
        } else {
            echo "alert('Nenhum arquivo foi recebido, tente novamente.');\r\n";
        }

        return false;
    }

    if ($rtePath) {
        $rtePath = substr($rtePath, 1);
    }

    $fileExt = strtolower(preg_replace("/.*\.(.+?)/s", "\\1", $fileName));
    $isImage = in_array($fileExt, explode(",", "gif,jpg,png,bmp,jpeg"));
    $isScript = in_array($fileExt, explode(",", "php,cgi,php4,php5,php6,pl,sh"));
    if (!$_RteConfig['allowAttachments'] && !$isImage) {
        echo "alert('Por favor, envie um arquivo de imagem. Formatos aceitos são GIF, JPG, PNG, BMP.');\r\n";
        return false;
    }
    if (!$_RteConfig['allowScripts'] && $isScript) {
        echo "alert('Extensão não permitida. Renomeie e tente novamente.');\r\n";
        return false;
    }

    $StrfileName = $fileName;
    $fileName = dHelper2::removeAccents($fileName);
    $fileName = preg_replace("/[^0-9a-zA-Z\.\(\)_]/", "", $fileName);
    $target = "{$_RteConfig['rtePath']}/{$fileName}";
    $targetO = "{$_RteConfig['rtePath']}/full_{$fileName}";
    $tryes = 0;
    while (file_exists($target)) {
        $tryes++;
        $target = "{$_RteConfig['rtePath']}/{$tryes}_{$fileName}";
        $targetO = "{$_RteConfig['rtePath']}/full_{$tryes}_{$fileName}";
    }

    $doResample = ($_RteConfig['maxWidth'] || !$_RteConfig['maxHeight']);
    if ($isImage && $doResample && $_RteConfig['allowResizer']) {
        $tm = new dThumbMaker;
        $load = $tm->loadFile($tmpName);
        if ($load === true) {
            $width = $tm->getWidth();
            $height = $tm->getHeight();

            if (($_RteConfig['maxWidth'] && $width > $_RteConfig['maxWidth']) || ($_RteConfig['maxHeight'] && $height > $_RteConfig['maxHeight'])) {
                // É uma imagem grande.
                // Não vou movê-la, mas sim gravar a cópia grande, redimensioná-la e oferece a inserção do link.
                $doResample = true;
                $ok1 = $tm->build($targetO);

                $tm->resizeMaxSize($_RteConfig['maxWidth'], $_RteConfig['maxHeight']);
                $ok2 = $tm->build($target);

                if (!$ok1 || !$ok2) {
                    @unlink($targetO);
                    @unlink($target);

                    dSystem::notifyAdmin('HIGH', "[RTE Config] Impossível gravar imagem depois de redimensioná-la.",
                        "OK1 (Salvando foto original): " . ($ok1 ? 'Sucesso' : 'Falhou') . " (Salvando em $targetO)\r\n" .
                        "OK2 (Salvando foto reduzida): " . ($ok2 ? 'Sucesso' : 'Falhou') . " (Salvando em $target)\r\n" .
                        "Files:TmpName: {$_FILES['upload']['tmp_name']} (tmpName:  {$tmpName})\r\n" .
                        "Files:Name:    {$_FILES['upload']['name']}     (fileName: {$fileName})\r\n" .
                        "Target:        {$target} (RtePath: {$_RteConfig['rtePath']})\r\n"
                    );
                    return false;
                }
            } else {
                unset($tm);
                $doResample = false;
            }
        } else {
            echo "alert('A imagem não foi reconhecida como válida, e não foi aceita. Você só pode enviar imagens em JPG, PNG, GIF e BMP.\\nTente outra imagem, ou converta a imagem para JPG ou PNG.');\r\n";
            return false;
        }
    }
    if (!$isImage || !$doResample) {
        $ok = is_uploaded_file($tmpName) ?
            move_uploaded_file($tmpName, $target) :
            copy($tmpName, $target);

        if (!$ok) {
            dSystem::notifyAdmin('HIGH', "[RTE Config] Upload falhou em move_uploaded_file.",
                "Files:TmpName: {$_FILES['upload']['tmp_name']} (tmpName:  {$tmpName})\r\n" .
                "Files:Name:    {$_FILES['upload']['name']}     (fileName: {$fileName})\r\n" .
                "Target:        {$target} (RtePath: {$_RteConfig['rtePath']})\r\n",
                true
            );
            die;
        }
    }

    // Se chegou aqui, é sinal que deu tudo certo.
    // Se exister a variável $doResample, é porque a imagem era grande e foi redimensionada.
    $relPathO = substr($targetO, strlen($_RteConfig['rtePath']) + 1);
    $relPath = substr($target, strlen($_RteConfig['rtePath']) + 1);

    return array(
        'relPathO' => $relPathO,
        'relPath' => $relPath,
        'doResample' => $doResample,
        'strFileName' => preg_replace("/(.+)\..+?$/", "\\1", $StrfileName)
    );
}
