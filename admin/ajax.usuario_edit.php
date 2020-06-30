<?php
require_once "config.php";

$usuarObj = dUsuario::isLoggedOrRedirect();
$action = isset($_GET['action']) ? $_GET['action'] : 'managePermissions';

// Gerenciamento da rede social não exige um "workObj", por isso vem antes.
if ($action == 'addSocial') {
    $provider = $_POST['provider'];
    $token = $_POST['token'];
    $userData = json_decode(file_get_contents("https://ws.imaginacom.com/login/checkAccessToken.php?provider={$provider}&access_token={$token}"),
        true);

    if ($provider == 'google' && $userData['sub']) {
        $usuarObj->v('google_id', $userData['sub'])->save();
        die("OK");
    }
    if ($provider == 'facebook' && $userData['user_id']) {
        $usuarObj->v('facebook_id', $userData['user_id'])->save();
        die("OK");
    }
    die("Failed");
}
if ($action == 'removeSocial') {
    $provider = $_POST['provider'];
    if ($provider == 'google') {
        $usuarObj->v('google_id', false)->save();
        die("OK");
    }
    if ($provider == 'facebook') {
        $usuarObj->v('facebook_id', false)->save();
        die("OK");
    }

    die("Failed");
}

$workObj = dUsuario::load($_POST['usuarId']) or die("Usuário não existe mais.");

// Verificações de segurança:
//   Tem que poder editar o usuário em questão
$canManage = $usuarObj->checkPerms('USER_MANAGE');
$canManageAll = $usuarObj->checkPerms('USER_MANAGE_ALL');
$isSubUser = ($workObj->getValue('usuar_id') == $usuarObj->getPrimaryValue());
$isSelf = ($workObj->getPrimaryValue() == $usuarObj->getPrimaryValue());

if (!$canManage) {
    die("Sem permissões para gerenciar usuários.");
}
if (!$canManageAll && !$isSubUser && !$isSelf) {
    die("Sem permissões para gerenciar este usuário.");
}

if ($action == 'managePermissions') {
    // Só pode dar/revogar permissões que ele mesmo possui.
    $permissao = trim($_POST['key']);
    $usuarObj->checkPerms($permissao) or die("Só é possível delegar permissões que você mesmo possua.");

    // Não pode mecher nas próprias permissões:
    if ($usuarObj->getPrimaryValue() == $workObj->getPrimaryValue()) {
        die("Você não pode modificar suas próprias permissões.");
    }
    if ($workObj->checkPerms('MASTER_ACCOUNT') && $permissao != 'MASTER_ACCOUNT') {
        die("O usuário SUPER possui todas as permissões por padrão, não sendo possível alterá-las individualmente.");
    }
    if ($workObj->checkPerms('MANAGER_ACCOUNT') && $permissao != 'MANAGER_ACCOUNT' && $permissao != 'MASTER_ACCOUNT') {
        die("O usuário MASTER possui todas as permissões por padrão, não sendo possível alterá-las individualmente.");
    }

    $exId = $db->singleResult("select id from d_usuar_permissoes where usuar_id='{$workObj->getPrimaryValue()}' and permissao='" . addslashes($permissao) . "'");
    if ($exId && $_POST['nv'] == 1) {
        die("CHECKED");
    }
    if ($exId && $_POST['nv'] == 0) {
        $db->query("delete from d_usuar_permissoes where id='{$exId}'");
        die("UNCHECKED");
    }
    if (!$exId && $_POST['nv'] == 1) {
        $db->query("insert into d_usuar_permissoes set usuar_id='{$workObj->getPrimaryValue()}', permissao='" . addslashes($permissao) . "'");
        die('CHECKED');
    }
    if (!$exId && $_POST['nv'] == 0) {
        die("UNCHECKED");
    }
}
if ($action == 'fbGenerate') {
    $exLink = $workObj->fbGetInviteLink($_POST['time']);
    if (!$exLink) {
        die("Desativado.");
    }
    die(
        "<div style='background: #EEE; border: 1px solid #999; padding: 5px'>" .
        "<table cellpadding='4' cellspacing='0' border='0' width='100%'>" .
        "	<tr>" .
        "		<td colspan='2'><b>Convite ativo:</b> <a href='#' onclick='return socialFb.fbRevokeInvite();'><small>Revogar</small></a></td>" .
        "	</tr>" .
        "	<tr>" .
        "		<td colspan='2' width='100%'><input readonly value='{$exLink}' style='font-size: 12px; width: 100%; background: transparent; padding: 4x 16px; border: 0' /></td>" .
        "	</tr>" .
        "	<tr>" .
        "		<td nowrap>Válido até:</td>" .
        "		<td width='100%'>" . date('d/m/Y H:i:s',
            $workObj->v('facebook_invite')) . " (Em aprox. " . round(($workObj->v('facebook_invite') - time()) / 60 / 60) . " horas)</td>" .
        "	</tr>" .
        "</table>" .
        "</div>"
    );
}
if ($action == 'fbRevokeInvite') {
    $workObj->v('facebook_invite', false)->save();
    echo "Desativado.";
}
