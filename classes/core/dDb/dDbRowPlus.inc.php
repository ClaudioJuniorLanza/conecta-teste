<?php
/**
 * $this->enableSetFoto(Array(
 * 'fno'=>true,
 * 'fn' =>Array(1280, 1280, 'resizeTouchInside'),
 * 'fnt'=>Array(450,  450,  'resizeTouchInside'),
 * ));
 *
 * $this->enableSetFile();
 *
 * $this->sortingSetRelated(Array());
 **/

class dDbRowPlus extends dDbRow
{
    // Fotos simples (até $this->getFotoLimit())
    private $setFoto = false;

    function enableSetFoto($fotoSize, $settings = array())
    {
        $settings += array(
            'fotoLimit' => 1
        );
        $this->setFoto = array(
            'fotoSize' => $fotoSize,
            'settings' => $settings
        );
    }

    function setFoto($filename, $n = 1, $ignoreType = array())
    {
        $_BaseDir = dSystem::getGlobal('baseDir');
        $id = $this->getPrimaryValue();
        $sizes = $this->getFotoSize(false, $n);
        if ($n > $this->getFotoLimit()) {
            $this->addError(false, "Apenas {$this->getFotoLimit()} foto(s) permitidas.");
            return false;
        }
        foreach ($sizes as $sizeKey => $size) {
            if (in_array($sizeKey, $ignoreType)) {
                unset($sizes[$sizeKey]);
            }
        }
        if (!sizeof($sizes)) {
            dSystem::notifyAdmin('HIGH', 'Sem tamanhos para trabalhar',
                "setFoto() foi chamado, mas no fim das contas não sobrou nenhum tamanho para gravar.",
                true
            );
            die;
        }

        $cl = get_class($this);
        $fn = array();
        foreach ($sizes as $sizeKey => $size) {
            // Quero obter o número 1 ou 101, 201, etc..
            $path = ((floor($this->getPrimaryValue() / 100) * 100 + 1) . '-' . (floor($this->getPrimaryValue() / 100) * 100 + 100));
            $sufix = (substr($sizeKey, 0, 2) == 'fn') ?
                substr($sizeKey, 2) :
                $sizeKey;

            if (!is_dir("{$_BaseDir}/fotos/{$path}")) {
                mkdir("{$_BaseDir}/fotos/{$path}");
                file_put_contents("{$_BaseDir}/fotos/{$path}/index.php",
                    file_get_contents("{$_BaseDir}/fotos/index.php"));
            }

            $fn[$sizeKey] = "{$_BaseDir}/fotos/{$path}/{$cl}-{$id}-{$n}{$sufix}.jpg";
            if (file_exists($fn[$sizeKey])) {
                unlink($fn[$sizeKey]);
            }
        }

        if ($filename) {
            $tm = false;
            foreach ($sizes as $sizeKey => $size) {
                if ($size === true) {
                    // Save original:
                    copy($filename, $fn[$sizeKey]);
                    continue;
                }
                if (!$tm) {
                    // Carrega a classe dThumbMaker.
                    $tm = new dThumbMaker;
                    $tm->setBgColor(255, 255, 255);
                    $load = $tm->loadFile($filename);

                    if ($load !== true) {
                        $this->addError(false, $load);
                        return false;
                    }

                    $tm->createBackup();
                } else {
                    $tm->restoreBackup();
                }

                if (is_callable($size[2])) {
                    // Se o parâmetro for um callback, chame-o.
                    // callback($thisObj, $tm, $width, $height)
                    call_user_func($size[2], $this, $tm, $size[0], $size[1]);
                } elseif (!is_numeric($size[2])) {
                    // Se o parâmetro for uma função específica no dThumbMaker, execute.
                    call_user_func(array($tm, $size[2]), $size[0], $size[1]);
                } else {
                    if ($size[2] === 5) {
                        $tm->resizeTouchOutside($size[0], $size[1]);
                    } elseif ($size[2] === 4) {
                        $tm->resizeTouchInside($size[0], $size[1]);
                    } elseif ($size[2] === 3) {
                        $tm->resizeMinSize($size[0], $size[1]);
                    } elseif ($size[2] === 2) {
                        $tm->resizeToFit($size[0], $size[1]);
                    } elseif ($size[2]) {
                        $tm->resizeExactSize($size[0], $size[1]);
                    } else {
                        $tm->resizeMaxSize($size[0], $size[1]);
                    }
                }
                $tm->build($fn[$sizeKey], 'jpg', 100);
            }
        }
        return true;
    }

