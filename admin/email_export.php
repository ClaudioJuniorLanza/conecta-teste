<?php
require_once "config.php";

$usuarObj = dUsuario::isLogged();
$usuarObj->checkPermsOrDie('SENTMAIL_VIEW');

$allEmails = $db->singleQuery("select distinct replyto_mail,replyto_name from d_emails");
if (!$allEmails) {
    die("Nenhum e-mail disponível até o momento.");
}

header("Content-Disposition: attachment;filename=emails_" . date('Y-m-d') . ".csv");
echo "\"E-mail\";\"Nome\"\r\n";
foreach ($allEmails as $item) {
    echo "\"";
    echo strtolower($item['replyto_mail']);
    echo "\";";
    echo "\"";
    echo addslashes($item['replyto_name']);
    echo "\"\r\n";
}

