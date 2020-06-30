<?php
require_once "config.php";

$inviteObj = cInvite::load($_GET['campa_id']) or die("Não encontrado.");

if ($inviteObj->v('is_weekly')) {
    $message = file_get_contents(dSystem::getGlobal('baseDir') . '/admin/emails/invite_weekly.html');
    $message = str_replace("[CUSTOM_MSG]", "", $message);
    $message = str_replace("[PIXEL]", "", $message);
    $message = str_replace("[UNSUB_LINK]", "#", $message);
    $message = str_replace("_INTERESSES_LINK_", "../app/meus-interesses.php", $message);
    $message = preg_replace("/\[T:(.+?)\]/", "\\1", $message);
    $message = str_replace(['_QUEREM_VENDER_', '_QUEREM_COMPRAR_'],
        "<tr><td>Lista customizada para cada usuário</td></tr>", $message);
    echo $message;
} else {
    echo $inviteObj->getMessage(true);
}
