<?php
/**
 * Referência rápida:
 * ---------------------------------------------------
 * self::enableSetFoto(Array(
 * $key =>Array(width, height, resizeMethod, format='jpg', quality=85)
 * 'fno'=>true,
 * 'fn' =>Array(1280, 1280, 'resizeTouchInside', $format, $quality),
 * 'fnt'=>Array(450,  450,  'resizeTouchInside', $format, $quality),
 * 'fnh'=>function($obj, $n, $outFn[, $tm[, $filename[, $ignoreType]]]){
 * if(!$tm){
 * $tm = new dThumbMaker;
 * $tm->loadFile($filename);
 * }
 * $tm->build($outFn);
 * },
 * 'fnm'=>Array(500, 250, function($obj, $tm, $width, $height, $n){
 * $tm->resizeMaxSize(400, 200);
 * });
 * ), Array('fotoLimit'=>1));
 *
 * self::enableSetFile(Array(
 * 'path'             =>dSystem::getGlobal('baseDir').'/dat/',
 * 'cbAfterFileAccept'=>false, // Callback(filename, Ofilename)
 * ));
 *
 * self::enableSorting($relatedFields='', $ensureConsistency=true);
 * self::sortingRefresh(false, "paren_id='12' and deleted='0'", "titulo")
 **/

class dDbRow3Plus extends dDbRow3
{
    // Fotos simples (até $this->getFotoLimit())
    static function enableSetFoto($fotoSize, $settings = array())
    {
        $settings += array(
            'fotoLimit' => 1
        );
        self::structSet('modules', 'setFoto', array(
            'fotoSize' => $fotoSize,
            'settings' => $settings
        ));
    }

