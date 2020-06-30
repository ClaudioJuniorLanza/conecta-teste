<?php
$_DisableAuth = true;
require_once "config.php";

$usuarObj = dUsuario::fbValidateInvite($_GET['code'], $_GET['hash']);
$_checked = array();
if ($usuarObj) {
    dUsuario::setAsLogged($usuarObj);
    if ($usuarObj->v('facebook_id')) {
        $_checked[] = 'facebook';
    }
    if ($usuarObj->v('google_id')) {
        $_checked[] = 'google';
    }

    if ($_POST) {
        $senhaOk = false;
        $senhaFailed = false;
        $redesOk = ($usuarObj->v('facebook_id') || $usuarObj->v('google_id'));

        if (@$_POST['senha']) {
            $senhaOk = $usuarObj
                ->v('username', $_POST['username'])
                ->v('senha', $_POST['senha'])
                ->save();

            if (!$senhaOk) {
                $senhaFailed = $usuarObj->listErrors(true);
            }
        }

        if (!$senhaFailed && ($senhaOk || $redesOk)) {
            // Sucesso!
            $usuarObj->v('facebook_invite', false)->save();
            dHelper2::redirectTo('index.php');
            die;
        } elseif (!$senhaFailed) {
            $senhaFailed[] = "Você precisa escolher alguma forma de autenticação antes de continuar.";
        }
    }
}

$senhaFailed = false;
$_ShowPage = ($usuarObj ? 'INTRO' : 'INVALID_INVITE');
?>
<!DOCTYPE html>
<head>
    <title>Acesso ao Painel Administrativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #777;
            text-align: center;
            padding: 0;
            margin: 0;
            font-size: 16px;
        }

        h1 {
            font-size: 30px;
        }

        input {
            padding: 3px 8px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <script type='text/javascript' src='https://ws.imaginacom.com/login/api.js'></script>
    <?php
    echo dResLoader::writeInclude('jquery', 'jquery-1.8.3', '../');
    echo dResLoader::writeInclude('jquery', 'jquery-dEasyRefresh', '../');
    ?>
</head>

<body>

<div align='center' style='padding: 16px'>
    <div style="max-width: 700px; width: 100%; background: #EEE; padding: 16px; font: 16px Roboto; text-align: left">
        <?= dConfiguracao::getConfig('CORE/NOME_DO_SITE'); ?>
        <hr/>
        <? if ($_ShowPage == 'INVALID_INVITE'): ?>
            <div style="font: 28px Calibri; font-weight: bold">Ops!</div>
            <br/>
            Estimado cliente, o link que você acessou não é mais válido, por um dos seguintes motivos:<br/>
            <ul>
                <li>Você já definiu um usuário/senha anteriormente;</li>
                <li>Você demorou muito para utilizar o link, e ele expirou.</li>
            </ul>
            Você pode tentar <a href="login.php">realizar seu
                login</a> no sistema, ou então, entre em contato com o suporte: (43) 3345-3394<br/>
        <? else: ?>
            <h1>Painel Administrativo</h1>
            <div>
                <? if ($senhaFailed): ?>
                    <div style='background: #FCC; margin-bottom: 16px; text-align: center; padding: 16px 8px'>
                        <?= implode("<br />", $senhaFailed); ?>
                    </div>
                <? endif ?>
                Deseja acessar o painel com suas redes sociais?<br/>
                <div style='margin: 32px'>
                    <script type='text/javascript'>
                        loginWithImaginacom.create(false, {
                            checked: '<?=$_checked ? implode(",", $_checked) : ""?>',
                            autoCheck: true,
                            onLogin: function (provider, token) {
                                var _t = this;
                                $.post("ajax.usuario_edit.php?action=addSocial", {
                                    provider: provider,
                                    token: token,
                                }, function (ret) {
                                    _t.setChecked(provider, true);
                                });
                                return false;
                            },
                            onUncheck: function (provider) {
                                var _t = this;
                                $.post("ajax.usuario_edit.php?action=removeSocial", {
                                    provider: provider,
                                }, function (ret) {
                                    _t.setChecked(provider, false);
                                });
                                return false;
                            }
                        });
                    </script>
                </div>
                Deseja definir uma senha de acesso?<br/>
                <small>(Opcional se você cadastrou suas redes sociais)</small><br/>
                <br/>
                <form method='post'>
                    <b>Nome de usuário:</b><br/>
                    <?= dInput2::input("name='username'", $usuarObj); ?><br/>
                    <br/>
                    <b>Senha:</b><br/>
                    <?= dInput2::input("name='senha'"); ?><br/>
                    <br/>
                    <button style='padding: 8px'>Pronto! Me leve ao painel.</button>
                </form>
            </div>
        <? endif ?>
    </div>
</div>

</body>
</html>
