<?php
require "config.php";
require "template.php";

$userObj = dUsuario::isLoggedOrRedirect();
$facebook = dFacebook::start('iPainel');

cAnuncio::checkExpired();

dAL::layTop(array('bodyTitle' => dConfiguracao::getConfig('CORE/NOME_DO_SITE')));

if (isset($_SESSION['iLogin-SuggestAssign-Google']) && !$userObj->v('google_id')) {
    echo "<div style='padding: 15px; background: #FFC; margin-bottom: 25px; line-height: 150%'>";
    echo "<b>Login com o Google:</b><br />";
    echo "Vimos que você tentou realizar login com sua conta do Google, mas não teve sucesso.<br />";
    echo "Deseja associar a sua conta do painel administrativo com sua conta do Google agora?<br />";
    echo "<br />";
    echo "<a href='change_password.php?action=addGoogle'>Sim</a> | <a href='#' onclick=\"$.post('ajax.usuario_edit.php?action=removeSocial', { provider: 'google' }); $(this).slideUp(); return false;\">Não</a>";
    echo "</div>";
}
if (isset($_SESSION['iLogin-SuggestAssign-Facebook']) && !$userObj->v('facebook_id')) {
    echo "<div style='padding: 15px; background: #FFC; margin-bottom: 25px; line-height: 150%'>";
    echo "<b>Login com o Facebook:</b><br />";
    echo "Vimos que você tentou realizar login com sua conta do Facebook, mas não teve sucesso.<br />";
    echo "Deseja associar a sua conta do painel administrativo com sua conta do Facebook agora?<br />";
    echo "<br />";
    echo "<a href='change_password.php?action=addFacebook'>Sim</a> | <a href='#' onclick=\"$.post('ajax.usuario_edit.php?action=removeSocial', { provider: 'facebook' }); $(this).slideUp(); return false;\">Não</a>";
    echo "</div>";
}

echo "Por favor, selecione as ações desejadas no menu à sua esquerda.<br />";


dAL::layBottom();
