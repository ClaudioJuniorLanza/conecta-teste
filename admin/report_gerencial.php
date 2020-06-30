<?php
require_once "config.php";
require_once "template.php";

$_isExcel = (@$_GET['action'] == 'export');

$rows = $db->singleQuery("
	SELECT
		c_anuncios.*,
		anunc.nome as anunciante,
		v.variedade,
		v.cultura,
		v.tecnologia,
		propo.id      as propon_id,
		propo.nome    as proponente,
		vt.tecnologia as troca_por,
		p.regiao as propo_regiao,
		p.valor  as propo_valor,
		p.status as propo_status
	from      c_anuncios
	left join c_ref_variedades v  on v.id = c_anuncios.varie_id
	left join c_ref_variedades vt on v.id = c_anuncios.troca_varie_id
	left join c_propostas p      on c_anuncios.id = p.anunc_id and !ISNULL(p.status)
	left join c_usuarios anunc   on anunc.id      = c_anuncios.usuar_id
	left join c_usuarios propo   on propo.id      = p.usuar_id
	where
		c_anuncios.status != 'Cancelado'
		
	order by data_proposta
");

if (!$_isExcel) {
    dAL::layTop(array(
        'bodyTitle' => "Relatório Gerencial",
    ));
    dAL::goBack(false, "<a href='report_gerencial.php?action=export'>Exportar para Excel</a>");
} else {
    header("Content-Type: application/force-download");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition: filename=\"relatorio.xls\"; charset=utf8");

    echo "<html>";
    echo "<head>";
    echo "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'>";
}


$_moeda = function ($n) {
    if (floatval($n)) {
        return dHelper2::moeda($n);
    }
    return '';
};
$_data = function ($d) {
    if (!$d) {
        return '';
    }

    return date('d/m/Y H:i', strtotime($d));
};

?>

    <style>
        .reportTable {
            border-collapse: collapse;
        }

        .reportTable th,
        .reportTable td {
            white-space: nowrap;
        }
    </style>
    <table border='1' cellpadding='4' class='reportTable'>
        <thead>
        <!-- Anúncio: -->
        <!--<tr>-->
        <!--	<th colspan='24'>Dados do Anúncio</th>-->
        <!--	<th colspan='4'>Proposta</th>-->
        <!--</tr>-->
        <tr>
            <th>Código</th>
            <th>Status</th>
            <th>Criação</th>
            <th>Anunciante</th>
            <th>Negócio</th>
            <th>Variedade/Cultivar</th>
            <th>Cultura</th>
            <th>Tecnologia</th>
            <th>Categoria</th>
            <th>Embalagem</th>
            <th>Peneira</th>
            <th>PMS</th>
            <th>Germinação</th>
            <th>Vigor EA. 48h</th>
            <th>Tratamento Ind.</th>
            <th>Tratamento <small>(Texto)</small></th>
            <th>Frete</th>
            <th>Região</th>
            <th>Forma de Pagamento</th>
            <th>Quantidade</th>
            <th>Valor Finalizado</th>
            <th>Valor dos Royalties</th>
            <th>Troca por</th>
            <th>Data Finalizado</th>

            <!-- Proponente (Se houver) -->
            <th>Proponente</th>
            <th>Região</th>
            <th>Valor da Proposta</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($rows as $idx => $row): ?>
            <tr>
                <td><a href="anunc_edit.php?id=<?= $row['id'] ?>">#<?= $row['codigo'] ?></a></td>
                <td><?= $row['status'] ?></td>
                <td><?= $_data($row['data_anuncio']) ?></td>
                <td><a href="cliente_edit.php?id=<?= $row['usuar_id'] ?>"><?= $row['anunciante'] ?></a></td>
                <td><?= $row['negocio'] ?></td>
                <td><?= $row['variedade'] ?></td>
                <td><?= $row['cultura'] ?></td>
                <td><?= $row['tecnologia'] ?></td>
                <td><?= $row['categoria'] ?></td>
                <td><?= $row['embalagem'] ?></td>
                <td><?= $row['peneira'] ?></td>
                <td><?= $row['pms'] ?></td>
                <td><?= $row['germinacao'] ?></td>
                <td><?= $row['vigor_ea48h'] ?></td>
                <td><?= $row['tratam_indust'] ?></td>
                <td><?= htmlspecialchars($row['tratam_texto']); ?></td>
                <td><?= $row['frete'] ?></td>
                <td><?= $row['regiao'] ?></td>
                <td><?= $row['forma_pgto'] ?></td>
                <td align='center'><?= $row['quantidade'] ?></td>
                <td align='right'><?= $_moeda($row['valor_por_embalagem']) ?></td>
                <td align='right'><?= $_moeda($row['valor_royalties']) ?></td>
                <td><?= $row['troca_por'] ?></td>
                <td><?= $row['data_encerrado'] ?></td>

                <!-- Proponente (Se houver) -->
                <td><a href="cliente_edit.php?id=<?= $row['propon_id'] ?>"><?= $row['proponente'] ?></a></td>
                <td><?= $row['propo_regiao'] ?></td>
                <td><?= $_moeda($row['propo_valor']) ?></td>
                <td><?= $row['propo_status'] ?></td>
            </tr>
        <? endforeach ?>
        </tbody>
    </table>


<?php
if (!$_isExcel) {
    dAL::layBottom();
}