<?php
$_EnableAudit = isset($_EnableAudit) ? $_EnableAudit : true;
$_DefaultSSL = isset($_DefaultSSL) ? $_DefaultSSL : 'ssl';
require_once dirname(__FILE__) . "/../config.php";

// _EnableSetup é verificado no ../config.php
// _DisableAuth é verificado na sequencia
$_DisableAuth = isset($_DisableAuth) ? $_DisableAuth : false;
if (dSystem::getGlobal('localHosted') && $_EnableSetup) {
    // Ambiente de desenvolvimento, assume-se que está logado como usuário MASTER.
    // Vamos localizar qualquer usuário MASTER para ser logado.
    $usuarObj = dUsuario::isLogged();

    if (!$usuarObj) {
        $db = dDatabase::start();
        $u = dUsuarPermissao::load(array(
            'loadExt' => 'usuarObj',
            'cbMakeQuery' => "where permissao='MASTER_ACCOUNT' limit 1"
        ));
        $u = $u ? $u->v('usuarObj') : false;
        if (!$u) {
            echo "Não existe um usuário MASTER cadastrado no sistema.<br />";
            echo "Isso não pode acontecer, deve haver sempre ao menos um usuário MASTER cadastrado.<br />";

            echo "Execute os seguintes comandos no SQL:<br />";
            echo "<pre style='background: #CCC; padding: 10px; border: 1px solid #CCC'>";
            echo "	insert into d_usuarios set id=1, usuar_id='1', data_cadastro=now(), username='master', senha='----', disabled='0', `deleted`='0';\r\n";
            echo "	insert into d_usuar_permissoes set usuar_id='1', permissao='MASTER_ACCOUNT';\r\n";
            echo "</pre>";

            die;
        }
        dUsuario::setAsLogged($u);
    }

    unset($usuarObj);
}

if (!$_DisableAuth) {
    // Verifique se está logado.
    // Se não tiver, direcione para o login e morra.
    dUsuario::isLoggedOrRedirect();
}

if (array_key_exists('fakeLoginOriginal', $_SESSION)) {
    dUsuario::$cloakedMaster = $_SESSION['fakeLoginOriginal'];
}

