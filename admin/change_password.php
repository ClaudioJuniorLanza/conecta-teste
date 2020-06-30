<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLogged();
$succMsg = array();
$errorMsg = array();
if ($_POST && !$_POST['old_password']) {
    $errorMsg[] = "Você precisa informar sua senha anterior antes de alterar para uma nova senha.";
} elseif ($_POST && !$_POST['new_password']) {
    $errorMsg[] = "Você não informou uma nova senha. Sua senha não foi alterada.";
} elseif ($_POST) {
    $trySenha = md5(trim($_POST['old_password']) . dSystem::getGlobal('hashkey'));
    $newSenha = trim($_POST['new_password']);

    $isPasswordOk = $db->singleResult("select id from d_usuarios where id='" . addslashes($usuarObj->v('id')) . "' and senha='" . addslashes($trySenha) . "' limit 1");
    if (!$isPasswordOk) {
        $errorMsg[] = "A senha atual informada não bate com a senha cadastrada.";
    } else {
        $usuarObj->setValue('senha', trim($_POST['new_password']));
        if ($usuarObj->save()) {
            $succMsg[] = "Senha atualizada com sucesso, utilize sua nova senha já no próximo acesso.";
        } else {
            $errorMsg = $usuarObj->listErrors(true);
        }
    }
}

$fb = dFacebook::start('iPainel');
if (@$_GET['action'] == 'addFacebook') {
    $usuarObj->v('facebook_id', @$_SESSION['iLogin-SuggestAssign-Facebook'])->save();
    $succMsg[] = "Associação bem sucedida! Agora você pode fazer login utilizando sua conta do Facebook.";
    unset($_SESSION['iLogin-SuggestAssign-Facebook']);
}
if (@$_GET['action'] == 'addGoogle') {
    $usuarObj->v('google_id', @$_SESSION['iLogin-SuggestAssign-Google'])->save();
    $succMsg[] = "Associação bem sucedida! Agora você pode fazer login utilizando sua conta do Google.";
    unset($_SESSION['iLogin-SuggestAssign-Google']);
}

dAL::layTop(array('bodyTitle' => "Minha conta"));
dAL::boxes($errorMsg, $succMsg);

$_checked = array();
if ($usuarObj->v('facebook_id')) {
    $_checked[] = 'facebook';
}
if ($usuarObj->v('google_id')) {
    $_checked[] = 'google';
}

dALCampo::Start("Login utilizando as redes sociais");
dALCampo::Misc(
    "<table width='100%'>" .
    "<tr valign='top'>" .
    "<td><div id='socialHolder' style='padding: 16px'></div></td>" .
    "<td>" .
    "<div style='padding: 16px'>" .
    "<b>Respostas à dúvidas frequentes:</b><br />" .
    "<ul style='line-height: 150%; margin-bottom: 0'>" .
    "	<li>Associar sua conta permitirá que você faça login no sistema com apenas um clique.</li>" .
    "	<li>O sistema <b style='color: #00F'>não terá acesso</b> às suas contas.</li>" .
    "	<li>Você <b style='color: #00F'>precisará estar logado</b> em suas contas para entrar no sistema.</li>" .
    "	<li>Você também poderá acessar o sistema normalmente <b style='color: #00F'>utilizando sua senha</b>.</li>" .
    "</ul>" .
    "</div>" .
    "</td>" .
    "</tr>" .
    "</table>"
);
dALCampo::Finish(true);

echo "<br />";

dALCampo::Start("Login através de senha:");
dALCampo::Finish(true);
?>
    <form method='post'>
        <div style="border: 1px solid #CCC; background: #EEE; padding: 10px; border-top: 0">
            Seu nome de usuário <i>(não pode ser alterado)</i>:<br/>
            <div style="padding: 3px; font-size: 14px; font-weight: bold">
                <?= $usuarObj->v('username') ?>
            </div>
            <br/>
            Informe sua senha atual antes de continuar:<br/>
            <input name='old_password' type='password' style='padding: 3px; font: 14px Arial; font-weight: bold'/><br/>
            <br/>
            Digite a sua nova senha:<br/>
            <input name='new_password' type='password' style='padding: 3px; font: 14px Arial; font-weight: bold'/><br/>
            <small>* Lembre-se que você é responsável pela sua senha, faça uma escolha sábia.</small><br/>
            <br/>
            <button style='padding: 10px'>Confirmar alteração de senha</button>
        </div>
    </form>

    <script type='text/javascript' src='https://ws.imaginacom.com/login/api.js'></script>
    <script type='text/javascript'>
        loginWithImaginacom.create('socialHolder', {
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

<?php
dAL::layBottom();

