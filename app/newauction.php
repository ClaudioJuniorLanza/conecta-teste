<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect();
$usuarObj->isComercianteOrRedirect();

$step     = 1;
if(in_array(@$_GET['type'], Array('venda', 'compra', 'troca'))){
	$tipo = $_GET['type'];
	$step = 2;
}
if(@$_GET['show'] == 'sucesso'){
	$step     = 3;
	$anuncCod = $db->singleResult("select codigo from c_anuncios where usuar_id='{$usuarObj->v('id')}' order by data_anuncio desc limit 1");
}

$copyFrom = call_user_func(function() use ($usuarObj){
	if(!isset($_GET['copyfrom'])){
		return false;
	}
	
	$anuncObj = cAnuncio::load($_GET['copyfrom'], [
		'useAsPrimaryKey'=>'codigo',
		'loadExt'=>'varieObj;trocaVarieObj'
	]);
	if(!$anuncObj){
		return false;
	}
	if($anuncObj->v('usuar_id') != $usuarObj->v('id')){
		return false;
	}
	if($anuncObj->v('status')   != 'Concluído'){
		return false;
	}
	
	$copyData = $anuncObj->export(['onlyFields'=>[
		'categoria',
		'germinacao',
		'embalagem',
		'vigor_ea48h',
		'peneira',
		'tratam_indust',
		'tratam_texto',
		'pms',
		'quantidade',
		'frete',
		'valor_por_embalagem',
		'valor_royalties',
		'regiao',
		'forma_pgto',
		'regiao',
	]]);
	$copyData['variedade']      = $anuncObj->v('varieObj')->v('variedade');
	$copyData['trocaVariedade'] = $anuncObj->v('trocaVarieObj')?$anuncObj->v('trocaVarieObj')->v('variedade'):false;
	return $copyData;
});

