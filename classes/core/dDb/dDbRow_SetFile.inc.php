<?php
// A ser desenvolvido no futuro.

class dDbRow_SetFile extends dDbRow
{
    // Anexar arquivo (coluna 'filename')
    function setFile($filename, $Ofilename = false)
    {
        global $_BaseDir;

        if (!$this->getPrimaryValue()) {
            return false;
        }

        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = "{$_BaseDir}/dat/{$cl}-{$id}.dat";

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
        $this->setValue('filename', $Ofilename);
        $ok2 = $this->flushUpdate();

        if (!$ok2) {
            $this->addError(false, "Nome de arquivo não foi aceito, renomeie o arquivo e tente novamente.");
            unlink($fn);
            return false;
        }

        return true;
    }

    function downloadFile($getAsBool = false)
    {
        global $_BaseDir;

        $cl = get_class($this);
        $id = $this->getPrimaryValue();
        $fn = "{$_BaseDir}/dat/{$cl}-{$id}.dat";

        if (!$this->getValue('filename')) {
            return false;
        }

        if ($getAsBool) {
            return file_exists($fn);
        }

        if (!file_exists($fn)) {
            die("Arquivo está cadastrado e passou no teste de verificação, mas não existe no servidor! Entre em contato conosco informando este erro.");
        }

        // Evita qualquer tipo de compactação e/ou informações indesejadas...
        @ob_end_clean();

        $filesize = filesize($fn);
        $filename = $this->getValue('filename');
        $filename = strtr($filename,
            "\xe1\xc1\xe0\xc0\xe2\xc2\xe4\xc4\xe3\xc3\xe5\xc5\xaa\xe7\xc7\xe9\xc9\xe8\xc8\xea\xca\xeb\xcb\xed\xcd\xec\xcc\xee\xce\xef\xcf" .
            "\xf1\xd1\xf3\xd3\xf2\xd2\xf4\xd4\xf6\xd6\xf5\xd5\x8\xd8\xba\xf0\xfa\xda\xf9\xd9\xfb\xdb\xfc\xdc\xfd\xdd\xff\xe6\xc6\xdf %",
            "aAaAaAaAaAaAacCeEeEeEeEiIiIiIiInNoOoOoOoOoOoOoouUuUuUuUyYyaAs__");

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
        global $_BaseDir;

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
        $fn = "{$_BaseDir}/dat/{$cl}-{$id}.dat";

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
                $linkTo = isset($this->fieldValues['link']) ?
                    $this->fieldValues['link'] :
                    false;
            }
            if ($altText == 'auto') {
                $altText = isset($this->fieldValues['titulo']) ?
                    $this->fieldValues['titulo'] :
                    false;
            }
        }

        $filename = strtr($this->getValue('filename'),
            "\xe1\xc1\xe0\xc0\xe2\xc2\xe4\xc4\xe3\xc3\xe5\xc5" .
            "\xaa\xe7\xc7\xe9\xc9\xe8\xc8\xea\xca\xeb\xcb\xed" .
            "\xcd\xec\xcc\xee\xce\xef\xcf\xf1\xd1\xf3\xd3\xf2" .
            "\xd2\xf4\xd4\xf6\xd6\xf5\xd5\x8\xd8\xba\xf0\xfa" .
            "\xda\xf9\xd9\xfb\xdb\xfc\xdc\xfd\xdd\xff\xe6\xc6\xdf %",
            "aAaAaAaAaAaAacCeEeEeEeEiIiIiIiInNoOoOoOoOoOoOoouUuUuUuUyYyaAs__");
        $embedFilename = "{$viewFile}/{$fakeClass}/{$this->getPrimaryValue()}/" . ($settings['forceDownload'] ? 'fd/' : '') . "{$filename}";
        if ($settings['onlyLink']) {
            return $embedFilename;
        }

        if ($tipo == 'image') {
            $str = "";
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
            $str = "";
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

    function deleteFromDatabase()
    {           // Por todos os módulos
        // Excluir arquivo anexo
        $this->setFile(false);

        return parent::deleteFromDatabase();
    }

}
