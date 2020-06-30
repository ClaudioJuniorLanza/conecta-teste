<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLoggedOrRedirect();
$usuarObj->checkPermsOrDie('USER_AUDITORIA');

$auditAdmin = (@$_GET['id']); // Se TRUE, está auditando o administrador. Se FALSE, está auditando o cliente.
$targeObj = ($auditAdmin) ?
    dUsuario::load(@$_GET['id']) :
    cUsuario::load(@$_GET['clien_id']);

if ($auditAdmin) {
    if (!$targeObj) {
        dHelper2::redirectTo("usuario_list.php");
        die;
    }

    // Pode auditar o usuário em questão?
    $allowed = $usuarObj->checkPerms('USER_MANAGE_ALL');
    if (!$allowed) {
        if ($usuarObj->checkPerms('USER_MANAGE')) {
            $allowed = ($targeObj->v('usuar_id') == $usuarObj->v('id'));
        }
        if (!$allowed) {
            dHelper2::redirectTo("usuario_noperms.php");
            die;
        }
    }
    if ($targeObj->checkPerms('MASTER_ACCOUNT') && !$usuarObj->checkPerms('MASTER_ACCOUNT')) {
        dHelper2::redirectTo("usuario_noperms.php");
        die;
    }
} else {
    if (!$targeObj) {
        dHelper2::redirectTo("cliente_list.php?type=agAprovados");
        die;
    }

    // Pode auditar o usuário em questão?
    $allowed = $usuarObj->checkPerms('USER_MANAGE_ALL');
    if (!$allowed) {
        dHelper2::redirectTo("usuario_noperms.php");
        die;
    }
}

$useData = isset($_POST['data']) ? $_POST['data'] : false;
$useWhat = isset($_POST['what']) ? $_POST['what'] : false;
$strFilter = isset($_POST['strFilter']) ? $_POST['strFilter'] : false;

dAL::layTop(array('bodyTitle' => "Auditoria de " . ($auditAdmin ? "usuário" : "agente/cliente") . ": {$targeObj->v($auditAdmin?'username':'nome')}"));
dAL::goBack();

dALForm::Start();
dALCampo::Start();
dALCampo::Misc("Selecione uma data:", dInput2::input("name='data'", $useData ? $useData : date('d/m/Y'), 'date'));
dALCampo::Misc("O que analisar: ",
    dInput2::select("name='what'", "Acessos,Modificações", $useWhat ? $useWhat : 'Modificações'));
dALCampo::Misc("Filtrar por conteúdo:", dInput2::input("name='strFilter'", $strFilter));
dALCampo::Finish();
dALForm::Finish();

