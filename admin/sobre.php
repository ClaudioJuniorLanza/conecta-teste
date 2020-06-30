<?php
require "config.php";
require "template.php";

$ap = ($_SERVER['QUERY_STRING'] == "egg");
if ($ap) {
    dSystem::notifyAdmin('LOW', "Eastern Egg found!", "O cliente achou o eastern egg no painel administrativo.");
}

dAL::layTop();
dAL::layTitle("Como estamos nos saindo?");

if (isset($_POST['mensagem'])) {
    $_POST['mensagem'] = trim($_POST['mensagem']);
    if (!strlen($_POST['mensagem'])) {
        echo "<b style='color: 900'>Você não digitou uma mensagem para ser enviada.</b><br><br>";
    } else {
        $nomeDoSite = dConfiguracao::getConfig('CORE/NOME_DO_SITE');
        $assunto = "Contato através do painel administrativo";
        $mensagem = "A seguinte mensagem foi enviada através do formulário 'Sobre...' do site {$nomeDoSite}\r\n";
        $mensagem .= "----------------------------------------\n";
        $mensagem .= "{$_POST['mensagem']}\r\n";
        $mensagem .= "----------------------------------------\n";
        $mensagem .= "Data/Hora: " . date("d/m/Y H:i:s") . "\n";
        $mensagem .= "Origem:    " . @$_SERVER['HTTP_REFERER'] . "\n";
        $mensagem .= "IP:        " . @$_SERVER['SERVER_ADDR'] . "\n";
        $mensagem .= "Porta:     " . @$_SERVER['REMOTE_PORT'] . "\n";
        $mensagem .= "Host:      " . gethostbyaddr($_SERVER['REMOTE_ADDR']) . "\n";
        $mensagem .= "Agente:    " . @$_SERVER['HTTP_USER_AGENT'] . "\n";

        mail("suporte@imaginacom.com", $assunto, $mensagem, "From: " . dConfiguracao::getConfig('CORE/MAIL_FROM'));
        echo "<b style='color: #090'>Obrigado por entrar em contato!</b> Assim que possível responderemos sua mensagem.<br><br>";
    }
}
?>
    <table cellspacing='0' cellpadding='4' width='100%'>
        <tr>
            <td valign='bottom'><img src="<?= ($ap ? 'images/bshadow.jpg' : 'images/shadow.jpg') ?>"/></td>
            <td valign='top'>
                <table cellspacing='5' bgcolor='#EEEEEE' style='border: 1px dotted #CCC'>
                    <tr>
                        <td colspan='2'><b style='font-size: 14px'>IMAGINACOM Plataformas Tecnológicas Ltda.</b></td>
                    </tr>
                    <tr>
                        <td width='60'><b>E-mail:</b></td>
                        <td>
                            <a href="mailto:suporte@imaginacom.com?subject=<?= urlencode(dConfiguracao::getConfig('CORE/NOME_DO_SITE')) ?>">suporte@imaginacom.com</a>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Fone:</b></td>
                        <td><a href="callto:+554333453394">+55 (43) 3345-3394</a></td>
                    </tr>
                    <tr>
                        <td><b>Site:</b></td>
                        <td><a href="http://www.imaginacom.com/" target='_blank'>www.imaginacom.com</a></td>
                    </tr>
                </table>
                <div style="line-height: 150%; margin-top: 15px">
                    Este projeto foi desenvolvido pela divisão de softwares <b>IMAGINACOM</b>.<br/>
                    <br/>
                    É nosso interesse conhecer <b>sua opinião</b>, e saber como podemos superar suas expectativas.<br/>
                    Você pode entrar em contato conosco através dos meios informados acima, ou diretamente pelo
                    formulário abaixo.<br/>
                    <br/>
                    Nosso objetivo é lhe oferecer a melhor experiência possível.<br/>
                    <br/>
                    <form method='post' action='sobre.php' style='display: inline'>
                        <textarea style='width: 90%; height: 100px' name='mensagem'></textarea><br>
                        <input type='submit' value='Confirmar'> <input type='reset' value='Redefinir'>
                    </form>
                </div>
            </td>
        </tr>
    </table>
<?
dAL::layBottom();
