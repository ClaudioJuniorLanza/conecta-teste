<?php
/**
 * dAL - Classe principal para controle do painel:
 * layTop:    NavBarTitle, MenuSelectedId, Extra_Style
 * layBottom: (void)
 * layTitle:  Title, MenuSelectedId
 * goBack:    BackToUrl (ou 'auto'), ExtraItensOnTop ($_SESSION['admGoBackTo'] = Array(link, title)
 * errorBox:  ErrorList
 * succBox:   SuccessList
 * boxes:     ErrorList, SuccessList
 **/

class dAL
{
    static $titleWasWritten = false;
    static $settings = array();

    static function layTop($settings = array())
    {
        if (!is_array($settings)) {
            dSystem::notifyAdmin('HIGH', "Inconsistência no painel administrativo",
                "layTop PRECISA ser chamado com parâmetros corretos:<br />" .
                "menuSel, bodyTitle, pageTitle, saveGoBack, baseHref, leftMenu, breadCrumbs",
                true
            );
        }

        self::$settings = &$settings;
        $settings += array(
            'menuSel' => false,
            'bodyTitle' => false,
            'goBack' => [
                // 'true'         --> Equivale a dAL::goBack('auto').
                // "Outra string" --> Equivale a goBack(false, "Outra String").
            ],
            'pageTitle' => false,
            'saveGoBack' => false,
            'baseHref' => false,
            'leftMenu' => true,
            'breadCrumbs' => true,
            'isSetup' => false,
        );
        if ($settings['bodyTitle'] && !$settings['pageTitle']) {
            $settings['pageTitle'] = strip_tags($settings['bodyTitle']);
        }
        if ($settings['pageTitle'] && !$settings['bodyTitle']) {
            $settings['bodyTitle'] = $settings['pageTitle'];
        }
        extract($settings, EXTR_REFS);

        if ($saveGoBack) {
            self::saveGoBack(strip_tags($pageTitle));
        }
        if ($breadCrumbs === true) {
            $breadCrumbs = array(true);
        }

        $db = dDatabase::start();
        $usuarObj = dUsuario::isLogged();
        $masterObj = dUsuario::$cloakedMaster ? dUsuario::$cloakedMaster : $usuarObj;
        if ($masterObj && $masterObj->checkPerms('USER_LOGIN_AS')) {
            $dropLoginList = $masterObj->checkPerms('USER_MANAGE_ALL') ?
                $db->singleQuery("select id,username from d_usuarios where deleted='0' order by username") :
                $db->singleQuery("select id,username from d_usuarios where deleted='0' and '{$masterObj->getPrimaryValue()}' IN (usuar_id, id) order by username");
        }
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <title><?= $pageTitle ? "{$pageTitle} - " : "" ?>Área Administrativa</title>
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <? if ($baseHref): ?>
                <base href="<?= $baseHref ?>"/>
            <? endif ?>
            <?= dResLoader::writeInclude('jquery', array(
                'jquery-1.8.3',
                'jquery-dRowDrag',
                'jquery-dEip',
                'jquery.fancybox.pack',
                'jquery-dInput2',
                'jquery.color-2.1.2.min',
                'jquery-dInput2',
                'jquery-dClickOutside',
                'jquery-coookie',
                'jquery-dHelper2',
            ), array('relPath' => '../')); ?>
            <?= dResLoader::writeInclude('css', 'jquery.fancybox,font-awesome', array('relPath' => '../')); ?>
            <?= dResLoader::writeInlineOnceThenRenderblock('css', 'estilo'); ?>
            <? if (dSystem::getGlobal('localHosted')): ?>
                <?= dResLoader::writeInclude('jquery', 'jquery-dEasyRefresh', '..'); ?>
                <script> $(function () {
                        dEasyRefresh.relPath = '../';
                    }); </script>
            <? endif ?>
        </head>

        <body>

        <div class='barTop noprint'>
            <a href="index.php"><img src="images/logo-conecta.png"/></a>
            <div class='dropUsers' style="float: right; height: 40px; border-left: 1px solid #999; padding: 0px 10px">
                <? if ($usuarObj): ?>
                    <a href="#" class="toggleNext"
                       style="display: block; height: 40px; padding: 0px 10px; line-height: 40px"><?= ucfirst($usuarObj->v('username')) ?>
                        <i class='fa fa-caret-down'></i></a>
                    <div style="display: none" class='container'>
                        <? if ($masterObj->checkPerms('USER_LOGIN_AS') && sizeof($dropLoginList) > 1): ?>
                            <div style='padding: 5px'>
                                Simular login como:<br/>
                            </div>
                            <? foreach ($dropLoginList as $item): ?>
                                <a href='#'
                                   onclick="templateQuickLoginAs(<?= $item['id'] ?>); return false" <?= ($item['id'] == $usuarObj->v('id') ? " class='active'" : '') ?>>
                                    <?= ucfirst($item['username']) ?>
                                    <? if ($masterObj->v('id') != $usuarObj->v('id') && $masterObj->v('id') == $item['id']): ?>
                                        <small>Você</small>
                                    <? endif ?>
                                </a>
                            <? endforeach ?>
                        <? endif ?>
                        <a href='logout.php' class='logout'><span class='fa fa-sign-out'></span> Log-out</a>
                    </div>
                    <script>
                        $(function () {
                            $(".dropUsers>.container").each(function () {
                                var $t = $(this);
                                $t.dClickOutside(function () {
                                    $t.fadeOut();
                                }, {ignoreList: 'a'});
                            });
                        });

                        function templateQuickLoginAs(id) {
                            $.post('ajax.template.php', {action: 'fakeLogin', fakeId: id}, function (ret) {
                                if (ret == 'CLOAKED' || ret == 'BACK-TO-NORMAL') {
                                    window.location.reload();
                                } else {
                                    alert(ret);
                                }
                            });
                        }
                    </script>
                <? else: ?>
                    <span style='line-height: 40px'>Modo de setup.</span>
                <? endif ?>
            </div>
        </div>
        <table cellpadding='0' cellspacing='0' width='100%'>
        <tr valign='top'>
        <? if ($leftMenu): ?>
        <td class='leftMenu noprint'>
            <div class='title'><?= dConfiguracao::getConfig('CORE/NOME_DO_SITE'); ?></div>
            <ul style='margin-bottom: 25px'>
                <?php
                require_once "template_menu.php";
                $_useIcon = function ($item, $level) {

                    if ($item['icon']) {
                        return (substr(ltrim($item['icon']), 0, 3) == 'fa-') ?
                            "<i class='fa {$item['icon']} fa-fw'></i>" :
                            "<img src='{$item['icon']}' />";
                    }
                    if ($level == 0) {
                        return "<i class='fa " . ($item['subs'] ? 'fa-folder' : 'fa-file-o') . " fa-fw'></i>";
                    }
                    if ($level == 1) {
                        return "<i class='fa " . ($item['subs'] ? 'fa-folder-o' : 'fa-file') . " fa-fw'></i>";
                    }
                    if ($level == 2) {
                        return "<i class='fa " . ($item['subs'] ? 'fa-folder' : 'fa-file') . " fa-fw'></i>";
                    }
                };
                foreach ($groupMenu as $item) {
                    echo "<li>";
                    echo $item['subs'] ?
                        "<a href='#'><span class='fa fa-caret-down' style='font-size: 12px; color: #000'></span>" :
                        "<a href='{$item['link']}'>";
                    echo $_useIcon($item, 0) . " {$item['title']}";
                    echo "</a>";
                    if ($item['subs']) {
                        echo "<ul style='display: none'>";
                        foreach ($item['subs'] as $subItem) {
                            echo "<li>";
                            echo $subItem['subs'] ?
                                "<a href='#'><span class='fa fa-caret-down' style='font-size: 12px; color: #000'></span>" :
                                "<a href='{$subItem['link']}'>";
                            echo $_useIcon($subItem, 1) . " {$subItem['title']}";
                            echo "</a>";
                            if ($subItem['subs']) {
                                echo "<ul style='display: none'>";
                                foreach ($subItem['subs'] as $subSubItem) {
                                    echo "<li>";
                                    echo $subSubItem['subs'] ?
                                        "<a href='#'><span class='fa fa-caret-down' style='font-size: 12px; color: #000'></span>" :
                                        "<a href='{$subSubItem['link']}'>";
                                    echo $_useIcon($subSubItem, 2) . "{$subSubItem['title']}";
                                    echo "</a>";
                                    echo "</li>";
                                }
                                echo "</ul>";
                            }
                            echo "</li>";
                        }
                        echo "</ul>";
                    }
                    echo "</li>";
                }
                ?>
            </ul>

            <script type='text/javascript'>
                var imaginaMenu = function () {
                };
                imaginaMenu.init = function () {
                    imaginaMenu.allContainers = $("ul", ".leftMenu");
                    imaginaMenu.allLinks = $("li>a", ".leftMenu");
                };
                imaginaMenu.setActive = function (jqoLink) {
                    imaginaMenu.allLinks.not(jqoLink).removeClass('sel');
                    jqoLink.addClass('sel');
                    $.cookie('imaginaMenu-active', imaginaMenu.allLinks.index(jqoLink), {path: '/'});
                };
                imaginaMenu.saveState = function () {
                    var idxOpen = [];
                    imaginaMenu.allContainers.each(function (idx) {
                        if ($(this).is(":visible"))
                            idxOpen.push(idx);
                    });
                    $.cookie('imaginaMenu-state', idxOpen.join("."), {path: '/'});
                };
                imaginaMenu.loadState = function () {
                    var selIdx = $.cookie('imaginaMenu-active');
                    var state = $.cookie('imaginaMenu-state');
                    if (selIdx != null && selIdx != -1) {
                        $(imaginaMenu.allLinks.get(selIdx)).addClass('sel');
                    }
                    if (state != null) {
                        state = state.split(".");
                        for (var x = 0; x < state.length; x++)
                            $(imaginaMenu.allContainers.get(state[x])).show();
                    }
                };
                $(function () {
                    $(".leftMenu").find("a").each(function () {
                        var t = $(this);
                        if (t.siblings().is("ul")) {
                            t.click(function () {
                                t.siblings().slideToggle(imaginaMenu.saveState);
                                if (t.attr('href') == '#')
                                    return false;

                                return true;
                            });
                        } else {
                            t.click(function () {
                                var t = $(this);
                                imaginaMenu.setActive(t);
                                return true;
                            });
                        }
                    });

                    imaginaMenu.init();
                    imaginaMenu.loadState();
                });
            </script>

        </td>
    <? endif ?>
        <td>
        <? if ($breadCrumbs && is_array($breadCrumbs)): ?>
        <ul id='nav-info' class='noprint' style='height: 31px; margin: 0; padding: 0'>
            <li><a href="index.php"><i class='fa fa-home'></i></a></li>
            <?php
            for ($x = 0; $x < sizeof($breadCrumbs); $x++) {
                echo "<li " . (($x == sizeof($breadCrumbs) - 1) ? "class='active'" : "") . ">";
                echo ($breadCrumbs[$x] === true) ?
                    $bodyTitle :
                    $breadCrumbs[$x];
                echo "</li>";
            }
            ?>
        </ul>
    <? endif ?>
        <div class='bodyTitle'>
            <h1><?= $bodyTitle ?></h1>
            <?
            foreach ($goBack as $_goBackItem) {
                echo ($_goBackItem === true) ?
                    dAL::goBack('auto', false, true) :
                    dAL::goBack(false, $_goBackItem, true);
            }
            ?>
        </div>
        <div class='mainBody'>
        <?php
    }

