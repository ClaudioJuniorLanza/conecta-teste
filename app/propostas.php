<?php
require_once "config.php";
require_once "template.php";

$usuarObj   = cUsuario::isLoggedOrRedirect();
$usuarObj->isComercianteOrRedirect();

$tipo       = (@$_GET['t'] == 'recebidas')?'recebidas':'enviadas';
$filter     = (@$_GET['f'] == 'finalizadas'?'finalizadas':'andamento');
$filterList = [
	'andamento'   => "Em andamento",
	'finalizadas' => "Arquivadas",
];
if(!isset($filterList[$filter])){
	$filter = 'finalizadas';
}

$propoList = [];
if($tipo == 'recebidas'){
	$_xtraWhere = ($filter=='andamento')?
		"c_propostas.status IN('Enviada','Aceita') and !isnull(c_propostas.data_revisado)":
		"c_propostas.status IN('Rejeitada','Negócio Fechado','Negócio Desfeito','Cancelada') and !isnull(c_propostas.data_revisado)";
	
	$propoList  = cProposta::multiLoad("where anuncObj.usuar_id='{$usuarObj->v('id')}' and {$_xtraWhere}", 'anuncObj');
}
elseif($tipo == 'enviadas'){
	$_xtraWhere = ($filter=='andamento')?
		"c_propostas.status IN('Enviada','Aceita')":
		"c_propostas.status IN('Rejeitada pelo Admin', 'Rejeitada','Negócio Fechado','Negócio Desfeito','Cancelada')";
	
	$propoList  = cProposta::multiLoad("where c_propostas.usuar_id='{$usuarObj->v('id')}' and {$_xtraWhere}", 'anuncObj');
}

layCima(false, Array(
	'extraCss'   =>['list-v2'],
	'extraJquery'=>'anunc-v2',
	'menuSel'    =>"propostas-{$tipo}",
));
?>
	<div class="mobileBar">
		Propostas <?=$tipo?>
	</div>
	
	<div class="barTop searchBar">
		<div>
			<b>Mostrar</b>
			<?=dInput2::select("id='dropFilterBy'", $filterList, $filter);?>
		</div>
	</div>

	<h1>Propostas <?=ucfirst($tipo)?> (<?=$filterList[$filter]?>)</h1>
	<? if($propoList): ?>
		<div class="anuncListV2">
			<? foreach($propoList as $propoObj){
				/* @var cProposta $propoObj */
				$propoObj->v('anuncObj')->renderAnuncio($usuarObj);
			} ?>
		</div>
	<? else: ?>
		<div style="padding: 16px">
			<?// if($tipo == 'recebidas'): ?>
				Você não tem nenhuma proposta <?=($filter=='andamento')?'em andamento':'arquivadas'?> no momento.<br />
			<?// endif ?>
		</div>
	<? endif ?>






<?php
layBaixo();