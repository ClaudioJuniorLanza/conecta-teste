<?php
require_once "config.php";

$usuarObj = dUsuario::isLogged();
if (!$usuarObj) {
    $groupMenu = array();
    return false;
}

$menu = array();
$exModules = dConfiguracao::getConfig('CORE/MODULES');
$exModules = $exModules ? explode(",", $exModules) : false;
if ($exModules) {
    foreach ($exModules as $exModule) {
        $exModule::modCreateMenu($menu);
    }
}

$menu[] = "Pré-cadastros : : fa-list";
$menu[] = "	Renasens   : raw_renasem_list.php    : fa-list";
$menu[] = "	Variedades : ref_variedades_list.php : fa-list";

$count = [
    'agPendentes' => $db->singleResult("select count(id) from c_usuarios where agente_pending='1'"),
    'agAprovados' => $db->singleResult("select count(id) from c_usuarios where agente_pending='0'  and '1' IN (agente_vendedor, agente_captador)"),
    // 'notAgentes' =>$db->singleResult("select count(id) from c_usuarios where agente_vendedor='0' and agente_captador='0'"),
    'notAgentes' => $db->singleResult("select count(id) from c_usuarios where !isnull(renasem) or (agente_pending='0' and agente_vendedor='0' and agente_captador='0')"),
];

$menu[] = "Gerenciar Usuários    : : fa-user";
$menu[] = "	Agentes (Pendentes) <span>{$count['agPendentes']}</span> : cliente_list.php?type=agPendentes";
$menu[] = "	Agentes (Aprovados) <span>{$count['agAprovados']}</span> : cliente_list.php?type=agAprovados";
$menu[] = "	Demais (não agentes) <span>{$count['notAgentes']}</span> : cliente_list.php?type=notAgentes";

$menu[] = "Gerenciar Anúncios   : : fa-newspaper-o";
$menu[] = "	Ag. Revisão   : anunc_list.php?status=Em+Análise";
$menu[] = "	Publicados    : anunc_list.php?status=Ag.+Propostas";
$menu[] = "	Concluídos    : anunc_list.php?status=Concluído";

$menu[] = "Agentes";
$menu[] = "	Agentes (Pendentes) <span>{$count['agPendentes']}</span> : cliente_list.php?type=agPendentes";
$menu[] = "	Agentes (Aprovados) <span>{$count['agAprovados']}</span> : cliente_list.php?type=agAprovados";
$menu[] = "	Ver atividade : agente_history.php";

$menu[] = "Banco de Dados : db_reports.php : fa-database";

$menu[] = "Convidar usuários : anunc_invite_list.php : fa-address-book-o";

$menu[] = "Relatório Gerencial : report_gerencial.php : fa-list-alt";

if ($usuarObj->checkPerms('SENTMAIL_VIEW')) {
    $menu[] = "Relatório de contatos : email_list.php : fa-envelope-o";
}
if ($usuarObj->checkPerms('USER_MANAGE')) {
    $menu[] = "Administradores : usuario_list.php : fa-users";
}

$menu[] = "Alterar senha : change_password.php : fa-key";
$menu[] = "Acessar o site : " . dSystem::getGlobal('baseUrl') . " : fa-external-link";
$menu[] = "Acessar o app  : " . dSystem::getGlobal('baseUrl') . "/app/ : fa-external-link";
$menu[] = "Log-out : logout.php : fa-sign-out";

$groupMenu = array();
foreach ($menu as $idx => $item) {
    $item = str_replace("://", "--dot.slash.slash--", $item);
    $info = explode(":", trim($item), 3);
    if (sizeof($info) > 1) {
        $info[1] = str_replace("--dot.slash.slash--", "://", $info[1]);
    }

    $lvl = 0;
    if (substr($item, 0, 3) == "\t\t\t") {
        $lvl = 3;
    } elseif (substr($item, 0, 2) == "\t\t") {
        $lvl = 2;
    } elseif (substr($item, 0, 1) == "\t") {
        $lvl = 1;
    } elseif (substr($item, 0, 1) != "\t") {
        $lvl = 0;
    }

    $thisItem = array(
        'title' => @$info[0],
        'link' => @$info[1],
        'icon' => @$info[2],
        'subs' => array()
    );
    if ($lvl == 0) {
        $groupMenu[] = $thisItem;
    }
    if ($lvl == 1) {
        $lastIdx1 = sizeof($groupMenu) - 1;
        $groupMenu[$lastIdx1]['subs'][] = $thisItem;
    }
    if ($lvl == 2) {
        $lastIdx1 = sizeof($groupMenu) - 1;
        $lastIdx2 = sizeof($groupMenu[$lastIdx1]['subs']) - 1;
        $groupMenu[$lastIdx1]['subs'][$lastIdx2]['subs'][] = $thisItem;
    }
    if ($lvl == 3) {
        $lastIdx1 = sizeof($groupMenu) - 1;
        $lastIdx2 = sizeof($groupMenu['subs'][$lastIdx1]) - 1;
        $lastIdx3 = sizeof($groupMenu['subs'][$lastIdx1]['subs'][$lastIdx2]) - 1;
        $groupMenu[$lastIdx1]['subs'][$lastIdx2]['subs'][$lastIdx3]['subs'][] = $thisItem;
    }
}


