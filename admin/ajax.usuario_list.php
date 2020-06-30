<?php
require_once "config.php";

$usuarObj = dUsuario::isLoggedOrRedirect();

$workObj = dUsuario::load($_POST['id']) or die("Usuário não existe mais.");

// Verificação de segurança: Tem que poder editar o usuário em questão
$canManage = $usuarObj->checkPerms('USER_MANAGE');
$canManageAll = $usuarObj->checkPerms('USER_MANAGE_ALL');
$isSubUser = ($workObj->getValue('usuar_id') == $usuarObj->getPrimaryValue());
$isSelf = ($workObj->getPrimaryValue() == $usuarObj->getPrimaryValue());

if (!$canManage) {
    die("Sem permissões para gerenciar usuários.");
}
if ($isSelf) {
    die("Para evitar problemas, você não pode excluir sua própria conta.");
}
if (!$canManageAll && !$isSubUser) {
    die("Sem permissões para gerenciar este usuário.");
}

// Também não pode excluir um usuário super
if ($workObj->checkPerms('MASTER_ACCOUNT') && !$usuarObj->checkPerms('MASTER_ACCOUNT')) {
    die("Para evitar problemas, você não pode excluir essa conta.");
}


if ($_POST['action'] == 'delete' && $usuarObj->checkPerms('USER_DELETE')) {
    if (!$workObj->getValue('deleted')) {
        $workObj->delete();
        die("OK");
    }
    die("Usuário já está excluído.");
} elseif ($_POST['action'] == 'full_wipe' && $usuarObj->checkPerms('USER_WIPE')) {
    if ($workObj->getValue('deleted')) {
        $workObj->delete(true);
        die("OK");
    }
    die("Usuário não pode ser excluído sem passar pela lixeira.");
} elseif ($_POST['action'] == 'undelete') {
    if ($workObj->getValue('deleted')) {
        $workObj->setValue('deleted', '0')->save();
        die("OK");
    }
    die("Usuário não estava excluído.");
}