layCima(false, Array(
	'extraCss'   =>Array('newauction'),
	'extraJquery'=>array('newauction'),
)); ?>
	<script>
		var copyFrom = <?=json_encode($copyFrom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
	</script>
	<div class="barTop">
		<div class="breadCrumbs step<?=$step?>"">
			<a href="newauction.php">Tipo do Anúncio</a>
			<a <?=($step==3)?"href='myauctions.php'":""?>>Informações do Anúncio</a>
			<a>Confirmação do Anúncio</a>
		</div>
	</div>
	
	<? if($step == 1): ?>
		<h1>Escolha o tipo de negócio</h1>
		<div class="chooseNegocio">
		<a class='opcao' href='newauction.php?type=venda'>
			<div class="titleOnGradient">
				Venda
			</div>
			<div class='iconHolder'>
				<img src="images/icon-type-venda.png" />
			</div>
			<div class='roundBtn'>
				Quero vender
			</div>
		</a>
		
		<a class='opcao' href='newauction.php?type=compra'>
			<div class="titleOnGradient">
				Compra
			</div>
			<div class='iconHolder'>
				<img src="images/icon-type-compra.png" />
			</div>
			<div class='roundBtn'>
				Quero comprar
			</div>
		</a>
		
		<a class='opcao' href='newauction.php?type=troca'>
			<div class="titleOnGradient">
				Troca
			</div>
			<div class='iconHolder'>
				<img src="images/icon-type-troca.png" />
			</div>
			<div class='roundBtn'>
				Quero trocar
			</div>
		</a>
		
	</div>
	<? endif ?>

	<? if($step == 2): ?>
		<h1>Preencha as informações</h1>
		<form action="newauction.php" class="novoAnuncio">
			<div class="titleOnGradient">
				Anúncio de <?=$tipo?>
			</div>
			<div class="chooseCultura initial">
				<div class="inputCultura">
					<span class='variTitle'>Variedade/Cultivar</span>
					<span class='inpAndBtn'>
						<input name='inputCultivar' class='inpCultivar' value="" list="cultivarList" />
						<span class='actionBtn'>
							<a href="#">Buscar</a>
						</span>
						<div class='warningMessage' style='display: none'></div>
					</span>
				</div>
				<div class='itemFound' style='display: none'>
					<div>
						<span>Cultura</span>
						<span class='txtCultura'>---</span>
					</div>
					<div>
						<span>Tecnologia</span>
						<span class='txtTecnologia'>---</span>
					</div>
				</div>
			</div>
			
			<div class='moreInfo' style='display: none'>
				<input type="hidden" name="negocio" value="<?=$tipo?>">
				<div class="inputBlock">
					<h2>Preencha a Ficha Técnica</h2>
					<div class='inpGrp'>
						<span>Categoria</span>
						<span>
							<?=dInput2::select("name='categoria'", dHelper2::csDropCategoria(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
					<div class='inpGrp'>
						<span>Germinação</span>
						<span>
							<?=dInput2::select("name='germinacao'", dHelper2::csDropGerminacao(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
					<div class='inpGrp'>
						<span>Embalagem:</span>
						<span>
							<?=dInput2::select("name='embalagem'", dHelper2::csDropEmbalagem(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small style='color: #F00'>* Obrigatório</small><? endif ?>
						</span>
					</div>
					<div class='inpGrp'>
						<span title="Vigor estimado após 48h">Vigor E.A. 48h</span>
						<span>
							<?=dInput2::select("name='vigor_ea48h'", dHelper2::csDropVigorEA48h(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
					<div class='inpGrp'>
						<span>Peneira</span>
						<span>
							<?=dInput2::select("name='peneira'", dHelper2::csDropPeneira(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
					<div class='tIndustrial'>
						<div class="inpGrp">
							<span>Tratam. Industrial</span>
							<span>
								<?=dInput2::select("name='tratam_indust'", dHelper2::csDropTratamentoIndustrial(), false, false, '-- Selecione --')?>
								<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
							</span>
						</div>
						<div class='txtTratamento'>
							<?=dInput2::input("name='tratam_texto' placeholder='Descreva o tratamento'"); ?>
						</div>
					</div>
					<div class='inpGrp'>
						<span>PMS</span>
						<span>
							<?=dInput2::select("name='pms'", dHelper2::csDropPMS(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
					<div class='inpGrp'>
						<span>Quantidade <small>(em KG)</small></span>
						<span>
							<?=dInput2::input("name='quantidade'")?>
							<? if($tipo != 'venda'): ?><small style='color: #F00'>* Obrigatório</small><? endif ?>
						</span>
					</div>
				</div>
				<div class="inputBlock">
					<h2>Transporte e Pagamento</h2>
					<div class='inpGrp'>
						<span>Frete</span>
						<span>
							<?=dInput2::select("name='frete'", dHelper2::csDropFrete(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
					<div class='tIndustrial'>
						<div class='inpGrp'>
							<span>Valor por kg <small>(R$)</small></span>
							<span>
								<?=dInput2::input("name='valor_por_kg'")?>
								<? if($tipo != 'venda'): ?><small style='color: #F00'>* Obrigatório</small><? endif ?>
							</span>
						</div>
						<? if($tipo == 'venda'): ?>
							<a href='#' class='btnInformRoyalties' onclick="$(this).closest('.tIndustrial').find('.royalties').slideToggle(); return false;">Informar valor dos Royalties (Opcional)</a>
							<div class='inpGrp royalties' style='display: none'>
								<span>Valor dos Royalties <small>(R$)</small></span>
								<span><?=dInput2::input("name='valor_royalties'")?></span>
							</div>
						<? endif ?>
					</div>
					<div class='inpGrp'>
						<span><?=($tipo == 'venda')?"Origem da semente":"Destino da semente"?></span>
						<span>
							<?=dInput2::select("name='regiao'", dHelper2::csDropRegiao(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small style='color: #F00'>* Obrigatório</small><? endif ?>
						</span>
					</div>
					<div class='inpGrp'>
						<span>Forma de Pagto.</span>
						<span>
							<?=dInput2::selectStr("name='forma_pgto'", dHelper2::csDropFormaPgto(), false, false, '-- Selecione --')?>
							<? if($tipo != 'venda'): ?><small>Opcional</small><? endif ?>
						</span>
					</div>
				</div>
				<? if($tipo == 'troca'): ?>
					<h2>Quero trocar por:</h2>
					<div class="chooseCultura onTroca">
						<div class="inputCultura">
							<span class='variTitle'>Variedade/Cultivar</span>
							<span class='inpAndBtn'>
								<input name='inputCultivarTroca' class='inpCultivar' value="" list="cultivarList" />
								<span class='actionBtn'>
									<a href="#">Buscar</a>
								</span>
								<div class='warningMessage' style='display: none'></div>
							</span>
						</div>
						<div class='itemFound' style='display: none'>
							<div>
								<span>Cultura</span>
								<span class='txtCultura'>---</span>
							</div>
							<div>
								<span>Tecnologia</span>
								<span class='txtTecnologia'>---</span>
							</div>
						</div>
					</div>
				<? endif ?>
				<div class='statusMessage' style='display: none'> <!-- .success ou .error -->
					<? /*
	                    - Adicione .error ou .success ao .statusMessage;
	                    - Alterne entre red-failed e green-checked
						
						<div><img src="images/icon-red-failed.png" /></div>
						<span>RENASEM não encontrado.<br />Seu cadastro não poderá prosseguir.</span>
	                */ ?>
				</div>
				<div class="centerMe">
					<button class="btnGreen" id="btnConfirmar">Confirmar Anúncio</button>
					<div class="introAnalise">
						Seu Anúncio será enviado para análise
						antes de ser enviado para cotação.
					</div>
				</div>
			</div>
		</form>
		<datalist id="cultivarList" class='cultivarList1'>
			<? foreach(cRefVariedade::multiLoad("order by variedade") as $varieObj): ?>
				<option value="<?=strtoupper($varieObj->v('variedade'))?>" />
			<? endforeach; ?>
		</datalist>
		
		<? if(dSystem::getGlobal('localHosted')): ?>
			<hr />
			<div id="debug" style='overflow: auto'>-- debug for development --</div>
			<? if(!$copyFrom): ?>
				<script>
					$(function(){
						$(".chooseCultura.initial input").val("");
						setTimeout(function(){
							$(".chooseCultura.initial .actionBtn a").click();
						}, 100);
//						$(".chooseCultura.initial a").first().click();
						// setTimeout(function(){
						// 	$("#btnConfirmar").focus();
						// }, 1000);
					});
				</script>
			<? endif ?>
		<? endif ?>
		
	<? endif ?>

	<? if($step == 3): ?>
		<h1>Pedido de anúncio confirmado</h1>
		<div class='greenBox'>
			<div class="destaque">
				Nº DO ANUNCIO: <?=$anuncCod?>
			</div><br />
			Você receberá a confirmação por e-mail
			<span>assim que seu Anúncio for enviado para cotação.</span>
		</div>
	
		<div align='center' style='padding: 0 16px; max-width: 400px; margin: 0 auto'>
			Você pode acompanhar todos os seus Anúncios
			em <a href="myauctions.php">Meus Anúncios</a>.
		</div>
	<? endif ?>
<?php
layBaixo();
