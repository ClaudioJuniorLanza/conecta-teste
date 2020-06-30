<?php
$_EnableAudit = false;
require_once "config.php";

$masterObj = dUsuario::isLogged();

if ($_POST['action'] == 'fakeLogin') {
    dAuditAcesso::autoLog(true);
    if (dUsuario::$cloakedMaster) {
        $masterObj = dUsuario::$cloakedMaster;
    }

    // Proteções existentes aqui:
    // --> Se não tiver USER_LOGIN_AS ou;
    // --> Se não puder gerenciar usuários ou;
    // --> Se tentar mudar para usuário fora do próprio grupo ou;
    // --> Se tentar escalar permissões.

    $masterObj->checkPermsOrDie('USER_LOGIN_AS');
    $targetObj = dUsuario::load($_POST['fakeId']);
    if ($targetObj->getPrimaryValue() == $masterObj->getPrimaryValue()) {
        dUsuario::setAsLogged($masterObj);
        unset($_SESSION['fakeLoginOriginal']);
        die("BACK-TO-NORMAL");
    } else {
        // Pode simular usuário deste grupo?
        if (!$masterObj->checkPerms('USER_MANAGE_ALL')) {
            $masterObj->checkPermsOrDie('USER_MANAGE');
            if ($masterObj->getPrimaryValue() != $targetObj->getValue('usuar_id')) {
                die("Desculpe, você só pode alternar entre seus próprios usuários.");
            }
        }

        // Vamos ver se está escalando permissões:
        $allPerms = dUsuario::getAllPerms();
        foreach ($allPerms as $key => $value) {
            if ($value === true) {
                continue;
            }

            if ($targetObj->checkPerms($key) && !$masterObj->checkPerms($key)) {
                die("Desculpe, o usuário desejado possui permissões que você não tem acesso, então não podemos simular o login.");
            }
        }

        $_SESSION['fakeLoginOriginal'] = $masterObj;
        dUsuario::setAsLogged($targetObj);
        dUsuario::$cloakedMaster = $targetObj;
        die("CLOAKED");
    }
} elseif ($_POST['action'] == 'keepAlive') {
    die("Keep alive OK!");
} elseif ($_POST['action'] == 'disableXDebug') {
    setcookie('XDEBUG_SESSION', null, time() - 10);
    setcookie('XDEBUG_SESSION', null, time() - 10, '/');
    setcookie('XDEBUG_SESSION', null, time() - 10, '../');
}