    static function layBottom()
    {
        ?>
        </div>
        <div style="border-top: 1px solid #ccc; font-size: 12px; padding: 5px; text-align: right; background: #EEE"
             class='noprint'>
            Divisão de softwares <a href="http://www.imaginacom.com/">IMAGINACOM</a>
        </div>
        </td>
        </tr>
        </table>
        <script type='text/javascript'>
            $(function () {
                $.fn.fancybox ?
                    $(".fancybox").fancybox() :
                    null;

                // A cada 5 minutos, faça um keep-alive.
                // Aproveite para verificar propostas expiradas.
                var ttl = 5 * 60 * 1000; // 5 minutos
                setInterval(function () {
                    $.post("ajax.template.php", {action: 'keepAlive'}, function (ret) {
                        // Keep alive OK.
                    });
                }, ttl);

                // Ativa o funcionamento da classe .toogleNext
                // Usage:
                //     <a href="#" class="toggleNext">Exibir/esconder</a>
                //     <div>Hello world</div>
                var _toggleNext = function (jqoRef, skipAnimation) {
                    var isCbox = (jqoRef.tagName.toLowerCase() == 'input' && jqoRef.type.toLowerCase() == 'checkbox');
                    var jqoNext = _findNext($(jqoRef));
                    var jqoPare = $(jqoRef).parent();
                    while (!jqoNext.length && jqoPare.length) {
                        jqoNext = _findNext(jqoPare);
                        jqoPare = jqoPare.parent();
                    }
                    if (!jqoNext.length) {
                        return false;
                    }

                    if (isCbox) {
                        if (skipAnimation) {
                            jqoRef.checked ?
                                jqoNext.show() :
                                jqoNext.hide();
                        } else {
                            jqoRef.checked ?
                                jqoNext.slideDown() :
                                jqoNext.slideUp();
                        }

                        return true;
                    }

                    skipAnimation ?
                        jqoNext.toggle() :
                        jqoNext.slideToggle();

                    return false;
                };
                var _findNext = function (jqoRef) {
                    var jqoNext = jqoRef.next();
                    while (jqoNext.is("br,label,span") && jqoNext.length) {
                        jqoNext = jqoNext.next();
                    }

                    return jqoNext;
                };
                $(".toggleNext").filter("input:checkbox").each(function () {
                    _toggleNext(this, true);
                });
                $(".toggleNext").click(function () {
                    _toggleNext(this);
                });

                // Ativa o funcionamento da classe .moreOptionsBtn
                // Usage:
                //     <button class='moreOptionsBtn' title="Mais opções"><i class='fa fa-align-justify'></i></button>
                //     <div class='moreOptionsPopup'><a href='#'>...</a></div>
                $(".moreOptionsBtn").click(function () {
                    $(this).closest('div').find('.moreOptionsPopup').stop().slideToggle();
                    return false;
                });
                $(".moreOptionsPopup").each(function () {
                    var _myTimer = false;
                    var $t = $(this);
                    $t.mouseover(function () {
                        if (_myTimer)
                            clearTimeout(_myTimer);
                    });
                    $t.mouseout(function () {
                        if (_myTimer)
                            clearTimeout(_myTimer);

                        _myTimer = setTimeout(function () {
                            $t.slideUp();
                        }, 500);
                    });
                    $t.dClickOutside({
                        ignoreList: $(".moreOptionsBtn"),
                    }, function () {
                        $(this).stop().slideUp();
                        if (_myTimer) clearTimeout(_myTimer);
                    });
                });
            });
        </script>
        </body>
        </html>
        <?php
    }