if ($useData) {
    $useData = dHelper2::brDateToUsDate($useData);

    if ($auditAdmin) {
        $theList = ($useWhat == 'Acessos') ?
            dAuditAcesso::multiLoad("where usuar_id='{$targeObj->v('id')}' and data_hora >= '{$useData} 00:00:00' and data_hora <= '{$useData} 23:59:59'") :
            dAuditObjeto::multiLoad("where usuar_id='{$targeObj->v('id')}' and data_hora >= '{$useData} 00:00:00' and data_hora <= '{$useData} 23:59:59'");
    } else {
        $theList = ($useWhat == 'Acessos') ?
            dAuditAcesso::multiLoad("where '{$targeObj->v('id')}' IN(clien_id, agent_id) and data_hora >= '{$useData} 00:00:00' and data_hora <= '{$useData} 23:59:59'") :
            dAuditObjeto::multiLoad("where '{$targeObj->v('id')}' IN(acessObj.clien_id, acessObj.agent_id) and d_audit_objetos.data_hora >= '{$useData} 00:00:00' and d_audit_objetos.data_hora <= '{$useData} 23:59:59'",
                'acessObj;acessObj.clienObj;acessObj.agentObj');
    }

    echo "<br />";
    if (!$theList) {
        echo "Não há eventos registrados para esta data.<br />";
    } else {
        if ($useWhat == 'Modificações') {
            echo "<table width='100%' cellpadding='2' cellspacing='0' border='1' style='border-collapse: collapse'>";
            echo "	<tr bgcolor='#CCCCCC'>";
            echo "		<td><b>Data/Hora:</b></td>";
            echo "		<td><b>Acesso:</b></td>";
            echo "		<td><b>Objeto:</b></td>";
            echo "		<td><b>Ação:</b></td>";
            echo "		<td><b>Dados:</b></td>";
            echo "	</tr>";
            foreach ($theList as $idx => $itemObj) {
                $strObjeto = "{$itemObj->v('class')}:{$itemObj->v('objet_id')}";
                if ($strFilter) {
                    if (stripos($strObjeto, $strFilter) === false
                        && stripos($itemObj->v('audit_id'), $strFilter) === false
                        && stripos($itemObj->v('acao'), $strFilter) === false
                        && stripos($itemObj->v('dados'), $strFilter) === false) {
                        // Ou seja, não consta em lugar nenhum...
                        continue;
                    }
                }


                $_dados = '**UNSET**';
                @eval("\$_dados = {$itemObj->v('dados')};");
                if ($_dados == '**UNSET**') {
                    $_dados = $itemObj->v('dados');
                }

                echo "<tr bgcolor='" . (($idx % 2) ? '#EEEEEE' : '#DDDDDD') . "'>";
                echo "	<td>{$itemObj->v('data_hora')}</td>";
                echo "	<td>{$itemObj->v('audit_id')}</td>";
                echo "	<td>{$strObjeto}</td>";
                echo "	<td>{$itemObj->v('acao')}</td>";
                echo "	<td>";
                if (!$auditAdmin && $itemObj->v('acessObj')->v('agent_id')) {
                    // Essa ação ocorreu durante um "Agindo em nome de..."
                    // Se estivermos auditando *o cliente simulado*, vamos avisar "Representado pelo agente: XXXX".
                    // Se estivermos auditando *o agente*, vamos avisar "Em nome de: XXXXXX"
                    if ($targeObj->v('id') == $itemObj->v('acessObj')->v('agent_id')) {
                        echo "<small style='color: #F00'>Em nome de: <b>{$itemObj->v('acessObj')->v('clienObj')->v('nome')}</b></small><br />";
                    } else {
                        echo "<small style='color: #F00'>Representado pelo agente: <b>{$itemObj->v('acessObj')->v('agentObj')->v('nome')}</b></small><br />";
                    }
                }
                dHelper2::dump($_dados);
                echo "	</td>";
                echo "</tr>";
            }
            echo "</table>";
        } elseif ($useWhat == 'Acessos') {
            $_output = array();
            foreach ($theList as $auditObj) {
                $_output["IP={$auditObj->v('ip')}, Sessão={$auditObj->v('session_id')}"][] = $auditObj;
            }

            foreach ($_output as $sessao => $eventos) {
                echo "<table width='100%' cellpadding='2' cellspacing='0' border='1' style='border-collapse: collapse; white-space: nowrap'>";
                echo "	<tr bgcolor='#CCCCCC'>";
                echo "		<td colspan='7'>{$sessao}</td>";
                echo "	</tr>";
                echo "	<tr bgcolor='#CCCCCC'>";
                // echo "		<td>#ID</td>";
                echo "		<td>Data/Hora</td>";
                echo "		<td>Ação:</td>";
                // echo "		<td>Filename</td>";
                echo "		<td>URL Completa</td>";
                echo "		<td>Dados adicionais</td>";
                echo "	</tr>";
                foreach ($eventos as $idx => $itemObj) {
                    $strAcao = $itemObj->v('acao') ? "Ação: {$itemObj->v('acao')}" : "";
                    $strAcao .= $itemObj->v('acao_id') ? " (id={$itemObj->v('acao_id')})<br />" : "";
                    $strAcao .= $itemObj->v('explicacao') ? "{$itemObj->v('explicacao')}" : "";
                    if ($strFilter) {
                        if (stripos($itemObj->v('id'), $strFilter) === false
                            && stripos($strAcao, $strFilter) === false
                            && stripos($itemObj->v('filename'), $strFilter) === false
                            && stripos($itemObj->v('reque_uri'), $strFilter) === false
                            && stripos($itemObj->v('post_data'), $strFilter) === false) {
                            // Ou seja, não consta em lugar nenhum...
                            continue;
                        }
                    }

                    if (!$auditAdmin && $itemObj->v('agent_id')) {
                        // Essa ação ocorreu durante um "Agindo em nome de..."
                        // Se estivermos auditando *o cliente simulado*, vamos avisar "Representado pelo agente: XXXX".
                        // Se estivermos auditando *o agente*, vamos avisar "Em nome de: XXXXXX"
                        if ($targeObj->v('id') == $itemObj->v('agent_id')) {
                            $strAcao = "<small style='color: #F00'>Em nome de: <b>{$itemObj->v('clienObj')->v('nome')}</b></small><br />" . $strAcao;
                        } else {
                            $strAcao = "<small style='color: #F00'>Representado pelo agente: <b>{$itemObj->v('agentObj')->v('nome')}</b></small><br />" . $strAcao;
                        }
                    }

                    $_dados = '**UNSET**';
                    @eval("\$_dados = {$itemObj->v('post_data')};");
                    if ($_dados == '**UNSET**') {
                        $_dados = $itemObj->v('post_data');
                    }

                    echo "<tr bgcolor='" . (($idx % 2) ? '#EEEEEE' : '#DDDDDD') . "'>";
                    // echo "	<td>{$itemObj->v('id')}</td>";
                    echo "	<td>{$itemObj->v('data_hora')}</td>";
                    echo "	<td>{$strAcao}</td>";
                    // echo "	<td>{$itemObj->v('filename')}</td>";
                    echo "	<td>{$itemObj->v('reque_uri')}</td>";
                    echo "	<td>";
                    ($_dados !== false) ? dHelper2::dump($_dados) : "";
                    echo "	</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<br />";
            }
        }
    }
}

dAL::layBottom();