    function setFoto($filename, $n = 1, $ignoreType = array())
    {
        if (!$this->isLoaded()) {
            $this->addError(false, 'O registro precisa estar no banco de dados antes de receber uma imagem.');
            return false;
        }

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
            $path = ((floor($id / 100) * 100 + 1) . '-' . (floor($id / 100) * 100 + 100));
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
            $_doFallback = false;
            foreach ($sizes as $sizeKey => $size) {
                if ($size === true) {
                    // Save original:
                    copy($filename, $fn[$sizeKey]);
                    continue;
                } elseif (is_callable($size)) {
                    // Se o size for um callback, então será chamado:
                    // callback($obj, $n, $outFn[, $tm[, $filename[, $ignoreType]]])
                    $retOk = call_user_func($size, $this, $n, $fn[$sizeKey], $tm, $filename, $ignoreType);
                    if ($retOk === false) {
                        // Se retornar FALSE, assume que foi um erro muito grave.
                        // Por exemplo, a imagem não pode ser carregada.
                        // Nesse caso, pare a execução e não tente gerar mais imagens.
                        // To-do: Não deveria ocorrer um fallback das imagens já geradas?!
                        $_doFallback = true;
                        break;
                    }
                } else {
                    $outFileType = 'jpg';
                    $outQuality = 85;    // Recomendação Google PageSpeed.
                    if (!$tm) {
                        // Carrega a classe dThumbMaker.
                        $tm = new dThumbMaker;
                        $tm->setBgColor(255, 255, 255);
                        $load = $tm->loadFile($filename);

                        if ($load !== true) {
                            $_doFallback = true;
                            break;
                        }

                        $tm->createBackup();
                    } else {
                        $tm->restoreBackup();
                    }

                    if (is_callable($size[2])) {
                        // Se o parâmetro for um callback
                        // callback($thisObj, $tm, $width, $height, $n, &$outFileType, &$outQuality)
                        $size[2]($this, $tm, $size[0], $size[1], $n, $outFileType, $outQuality);
                    } else {
                        if (in_array($size[2], array(5, 'resizeTouchOutside', 'tuchOutside'))) {
                            $tm->resizeTouchOutside($size[0], $size[1]);
                        } elseif (in_array($size[2], array(4, 'resizeTouchInside', 'touchInside'))) {
                            // TouchInside pode ter dois parâmetros a mais, sendo eles:
                            // --> size[2]: fillWithBackground (default: false | Se for TRUE, será Array(255, 255, 255))
                            // --> size[3]: align              (default: Array('center', 'middle'))
                            if (isset($size[2])) {
                                // $tm->bgColor = !is_array($size[2])?Array(255, 255, 255):$size[2];
                                isset($size[3]) ?
                                    $tm->resizeTouchInside($size[0], $size[1], $size[2], $size[3]) :
                                    $tm->resizeTouchInside($size[0], $size[1], $size[2]);
                            } else {
                                $tm->resizeTouchInside($size[0], $size[1]);
                            }
                        } elseif (in_array($size[2], array(3, 'resizeMinSize', 'minSize'))) {
                            $tm->resizeMinSize($size[0], $size[1]);
                        } elseif (in_array($size[2], array(2, 'resizeToFit', 'toFit'))) {
                            $tm->resizeToFit($size[0], $size[1]);
                        } elseif (in_array($size[2], array(1, 'resizeExactSize', 'exactSize', true), true)) {
                            $tm->resizeExactSize($size[0], $size[1]);
                        } elseif (in_array($size[2], array(0, 'resizeMaxSize', 'maxSize', false), true)) {
                            $tm->resizeMaxSize($size[0], $size[1]);
                        } else {
                            dSystem::notifyAdmin('HIGH', 'Não entendi como redimensionar a imagem...',
                                "Os parâmetros conhecidos são touchOutside, touchInside, maxSize, minSize, toFit, exactSize.\r\n" .
                                "No entanto, o parametro fornecido foi " . var_export($size[2], true) . "\r\n",
                                "\r\n" .
                                "Para não deixar arquivos inconsistentes, vou assumir que é maxSize, mas isso deve ser revisado.\r\n" .
                                "Arquivo em questão: {$fn[$sizeKey]}\r\n"
                            );
                            $tm->resizeMaxSize($size[0], $size[1]);
                        }
                    }

                    if (isset($size[3])) {
                        $outFileType = $size[3];
                    }

                    if (isset($size[4])) {
                        $outQuality = $size[4];
                    }

                    $tm->build($fn[$sizeKey], $outFileType, $outQuality);
                }
            }

            if ($_doFallback) {
                // Em algum momento, tivemos um problema crítico na geração das imagens.
                // Vamos remover tudo que, porventura, tenha sido gravado com sucesso.
                foreach ($sizes as $sizeKey => $size) {
                    if (file_exists($fn[$sizeKey])) {
                        unlink($fn[$sizeKey]);
                    }
                }

                return false;
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
        if (!$this->isLoaded()) {
            return false;
        }

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
        $sizes = self::structGet('modules', 'setFoto', 'fotoSize');
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
        return self::structGet('modules', 'setFoto') ?
            self::structGet('modules', 'setFoto', 'settings', 'fotoLimit') :
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
            } else {
                $exFoto = $this->getFoto("{$_BaseDir}/", 'fn', $n);
                if ($exFoto) {
                    $this->setFoto($exFoto, $n, array('fn'));
                }
            }
        }