    static function layTitle($title, $selected = false)
    {
        if (self::$titleWasWritten) {
            echo "<script> $(document).ready(function(){ $('#layTop_layTitle').hide(); }) </script>";
            self::$titleWasWritten = false;
        }

        echo "<div class='title'>$title</div>\n";
        if ($selected) {
            echo "<script type='text/javascript'>\n<!--\nd.openTo($selected, true);\n//-->\n</script>\n";
        }
    }

    static function saveGoBack($backStr)
    {
        $_SESSION['admGoBackTo'] = array($_SERVER['REQUEST_URI'], $backStr);
    }

    static function goBack($goBack = 'auto', $xtra = false, $getAsRet = false)
    {
        // $xtra pode ser uma string, ou um array com várias strings.
        if ($goBack == 'auto' && isset($_SESSION['admGoBackTo'])) {
            $goBack = $_SESSION['admGoBackTo'];
        } elseif ($goBack && $goBack != 'auto') {
            $goBack = explode("|", $goBack);
        } else {
            $goBack = false;
        }

        if ($xtra && !is_array($xtra)) {
            $xtra = array($xtra);
        }

        if ($goBack) {
            $goBackStr = "Voltar para <a href='{$goBack[0]}'><b>{$goBack[1]}</b></a>";
            if ($xtra) {
                $xtra = array_merge(array($goBackStr), $xtra);
            } else {
                $xtra = array($goBackStr);
            }
        }
        if ($xtra) {
            $writeHtml = "<div class='goBack noprint'><i class='fa fa-angle-right'></i> " . implode(" | ",
                    $xtra) . "</div>";
            if ($getAsRet) {
                return $writeHtml;
            }
            echo $writeHtml;
            echo "<script>\r\n";
            echo "$(function(){\r\n";
            echo "	$('.bodyTitle').append($('.goBack'));\r\n";
            echo "});\r\n";
            echo "</script>\r\n";
        }
    }