    function getFoto($relativePath = '', $type = '', $n = 1)
    {
        // Recupera endereço direto da foto.
        //   relativePath: Adicionar prefixo
        //   type: fn, fnt, fno
        //   n:    Qual foto retornar
        //
        // Se não houver $type, retorna todos os types num array.
        // Se não houver $n, retorna a primeira foto.

        $ret = $this->getFotos($relativePath, true);
        return $type ?
            $ret[$n][$type] :
            $ret[$n];
    }

    function getFotos($relativePath = '', $getEmptyToo = false)
    {
        // Retorna um Array no seguinte formato:
        // [1] => Array('fno' => Original, 'fn'  => Full, 'fnt' => Miniatura)
        $_BaseDir = dSystem::getGlobal('baseDir');
        $ret = array();
        $cl = get_class($this);
        $id = $this->getPrimaryValue();

        if ($relativePath && substr($relativePath, -1) != '/') {
            $relativePath .= "/";
        }

        for ($n = 1; $n <= $this->getFotoLimit(); $n++) {
            $tmpRet = array();

            $sizes = $this->getFotoSize(false, $n);
            foreach ($sizes as $sizeKey => $size) {
                $path = ((floor($this->getPrimaryValue() / 100) * 100 + 1) . '-' . (floor($this->getPrimaryValue() / 100) * 100 + 100));
                $sufix = (substr($sizeKey, 0, 2) == 'fn') ?
                    substr($sizeKey, 2) :
                    $sizeKey;

                $fn = "fotos/{$path}/{$cl}-{$id}-{$n}{$sufix}.jpg";
                if (file_exists("{$_BaseDir}/{$fn}")) {
                    $tmpRet[$sizeKey] = "{$relativePath}{$fn}";
                } elseif ($getEmptyToo) {
                    $tmpRet[$sizeKey] = false;
                }
            }
            if ($tmpRet) {
                $ret[$n] = $tmpRet;
            }
        }

        return $ret;
    }

    function getFotosN()
    {
        $gf = $this->getFotos();
        return $gf ? sizeof($gf) : 0;
    }

    function getFotoSize($type = false, $n = 1)
    {
        // Tipos de input aceitáveis:
        //   FALSE: Retorna todos os tipos.
        //   fno = Original
        //   fnt = Miniatura
        //   fn  = Grande, para PC e Tablet
        //   fnm = Grande, para Mobile

        // Valores esperados para cada tamanho:
        // FALSE = Não armazena a foto.. Mesma coisa que não ter.
        // TRUE  = Armazena a foto em tamanho original
        // Altura X Largura X Redimensionar
        //     Redimensionar:
        //     0=resizeMaxSize    1=resizeExactSize
        //     2=resizeToFit      3=resizeMinSize
        //     4=touchInside      5=touchOutside  (Inside: Imagem fica menor ou igual do que o tamanho informado, Outside: Imagem fica igual ou maior)
        $sizes = $this->setFoto['fotoSize'];
        if (!$type) {
            return $sizes;
        }
        if (!isset($sizes[$type])) {
            die("getFotoSize: Unknown type {$type}");
        }

        return $sizes[$type];
    }

    function getFotoLimit()
    {
        // Quantos fotos estarão disponíveis nas funções de fotos simples?
        return $this->setFoto ?
            $this->setFoto['settings']['fotoLimit'] :
            0;
    }

    function republishFotos()
    {
        // Recuperar as fotos originais, e redimensionar para as novas dimensões.
        $_BaseDir = dSystem::getGlobal('baseDir');
        for ($n = 1; $n <= $this->getFotoLimit(); $n++) {
            $exFoto = $this->getFoto("{$_BaseDir}/", 'fno', $n);
            if ($exFoto) {
                $this->setFoto($exFoto, $n, array('fno'));
            }
        }

        return true;
    }

    function setFotoIsEnabled()
    {
        return !($this->setFoto === false);
    }

    // SetFile: Anexar arquivo (coluna 'filename')
    private $setFile = false;

