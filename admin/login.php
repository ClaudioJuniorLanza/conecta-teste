<?php
$_DisableAuth = true;
$_EnableSetup = false;
require_once "config.php";

dAuditAcesso::blockPost(true);

$erro = false;
if (dUsuario::checkRememberMe()) {
    $afterLogGoTo = isset($_GET['gg']) ?
        base64_decode($_GET['gg']) :
        'index.php';

    dHelper2::redirectTo($afterLogGoTo);
    die;
} elseif (@$_POST['x_username'] && @$_POST['x_senha']) {
    if ($usuarObj = dUsuario::logIn(@$_POST['x_username'], @$_POST['x_senha'])) {
        if (@$_POST['x_remember']) {
            $usuarObj->saveRememberMe();
        }

        $afterLogGoTo = isset($_SESSION['iLogin-afterLoggedGoTo']) ? $_SESSION['iLogin-afterLoggedGoTo'] : 'index.php';
        unset($_SESSION['iLogin-afterLoggedGoTo']);
        unset($_SESSION['iLogin-loginUrl']);
        dHelper2::redirectTo($afterLogGoTo);
        die;
    } else {
        $erro = "Usuário e/ou senha inválidos.<br /><br />Esta tentativa de acesso<br /><b>foi registrada</b>.";
    }
} elseif (@$_GET['fbToken']) {
    $userData = json_decode(file_get_contents("https://ws.imaginacom.com/login/checkAccessToken.php?provider=facebook&access_token={$_GET['fbToken']}"),
        true);

    if (@$userData['user_id']) {
        $_userId = $userData['user_id'];
        $fb = dFacebook::start('iPainel');
        $fb->setAccessToken($_GET['fbToken']);
        $fb->sessionUpdate();

        $usuarObj = dUsuario::load($userData['user_id'], 'facebook_id');
        if (!$usuarObj) {
            $userData = $fb->graphRequest('/me?fields=id,name,email');
            if (@$userData['email']) {
                $usuarObj = dUsuario::load($userData['email'], 'email');
                if ($usuarObj) {
                    $usuarObj->v('facebook_id', $userData['id'])->save();
                }
            }
        }

        if ($usuarObj) {
            dUsuario::setAsLogged($usuarObj);
            if (@$_GET['rm']) {
                $usuarObj->saveRememberMe();
            }
            $afterLogGoTo = isset($_SESSION['iLogin-afterLoggedGoTo']) ? $_SESSION['iLogin-afterLoggedGoTo'] : 'index.php';
            unset($_SESSION['iLogin-afterLoggedGoTo']);
            unset($_SESSION['iLogin-loginUrl']);
            unset($_SESSION['iLogin-SuggestAssign-Facebook']);
            dHelper2::redirectTo($afterLogGoTo);
            die;
        } else {
            $_SESSION['iLogin-SuggestAssign-Facebook'] = $_userId;
            $erro = "{$userData['name']} ({$userData['id']}), já sabemos quem é você, mas não sabemos se você tem acesso ao sistema.<br />";
            $erro .= "Entre com sua senha no painel administrativo uma única vez para relacionarmos a sua conta.<br />";
        }
    } else {
        $erro = "Desculpe, mas não conseguimos te identificar pelo Facebook, ou você não confirmou a autorização.";
    }
} elseif (@$_GET['gToken']) {
    $userData = json_decode(file_get_contents("https://ws.imaginacom.com/login/checkAccessToken.php?provider=google&access_token={$_GET['gToken']}"),
        true);
    if ($userData['sub']) {
        $usuarObj = dUsuario::load($userData['sub'], 'google_id');
        if (!$usuarObj && @$userData['email'] && @$userData['email_verified'] == 'true') {
            $usuarObj = dUsuario::load($userData['email'], 'email');
            if ($usuarObj) {
                $usuarObj->v('google_id', $userData['sub'])->save();
            }
        }
        if ($usuarObj) {
            dUsuario::setAsLogged($usuarObj);
            if (@$_GET['rm']) {
                $usuarObj->saveRememberMe();
            }

            $afterLogGoTo = isset($_SESSION['iLogin-afterLoggedGoTo']) ? $_SESSION['iLogin-afterLoggedGoTo'] : 'index.php';
            unset($_SESSION['iLogin-afterLoggedGoTo']);
            unset($_SESSION['iLogin-loginUrl']);
            unset($_SESSION['iLogin-SuggestAssign-Google']);
            dHelper2::redirectTo($afterLogGoTo);
            die;
        } else {
            $_SESSION['iLogin-SuggestAssign-Google'] = $userData['sub'];
            $erro = "{$userData['name']}, você foi identificado no Google, mas ainda não sabemos<br />";
            $erro .= "se você tem acesso ao painel. Utilize sua senha para associar sua conta pela primeira vez.<br />";
        }
    } else {
        $erro = "Impossível confirmar o login com o Google";
    }
} else {
    if (isset($_GET['gg'])) {
        $_SESSION['iLogin-afterLoggedGoTo'] = base64_decode($_GET['gg']);
    }
    $_SESSION['iLogin-loginUrl'] = $_SERVER['REQUEST_URI'];
}