    static function getGoBack()
    {
        if (isset($_SESSION['admGoBackTo'])) {
            return $_SESSION['admGoBackTo'][0];
        }
        return false;
    }

    static function handleSaveAnd($addNewUrl = 'auto', $goBackUrl = 'auto')
    {
        if (!isset($_POST['_saveAnd'])) {
            return false;
        }

        if ($_POST['_saveAnd'] == 'goBack') {
            $goBackTo = ($goBackUrl != 'auto') ?
                $goBackUrl :
                dAL::getGoBack();

            if (!$goBackTo) {
                return false;
            }

            dHelper2::redirectTo($goBackTo);
            die;
        }
        if ($_POST['_saveAnd'] == 'addNew') {
            $addNew = ($addNewUrl != 'auto') ?
                $addNewUrl :
                $_SERVER['PHP_SELF'];

            dHelper2::redirectTo($addNew);
            die;
        }
    }

    static function errorBox($errorList)
    {
        if ($errorList) {
            echo "<div style='border: 1px solid #888; background: #FEE; padding: 15px; margin-bottom: 15px; float: left; position: relative'>";
            echo "<i class='fa fa-exclamation-triangle' style='color: #C00; font-size: 24px; position: absolute; bottom: -10px; right: -5px;'></i> ";
            echo implode("<br />", $errorList);
            echo "<br style='clear: both' />";
            echo "</div>";
            echo "<br style='clear: both' />";
        }
    }

    static function succBox($succList)
    {
        if ($succList) {
            echo "<div style='border: 1px solid #888; background: #CFC; padding: 15px; margin-bottom: 15px; float: left; position: relative'>";
            echo "<i class='fa fa-check' style='color: #080; font-size: 24px; position: absolute; bottom: -10px; right: -5px;'></i> ";
            echo implode("<br />", $succList);
            echo "<br style='clear: both' />";
            echo "</div>";
            echo "<br style='clear: both' />";
        }
    }

    static function boxes($er, $su)
    {
        if ($er && $su) {
            $su[] = "<small style='font-style: italic'>* Apenas parte da solicitação foi bem sucedida. Resolva os problemas apontados abaixo e tente novamente.</small>";
        }
        self::succBox($su);
        self::errorBox($er);
    }
}

/**
 * dALCampo - Formato padrão:
 * Start: Título
 * Drop: Descrição, Nome do Campo, [callback], [Texto à direita]
 * Text: Descrição, Nome do Campo, [size], [max], [Texto à direita]
 * Area: Descrição, Nome do Campo, [linhas], [Texto à direita]
 * CBox: Descrição, Nome do Campo, [valor], [checked], [Texto à direita]
 * Imag: Descrição, Nome do Campo, [Texto à direita], [delete_name], [exibir existente=0,1,2], [existente path]
 * Read: Descrição, Nome do Campo, [Texto vazio]
 * Finish: (void)
 **/