    function enableSetFile($settings = array())
    {
        if ($settings === true) {
            $settings = array();
        }

        $this->setFile = dHelper2::addDefaultToArray($settings, array(
            'path' => dSystem::getGlobal('baseDir') . '/dat/',
            'cbAfterFileAccept' => false, // Callback(filename, Ofilename)
        ));
    }

    function setFile($filename, $Ofilename = false)
    {
        $_BaseDir = dSystem::getGlobal('baseDir');
        if (!$this->getPrimaryValue()) {
            return false;
        }

        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = $this->getFileFn();
        if ($filename && !$Ofilename) {
            $this->addError(false, "Filename original inválido.");
            return false;
        }
        if (file_exists($fn)) {
            unlink($fn);
        }
        if (!$filename) {
            $this->startUpdate();
            $this->setValue('filename', false);
            $this->flushUpdate();
            return true;
        }

        $ok = is_uploaded_file($filename) ?
            move_uploaded_file($filename, $fn) :
            copy($filename, $fn);

        if (!$ok) {
            $this->addError(false, "Impossível mover o arquivo para a pasta de destino.");
            return false;
        }

        $this->startUpdate();
        $this->setValue('filename', basename($Ofilename));
        $ok2 = $this->flushUpdate();

        if (!$ok2) {
            $this->addError(false, "Nome de arquivo não foi aceito, renomeie o arquivo e tente novamente.");
            unlink($fn);
            return false;
        }

        if ($this->setFile['cbAfterFileAccept']) {
            call_user_func($this->setFile['cbAfterFileAccept'], $fn, $Ofilename);
        }

        return true;
    }

    function getFileFn($ext = '')
    {
        if (!$this->getPrimaryValue()) {
            return false;
        }

        $path = rtrim($this->setFile['path'], '/');

        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = "{$path}/{$cl}-{$id}{$ext}.dat";
        return $fn;
    }

    function hasFile()
    {
        return $this->downloadFile(true);
    }

    function downloadFile($getAsBool = false)
    {
        $_BaseDir = dSystem::getGlobal('baseDir');
        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = $this->getFileFn();

        if (!$this->getValue('filename')) {
            return false;
        }

        if ($getAsBool) {
            return file_exists($fn);
        }

        if (!file_exists($fn)) {
            dSystem::notifyAdmin('HIGH', "Inconsistência entre database/arquivos",
                "Usuário tentou baixar um registro que existe no banco de dados, " .
                "mas não consegui encontrar o arquivo real.\r\n" .
                "\r\n" .
                "Minha classe: " . get_class($this) . "\r\n" .
                "Meus valores: " . print_r($this->saveToArray(), true),
                true
            );
            die;
        }

        // Evita qualquer tipo de compactação e/ou informações indesejadas...
        @ob_end_clean();

        $filesize = filesize($fn);
        $filename = dHelper2::stringToUrl($this->getValue('filename'), '.');

        header("Content-Type: application/force-download");
        header("Content-Transfer-Encoding: binary");
        header("Content-Disposition: filename=\"{$filename}\"; charset=iso8859-1");
        if ($filesize) {
            header("Content-Length: {$filesize}");
        }

        readfile($fn);
        die;
    }