?><!DOCTYPE html>
<html>
<head>
    <title>IMAGINACOM - Acesso restrito</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            padding: 0;
        }

        div, td {
            font: 11px Verdana
        }

        input {
            border: 1px solid #000;
            font: 11px Arial;
            margin: 2px
        }
    </style>
</head>

<body bgcolor="#EEEEEE" onload="document.getElementById('login').focus()">

<script>
    function doLogin() {
        document.getElementById('isu').value = document.getElementById('login').value;
        document.getElementById('isp').value = document.getElementById('senha').value;
        document.getElementById('ire').value = document.getElementById('inpRemember').checked ? '1' : '0';
        document.forms['foo'].submit();
    }

    function loginUsing(extraGet) {
        var _concat = (window.location.href.indexOf("?") > 0) ?
            "&" :
            "?";

        if (document.getElementById('inpRemember').checked) {
            extraGet += "&rm=1"; // Remember me = yes
        }

        var continueTo = document.getElementsByName('foo')[0].action + _concat + extraGet;
        location.href = continueTo;
        return false;
    }
</script>

<form method='post' action='<?= $_SERVER["REQUEST_URI"] ?>' style='display: inline' name='foo'>
    <input type='hidden' id='ire' name='x_remember'/>
    <input type='hidden' id='isu' name='x_username'/>
    <input type='hidden' id='isp' name='x_senha'/>
</form>

<div align='center'>
    <br/>
    <br/>
    <br/>
    <br/>
    <div style='width: 100%; max-width: 360px; border: 1px solid #CCC; text-align: left; padding: 5px; background: #FFF'>
        <b>Acesso restrito</b><br/>
        <? if (!$erro): ?>
            <br/>
            Digite seu login e senha nos campos abaixo.<br/>
            Se você não tem um login e senha, <a href='../'>clique aqui</a>.<br/>
        <? endif ?>
        <hr size='1'/>
        <table width='100%'>
            <tr>
                <td>
                    <img src='images/logo_notext.gif' alt='IMAGINACOM Sistemas'/>
                </td>
                <td align='right' valign='bottom'>
                    <? if ($erro): ?>
                    <br/>
                    <br/>
                        <?= ($erro ? "<div class='error'>$erro</div><br>" : "") ?><br/>
                    <br/>
                        <a href="<?= $_SESSION['iLogin-loginUrl'] ?>">Voltar</a>
                    <? else: ?>
                        <b>Login: <input type='text' id='login'/></b><br/>
                        <b>Senha: <input type='password' id='senha' onkeydown="if(event.keyCode == 13) doLogin();"/></b>
                    <br/>
                        <table width='100%'>
                            <tr>
                                <td>
                                    <input type='checkbox' name='remember' id='inpRemember'
                                           onclick="return confirm('Você será logado automaticamente nos seus próximos acessos.\nNão use esta opção em dispositivos compartilhados.')">
                                    <label for='inpRemember'>Lembrar senha</label>
                                </td>
                                <td align='right'><input type='button' onclick='doLogin()' value='Login...'></td>
                            </tr>
                        </table>
                        <script> document.getElementById('login').focus(); </script>
                    <? endif ?>
                </td>
            </tr>
        </table>
        <hr size='1'/>
        <div align='center' style='font: 10px Arial; color: #AAA'>
            Sistema desenvolvido por <a href='http://www.imaginacom.com/' style='color: #888'><b>IMAGINACOM</b></a>
        </div>
    </div>
    <? if (!$erro): ?>
        <br/>
        <div style="width: 100%; max-width: 360px; padding-right: 5px; text-align: right">
            <script type='text/javascript' src='https://ws.imaginacom.com/login/api.js?<?= time() ?>'></script>
            <script type='text/javascript'>
                loginWithImaginacom.create(false, {
                    onLogin: function (provider, token) {
                        if (provider == 'google') {
                            loginUsing('gToken=' + token);
                        } else if (provider == 'facebook') {
                            loginUsing('fbToken=' + token);
                        }
                        return false;
                    },
                });
            </script>
        </div>
    <? endif ?>
</div>

</body>
</html>
