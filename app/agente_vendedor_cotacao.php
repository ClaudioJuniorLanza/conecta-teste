<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if(!$usuarObj->v('agente_vendedor')){
	dHelper2::redirectTo("agente_central.php");
	die;
}

layCima("Agente Vendedor", [
	'menuSel'     => 'agente_vendedor',
	'extraCss'    => 'agente',
	'extraJquery' => 'agente_vendedor',
]);?>
	<form method='get' action='agente_vendedor_search.php' class="agente vendedor fullHeight cotacaoForm">
		<div>
			<h1>Cotação Rapida</h1>
			<hr size='1' />
			<b>Destino da Semente:</b>
			<div class="twoCols">
				<div class="inpGroup">
					<span>UF:</span>
					<div><?=dInput2::input("name='uf' maxlength='2' style='width: 50px; text-transform: uppercase'"); ?></div>
				</div>
				<div class="inpGroup">
					<span>Cidade:</span>
					<div><?=dInput2::input("name='cidade' style='text-transform: capitalize'"); ?></div>
				</div>
			</div>
			<br />
			<b>Selecione o produto:</b>
			<div class="inpGroup">
				<span>Variedade/Cultivar:</span>
				<div><input name='produto' class='inpCultivar' value="" list="cultivarList" style='width: 100%' /></div>
			</div>
			<br />
			<b>Quantidade desejada:</b><br />
			<?=dInput2::select("name='unidade'", 'kg=Kilogramas,área=Área a Ser Plantada'); ?>:
			<?=dInput2::input("name='quantidade' placeholder='Quantidade' type='tel' maxlength='15' style='width: 115px'"); ?><br />
			<br />
		</div>
		<div class='onBottom'>
			<button class="accentBotao">INICIAR COTAÇÃO <i class='fa fa-caret-right'></i><i class='fa fa-caret-right'></i><i class='fa fa-caret-right'></i></button>
		</div>
	</form>

	<script>
		$(function(){
			$("select[name=unidade]").on('input', function(){
				var placeHolder = ($(this).val()=='kg')?'Quantidade':'Área';
				$("input[name=quantidade]").attr('placeholder', placeHolder);
			});
			$(".cotacaoForm").on('submit', function(){
				var jqoProduto = $(".inpCultivar");
				var jqoList    = $("#cultivarList");
				
				var produStr = $.trim(jqoProduto.val());
				if(!produStr.length){
					return false;
				}
				
				if(!$("option[value='"+produStr+"']", jqoList).length){
					alert("O produto buscado não foi encontrado na lista. Tente selecionar um produto da lista.");
					return false;
				}
				
				return true;
			});
		});
	</script>

	<datalist id="cultivarList">
		<? foreach(cRefVariedade::multiLoad("order by variedade") as $varieObj): ?>
			<option value="<?=strtoupper($varieObj->v('variedade'))?>" />
		<? endforeach; ?>
	</datalist>
<?php
layBaixo();