    function embedFile($viewFile = false, $settings = false)
    {
        $_BaseDir = dSystem::getGlobal('baseDir');

        // viewFile:
        //   a) Se acabar com '/', é path relativo para o arquivo viewFile.php. Ex: ../
        //   b) Se nome do arquivo, indica responsável pelo download/exibição desses arquivos. Default: viewFile.php

        // settings:
        //   (bool)forceDownload
        //   [fakeClass]        => false  // Passar outro texto como identificação na hora do download. Adicionar exceção no viewFile.php.
        //   [forceDownload]    => false  // Sempre retornar código para download direto, mesmo sendo imagem ou flash.
        //   [onlyLink]         => false  // Retornar apenas o link para download, sem tags.
        //   [linkTo]           => 'auto' // Para imagem ou flash: Ao ser clicado, link para...
        //   [altText]          => 'auto' // Para imagem ou flash: Texto alternativo...
        //   [target]           => false  // Para imagem ou flash com linkTo: target=...
        //   [onclick]          => false  // Para imagem ou flash com linkTo: onclick=...
        //   [flashTransparent] => true   // Para flash: Ativar wmode='transparent'

        if (is_bool($settings) && $settings) {
            $settings = array('forceDownload' => true);
        } else {
            $defSettings = array();
            $defSettings['fakeClass'] = false;
            $defSettings['forceDownload'] = false;
            $defSettings['onlyLink'] = false;
            $defSettings['linkTo'] = 'auto';
            $defSettings['altText'] = 'auto';
            $defSettings['target'] = false;
            $defSettings['onclick'] = false;
            $defSettings['flashTransparent'] = true;
            if (is_array($settings)) {
                $settings += $defSettings;
            } else {
                $settings = $defSettings;
            }
        }

        if (!$viewFile) {
            $viewFile = 'viewFile';
        }
        if (substr($viewFile, -1) == "/") {
            $viewFile .= "viewFile";
        }

        $viewFile = preg_replace("/\.php$/", "", $viewFile);
        $fakeClass = $settings['fakeClass'] ?
            $settings['fakeClass'] :
            get_class($this);

        if (!$this->getPrimaryValue()) {
            return false;
        }

        if (!$this->getValue('filename')) {
            return false;
        }

        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = $this->getFileFn();

        if ($settings['forceDownload']) {
            $tipo = 'download';
        } else {
            switch (preg_replace("/.+\./", "", $this->getValue('filename'))) {
                case 'gif':
                case 'jpg':
                case 'png':
                case 'bmp':
                    $tipo = 'image';
                    break;

                case 'swf':
                    $tipo = 'flash';
                    break;

                default:
                    $tipo = 'link';
            }
        }

        if ($tipo != 'download') {
            $size = @getimagesize($fn);
            if (!$size) {
                $tipo = 'download';
                $size = false;
            }
        }
        if ($tipo != 'download') {
            $linkTo = $settings['linkTo'];
            $altText = $settings['altText'];

            if ($linkTo == 'auto') {
                if (method_exists($this, 'getLink')) {
                    $linkTo = $this->getLink();
                } elseif (isset($this->fieldValues['link'])) {
                    $linkTo = $this->fieldValues['link'];
                } else {
                    $linkTo = false;
                }
            }
            if ($altText == 'auto') {
                if (method_exists($this, 'getTitle')) {
                    $altText = $this->getTitle();
                } elseif (isset($this->fieldValues['titulo'])) {
                    $altText = $this->fieldValues['titulo'];
                } else {
                    $altText = false;
                }
            }
        }

        $filename = dHelper2::stringToUrl($this->getValue('filename'), '.');
        $embedFilename = "{$viewFile}/{$fakeClass}/{$this->getPrimaryValue()}/" . ($settings['forceDownload'] ? 'fd/' : '') . "{$filename}";
        if ($settings['onlyLink']) {
            return $embedFilename;
        }

        $str = '';
        if ($tipo == 'image') {
            if ($linkTo) {
                $str .= "<a href='{$linkTo}'";
                if ($settings['target']) {
                    $str .= " target='{$settings['target']}'";
                }
                if ($settings['onclick']) {
                    $str .= " onclick=\"{$settings['onclick']}\"";
                }
                $str .= ">";
            }

            $str .= "<img src='{$embedFilename}' width='{$size[0]}' height='{$size[1]}' border='0'";
            if ($altText) {
                $str .= " alt=\"" . htmlspecialchars($altText) . "\"";
            }
            $str .= " />";

            if ($linkTo) {
                $str .= "</a>";
            }
        }
        if ($tipo == 'flash') {
            if ($this->getValue('link')) {
                $str .= "<a href='{$linkTo}'";
                if ($settings['target']) {
                    $str .= " target='{$settings['target']}'";
                }
                if ($settings['onclick']) {
                    $str .= " onclick=\"{$settings['onclick']}\"";
                }
                $str .= ">";
                $str .= "<img style='position: absolute; width: {$size[0]}px; height: {$size[1]}px; z-index: 10' src='images/spacer.gif' border='0'";
                if ($altText) {
                    $str .= " alt=\"" . htmlspecialchars($altText) . "\"";
                }
                $str .= " />";
                $str .= "</a>";
            }
            $str .= "<object classid='clsid:D27CDB6E-AE6D-11CF-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0' border='0' width='{$size[0]}' height='{$size[1]}'>";
            if ($settings['flashTransparent']) {
                $str .= "<param name='wmode' value='transparent' />";
            }
            $str .= "<param name='movie' value='{$embedFilename}' />";
            $str .= "<param name='quality' value='High'>";
            $str .= "<embed src='{$embedFilename}' " . ($settings['flashTransparent'] ? "wmode='transparent' " : "") . " pluginspage='http://www.macromedia.com/go/getflashplayer' type='application/x-shockwave-flash' width='{$size[0]}' height='{$size[1]}' quality='High'>";
            $str .= "</object>";
        }
        if ($tipo == 'download') {
            $str .= "<a href='{$embedFilename}'>{$this->getValue('filename')}</a>";
        }

        return $str;
    }