        return true;
    }

    function embedFoto($relativePath = '', $type = '', $n = 1, $imgAttr = false, $aAttr = false, $strIfNoImage = false)
    {
        $ret = ($aAttr ? "<a {$aAttr}>" : "");
        if ($this->getFoto('', $type, $n)) {
            $ret .= "<img src='{$this->getFoto($relativePath, $type, $n)}' {$imgAttr} />";
        } else {
            $ret .= ($strIfNoImage ? $strIfNoImage : "(Sem imagem)");
        }
        $ret .= ($aAttr ? "</a>" : "");

        return $ret;
    }

    function setFotoIsEnabled()
    {
        return self::structGet('modules', 'setFoto');
    }

    static function republishAll($requiredFields = 'id')
    {
        $myClass = get_called_class();
        if (!$myClass::structExists()) {
            $myClass::buildStruct();
        }

        $fotoLimit = self::structGet('modules', 'setFoto', 'settings', 'fotoLimit');
        $allRows = $myClass::multiLoad(array('onlyFields' => $requiredFields));
        $nRows = sizeof($allRows);
        $initTime = microtime(true);
        $_myFlush = function () {
            echo str_repeat("<!-- extra buffer -->", 1024);
            flush();
        };

        echo "<pre>";
        echo "Iniciando a re-publicação de imagens da classe <b style='color: #F00'>{$myClass}</b>.\r\n";
        echo "Foram localizados <b>{$nRows}</b> elementos, cada um com até <b>{$fotoLimit}</b> imagens.\r\n";
        echo "\r\n";
        foreach ($allRows as $idx => $rowObj) {
            $timeElapsed = microtime(true) - $initTime;
            $mediaPorSegundo = ($idx + 1) / ($timeElapsed ? $timeElapsed : 1);
            $itensRemaining = ($nRows - ($idx + 1));
            $timeRemaining = number_format($itensRemaining / $mediaPorSegundo, 2);
            echo "  ID=(" . str_pad($rowObj->getPrimaryValue(), 3, ' ', STR_PAD_LEFT) . "), processando.... ";
            $_myFlush();
            $rowObj->republishFotos();
            echo "  Done (" . round((($idx + 1) / $nRows) * 100, 2) . "%). Time elapsed: " . number_format($timeElapsed,
                    2) . ". Time remaining: {$timeRemaining}.\r\n";
        }

        echo "\r\n";
        echo "<b style='color: #F00'>Done republishing {$myClass}.</b>";
        echo "</pre>";
    }

    // SetFile: Anexar arquivo (coluna 'filename')
    static function enableSetFile($settings = array())
    {
        if ($settings === true) {
            $settings = array();
        }

        self::structSet('modules', 'setFile', $settings + array(
                'path' => dSystem::getGlobal('baseDir') . '/dat/',
                'cbAfterFileAccept' => false, // Callback(filename, Ofilename)
            ));
    }

    function setFileData($Ofilename, &$filedata)
    {
        return self::setFile('---filedata---', $Ofilename, $filedata);
    }

    function setFile($filename, $Ofilename = false, &$filedata = false)
    {
        if (!$this->isLoaded()) {
            $this->addError(false, 'O registro precisa estar no banco de dados antes de receber um arquivo.');
            return false;
        }

        $_BaseDir = dSystem::getGlobal('baseDir');
        if (!$this->getPrimaryValue()) {
            return false;
        }

        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = $this->getFileFn();
        if ($filename && !$Ofilename) {
            $this->addError(false, "Você precisa informar o parâmetro Ofilename.");
            return false;
        }
        if (file_exists($fn)) {
            unlink($fn);
        }

        if (!$filename) {
            // Excluir arquivo relacionado...
            return true;
        }

        if ($filename == '---filedata---') {
            $ok = file_put_contents($fn, $filedata);

            if (!$ok) {
                $this->addError(false, "Impossível salvar o conteúdo do arquivo enviado na pasta de destino.");
                return false;
            }
        } else {
            $ok = is_uploaded_file($filename) ?
                move_uploaded_file($filename, $fn) :
                copy($filename, $fn);

            if (!$ok) {
                $this->addError(false, "Impossível mover o arquivo para a pasta de destino.");
                return false;
            }
        }

        $this->v('filename', basename($Ofilename));
        if ($this->isAliasEnabled('fileext')) {
            $this->v('fileext', strtolower(preg_replace("/.+\./", "", $Ofilename)));
        }
        if ($this->isAliasEnabled('filesize')) {
            $this->v('filesize', filesize($fn));
        }

        $ok2 = $this->save();
        if (!$ok2) {
            $this->addError(false, "Nome de arquivo não foi aceito, renomeie o arquivo e tente novamente.");
            unlink($fn);
            return false;
        }

        if (self::structGet('modules', 'setFile', 'cbAfterFileAccept')) {
            call_user_func(self::structGet('modules', 'setFile', 'cbAfterFileAccept'), $fn, $Ofilename);
        }

        return true;
    }

    function getFileFn($ext = '')
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $path = rtrim(self::structGet('modules', 'setFile', 'path'), '/');

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
        if (!$this->isLoaded()) {
            return false;
        }

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
                "Meus valores: " . print_r($this->export(), true),
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
        if (!$this->isLoaded()) {
            return false;
        }

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
        return self::structGet('modules', 'setFile');
    }

    // Sorting
    static $sortingCache = array();

    static function sortingPause($yesno, $keyName = false)
    {
        $className = get_called_class();
        if (!$className::structExists()) {
            $className::buildStruct();
        }
        if (!$keyName) {
            foreach (array_keys(self::structGet('modules', 'sorting')) as $keyName) {
                self::sortingPause($yesno, $keyName);
            }
            return;
        }
        self::structSet('modules', 'sorting', $keyName, 'paused', $yesno);
    }

    static function enableSorting($relatedFields = array(), $ensureConsistency = true, $keyName = 'ordem')
    {
        // ensureConsistency:
        // --> Itens afetados:
        //     a) Sempre que um item é movido para uma posição maior, o banco de dados é consultado para determinar que
        //        aquela posição não é acima do máximo.
        //     b) Todos os objetos terão sua posição resgatada diretamente do banco de dados, para garantir que updates
        //        realizados durante o save() de outros objetos não afetou a sua posição pré-carregada.
        // --> Não afeta:
        //     a) !isLoaded && ->v('id', 10)
        //
        // Recomendável: Sempre true
        //
        if (is_string($relatedFields)) {
            $relatedFields = explode(",", $relatedFields);
        }

        self::removeModifier($keyName, 'force_int');
        self::structSet('modules', 'sorting', $keyName, 'relatedFields', $relatedFields);
        self::structSet('modules', 'sorting', $keyName, 'ensureConsistency', $ensureConsistency);
        self::setDefaultValue($keyName, 'MAX');
    }

    static function sortingGetRelated($asWhereObj = false, $useOriginal = false, $keyName = 'ordem')
    {
        $ret = array();
        $ret = self::structGet('modules', 'sorting', $keyName, 'relatedFields');

        /**
         * Importante - Limite: 4
         * Para outro limite, edite o método sortingRefresh().
         **/

        if ($asWhereObj) {
            $str = array();
            foreach ($ret as $key) {
                $value = ($useOriginal) ?
                    $asWhereObj->getOriginal($key) :
                    $asWhereObj->getValue($key);

                $compareTo = self::formatToQuery($value, $key);
                if (strtolower($compareTo) == "null") {
                    $str[] = "isnull({$key})";
                } else {
                    $str[] = "{$key} = {$compareTo}";
                }
            }
            return implode(" and ", $str);
        }
        return $ret;
    }

    static function sortingRefresh($keyName = false, $where = false, $orderBy = false, $useTransaction = true)
    {
        // $keyName: if false, refreshes all sorting available;
        // $where:  use to limit the query to some specific "Where Related". Example: "paren_id='2' and deleted='0'"
        // $orderBy:
        //      false:                           Would fix any inconsistences found (ex: 3, 4, 7, 8, 9)
        //      'fieldName' or 'fieldName desc': Would reorder to match.

        $className = get_called_class();
        if (!$className::structExists()) {
            $className::buildStruct();
        }
        if (!$keyName) {
            if ($useTransaction) {
                $className::getDb()->query("start transaction");
            }
            foreach (array_keys(self::structGet('modules', 'sorting')) as $keyName) {
                self::sortingRefresh($keyName, $where, $orderBy, false);
            }
            if ($useTransaction) {
                $className::getDb()->query("commit");
            }
            return true;
        }

        $db = $className::getDb();
        $wh = $className::sortingGetRelated(false, false, $keyName);
        $tableName = $className::structGet('tableName');
        $primaryKey = $className::structGet('primaryKey');
        $orderBy = ($orderBy ? $orderBy : $keyName);

        $arrMap = array();
        $doChanges = array();
        $qh = $db->query(
            "select " .
            "{$primaryKey}," .
            ($wh ? "concat(COALESCE(" . implode(",''),'|sr|',COALESCE(", $wh) . ",'')) as useKey," : "") .
            "{$keyName} " .
            "from {$tableName} " .
            ($where ? " where {$where} " : "") .
            "order by " .
            ($wh ? implode(",", $wh) . "," : "") .
            $orderBy
        );

        while ($item = $db->fetch($qh)) {
            $newPos = @++$arrMap[$wh ? $item['useKey'] : '--count--'];
            if ($newPos != $item[$keyName]) {
                $doChanges[$item[$primaryKey]] = $newPos;
            }
        }
        $db->freeResult($qh);
        unset($arrMap);

        if ($doChanges && $useTransaction) {
            $db->query("start transaction");
        }
        foreach ($doChanges as $primaryValue => $newPos) {
            $db->query("update {$tableName} set {$keyName}='{$newPos}' where {$primaryKey}=" . self::formatToQuery($primaryValue,
                    $primaryKey));
        }
        if ($doChanges && $useTransaction) {
            $db->query("commit");
        }
    }

    function sortingMoveToTop($keyName = 'ordem')
    {
        return $this->sortingMoveTo(1, $keyName);
    }

    function sortingMoveToBottom($keyName = 'ordem')
    {
        return $this->setValue($keyName, 'MAX')->save();
    }

    function sortingPrepareToDelete($keyName = 'ordem')
    {
        return $this->v($keyName, false)->save();
    }

    function sortingMoveTo($new, $keyName = 'ordem')
    {
        return $this->setValue($keyName, $new)->save();
    }

    function sortingIsEnabled()
    {
        return is_array(self::structGet('modules', 'sorting'));
    }

    function _sortingKeysChanged($keyName = 'ordem')
    {
        $allRelated[] = 'id';
        $isChanged = false;
        $allRelated = self::sortingGetRelated(false, false, $keyName);
        foreach ($allRelated as $aliasName) {
            if ($this->getOriginal($aliasName) !== $this->getValue($aliasName)) {
                $isChanged = true;
                break;
            }
        }
        return $isChanged;
    }

    function _sortingGetQuery($old, $new, $keyName = 'ordem')
    {
        if (!$old && $new) {
            return "{$keyName}={$keyName}+1 where {$keyName} >= " . self::formatToQuery($new, $keyName);
        }
        if ($old && !$new) {
            return "{$keyName}={$keyName}-1 where {$keyName} >= " . self::formatToQuery($old, $keyName);
        }
        if ($old > $new) {
            return "{$keyName}={$keyName}+1 where {$keyName} >= " . self::formatToQuery($new,
                    $keyName) . " and {$keyName} <  " . self::formatToQuery($old, $keyName);
        }
        if ($old < $new) {
            return "{$keyName}={$keyName}-1 where {$keyName} >  " . self::formatToQuery($old,
                    $keyName) . " and {$keyName} <= " . self::formatToQuery($new, $keyName);
        }
        trigger_error("_sortingGetQuery(old={$old}, new={$new}). No query to return!");
        return false;
    }

    function _sortingHandle($keyName = false)
    {
        // If keyName is false, apply it to everything.
        # echo "<b>_sortingHandle({$keyName})</b><br />";
        if ($keyName == false) {
            $postCmds = array();
            foreach (array_keys(self::structGet('modules', 'sorting')) as $keyName) {
                $toMerge = $this->_sortingHandle($keyName);
                if ($toMerge === false) {
                    $postCmds = false;
                    break;
                }
                $postCmds = array_merge($postCmds, $toMerge);
            }
            return $postCmds;
        }

        if (self::structGet('modules', 'sorting', $keyName, 'paused')) {
            return array();
        }

        // Prepara as variáveis compartilhadas:
        $db = self::getDb();
        $tableName = self::structGet('tableName');
        $primaryKey = self::structGet('primaryKey');
        $className = get_called_class();
        $WRn = "";
        $_max = false;
        $getMax = function () use (&$_max, &$db, &$tableName, &$keyName, &$WRn) {
            if ($_max !== false) {
                return $_max;
            }

            return $_max = intval($db->singleResult("select max({$keyName}) from {$tableName} " . ($WRn ? " where {$WRn}" : ""),
                "Detecting maximum value for {$keyName}."));
        };
        $_ensureConsistency = self::structGet('modules', 'sorting', $keyName, 'ensureConsistency');

        // Ensure that the item is really not loaded.
        if (!$this->isLoaded() && $this->v($primaryKey)) {
            // Se o item é !isLoaded, mas o programador utilizou ->setValue($primaryKey, ...),
            // então vamos confirmar se o registro realmente não existia anteriormente. Se existia,
            // vamos carregá-lo e considerar que não é um item novo, mas um update.
            //
            // Em outras palavras, vamos nos proteger contra barbeiragens do programador.
            // $_ensureConsistency não afeta esta checagem.
            //
            # echo "<div style='padding: 5px; margin: 5px 0px; background: #FCC; border: #F99'>";
            # echo "<i style='color: #F00'>Anti-barbeiragem: O objeto realmente é novo?</i><br />";
            $primaryKey = self::structGet('primaryKey');
            $newData = $this->fieldValues;
            $loadSql = self::makeQuery(array(
                'onlyFields' => $this->getAliasEnabled(),
                'loadExt' => false,
                'callback' => "where {$primaryKey}={$this->getModValue($primaryKey, 'sql')}",
            ));
            $oldData = $db->singleLine($loadSql, "Sorting Module: Checking if '{$primaryKey}' already exists...");
            if ($oldData) {
                # echo "- Já existia, vou importar os dados antigos e re-importar o newData...<br />";
                $this->loadArray($oldData, array('format' => 'db', 'setLoaded' => true, 'noChecks' => true));
                $this->loadArray($newData, array('format' => 'basic'));
                # dHelper2::dump($this);
            }
            # echo "</div>";
        }

        // Ensure that all related fields are loaded, on at least, the loaded ones were not modified.
        $checkFields = array_merge(array($keyName), self::sortingGetRelated(false, false, $keyName));
        $_fieldsNotLoaded = array();
        $_fieldsAnyChanges = array();
        foreach ($checkFields as $aliasName) {
            if (!$this->isAliasEnabled($aliasName)) {
                $_fieldsNotLoaded[] = $aliasName;
            } elseif ($this->getOriginal($aliasName) != $this->getValue($aliasName)) {
                $_fieldsAnyChanges = true;
            }
        }
        if ($_fieldsNotLoaded) {
            if (sizeof($checkFields) == sizeof($_fieldsNotLoaded)) {
                // Nothing is loaded, so there is nothing to worry about.
                return array();
            }

            // Only some relatedFields are loaded.. We still can proceed, if (and only if) they are not changed.
            if ($_fieldsAnyChanges) {
                $this->addError($keyName,
                    "Some relatedFields required for sorting module are not loaded (" . implode(",",
                        $_fieldsNotLoaded) . "). Can't proceed.");
                return false;
            }
        }
        if (!$_fieldsAnyChanges) {
            # echo ": * No relatedFields changed. We can stop here.<br />";
            return array();
        }

        // Build WRn (Must be called BEFORE any getMax() usage)
        // However, it should be called after ensuring that all aliases are loaded.
        $WRn = self::sortingGetRelated($this, false, $keyName);

        // Ensure that getOriginal($keyName) has the most up-to-date value, even after
        // other objects updates.
        if ($this->isLoaded() && $_ensureConsistency && $this->getOriginal($keyName) !== false) {
            if (isset(dDbRow3Plus::$sortingCache[$className][$WRn ? $WRn : '---'])) {
                # echo ": Checagem de consistência (after-update)<br />";
                $curPos = $db->singleResult("select {$keyName} from {$tableName} where id='{$this->v($primaryKey)}'");
                if ($curPos != $this->getOriginal($keyName)) {
                    # echo ": <i>Detectei possível inconsistência, atualizando de {$this->getOriginal($keyName)} para {$curPos}...</i><br />";
                    if (!isset($this->extra['fieldOriginal'])) {
                        $this->extra['fieldOriginal'] = array();
                    }

                    $this->extra['fieldOriginal'][$keyName] = self::sModApply('db2basic', $keyName, $curPos, $this);
                }
            }
            # else{
            # echo "-- Não devo revisar a consistência ('$className:$WRn' inexiste)<br />";
            # }
        }

        // Make sure the 'ordem' value is a valid number (or false).
        $new = $this->v($keyName);
        if ($new === false) {
            $new = $this->isLoaded() ? false : 'MAX';
        }
        if (strtoupper($new) == 'EMPTY') {
            $new = false;
        }
        if ($new !== false && !is_numeric($new)) {
            $new = 'MAX';
        }
        if (strtoupper($new) == 'MAX') {
            $new = $getMax() + 1;
        }
        if ($new !== false && intval($new) < 1) {
            $new = 1;
        }
        $this->setValue($keyName, $new);

        // Default actions:
        $_shared = array(
            'obj' => $this,
            'className' => $className,
            'getMax' => &$getMax,
            'tableName' => &$tableName,
            'keyName' => &$keyName,
            'WRn' => &$WRn,
            '_max' => &$_max,
            '_ensureConsistency' => &$_ensureConsistency,
        );
        $_doAllocate = function () use ($_shared) {
            extract($_shared, EXTR_REFS);

            $newPos = $obj->getValue($keyName);
            # echo "<b>_doAllocate()</b><br />";
            # echo "<table border='1' style='font-size: 10px'>";
            # echo "<tr><td>newPos:</td><td>{$newPos}</td></tr>";
            # echo "<tr><td>WRn:</td><td>{$WRn}</td></tr>";
            # echo "<tr><td>_max:</td><td>".($_max?$_max:"not-loaded")."</td></tr>";
            # echo "<tr><td>EnsureConsistency:</td><td>".($_ensureConsistency?"Yes":"No")."</td></tr>";
            # echo "</table>";
            if (!$newPos) {
                trigger_error("dDbRow3/Sorting/Allocate: Can't allocate element on an empty position.");
                return false;
            }

            $max = ($_ensureConsistency || $_max !== false) ? $getMax() : false;
            // $max = (false|0|integer)

            if ($max !== false && $newPos >= $max + 1) {
                # echo ":: Inserindo na última posição (newPos={$newPos}), não preciso atualizar mais nada.<br />";
                $obj->setValue($keyName, $getMax() + 1);
                return array();
            }

            # echo ":: Inserindo na posição desejada (newPos={$newPos}).<br />";
            dDbRow3Plus::$sortingCache[$className][$WRn ? $WRn : '---'] = true;
            return array(
                "/* Allocate */ update {$tableName} set " . $obj->_sortingGetQuery(false, $newPos,
                    $keyName) . ($WRn ? " and {$WRn}" : "")
            );
        };
        $_doDeallocate = function ($original) use ($_shared) {
            extract($_shared, EXTR_REFS);

            # echo "<b>_doDeallocate(original=".($original?'true':'false').")</b><br />";
            # echo ":: Use getOriginal instead of getValue? ".($original?'yes':'no')."<br />";

            $WR = ($original) ?
                $className::sortingGetRelated($obj, true, $keyName) :
                $WRn;

            $oldPos = $obj->getOriginal($keyName);
            if (!$oldPos) {
                trigger_error("dDbRow3/Sorting/Deallocate: Can't deallocate without knowing the correct position (!oldPos)");
                return array();
            }

            # echo ":: * Desalocando.<br />";
            dDbRow3Plus::$sortingCache[$className][$WR ? $WR : '---'] = true;
            return array(
                "/* Deallocate */ update {$tableName} set " . $obj->_sortingGetQuery($oldPos, false,
                    $keyName) . ($WR ? " and {$WR}" : "")
            );
        };
        $_doUpdatePos = function () use ($_shared) {
            extract($_shared, EXTR_REFS);

            $oldPos = $obj->getOriginal($keyName);
            $newPos = $obj->getValue($keyName);
            # echo "<b>_doUpdatePos()</b><br />";
            # echo "<table border='1'>";
            # echo "<tr><td>oldPos</td><td>{$oldPos}</td></tr>";
            # echo "<tr><td>newPos</td><td>{$newPos}</td></tr>";
            # echo "<tr><td>_max:</td><td>".($_max?$_max:"not-loaded")."</td></tr>";
            # echo "<tr><td>EnsureConsistency:</td><td>".($_ensureConsistency?"Yes":"No")."</td></tr>";
            # echo "</table>";
            # dHelper2::dump($obj);

            if (($_ensureConsistency || $_max) && $newPos > $getMax()) {
                # echo ":: * Barbeiragem detectada: newPos={$newPos}, só que a última posição seria {$getMax()}. Alterando.<br />";
                $newPos = $getMax();
                $obj->setValue($keyName, $newPos);
            }
            if ($oldPos == $newPos) {
                # echo ":: * No changes, won't update anything.<br />";
                return array();
            }
            if (!$oldPos || !$newPos) {
                trigger_error("dDbRow3/Sorting/UpdatePos: oldPos({$oldPos}) and newPos({$newPos}) must both be positive. If not, use _doAllocate or _doDeallocate.<br />");
                return array();
            }

            dDbRow3Plus::$sortingCache[$className][$WRn ? $WRn : '---'] = true;
            return array(
                "/* _doUpdatePos() */ update {$tableName} set " . $obj->_sortingGetQuery($oldPos, $newPos,
                    $keyName) . ($WRn ? " and {$WRn}" : "")
            );
        };

        // If object is not loaded (nor allocated) and has a position informed, place it in the correct position.
        if (!$this->isLoaded() || !$this->getOriginal($keyName)) {
            if ($this->getValue($keyName)) {
                return $_doAllocate();
            }

            return array();
        }

        // If sortingKeys changes, deallocate from old group, and allocate on the new group.
        if ($this->_sortingKeysChanged($keyName)) {
            $ret = array();
            if ($this->getOriginal($keyName)) {
                $ret = array_merge($ret, $_doDeallocate(true));
            }
            if ($this->getValue($keyName)) {
                $ret = array_merge($ret, $_doAllocate());
            }
            return $ret;
        }

        // If preparing to delete.. (dis-associate)
        if ($this->getOriginal($keyName) && !$this->getValue($keyName)) {
            $this->setValue($keyName, false, 'basic');
            return $_doDeallocate(false);
        }

        // Otherwise, just move!
        return $_doUpdatePos();
    }

    function save()
    {
        if (!$this->sortingIsEnabled()) {
            return parent::save();
        }
        # echo "<div style='border: 1px solid #888; padding: 5px; margin: 5px 0px; background: #EEE' title='dDbRow3Plus::save()'>";
        # echo "<div style='font-size: small; border-bottom: 1px solid #666'><span style='float: right; font-size: 9px'>dDbRow3Plus::save()</span>Título={$this->v('titulo')}</div>";
        $afterQuery = $this->_sortingHandle();
        if ($afterQuery === false) {
            return false;
        }

        $saveOk = parent::save();
        if ($saveOk && $afterQuery) {
            $db = self::getDb();
            $primaryKey = self::structGet('primaryKey');
            foreach ($afterQuery as $sqlQuery) {
                $db->query($sqlQuery .= " and {$primaryKey} != " . self::formatToQuery($saveOk, $primaryKey),
                    "Query built by sorting module");
            }
        }
        # echo "</div>";

        return $saveOk;
    }

    private function _delete()
    {
        if (!$this->isLoaded()) {
            return false;
        }

        // Remove todas as possíveis fotos existentes
        for ($x = 1; $x <= $this->getFotoLimit(); $x++) {
            $this->setFoto(false, $x);
        }

        // Organiza as posições dos objetos que não serão removidos
        $afterQuery = array();
        if ($this->sortingIsEnabled()) {
            foreach (array_keys(self::structGet('modules', 'sorting')) as $keyName) {
                if ($this->isAliasEnabled($keyName)) {
                    $this->setValue($keyName, false);
                }
            }
            $afterQuery = $this->_sortingHandle();
        }

        if ($this->setFileIsEnabled()) {
            $this->setFile(false);
        }

        $delOk = parent::__call('delete', array());
        if ($delOk && $afterQuery) {
            $db = self::getDb();
            $primaryKey = self::structGet('primaryKey');
            foreach ($afterQuery as $sqlQuery) {
                $db->query($sqlQuery);
            }
        }
        return $delOk;
    }

    // Permite o overloading (compatível com dDbRow3)
    function __call($method, $params)
    {
        if ($method == 'delete') {
            return $this->_delete();
        }
        return parent::__call($method, $params);
    }
}

