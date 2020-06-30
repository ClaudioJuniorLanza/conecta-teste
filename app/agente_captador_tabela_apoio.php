<?php
require_once "config.php";
require_once "template.php";

// $usuarObj = cUsuario::isLoggedOrRedirect(true);
// $usuarObj->isAgenteOrRedirect();
// if(!$usuarObj->v('agente_captador')){
// 	// Sem permissões.
// 	dHelper2::redirectTo("agente_central.php");
// 	die;
// }

?><!DOCTYPE html>
<html>
<head>
	<title>Tabela de Apoio</title>
</head>

<body>
<div style="float: left; margin: 8px">
	<a name="categoria"><h1>Categorias</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropCategoria() as $item): ?>
			<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
		<? endforeach; ?>
	</table>
</div>
<div style="float: left; margin: 8px">
	<a name="germinacao"><h1>Germinação</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropGerminacao() as $item): ?>
			<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
		<? endforeach; ?>
	</table>
</div>
<div style="float: left; margin: 8px">
	<a name="embalagem"><h1>Embalagem</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropEmbalagem() as $item): ?>
			<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
		<? endforeach; ?>
	</table>
</div>
<div style="float: left; margin: 8px">
	<a name="vigor"><h1>Vigor E.A. 48h</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropVigorEA48h() as $item): ?>
			<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
		<? endforeach; ?>
	</table>
</div>
<div style="float: left; margin: 8px">
	<a name="peneira"><h1>Peneira</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropPeneira() as $item): ?>
			<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
		<? endforeach; ?>
	</table>
</div>
<div style="float: left; margin: 8px">
	<a name="tratam_indust"><h1>Tratam. Industr.</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropTratamentoIndustrial() as $item): ?>
			<? if($item == 'Sim'): ?>
				<tr>
					<td><i style='color: blue'><b>Nome do Tratamento</b> (Texto livre)</i></td>
				</tr>
			<? else: ?>
				<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
			<? endif ?>
		<? endforeach; ?>
	</table>
</div>
<div style="float: left; margin: 8px">
	<a name="pms"><h1>PMS</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<? foreach(dHelper2::csDropPMS() as $item): ?>
			<tr><td><?=is_array($item)?$item[1]:$item?></td></tr>
		<? endforeach; ?>
	</table>
</div>
<!--<div style="float: left; margin: 8px">-->
<!--	<a name="regiao_its"><h1>Região ITS</h1></a>-->
<!--	<table border='1' cellpadding='4' style="border-collapse: collapse">-->
<!--		--><?// foreach(dHelper2::csDropRegiao() as $item): ?>
<!--			<tr><td>--><?//=is_array($item)?$item[1]:$item?><!--</td></tr>-->
<!--		--><?// endforeach; ?>
<!--	</table>-->
<!--</div>-->
<!--<div style="float: left; margin: 8px">-->
<!--	<a name="formas_pgto"><h1>Formas de Pagamento</h1></a>-->
<!--	<table border='1' cellpadding='4' style="border-collapse: collapse">-->
<!--		--><?// foreach(dHelper2::csDropFormaPgto() as $item): ?>
<!--			<tr><td>--><?//=is_array($item)?$item[1]:$item?><!--</td></tr>-->
<!--		--><?// endforeach; ?>
<!--	</table>-->
<!--</div>-->

<div style="clear: both">
	<a name="produtos"><h1>Produtos Conhecidos:</h1></a>
	<table border='1' cellpadding='4' style="border-collapse: collapse">
		<thead>
			<tr>
				<th><b>Cultura</b></th>
				<th><b>Variedade/Cultivar</b></th>
			</tr>
		</thead>
		<? foreach($db->singleQuery("select cultura,variedade from c_ref_variedades where !isnull(cultura) and !isnull(variedade) order by cultura,variedade") as $row): ?>
			<tr>
				<td><?=$row['cultura']?></td>
				<td><?=$row['variedade']?></td>
			</tr>
		<? endforeach; ?>
	</table>
</div>
	
</body>
</html>