    function setFileIsEnabled()
    {
        return !($this->setFile === false);
    }

    // Sorting
    private $relatedFields = false;

    function sortingIsEnabled()
    {
        return ($this->relatedFields !== false);
    }

    function sortingSetRelated($relatedFields = array())
    {
        $this->relatedFields = $relatedFields;
    }

    function sortingGetRelated($asWhere = false)
    {
        $ret = array();
        $ret = $this->relatedFields;

        /**
         * Importante - Limite: 4
         * Para outro limite, edite o método sortingRefresh().
         **/

        if ($ret && $asWhere) {
            $str = array();
            foreach ($ret as $key) {
                $compareTo = $this->formatToQuery($this->getOriginal($key), $key);
                if (strtolower($compareTo) == "null") {
                    $str[] = "isnull({$this->useQuotes}{$key}{$this->useQuotes})";
                } else {
                    $str[] = "{$this->useQuotes}{$key}{$this->useQuotes} = {$compareTo}";
                }
            }
            return implode(" and ", $str);
        }
        return $ret;
    }

    function sortingMoveToTop()
    {
        return $this->sortingMoveTo(1);
    }

    function sortingMoveToBottom()
    {
        return $this->sortingMoveTo('MAX');
    }

    function sortingMoveTo($new)
    {
        $db = dDatabase::start();
        $tb = $this->mainTable["tableName"];
        $wh = $this->sortingGetRelated(true);
        $max = intval($db->singleResult("select max(ordem) from {$tb}" . ($wh ? " where {$wh}" : "")));
        $old = $this->getOriginal('ordem');

        $this->castMsg("Max from database: OLD={$old}, MAX={$max}. Where: {$wh}");

        if (strtoupper($new) == 'MAX') {
            $new = ($max + 1);
        }

        $new = intval($new);
        if ($new < 1) {
            $new = 1;
        }

        if (!$old) {
            $max++;
            $this->castMsg("Detectado novo registro pois não possui OLD. Max com este objeto agora é {$max}");
            if ($new > $max) {
                $new = $max;
            } elseif ($new < $max) {
                $db->query("update {$tb} set ordem = ordem+1 where " . ($wh ? "{$wh} and" : "") . " ordem >= '$new'",
                    "Novo item entrando no meio");
            }
        } else {
            if ($new == $old) {
                return $new;
            }

            if ($new > $max) {
                $new = $max;
            }

            # echo "Nova posição: {$new}<br />";
            # echo "Old position: {$old}<br />";
            # echo "Max position: {$max}<br />";

            if ($new < $old) {
                $db->query("update {$tb} set ordem = ordem+1 where " . ($wh ? "{$wh} and" : "") . " ordem >= '$new' and ordem <  '$old'",
                    "Altera a posição dos registros antes deste.");
            }
            if ($new > $old) {
                $db->query("update {$tb} set ordem = ordem-1 where " . ($wh ? "{$wh} and" : "") . " ordem > '$old'  and ordem <= '$new'",
                    "Altera a posição dos registros depois deste.");
            }

        }
        $db->query("update {$tb} set ordem='{$new}' where {$this->mainTable['primaryIndex']}='{$this->getPrimaryValue()}'",
            "Atualiza a posição do item");
        return $new;
    }

