<?php
require_once "config.php";
require_once "template.php";

$type = @$_GET['type'];
if (!$type) {
    $type = 'notAgentes';
}
$isAgente = (substr($type, 0, 2) == 'ag');

$dropMonth = array_map(function ($rawMonth) {
    $year = substr($rawMonth, 0, 4);
    $month = substr($rawMonth, 5);

    return [
        $rawMonth,
        ucfirst(dHelper2::getMonthList($month)) . " de " . $year,
    ];
}, dHelper2::getAllMonthsBetween(date('Y-m'), '2020-01'));
$dropAgentes = array_map(function ($agenteObj) {
    return [
        $agenteObj->v('id'),
        $agenteObj->v('nome'),
    ];
}, cUsuario::multiLoad("where '1' IN (agente_captador, agente_vendedor) order by nome"));

$useMonth = @$_GET['mes'];
$useAgenteId = intval(@$_GET['aid']);
if (!preg_match("/[0-9]{4}-[0-9]{2}/", $useMonth)) {
    $useMonth = date('Y-md');
}

dAL::layTop(array('bodyTitle' => "Gerenciar Atividade do Agente", 'saveGoBack' => true));
?>
    <form method='get'>
        <table>
            <tr>
                <td>Selecione o período:</td>
                <td><?= dInput2::select("name='mes'", $dropMonth, $useMonth) ?></td>
            </tr>
            <tr>
                <td>Exibir apenas o agente:</td>
                <td><?= dInput2::select("name='aid'", $dropAgentes, $useAgenteId, false, "-- Todos --") ?></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button>
                        <i class='fa fa-search'></i> Buscar novamente
                    </button>
                </td>
            </tr>
        </table>
    </form>
    <hr/>

<?php
// agente_central.php --> Página inicial da Central do Agente
// agente_vendedor.php --> Página inicial da Central do Agente
// agente_clientes.php --> Página inicial da Central do Agente
// agente_cliente_edit.php?add=new --> Página inicial da Central do Agente
// agente_cliente_edit.php?add=new --> Página inicial da Central do Agente
// agente_cliente_edit.php?add=new
// agente_cliente_edit.php?gotoAnuncio=&cnpj_ou_renasem=04867569984
// /im/conecta_sementes/site/app/agente_captador_enviar.php
// /im/conecta_sementes/site/app/agente_captador.php
// agente_vendedor_cotacao.php
// agente_vendedor_search.php?uf=pr&cidade=londrina&produto=14SS18+MILHO+SAFRINHA

$searchCache = [
    'varieStr' => [], // Cache of varieStr[str].
    'clienteId' => [], // Cache of cUsuario[id]
];

function getClienteById($id)
{
    global $searchCache;

    if (isset($searchCache['clienteId'][$id])) {
        return $searchCache['clienteId'][$id];
    }

    return
        $searchCache['clienteId'][$id] =
            cUsuario::load($id);
}

function getVariedade($varieStr)
{
    global $searchCache;

    $varieStr = mb_strtoupper($varieStr);
    if (isset($searchCache['varieStr'][$varieStr])) {
        return $searchCache['varieStr'][$varieStr];
    }

    return
        $searchCache['varieStr'][$varieStr] =
            cRefVariedade::load(['...' => "where variedade='" . addslashes($varieStr) . "' limit 1"]);
}

function getNumberOfResults($varieStr, $destUf = false, $destCity = false)
{
    $varieObj = getVariedade($varieStr);
    if (!$varieObj) {
        return false;
    }

    $varieId = $varieObj->v('id');

    // $destUf e $destCity serão ignorados, pois no momento não estamos aplicando os filtros
    // de preferências (onde produtos não podem ser enviados para determinados locais).

    $s = new dDbSearch3('cAnuncio', [
        'onlyFields' => 'id,codigo,negocio,status',
    ]);
    $s->addWhere("status  = 'Ag. Propostas'");
    $s->addWhere("negocio = 'Venda'");
    $s->addWhere("varie_id", $varieId);
    $s->setOrderBy('valor_por_kg,embalagem,valor_por_embalagem');
    $lista = $s->perform();
    return sizeof($lista);
}

// Acao                 | Explicacao
// AGENTE_SEARCH_CLIENT | json: { searchFor: xxxxxxxxx, clienName: yyyyyy }
// AGENTE_SEARCH_FOR    | json: { uf: xx, cidade: yyyy, nResults: yy }