class dALCampo
{
    static $useClass = 'default';

    static function Start($descricao = false, $noBorderTop = false)
    {
        echo "<table width='100%' cellpadding='0' cellspacing='0' border='1' class='dALCampo'>\n";
        if ($descricao) {
            echo "<tr><td colspan='2' class='start'>$descricao</td></tr>\n";
        }
    }

    static function Drop($descricao, $nome, $callback = false, $texto = false)
    {
        echo "<tr>\n";
        echo "<td class='descricao'>$descricao</td>\n";
        echo "<td class='edicao'>\n";

        if (is_array($callback)) {
            $lista = $callback;
        } elseif (strpos($callback, ":") !== false) {
            // tabela:id:nome:where
            $parts = explode(":", $callback);
            $db = dDatabase::start();
            $lista = $db->singleQuery("select {$parts[1]}, {$parts[2]} from {$parts[0]}" . (isset($parts[3]) ? " where {$parts[3]}" : ""));
            unset($parts, $db);
        } elseif (strpos($callback, ",") !== false) {
            // opcao1=Desc,opcao2=Desc,opcao3=Desc
            $lista = array();
            $tmp = explode(",", $callback);
            foreach ($tmp as $item) {
                $tmp2 = explode("=", $item);
                $lista[] = array(trim($tmp2[0]), trim($tmp2[1]));
            }
            unset($tmp, $tmp2);
        } elseif (function_exists($callback)) {
            $lista = call_user_func($callback);
        } else {
            $lista = array();
            echo "{Impossível gerar lista: $callback}";
        }

        $topItem = array(array('', '-- Selecione: --'));
        $lista = array_merge($topItem, $lista);
        // echo "<pre>".print_r($lista, true)."</pre>";

        echo dInput2::select("name='{$nome}' id='drop-{$nome}'", $lista, self::getValue($nome));
        if ($texto) {
            echo " <small>$texto</small>";
        }
        echo "</td>\n";
        echo "</tr>\n";
    }

    static function Text($descricao, $nome, $size = 25, $max = false, $texto = false)
    {
        echo "<tr>\n";
        echo "<td class='descricao'>$descricao</td>\n";
        echo "<td class='edicao'>\n";
        switch ($size) {
            case 'datetime':
            case 'date':
                echo dInput2::input("name='{$nome}'", self::getValue($nome), $size);
                break;
            default:
                echo dInput2::input("name='{$nome}' size='{$size}' " . ($max ? "maxlength='{$max}' " : ""),
                    self::getValue($nome));
        }
        if ($texto) {
            echo "<small>$texto</small>";
        }
        echo "</td>\n";
        echo "</tr>\n";
    }

    static function Area($descricao, $nome, $linhas = 3, $texto = false)
    {
        echo "<tr>\n";
        $spanMe = true;
        if ($descricao || $texto) {
            echo "<td class='descricao'>$descricao";
            if ($texto) {
                echo "<br /><small>$texto</small>";
            }
            echo "</td>\n";
            $spanMe = false;
        }
        echo "<td class='edicao' " . ($spanMe ? "colspan='2'" : "") . ">\n";
        echo dInput2::textarea("style='width: 100%' name='$nome' rows='$linhas'", self::getValue($nome));
        echo "</td>\n";
        echo "</tr>";
    }

    static function CBox($descricao, $nome, $valor = '1', $checked = null, $texto = "Clique para selecionar")
    {
        echo "<tr>\n";
        echo "<td class='descricao'>\n";
        echo "$descricao";
        echo "</td>\n";
        echo "<td class='edicao'>\n";
        if ($checked === null) {
            $checked = self::getValue($nome);
        }

        echo dInput2::checkbox("name='$nome' value='$valor' class='noborder'", $checked, $texto);

        echo "</td>\n";
        echo "</tr>\n";
    }

    static function Foto($descricao, $nome, $texto = false, $showkey = 'fnt', $n = 1)
    {
        $a = self::getClass();
        return self::Imag(
            $descricao,
            $nome,
            $texto,
            'del_' . $nome,
            ($a->getFoto('../', 'fn', $n) ? 1 : false),
            ($a->getFoto('../', $showkey, $n) . "?seed=" . time()),
            ($a->getFoto('../', 'fno', $n) ? $a->getFoto('../', 'fno', $n) . "?seed=" . time() : false)
        );
    }

