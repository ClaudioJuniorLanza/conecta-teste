<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLogged();
$usuarObj->checkPermsOrDie('SENTMAIL_VIEW');

$emailId = intval(@$_GET['id']);
if (!$emailId) {
    header("Location: email_list.php");
    die;
}

$e = dEmail::load($emailId) or die("Not found.");
$anexos = dAnexo::multiLoad("where rel='dEmail' and rel_id='{$e->v('id')}'");

dAL::layTop(array('bodyTitle' => "E-mail: {$e->getValue('subject')}"));
dAL::goBack();

$dsmObj = $e->getValue('dsm_object') ? unserialize($e->getValue('dsm_object')) : false;
$formatEmail = function ($item) {
    if (is_array($item)) {
        return $item[0];
    }
    $item = dSendMail3::unNormalizeEmail($item);
    return "{$item[0]}" . ($item[1] ? " ({$item[1]})" : "");
};

if ($dsmObj) {
    $mailFrom = $formatEmail($dsmObj->getFrom());
    $replyTo = $formatEmail($dsmObj->getHeader('Reply-To'));
    $mailTo = implode(", ", array_map($formatEmail, $dsmObj->getTo()));
    $subject = $dsmObj->getSubject();
} else {
    $mailFrom = $e->v('mailfrom');
    $replyTo = $e->v('replyto_mail');
    $mailTo = $e->v('mailto');
    $subject = $e->v('subject');
}

?>
    Veja abaixo a cópia do e-mail que foi enviado pelo servidor:<br/>
    <br/>
    <div align='center'>
        <div style='width: 80%; border: 1px solid #777; text-align: left; padding: 5px'>
            <div style='padding-bottom: 10px; border-bottom: 1px solid #999'>
                <b>Remetente:</b> <?= $mailFrom ?><br/>
                <b>Responder para:</b> <?= $replyTo ?><br/>
                <b>Destinatário:</b> <?= $mailTo ?><br/>
                <b>Data e hora:</b> <?= $e->getValue('data_hora') ?><br/>
            </div>
            <div style='border-bottom: 1px solid #999; padding: 10px; background: #FFF'>
                <b>Assunto:</b> <?= $subject ?>
            </div>
            <? if ($anexos): ?>
                <div style='border-bottom: 1px solid #999; padding: 10px; background: #FFC'>
                    <b>Anexos:</b>
                    <? foreach ($anexos as $anexoObj): ?>
                        <?= $anexoObj->embedFile('../viewFile.php', array('forceDownload' => true)) ?>
                    <? endforeach ?>
                </div>
            <? endif ?>
            <div style="padding-top: 10px">
                <?= $e->getValue('text_html'); ?>
            </div>
        </div>
    </div>
<?php
dAL::layBottom();