$allFiles = [
    'agente_central.php' => "Acessou a Central do Agente",
    'agente_vendedor.php' => "Acessou a Central Vendedor",
    'agente_clientes.php' => function ($getParams, $postData) {
        if (@$getParams['updated']) {
            return "Voltou para 'Meus Clientes'";
        }
        return "Acessou 'Meus Clientes'";
    },
    'agente_cliente_edit.php' => function ($getParams, $postData, $auditObj) {
        if (@$getParams['add'] == 'new') {
            return "Clicou para cadastrar novo cliente";
        }
        if (@$getParams['cnpj_ou_renasem']) {
            if ($postData) {
                return "Cadastrou ou atualizou os dados do cliente.";
            }

            return [
                "Buscando pelo RENASEM/CNPJ",
                "<a href='{$auditObj->v('reque_uri')}' target='_blank' style='white-space: nowrap''>{$getParams['cnpj_ou_renasem']}</a>"
            ];
        }
        if (@$getParams['id']) {
            $_params = $auditObj->v('explicacao') ?
                json_decode($auditObj->v('explicacao'), true) :
                [];

            // if(!isset($_params['cliente'])){
            $clienObj = getClienteById($getParams['id']);
            $nomeCliente = $clienObj ?
                $clienObj->v('nome') :
                "-- Desconhecido --";

            $_params['cliente'] = $nomeCliente;
            $auditObj->v('explicacao', json_encode($_params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))->save();
            // }

            $nomeCliente = $_params['cliente'];

            if ($auditObj->v('post_data')) {
                eval("\$_tmp = {$auditObj->v('post_data')};");
                $whatChanged = [];
                foreach ($_tmp as $idx => $val) {
                    $whatChanged[] = "{$idx}: {$val}";
                }
                $whatChanged = implode("; ", $whatChanged);

                return [
                    "Alterou dados do cliente",
                    "<a href='{$auditObj->v('reque_uri')}' target='_blank' style='white-space: nowrap'>{$nomeCliente}</a>",
                    // htmlspecialchars($whatChanged),
                ];
            } else {
                return [
                    "Acessou dados do cliente",
                    "<a href='{$auditObj->v('reque_uri')}' target='_blank' style='white-space: nowrap'>{$nomeCliente}</a>",
                ];
            }
        }

        return false;
    },
    'agente_captador.php' => "Acessou a interface de captação",
    'agente_captador_enviar.php' => "Acessou a página para enviar a planilha",
    'agente_vendedor_cotacao.php' => "Clicou para iniciar uma cotação rápida",
    'agente_vendedor_search.php' => function ($getParams, $postData, $auditObj) {
        $_params = $auditObj->v('explicacao') ?
            json_decode($auditObj->v('explicacao'), true) :
            [];

        $_needSave = false;
        if (!isset($_params['nResultados'])) {
            $nResultados = getNumberOfResults($getParams['produto'], $getParams['uf'], $getParams['cidade']);
            $_params['nResultados'] = $nResultados;
            $_needSave = true;
        }
        if (!isset($_params['produto'])) {
            $produObj = getVariedade($getParams['produto']);
            $nomeProduto = $produObj ?
                "{$produObj->v('variedade')}" :
                mb_strtoupper($getParams['produto']);

            $_params['produto'] = $nomeProduto;
            $_needSave = true;
        }
        if ($_needSave) {
            $auditObj->v('explicacao', json_encode($_params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))->save();
        }

        $cidade = mb_strtoupper($getParams['cidade']);
        $uf = mb_strtoupper($getParams['uf']);
        $nomeProduto = $_params['produto'];
        $nResultados = $_params['nResultados'];


        $quantidade = (@$getParams['unidade'] && @$getParams['quantidade']) ?
            (($getParams['unidade'] == 'área') ? "Área: {$getParams['quantidade']}" : "Qtd: {$getParams['quantidade']}kg") :
            "";

        return [
            htmlspecialchars("Realizou busca/cotação"),
            htmlspecialchars($cidade),
            htmlspecialchars($uf),
            htmlspecialchars($nomeProduto),
            htmlspecialchars($quantidade),
            " <a href='{$auditObj->v('reque_uri')}' target='_blank' style='white-space: nowrap'>{$nResultados} resultados</a>",
        ];
    },
];
$allLog = dAuditAcesso::multiLoad("
	where 
		data_hora >= '{$useMonth}-01 00:00:00' and 
		data_hora <  DATE_ADD('{$useMonth}-01 00:00:00', INTERVAL 1 MONTH) and
		filename  IN ('" . implode("', '", array_keys($allFiles)) . "')
		" . ($useAgenteId ? ("and clien_id = '" . intval($useAgenteId) . "'") : "") . "
	order by data_hora desc
	limit 1000", 'clienObj(nome);agentObj(nome)'
);

if ($allLog) {
    echo "<table border='1' style='border-collapse: collapse' cellpadding='4' cellspacing='0' width='100%'>";
    echo "  <thead>";
    echo "      <tr>";
    echo "          <td><b>Data/Hora</b></td>";
    echo "          <td><b>Agente</b></td>";
    echo "          <td nowrap><b>Agindo em nome de...</b></td>";
    echo "          <td><b>Explicação</b></td>";
    echo "      </tr>";
    echo "  </thead>";
    echo "  <tbody>";
    foreach ($allLog as $auditObj) {
        // O campo "agenteName" só estará preenchido se ele estiver simulando login...
        $agenteName = $auditObj->v('agentObj') ? $auditObj->v('agentObj')->v('nome') : false;
        $clientName = $auditObj->v('clienObj') ? $auditObj->v('clienObj')->v('nome') : false;
        if (!$agenteName && $clientName) {
            $agenteName = $clientName;
            $clientName = false;
        }

        if (is_string($allFiles[$auditObj->v('filename')])) {
            $writeString = [
                $allFiles[$auditObj->v('filename')],
            ];
        } else {
            $params = parse_url($auditObj->v('reque_uri'));
            $getParams = [];
            if (@$params['query']) {
                parse_str($params['query'], $getParams);
            }
            $postData = $auditObj->v('post_data');
            $writeString = call_user_func($allFiles[$auditObj->v('filename')], $getParams, $postData, $auditObj);
        }

        echo "<tr>";
        echo "  <td>{$auditObj->v('data_hora')}</td>";
        echo "  <td>{$agenteName}</td>";
        echo "  <td>{$clientName}</td>";
        echo "  <td title='" . htmlspecialchars($auditObj->v('reque_uri')) . "'>" . implode("</td><td>",
                is_array($writeString) ? $writeString : [$writeString]) . "</td>";
        echo "</tr>";
    }
    echo "  </tbody>";
    echo "</table>";
} else {
    echo "<br />";
    echo "<br />";
    echo "Nenhuma atividade detectada no período.<br />";
    echo "<br />";
    echo "<br />";
}

dAL::layBottom();