    static function Imag(
        $descricao,
        $nome,
        $texto = false,
        $deletename = false,
        $exibir = false,
        $path = false,
        $link = false
    ) {
        echo "<tr>\n";
        echo "<td class='descricao'>$descricao";
        if ($texto) {
            echo "<br /><small>$texto</small>";
        }
        echo "</td>";
        echo "<td class='edicao'>";
        echo "<input type='file' name='$nome' />";
        if ($exibir == 1) { // Inline
            if ($deletename) {
                echo " ";
                echo dInput2::checkBox("name='$deletename' class='noborder'", false, "Excluir arquivo existente");
            }
            echo "<center>";
            if ($link) {
                echo "<a href='$link' target='_blank' class='fancybox'>";
            }
            echo "<img src='$path' alt='' style='border: 0' />";
            if ($link) {
                echo "</a>";
            }
            echo "</center>";
        } elseif ($exibir == 2) { // Link externo
            echo " <a href='$path' target='_blank'>(Ver arquivo atual)</a><br />";
            if ($deletename) {
                echo dInput2::checkBox("name='$deletename' class='noborder'", false, "Excluir arquivo");
            }
        }
        echo "</td>";
        echo "</tr>";
    }

    static function Read($descricao, $nome, $texto = false)
    {
        echo "<tr>\n";
        echo "<td class='descricao'>$descricao</td>";
        echo "<td class='edicao'>";
        $val = $nome ? self::getValue($nome) : false;

        // Se nome E texto, aparece TEXTO quando não houver nome
        if ($nome && $texto) {
            echo $val ? $val : $texto;
        } elseif ($nome && !$texto) {
            echo $val ? $val : '---';
        } elseif (!$nome && $texto) {
            echo $texto;
        } else {
            echo "Nada para exibir.";
        }
        echo "</td>";
        echo "</tr>\n";
    }

    static function Rich($descricao, $nome, $linhas = 6, $toolbar = 3, $texto = false, $style = array())
    {
        global $a, $__dALCampoRTEJS;

        $settings = dHelper2::addDefaultToArray($style, array(
            // * Qualquer outra propriedade enviada em $style será passada diretamente para o rte_default.css.php via $_GET
            'font' => '',
            // Fonte padrão. Ex: 11pt Arial
            'background' => '',
            // Cor do fundo. Ex: FFF
            'color' => '',
            // Cor do texto. Ex: 000
            'link' => '',
            // Cor do link.  Ex: 00F
            'width' => '',
            // Largura do texto no editor. Ex: '600px'
            'margin' => '',
            // Margem da borda. Ex: '5px'
            'line-height' => '',
            // Line-height. Ex: 150%
            'css' => '',
            // Arquivo CSS a ser utilizado como padrão (ignora todas as configurações acima). Ex: rte_default.css
            'imgWidth' => dConfiguracao::getConfig('TEMPLATE/BODY_WIDTH'),
            // Largura máxima para upload de imagens.
        ));
        if (!$settings['css']) {
            $settings['css'] = "rte_default.css.php?";
            if (dSystem::getGlobal('localHosted')) {
                $settings['css'] .= "seed=" . time();
            }
            foreach ($settings as $key => $value) {
                if ($key == 'imgWidth') {
                    continue;
                }
                if (!$value) {
                    continue;
                }

                // Temos que remover o '#', senão o firewall bloqueia (mod_security)
                $value = str_replace("#", "", $value);
                $settings['css'] .= "&{$key}=" . urlencode($value);
            }

        }


        if (!@$__dALCampoRTEJS) {
            // Load only once...
            echo "<script src='../rte/ckeditor.js'></script>";
            echo "<script> loadJquery('../rte/adapters/jquery.js'); </script>";
            $__dALCampoRTEJS = true;
        }

        echo "<tr>\n";
        if ($descricao || $texto) {
            echo "<td colspan='2' class='edicao'>";
            echo "<b>{$descricao}</b><br />";
            if ($texto) {
                echo "<small>$texto</small><br />";
            }
        }

        $uid = uniqid("rtel_");
        $height = $linhas * 20;

        if (!$toolbar) {
            $toolbar = 3;
        }

        $ativa1 = ($toolbar & 1);
        $ativa2 = ($toolbar & 2);

        $config = array();
        $config[] = "<script> $(function(){ $('#{$uid}').ckeditor(function(){}, {\r\n";
        $config[] = "skin: 'v2',";
        $config[] = "extraPlugins :            'mediaembed',"; // ex: autrogrow,mediaembed
        $config[] = "language:                 'pt-br',";
        $config[] = "filebrowserBrowseUrl     : CKEDITOR.basePath + '../admin/rte_manage.php?type=files&browse=1',";
        $config[] = "filebrowserploadUrl      : CKEDITOR.basePath + '../admin/rte_manage.php?type=files&upload=1',";
        $config[] = "filebrowserImageBrowseUrl: CKEDITOR.basePath + '../admin/rte_manage.php?type=images&browse=1&maxWidth={$settings['imgWidth']}',";
        $config[] = "filebrowserImageUploadUrl: CKEDITOR.basePath + '../admin/rte_manage.php?type=images&upload=1&maxWidth={$settings['imgWidth']}',";
        $config[] = "entities: false, ";
        $config[] = "entities_greek: false, ";
        $config[] = "entities_latin: false, ";
        $config[] = "htmlEncodeOutput: false, ";
        # $config[] = "filebrowserFlashBrowseUrl: CKEDITOR.basePath + '../admin/rte_manage.php?type=flash&browse=1',";
        # $config[] = "filebrowserFlashUploadUrl: CKEDITOR.basePath + '../admin/rte_manage.php?type=flash&upload=1',";
        $config[] = "contentsCss:          '{$settings['css']}',";
        $config[] = "height:               {$height},";
        if ($settings['width']) {
            $config[] = "width:                " . (($settings['width'] + 10) < 650 ? 650 : ($settings['width'] + 10)) . ",";
        }
        $config[] = "toolbar : [";
        if ($ativa1 && $ativa2) {
            // Show Source e Show Blocks:
            // - Se for exibir o editor completo, exibir no início.
            // - Caso contrário, exibir somente no final.
            $config[] = "	['Source','*-','*Preview','*-','*Templates'],";
        }
        if ($ativa1) {
            $config[] = "	['*Cut','*Copy','Paste','PasteText','PasteFromWord','-','*Print', '*SpellChecker', '*Scayt'],";
        }
        if ($ativa2) {
            $config[] = "	['Undo','Redo','-','*Find','*Replace','*-','*SelectAll','*RemoveFormat'],";
            // $config[] = "	['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],";
            // $config[] = "	['BidiLtr', 'BidiRtl'],";
            if ($ativa1) {
                $config[] = "	'/',";
            }
        }
        if ($ativa1) {
            $config[] = "	['Bold','Italic','Underline','Strike','-','*Subscript','*Superscript'],";
            $config[] = "	['TextColor','BGColor'],";
            $config[] = "	['NumberedList','BulletedList','-','Outdent','Indent','*Blockquote','*CreateDiv'],";
            $config[] = "	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],";
            $config[] = "	['Link','Unlink','*Anchor'],";
        }
        if ($ativa2) {
            $config[] = "	['Image','MediaEmbed','*Flash','Table','HorizontalRule','Smiley','SpecialChar','*PageBreak'],";
            $config[] = "	'/',";
            $config[] = "	['Styles','Format','Font','FontSize'],";
        }
        if (!$ativa1 || !$ativa2) {
            // Show Source e Show Blocks:
            // - Se for exibir o editor completo, exibir no início.
            // - Caso contrário, exibir somente no final.
            $config[] = "	['Source','*-','*Preview','*-','*Templates'],";
        }

        # $config[] = "	['*Maximize', '*-','*About']";
        $config[] = "]";
        $config[] = "}); }); </script>";
        echo implode("\r\n", $config);
        echo dInput2::textarea("name='{$nome}' id='{$uid}' rows='5' style='width: 100%'", self::getValue($nome));
        echo "<small style='display: block'>Para quebra de linha simples, pressione SHIFT+ENTER</small>";
        echo "</td>\n";
        echo "</tr>";
    }

