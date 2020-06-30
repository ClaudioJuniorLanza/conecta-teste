<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLoggedOrRedirect();
$usuarObj->checkPermsOrDie('USER_MANAGE');

$succMsg = array();
$id = isset($_GET['id']) ? intval($_GET['id']) : false;

$a = dUsuario::loadOrNew($id);

if ($a->getPrimaryValue()) {
    $canManageAll = $usuarObj->checkPerms('USER_MANAGE_ALL');
    $isSubUser = ($a->getValue('usuar_id') == $usuarObj->getPrimaryValue());
    $isSelf = ($a->getPrimaryValue() == $usuarObj->getPrimaryValue());

    if (!$canManageAll && !$isSubUser && !$isSelf) {
        dSystem::notifyAdmin('LOW', "dFramework: Usuário sem privilégio tentou editar outro usuário",
            "O usuário {$usuarObj->getPrimaryValue()} tentou editar o id={$a->getPrimaryValue()}.\r\n" .
            "Acesso foi negado, e botão de VOLTAR será exibido ao usuário."
        );
        dHelper2::includePage(dirname(__FILE__) . "/usuario_noperms.php");
        die;
    }
}

if ($_POST) {
    if ($a->getPrimaryValue() == 1 && $usuarObj->getPrimaryValue() != 1) {
        $a->addError(false, "Desculpe, modificações nesta conta foram bloqueadas para sua segurança.");
    } else {
        $a->loadArray($_POST, 'id,data_cadastro,data_ult_login');
        $a->setValue('disabled', isset($_POST['disabled']) ? '1' : '0');
        if (!$a->getPrimaryValue()) {
            $a->setValue('usuar_id', $usuarObj->getPrimaryValue());
        }

        if ($newid = $a->save()) {
            $succMsg[] = "Dados foram salvos com sucesso!";
            $id = $newid;
        }
    }
}

dAL::layTop(array(
    'menuSel' => 'loja',
    'bodyTitle' => $a->getPrimaryValue() ? "Usuário '{$a->getValue('username')}'" : "Cadastrar novo usuário"
));
dAL::goBack();

dAL::boxes($a->listErrors(true), $succMsg);

dALForm::Start();
dALCampo::Start("Insira as informações:");
dALCampo::Misc("Cadastrado em:", $a->getValue('data_cadastro'));
dALCampo::Misc("Últ. login:", $a->getValue('data_ult_login') ? $a->getValue('data_ult_login') : '<i>Nunca</i>');
if ($usuarObj->checkPerms('USER_MANAGE_ALL')) {
    dALCampo::Misc("Responsável:",
        dInput2::select("name='usuar_id'", $db->singleQuery("select id,username from d_usuarios"), $a));
}
dALCampo::Text("Usuário:", 'username');
dALCampo::Text("Senha", "senha", 25, 30, " (Preencha para alterar a senha existente)");
dALCampo::Text("Email", "email", 30, false, " (Opcional)");

// Facebook Invite:
$_strFacebook = "";
$_strFacebook .= "<div class='fbInviteLink' style='margin-bottom: 10px'>Desativado.</div>";
$_strFacebook .= "Gerar convite válido por " .
    "<a href='#' onclick=\"return socialFb.fbGenerate('+24 hours')\">24 horas</a> | " .
    "<a href='#' onclick=\"return socialFb.fbGenerate('+2 days')\">2 dias</a> | " .
    "<a href='#' onclick=\"return socialFb.fbGenerate('+1 week')\">1 semana</a> | " .
    "<a href='#' onclick=\"return socialFb.fbGenerate('+1 month')\">1 mês</a>";
$_strFacebook .= "</div>";
dALCampo::Misc("Login via convite:", $_strFacebook);

dALCampo::Finish();

echo "<br />";


if ($a->getPrimaryValue()) {
    $allSettings = dUsuario::getAllPerms();
    $allSettingsDisplay = array();

    array_unshift($allSettingsDisplay, array('id' => false, 'titulo' => "Usuário"));
    foreach ($allSettingsDisplay as $idx => $displayInfo) {
        $tmpInKey = "Permissões genéricas";
        $tmpCheck = $displayInfo['id'] ? $allSettingEmpr : $allSettings;
        foreach ($tmpCheck as $key => $title) {
            if ($title === true) {
                $tmpInKey = $key;
                continue;
            }
            if (!$usuarObj->checkPerms($key, $displayInfo['id'])) {
                // Se o usuário logado não tem permissões, então não exibe essa opção.
                continue;
            }

            $allSettingsDisplay[$idx]['allPermissoes'][$tmpInKey][$key] = array(
                'title' => $title,
                'checked' => $a->checkPerms($key, $displayInfo['id'])
            );
        }
    }

    echo "<table width='100%'>";
    echo "	<tr valign='top'>";
    foreach ($allSettingsDisplay as $displayInfo) {
        if (!@$displayInfo['allPermissoes']) {
            continue;
        }

        echo "		<td>";
        if (sizeof($allSettingsDisplay) > 1) {
            echo "			<div style='padding: 3px; background: #995; font-weight: bold'>{$displayInfo['titulo']}:</div>";
        }
        foreach ($displayInfo['allPermissoes'] as $title => $allSettings2) {
            echo "<div style='background: #DDF; font-weight: bold; padding: 5px'>{$title}</div>";
            echo "<table cellpadding='3' style='font: 12px Arial'>";
            foreach ($allSettings2 as $settingKey => $item) {
                echo "<tr>";
                echo "<td colspan='2' bgcolor='#EEEEEE' title='{$settingKey}'>";
                echo dInput2::checkbox("name='{$settingKey}:{$displayInfo['id']}' onclick='changeCbOk({$a->getPrimaryValue()}, this)'",
                    $item['checked'], $item['title']);
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "		</td>";
    echo "	</tr>";
    echo "</table>";
}
dALForm::Finish();

echo "<br />";

?>
    <script type='text/javascript'>
        var usuarId = "<?=$a->v('id')?>";
    </script>
    <script type='text/javascript'>
        function changeCbOk(usuarId, obj) {
            var parts = obj.name.match(/^(.+):(.*?)$/);
            var key = parts[1];
            var key_id = parts[2];
            $.post("ajax.usuario_edit.php?action=managePermissions", {
                usuarId: usuarId,
                key: key,
                key_id: key_id,
                nv: obj.checked ? '1' : '0'
            }, function (ret) {
                if (ret == 'CHECKED')
                    obj.checked = true;
                else if (ret == 'UNCHECKED')
                    obj.checked = false;
                else {
                    alert(ret);
                    obj.checked = !obj.checked;
                }
                obj.disabled = false;
            });
            obj.disabled = true;
            return true;
        }
    </script>

<? if ($a->v('id')): ?>
    <script>
        var socialFb = {
            fbGenerate: function (time) {
                $.post("ajax.usuario_edit.php?action=fbGenerate", {usuarId: usuarId, time: time}, function (ret) {
                    $(".fbInviteLink").html(ret);
                });
                return false;
            },
            fbRevokeInvite: function () {
                $.post("ajax.usuario_edit.php?action=fbRevokeInvite", {usuarId: usuarId}, function (ret) {
                    $(".fbInviteLink").html(ret);
                });
                return false;
            }
        };
        $(function () {
            socialFb.fbGenerate('');
        });
    </script>
<? endif ?>
<?php
dAL::layBottom();