    function sortingPrepareToDelete()
    {
        // Considera os campos 'originais'.

        $db = dDatabase::start();
        $tb = $this->mainTable["tableName"];
        $old = $this->getOriginal('ordem');
        $wh = $this->sortingGetRelated(true);
        if ($old) {
            $db->query("update {$tb} set ordem = ordem-1 where " . ($wh ? "{$wh} and" : "") . " ordem > '$old'",
                "Remove a 'Ordem' do produto e prepara para exclusão");
            $this->setValue('ordem', false);
        }
    }

    function sortingRefresh()
    {
        $db = dDatabase::start();
        $tb = $this->mainTable["tableName"];
        $wh = $this->sortingGetRelated();

        if (!$wh) {
            $n = 1;
            $allObjs = $db->singleColumn("select id from {$tb} order by ordem");
            foreach ($allObjs as $objId) {
                $db->query("update {$tb} set ordem='" . ($n++) . "' where id='{$objId}'");
            }

            return true;
        } else {
            // Como fazer? KISS!
            // Sejamos honestos, quantas vezes vai haver mais que quatro filtros?! É impossível!
            $whereToCheck = array();

            if (sizeof($wh) == 1) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    $whereToCheck[] = "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]);
                }
            }
            if (sizeof($wh) == 2) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        $whereToCheck[] =
                            "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                            "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]);
                    }
                }
            }
            if (sizeof($wh) == 3) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                $allValuesForKey2 = $db->singleColumn("select distinct {$wh[2]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        foreach ($allValuesForKey2 as $updateMe2) {
                            $whereToCheck[] =
                                "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                                "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]) . " and " .
                                "{$wh[2]}=" . $this->formatToQuery($updateMe2, $wh[2]);
                        }
                    }
                }
            }
            if (sizeof($wh) == 4) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                $allValuesForKey2 = $db->singleColumn("select distinct {$wh[2]} from {$tb}");
                $allValuesForKey3 = $db->singleColumn("select distinct {$wh[3]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        foreach ($allValuesForKey2 as $updateMe2) {
                            foreach ($allValuesForKey3 as $updateMe3) {
                                $whereToCheck[] =
                                    "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                                    "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]) . " and " .
                                    "{$wh[2]}=" . $this->formatToQuery($updateMe2, $wh[2]) . " and " .
                                    "{$wh[3]}=" . $this->formatToQuery($updateMe2, $wh[3]);
                            }
                        }
                    }
                }
            }
            if (sizeof($wh) == 5) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                $allValuesForKey2 = $db->singleColumn("select distinct {$wh[2]} from {$tb}");
                $allValuesForKey3 = $db->singleColumn("select distinct {$wh[3]} from {$tb}");
                $allValuesForKey4 = $db->singleColumn("select distinct {$wh[4]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        foreach ($allValuesForKey2 as $updateMe2) {
                            foreach ($allValuesForKey3 as $updateMe3) {
                                foreach ($allValuesForKey4 as $updateMe4) {
                                    $whereToCheck[] =
                                        "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                                        "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]) . " and " .
                                        "{$wh[2]}=" . $this->formatToQuery($updateMe2, $wh[2]) . " and " .
                                        "{$wh[3]}=" . $this->formatToQuery($updateMe2, $wh[3]) . " and " .
                                        "{$wh[4]}=" . $this->formatToQuery($updateMe2, $wh[4]);
                                }
                            }
                        }
                    }
                }
            }
            if (sizeof($wh) > 5) {
                $this->castDbg(2, "Limite de filtros é 5. Edite o este método automaticamente.");
                return false;
            }

            foreach ($whereToCheck as $where) {
                $n = 1;
                $allObjs = $db->singleColumn("select id from {$tb} where {$where} order by ordem");
                foreach ($allObjs as $objId) {
                    $db->query("update {$tb} set ordem='" . ($n++) . "' where id='{$objId}'");
                }
            }
        }
    }

    function saveToDatabase($pid = false)
    {
        if (!$this->sortingIsEnabled()) {
            return parent::saveToDatabase();
        }

        // Antes e depois de salvar, modifique campo "Ordem" dos objetos semelhantes.
        $oldPos = $this->getOriginal('ordem');
        $newPos = $this->getValue('ordem');

        $this->castMsg("Salvando... OldPos={$oldPos}, NewPos={$newPos}");

        if (!$this->getPrimaryValue()) { // Não estava carregado...
            if ($pid) {
                $this->castMsg("Tentando forçar um load...");
                // Mas tentou substituir algo!
                // Então tente carregar esse algo e tente novamente...

                // 1. Pegar valores que era pra salvar...
                $newValues = array();
                foreach ($this->fieldProps as $aliasName => $prop) {
                    $newValues[$aliasName] = array($this->fieldValues[$aliasName], $prop['raw']);
                }

                // 2. Carregar do banco de dados...
                if ($this->loadFromDatabase($pid)) {
                    // 3. Substituir do database pelos dados que era pra salvar no início
                    foreach ($newValues as $key => $value) {
                        $this->setValue($key, $value[0], $value[1]);
                    }
                }

                unset($newValues);

                $oldPos = $this->getOriginal('ordem');
                $newPos = $this->getValue('ordem');
            }
            if (!$this->getPrimaryValue()) { // Se continua não carregado, ou seja: é um novo item!
                $this->castMsg("É objeto novo!");
                // Salvar e, se tiver o valor de 'ordem', definir a posição!
                // Se não tiver, mover para o final da lista.
                $this->setValue('ordem', false);

                $ret = parent::saveToDatabase($pid);
                if ($ret && $newPos) {
                    $this->castMsg("Tem posição definida, utilizando ela!");
                    $this->sortingMoveTo($newPos);
                } elseif ($ret) {
                    $this->castMsg("Não foi informada uma posição, movendo para o final.");
                    $this->sortingMoveToBottom();
                }

                return $ret;
            }
        }

        // Se chegou até aqui, é um objeto existente e carregado, e possui getValue e getOriginal.
        $this->castMsg("Objeto já carregado, tentando atualizar posição (OldPos={$oldPos}, NewPos={$newPos})");

        // Tem modificação em campos importantes?
        $isKeyChanged = false;
        $checkKeys = $this->sortingGetRelated();
        if ($checkKeys) {
            foreach ($checkKeys as $key) {
                if ($this->isModified($key)) {
                    $isKeyChanged = true;
                    break;
                }
            }
        }

        $this->castMsg("Tem modificação em campo importante? " . ($isKeyChanged ? 'Sim' : 'Nao'));

        if ($isKeyChanged) { // Se houver modificação em campos importantes...
            $this->sortingPrepareToDelete();
            $ret = parent::saveToDatabase($pid);
            if ($ret) {
                // Salvou e tem uma posição...
                $this->castMsg("Salvei com sucesso, definindo nova posição: {$newPos}");
                if ($newPos) {
                    $this->sortingMoveTo($newPos);
                } else {
                    $this->sortingMoveToBottom();
                }
            } elseif (!$ret) {
                // Falhou e não conseguiu salvar!
                // Então volta temporariamente os campos importantes
                $newValues = array();
                foreach ($this->fieldProps as $aliasName => $prop) {
                    $newValues[$aliasName] = array($this->fieldValues[$aliasName], $prop['raw']);
                    $this->setValue($aliasName, $this->getOriginal($aliasName));
                }

                // Define a posição original
                $this->sortingMoveTo($oldPos);

                // E retorna novamente o objeto pra estado atual
                foreach ($newValues as $key => $value) {
                    $this->setValue($key, $value[0], $value[1]);
                }
            }
            return $ret;
        } else {              // Se não houver modificação em campos importantes...
            # --> Causando bug.. Não lembro por que existia isso!
            # $this->setValue('ordem', false);
            # $ret  = parent::saveToDatabase($pid);

            // Se mudou a posição ou o ID do item a ser salvo...
            if ($oldPos != $newPos) {
                $this->sortingMoveTo($newPos);
            }

            # Peguei o bug de cima e joguei pra cá, parece que funcionou.
            # Exige mais testes!
            $ret = parent::saveToDatabase($pid);
        }

        return $ret;
    }

    function deleteFromDatabase()
    {
        // Remove todas as possíveis fotos existentes
        for ($x = 1; $x <= $this->getFotoLimit(); $x++) {
            $this->setFoto(false, $x);
        }

        // Organiza as posições dos objetos que não serão removidos
        if ($this->sortingIsEnabled()) {
            $this->sortingPrepareToDelete();
        }

        if ($this->setFileIsEnabled()) {
            $this->setFile(false);

        }

        return parent::deleteFromDatabase();
    }
}