    static function Misc($descricao, $edicao = null)
    {
        echo "<tr>\n";
        if ($edicao === null) {
            echo "<td colspan='2'>{$descricao}</td>\n";
        } else {
            echo "<td class='descricao'>{$descricao}</td>\n";
            echo "<td class='edicao'>{$edicao}</td>\n";
        }
        echo "</tr>\n";
    }

    static function Finish($options = false)
    {
        if ($options !== true) {
            echo "<tr>\n";
            echo "<td style='border-right: 0; width: 20%'>";

            echo "</td>";
            echo "<td style='border-left: 0' class='finish'>";
            if ($options) {
                if ($options == 'auto') {
                    $options = 'goBack,addNew,stay';
                }

                $options = explode(",", $options);
                foreach ($options as $option) {
                    if ($option == 'goBack') {
                        echo "	<button style='text-align: left' name='_saveAnd' value='goBack'><i class='fa fa-mail-reply'></i> Salvar e voltar</button>";
                    } elseif ($option == 'addNew') {
                        echo "	<button style='text-align: left' name='_saveAnd' value='addNew'><i class='fa fa-plus-circle'></i> Salvar e cadastrar outro</button>";
                    } elseif ($option == 'stay') {
                        echo "	<button style='text-align: left'><i class='fa fa-floppy-o'></i> Salvar e permanecer nesta página</button>";
                    } else {
                        echo $option;
                    }
                }
            } else {
                echo "	<button style='text-align: left'><i class='fa fa-floppy-o'></i> Confirmar</button>";
            }
            echo "</td></tr>\n";
        }
        echo "</table>\n";
    }

    static function getClass()
    {
        if (is_object(self::$useClass)) {
            return self::$useClass;
        }
        if (self::$useClass == 'default') {
            return $GLOBALS['a'];
        }
        return self::$useClass;
    }

    static function getValue($var)
    {
        $o = self::getClass();

        if (!$o) {
            return false;
        } elseif (is_object($o)) {
            return $o->getValue($var);
        } elseif (function_exists($var)) {
            return call_user_func($o, $var);
        }

        return $o;
    }
}

/**
 * dALForm - Parâmetros:
 * Start:  [Action], [File], [Name], [Method]
 * Finish: (void)
 **/
