<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if(!$usuarObj->v('agente_vendedor')){
	dHelper2::redirectTo("agente_central.php");
	die;
}

$varieId = @$db->singleResult("select id from c_ref_variedades where variedade='".addslashes($_GET['produto'])."' limit 1");
if(!$varieId){
	dHelper2::redirectTo("agente_vendedor_cotacao.php");
	die;
}
$varieObj = cRefVariedade::load($varieId);

$s = new dDbSearch3('cAnuncio', [
	'loadExt'    => 'varieObj',
	'onlyFields' => 'id,codigo,data_anuncio,embalagem,pms,regiao,cidade,uf,quantidade,valor_por_embalagem,valor_por_kg',
]);
$s->addWhere("status  = 'Ag. Propostas'");
$s->addWhere("negocio = 'Venda'");
$s->addWhere("varie_id", $varieId);
$s->setOrderBy('valor_por_kg,embalagem,valor_por_embalagem');
$lista = $s->perform();

$orderBy = "preco";
if(@$_GET['ob'] == "preco_ha")  $orderBy = 'preco_ha';
if(@$_GET['ob'] == "distancia") $orderBy = 'distancia';

// Vamos garantir que temos a distancia para todos os itens da lista...
$localDestino = strtolower(dHelper2::removeAccents("{$_GET['cidade']} - {$_GET['uf']}"));
$locaisOrigem = [];
foreach($lista as $anuncObj){
	$_useOrigem = strtolower(dHelper2::removeAccents("{$anuncObj->v('cidade')} - {$anuncObj->v('uf')}"));
	if(!$anuncObj->v('cidade')){
		$_cidadeUf  = dHelper2::csRegiaoITSToCidade($anuncObj->v('regiao'));
		$_useOrigem = $_cidadeUf?
			strtolower(dHelper2::removeAccents(implode(" - ", $_cidadeUf))):
			false;
	}
	
	if($_useOrigem){
		$anuncObj->setVirtual('use_origem', $_useOrigem);
		$locaisOrigem[] = $_useOrigem;
	}
}
cRefDistancia::ensureAllDistances($locaisOrigem, $localDestino);

// Vamos adicionar a DISTÂNCIA e o PREÇO/HA para cada item na lista.
$_allDistancias = [];
$_allCustoHa    = [];
foreach($lista as $idx=>$anuncObj){
	/** @var canuncio $anuncObj */
	$_origem    = $anuncObj->getVirtual('use_origem');
	if($_origem){
		$_distancia = intval(cRefDistancia::getDistance($_origem, $localDestino, false));
		$_custoHa   = $anuncObj->calculaCustoHa($_distancia);
		
		$anuncObj->setVirtual('distancia', $_distancia);
		$anuncObj->setVirtual('preco_ha',  $_custoHa);
		
		$_allDistancias[] = $_distancia;
		$_allCustoHa[]    = $_custoHa;
	}
	else{
		$anuncObj->setVirtual('distancia', '?');
		$anuncObj->setVirtual('preco_ha',  false);
		
		$_allDistancias[] = 0;
		$_allCustoHa[]    = 0;
	}
}

if($orderBy == "distancia"){
	array_multisort($_allDistancias, $lista);;
}
elseif($orderBy == "preco_ha"){
	array_multisort($_allCustoHa, $lista);
}

layCima("Agente Vendedor", [
	'menuSel'     => 'agente_vendedor',
	'extraCss'    => 'agente',
	'extraJquery' => 'agente_vendedor',
]);
?>
	<div class="agente vendedor">
		<div class="backLine">
			<a href="#" onclick="history.go(-1); return false"><i class='fa fa-reply'></i> Voltar</a>
		</div>
		<h1>
			<b><?=$varieObj->v('variedade')?></b> para
			<b><?=htmlspecialchars(dHelper2::stringToTitle(mb_strtolower($_GET['cidade'])))?>, <?=htmlspecialchars(strtoupper($_GET['uf']))?></b>
		</h1>
		<? if($lista): ?>
			<div class="barTop searchBar">
				<div>
					Ordernar por:
					<?=dInput2::select("name='ob'", ['preco'=>'Menor Preço','preco_ha'=>'Menor Preço/ha','distancia'=>'Menor Distância'], $orderBy); ?>
				</div>
			</div>
		<? endif ?>
		<div class="vendaResults">
			<? if($lista): ?>
				<table class="tabela" cellpadding='4' cellspacing='0' border='1' style="border-collapse: collapse" width='100%'>
					<thead>
						<tr>
							<th><b>Anúncio</b></th>
							<th><b>Distância</b></th>
							<th><b>Preço</b></th>
							<th><b>Preço/ha</b></th>
							<th>Ação</th>
						</tr>
					</thead>
					<tbody>
						<? foreach($lista as $anuncObj):
							/** @var cAnuncio $anuncObj */
							$unidade = $anuncObj->v('valor_por_kg')?"kg":"un";
							?>
							<tr>
								<td><?=$anuncObj->v('codigo')?></td>
								<td>Aprox. <?=$anuncObj->getVirtual('distancia')?>km</td>
								<td><?php
									// Preço unitário ou por kiloggrama
									echo "R$ ";
									echo dHelper2::moeda($anuncObj->v('valor_por_kg')?$anuncObj->v('valor_por_kg'):$anuncObj->v('valor_por_embalagem'))."/{$unidade}";
								?></td>
								<td><?php
									// Preço por hectare.
									if($anuncObj->getVirtual('preco_ha')){
										echo "R$ ".dHelper2::moeda($anuncObj->getVirtual('preco_ha'));
									}
									else{
										echo "<small>Indisponível</small>";
									}
									?></td>
								<td align='center'>
									<? if($usuarObj->agenteGetActingAs()): ?>
										<a href="ver-anuncio.php?codigo=<?=$anuncObj->v('codigo')?>" target='_blank' class='roundBotao menor' title="Abrir anúncio"><i class='fa fa-external-link'></i></a>
									<? endif ?>
									<a href="agente_cliente_choose.php?gotoAnuncio=<?=$anuncObj->v('codigo')?>" target='_blank' class='roundBotao menor' title="Cadastrar/escolher clietne"><i class='fa fa-user'></i></a>
								</td>
							</tr>
						<? endforeach ?>
					</tbody>
				</table>
				<br />
			<? else: ?>
				<br />
				<br />
				<br />
				Nenhum resultado encontrado.<br />
				<br />
				<a href="#" onclick="history.go(-1); return false;" class='roundBotao'><i class='fa fa-reply'></i> Voltar</a>
			<? endif ?>
			<br />
			<br />
			<br />
			<br />
			<br />
			<br />
		</div>
	</div>
<script>
	$(function(){
		// Vamos fazer funcionar a barTop.
		$(".barTop select").on('change', function(){
			var changeKey = $(this).attr('name');
			var changeTo  = $(this).val();
			dHelper2.changeUrl(changeKey, changeTo);
		});
	})
</script>
<?
layBaixo();