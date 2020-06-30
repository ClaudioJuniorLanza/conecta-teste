<?php
require_once "config.php";
require_once "template.php";

$succMsg = array();
$a = cInvite::loadOrNew(@$_GET['id']);

if (@$_GET['detailed']) {
    dHelper2::redirectTo($a->getDetailsLink());
    die;
}

if ($a->v('api_campa_id')) {
    // Campanha já enviada.
    $a->syncWithAPI();
} elseif (@$_GET['send']) {
    if ($_POST['sendMode'] == 'test') {
        if (trim($_POST['test_to'])) {
            $a->send($_POST['test_to']);
        }
    } else {
        $a->send();
    }
} else {
    if ($_POST) {
        // Load checkboxes here.
        $_POST['anuncios'] = @$_POST['anuncios'] ? implode(",", $_POST['anuncios']) : false;

        $toList = explode("\n", strtolower(trim($_POST['to_list'])));
        $toList = array_map('trim', $toList);
        $toList = array_filter($toList);
        if ($_POST['merge_users']) {
            $allUsers = $db->singleColumn("SELECT DISTINCT email FROM c_usuarios");
            if ($_POST['merge_users'] == 'add') {
                $toList = array_merge($toList, $allUsers);
            } else {
                $toList = array_diff($toList, $allUsers);
            }
        }
        $toList = array_unique($toList);

        // Não permite o envio pra clientes que possuem interesses cadastrados.
        $rawUsers = cUsuario::multiLoad("where !isnull(interesses_json)");
        $notList = [];
        foreach ($rawUsers as $usuarObj) {
            $interesses = $usuarObj->getInteresses();
            if (!$interesses['compra'] != !$interesses['venda']) {
                $notList[] = $usuarObj->v('email');
                continue;

            }
            if ($interesses['venda'] && $interesses['venda']['userDefined']) {
                $notList[] = $usuarObj->v('email');
                continue;
            }
            if ($interesses['compra'] && $interesses['compra']['userDefined']) {
                $notList[] = $usuarObj->v('email');
                continue;
            }
        }

        $toList = array_diff($toList, $notList);
        $a->v('to_list', $_POST['to_list'] = implode("\r\n", $toList));

        $a->loadFromArray($_POST, 'id');
        if ($newid = $a->save()) {
            $succMsg[] = "O convite foi salvo com sucesso! <a href='anunc_invite_edit.php?id={$a->v('id')}'>Editar novamente</a>";
        }
    }
}

dAL::layTop(array('bodyTitle' => (!$a->getPrimaryValue() ? 'Nov' : 'Editar ') . "o convite"));
dAL::goBack(true, $a->isLoaded() ? "<a href='anunc_invite_edit.php'>Cadastrar outro</a>" : false);

dAL::boxes($a->listErrors(true), $succMsg);