class dALForm
{
    static function Start($action = false, $file = false, $name = false, $method = 'post')
    {
        if (!$name) {
            $name = uniqid('form');
        }

        if (!$action) {
            global $a;
            $id = $a ? $a->getPrimaryValue() : false;
            $action = $_SERVER['REQUEST_URI'];
            if (!isset($_GET['id']) && $id) {
                $action .= ((strpos($action, '?') !== false) ? "&" : "?") . "id={$id}";
            }
        }

        echo "<form name='$name' method='$method' action='$action'";
        if ($file) {
            echo " enctype='multipart/form-data'";
        }
        echo " style='display: inline'> ";
    }

    static function Finish()
    {
        echo "</form>";
    }
}

/**
 * dALList - Parâmetros
 * ShowSearchBox: (void)
 * Start:    (void)
 * BuildHeader: String:Campos para ordenar
 * RowStart: [background-color]
 * RowCell:  innerHtml
 * RowEnd:   edit_link, [delete_link], [extra_txt]
 * RowText:  text, [align]
 * Finish:   (void)
 **/
class dALList
{
    static $totalColunas;
    static $linhaPar;

    static function Start()
    {
        echo "<table width='100%' border='0' cellspacing='0' class='dalLista' cellpadding='4'>";
    }

    static function BuildHeader($fields)
    {
        global $s;
        echo "<tr class='header'>";
        foreach ($fields as $fieldName => $descricao) {
            echo "<td>";
            echo (!is_int($fieldName)) ?
                "<a href='" . $s->writeSortLink($fieldName) . "'>$descricao</a>" :
                "$descricao";
            echo "</td>";
        }
        echo "<td align='center' width='60'>Mais...</td>";
        echo "</tr>";
        self::$totalColunas = sizeof($fields) + 1;
    }

    static function RowStart($background = false)
    {
        echo "<tr class='row" . (self::$linhaPar ? 1 : 2) . "'";
        if ($background) {
            echo " style='background: $background'";
        }
        echo ">";
        self::$linhaPar = (self::$linhaPar) ? false : true;
    }

    static function RowCell($texto, $align = false)
    {
        echo "<td";
        if ($align) {
            echo " align='$align'";
        }
        echo ">";
        echo $texto;
        echo "</td>";
    }

    static function RowEnd($edit_link = false, $del_link = false, $extraTxt = false)
    {
        echo "<td align='center' width='60'>";
        if ($edit_link) {
            echo "<a href='$edit_link'><img src='images/editbutton.gif' alt='Editar' border='0'>";
        }
        if ($del_link) {
            echo "<a href='$del_link' onclick=\"return confirm('Tem certeza que deseja excluir este item?\\n\\nEsta operação não pode ser desfeita.');\"><img src='images/deletebutton.gif' alt='Excluir' border='0'></a>";
        }
        if ($extraTxt) {
            echo $extraTxt;
        }
        echo "</td>";
        echo "</tr>";
    }

    static function RowText($text, $align = 'center')
    {
        echo "<tr class='footer'>";
        echo "<td colspan='" . (self::$totalColunas) . "' align='$align'>$text</td>";
        echo "</tr>";
    }

    static function Finish()
    {
        echo "</table>";
    }

    static function Write($params)
    {
        if (!isset($params['table'])) {
            die("Table é obrigatório.");
        }
        if (!isset($params['pagination'])) {
            $params['pagination'] = 50;
        }

        if (!isset($params['colTitles'])) {
            $params['colTitles'] = false;
        }
        if (!isset($params['colFields'])) {
            die("Fields são obrigatórios");
        }
        if (!isset($params['orderBy'])) {
            $params['orderBy'] = 'id';
        }
        if (!isset($params['allowOptions'])) {
            $params['allowOptions'] = false;
        }
        if (!isset($params['allowClick'])) {
            $params['allowClick'] = $params['allowOptions'];
        }
        if (!isset($params['allowBusca'])) {
            $params['allowBusca'] = true;
        }
        if (!isset($params['allowCols'])) {
            $params['allowCols'] = true;
        }
        if (!isset($params['allowSorting'])) {
            $params['allowSorting'] = true;
        }

        if (!isset($params['allowMoving'])) {
            $params['allowMoving'] = false;
        }
        if (!isset($params['usingClass'])) {
            $params['usingClass'] = false;
        }

        if (!isset($params['tableWidth'])) {
            $params['tableWidth'] = '100%';
        }

        if (!isset($params['modColumns'])) {
            $params['modColumns'] = false;
        }
        if (!isset($params['modOptions'])) {
            $params['modOptions'] = false;
        }
        if (!isset($params['search'])) {
            $params['search'] = new dDbSearch(dDatabase::start());
        }

        extract($params);
        eval("?>" . file_get_contents(dirname(__FILE__) . '/inc/dAL.SearchBox.inc.php'));
    }
}

/**
 * dALC (dAL Common) - Parâmetros
 **/
class dALC
{
    static function IntOrFalse(&$var)
    {
        if (isset($var) && $var !== null) {
            return $var;
        }
        return false;
    }
}
