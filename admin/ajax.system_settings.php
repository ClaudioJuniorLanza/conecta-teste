<?php
require_once "config.php";

$u = dUsuario::isLoggedOrRedirect();
$u->checkPermsOrDie('MASTER_SETTINGS');

$key = $_POST['key'];
$val = $_POST['nv'];

$c = dConfiguracao::loadOrNew($key, 'key');
if (!$c->isLoaded()) {
    $c->setValue('key', $key);
}
$c->setValue('value', $val);
if ($c->save()) {
    dConfiguracao::loadConfig(true);
    echo "OK:" . dConfiguracao::getConfig($key);
} else {
    echo "ERRO:";
    echo implode(", ", $c->listErrors(true));
}
die;