if ($a->v('api_campa_id')) {
    $strStatus = "<i class='fa fa-spinner fa-spin'></i> Em andamento";
    if ($a->v('api_is_paused')) {
        $strStatus = "<i class='fa fa-pause'></i> Pausada";
    }
    if ($a->v('api_data_finish')) {
        $strStatus = "<i class='fa fa-check'></i> Concluída";
    }

    dALCampo::Start("Status do envio");
    if (dUsuario::isLogged()->checkPerms('MASTER_ACCOUNT')) {
        dSystem::getGlobal('localHosted') ?
            dALCampo::Misc("Código da campanha:",
                "<a href='http://192.168.25.9/im/clientcare/site/admin/campanha_edit.php?id={$a->v('api_campa_id')}'>#{$a->v('api_campa_id')}</a> <small>Local</small>") :
            dALCampo::Misc("Código da campanha:",
                "<a href='https://www.clientcare.com.br/admin/campanha_edit.php?id={$a->v('api_campa_id')}'>#{$a->v('api_campa_id')}</a>");
    }
    dALCampo::Misc("Atualizado em:", $a->v('api_last_update'));
    dALCampo::Misc("Status da campanha", $strStatus);
    dALCampo::Misc("Destinatários total: ",
        $a->v('c_ndestinos') . " <a href='anunc_invite_edit.php?id={$a->v('id')}&detailed=yes' target='_blank'>(Ver relatório detalhado)</a>");
    dALCampo::Misc("Envios efetivos: ", $a->v('api_nsent'));
    dALCampo::Misc("Viram o e-mail: ", $a->v('api_nviewed'));
    dALCampo::Misc("Clicaram no email: ", $a->v('api_nclicked'));

    dALCampo::Finish(true);
    echo "<br />";
    dALCampo::Start("E-mail que foi enviado");
    dALCampo::Misc(
        "<iframe frameborder='0' src='ifr.anunc_invite_edit.preview.php?campa_id={$a->v('id')}' onload=\"this.style.height = this.contentWindow.document.body.scrollHeight + 'px';\" style='width: 100%; height: 1200px'></iframe>"
    );
    dALCampo::Finish(true);
} elseif (@$_GET['send']) {
    if ($_POST['sendMode'] == 'test') {
        echo "<b>O teste foi enviado para {$_POST['test_to']}</b>.<br />";
        echo "Aguarde o recebimento nos próximos 60 segundos.<br />";
        echo "<br />";
        echo "<a href='{$_SERVER['PHP_SELF']}?id={$a->v('id')}&preview=yes'>Voltar para a pré-visualização</a>";
    } else {
        echo "<b>O envio foi agendado e terá início nos próximos 10 minutos.</b><br />";
        echo "Você pode acompanhar o envio acessando o painel administrativo a qualquer momento.<br />";
        echo "<br />";
        echo "<a href='{$_SERVER['PHP_SELF']}?id={$a->v('id')}'>Acompanhar envio</a>";
    }
    die;
} elseif ($a->isLoaded() && @$_GET['preview'] && !$a->listErrors()) {
    echo "<form method='post' action='{$_SERVER['PHP_SELF']}?id={$a->v('id')}&send=now' onsubmit=\"if(typeof x != 'undefined'){ console.log(9999); return false;} x = true; return true;\">";
    echo "<div style='padding: 16px'>" .
        "<a href='anunc_invite_edit.php?id={$a->v('id')}' style='text-decoration: none'><i class='fa fa-reply'></i> Voltar</a>" .
        "</div>";

    dALCampo::Start("Confirme o envio quando estiver pronto");
    dALCampo::Misc(
        dInput2::radio("name='sendMode' value='test'", true, " Enviar apenas teste:"),
        "Para: " . dInput2::input("name='test_to' size='35'", dUsuario::isLogged()->v('email'))
    );
    dALCampo::Misc(
        dInput2::radio("name='sendMode' value='now'", false, " Confirmar envio:"),
        "Enviar para <b>{$a->v('c_ndestinos')}</b> destinatários.<br />" .
        "<hr size=''1' />" .
        "<i>Esta operação é irreversível, e não pode ser pausada.</i><br />" .
        "O envio começará em no máximo 10 minutos após a confirmação."
    );
    dALCampo::Finish("<button style='text-align: left'><i class='fa fa-send'></i> Enviar agora!</button>");
    echo "<br />";

    dALCampo::Start("Pré-visualização");
    dALCampo::Misc(
        "<div style='padding: 16px'>" .
        "<a href='anunc_invite_edit.php?id={$a->v('id')}' style='text-decoration: none'><i class='fa fa-reply'></i> Voltar</a>" .
        "</div>" .
        "<iframe frameborder='0' src='ifr.anunc_invite_edit.preview.php?campa_id={$a->v('id')}' onload=\"this.style.height = this.contentWindow.document.body.scrollHeight + 'px';\" style='width: 100%; height: 1200px'></iframe>"
    );
    dALCampo::Finish(true);
} else {
    $idsSelected = $a->v('anuncios') ? explode(",", $a->v('anuncios')) : array();
    $queremVender = cAnuncio::multiLoad("where negocio='Venda'  and status='Ag. Propostas'", 'varieObj');
    $queremComprar = cAnuncio::multiLoad("where negocio='Compra' and status='Ag. Propostas'", 'varieObj');
    foreach ($queremVender as $anuncObj) {
        if (in_array($anuncObj->v('id'), $idsSelected)) {
            $anuncObj->setVirtual('checked', true);
        }
    }
    foreach ($queremComprar as $anuncObj) {
        if (in_array($anuncObj->v('id'), $idsSelected)) {
            $anuncObj->setVirtual('checked', true);
        }
    }

    dALForm::Start("{$_SERVER['PHP_SELF']}?id={$a->v('id')}&preview=yes");
    dALCampo::Start(($a->isLoaded() ? "Editar " : "Inserir") . " as informações:");
    dALCampo::Text('Assunto do e-mail:', 'subject', 50);
    dALCampo::Finish(true);
    echo "<div style='border: 1px solid #CCC; border-top: 0; border-bottom: 0'>";
    echo "<table width='100%' cellpadding='0' cellspacing='0'>";
    echo "  <tr valign='top'>";
    echo "      <td style='padding: 8px'>";
    echo "			<b>Destinatários (um por linha):</b><br />";
    echo dInput2::textarea("style='width: 300px; height: 250px; margin-top: 8px; white-space: nowrap' name='to_list' placeholder='email1@dominio.com\nemail2@dominio.com\n...'",
            $a) . "<br />";
    echo "<div style='font-size: 12px'>";
    echo dInput2::radio("name='merge_users' value=''", true, " Apenas lista acima") . "<br />";
    echo dInput2::radio("name='merge_users' value='add'", false, " Mesclar usuários cadastrados") . "<br />";
    echo dInput2::radio("name='merge_users' value='avoid'", false, " Evitar usuários cadastrados") . "<br />";
    echo "</div>";

    echo dInput2::textarea("style='width: 300px; height: 250px; margin-top: 8px' name='custom_msg' placeholder='Mensagem personalizada pra mandar no início do e-mail...'",
            $a) . "<br />";

    echo "      </td>";
    echo "      <td width='100%' style='border-left: 1px solid #CCC; padding: 8px'>";
    echo "          <b>Quais anúncios enviar?</b><br />";
    echo "          <table width='100%' border='1' style='border-collapse: collapse; margin-top: 8px'>";
    echo "              <tr valign='top'>";
    echo "              <td style='padding: 8px'>";
    echo "                  <b>Querem vender</b><br />";
    echo "  <div style='white-space: nowrap; font-size: 12px'>";
    foreach ($queremVender as $anuncObj) {
        echo dInput2::checkbox("name='anuncios[]' value='{$anuncObj->v('id')}'", $anuncObj->getVirtual('checked'),
                " #{$anuncObj->v('codigo')} - {$anuncObj->v('varieObj')->v('variedade')}") . "<br />";
    }
    echo "  </div>";
    echo "              </td>";
    echo "              <td style='padding: 8px'>";
    echo "                  <b>Querem comprar</b><br />";
    echo "  <div style='white-space: nowrap; font-size: 12px'>";
    foreach ($queremComprar as $anuncObj) {
        echo dInput2::checkbox("name='anuncios[]' value='{$anuncObj->v('id')}'", $anuncObj->getVirtual('checked'),
                " #{$anuncObj->v('codigo')} - {$anuncObj->v('varieObj')->v('variedade')}") . "<br />";
    }
    echo "  </div>";
    echo "              </td>";
    echo "              </tr>";
    echo "          </table>";
    echo "      </td>";
    echo "  </tr>";
    echo "</table>";
    echo "</div>";

    dALCampo::Start();
    dALCampo::Misc("<b style='color: #F00'>Seus limites:</b>",
        "Até 50 destinatários/dia: Grátis <small>(Incluso na manutenção)</small><br />" .
        "De  51 a 200 destinatários/dia: R$ 20 <small>(para cada dia que o limite for ultrapassado)</small><br />" .
        "De  201 a 500 destinatários/dia: R$ 40 <small>(para cada dia que o limite for ultrapassado)</small><br />" .
        "De  500 a 1.500 destinatários/dia: R$ 80 <small>(para cada dia que o limite for ultrapassado)</small>");
    dALCampo::Finish("<button style='text-align: left'><i class='fa fa-floppy-o'></i> Salvar e pré-visualizar</button>");
    dALForm::Finish();
}

dAL::layBottom();

