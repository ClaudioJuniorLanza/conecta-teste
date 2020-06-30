<?php
require_once "config.php";
require_once "template.php";

if(@$_GET['type'] && in_array($_GET['type'], Array('compra', 'venda', 'troca'))){
	dHelper2::includePage('inc.listauctions.php', [
		'page'=>'Meus-Anuncios',
		'type'=>$_GET['type']
	]);
	die;
}

$usuarObj = cUsuario::isLoggedOrRedirect();
$usuarObj->isComercianteOrRedirect();

$count    = $db->singleIndexV("
	select
		negocio,
		count(id)
	from
		c_anuncios
	WHERE
		usuar_id = '{$usuarObj->v('id')}'
		and status NOT IN('Cancelado', 'Concluído')
	group by(negocio)
");

layCima("Buscar oportunidades", Array('extraCss'=>Array('newauction')));
?>
	<h1>Meus Anúncios</h1>
	<div class="chooseNegocio">
	<a class='opcao' href='myauctions.php?type=venda'>
		<div class="titleOnGradient">
			Venda
			<?=@$count['Venda']?"<small>{$count['Venda']}</small>":""?>
		</div>
		<div class='iconHolder'>
			<img src="images/icon-type-venda.png" />
		</div>
		<div class='roundBtn'>
			Anúncio de Venda
		</div>
	</a>
	
	<a class='opcao' href='myauctions.php?type=compra'>
		<div class="titleOnGradient">
			Compra
			<?=@$count['Compra']?"<small>{$count['Compra']}</small>":""?>
		</div>
		<div class='iconHolder'>
			<img src="images/icon-type-compra.png" />
		</div>
		<div class='roundBtn'>
			Anúncio de Compra
		</div>
	</a>
	
	<a class='opcao' href='myauctions.php?type=troca'>
		<div class="titleOnGradient">
			Troca
			<?=@$count['Troca']?"<small>{$count['Troca']}</small>":""?>
		</div>
		<div class='iconHolder'>
			<img src="images/icon-type-troca.png" />
		</div>
		<div class='roundBtn'>
			Anúncio de Troca
		</div>
	</a>
</div>
<?php
layBaixo();
