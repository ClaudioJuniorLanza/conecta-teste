<?php
require_once "config.php";
require_once "template.php";

if(@$_GET['type'] && in_array($_GET['type'], Array('compra', 'venda', 'troca'))){
	dHelper2::includePage('inc.listauctions.php', [
		'page'=>'Oportunidades',
		'type'=>$_GET['type']
	]);
	die;
}

$usuarObj = cUsuario::isLoggedOrRedirect();
$usuarObj->isComercianteOrRedirect();

$count    = $db->singleIndexV("
	select    negocio,count(c_anuncios.id)
	FROM	  c_anuncios
    left join c_propostas propoObj on propoObj.anunc_id = c_anuncios.id and propoObj.usuar_id = '{$usuarObj->v('id')}'
	where
		 c_anuncios.status NOT IN('Cancelado', 'Em Análise') OR
		(c_anuncios.status IN ('Ag. Aceite', 'Ag. Intermediação', 'Concluído') and !isnull(propoObj.status))
	group by(negocio)
");

layCima("Buscar oportunidades", Array('extraCss'=>Array('newauction')));
?>
	<h1>Oportunidades</h1>
	<div class="chooseNegocio">
	<a class='opcao' href='listauctions.php?type=compra'>
		<div class="titleOnGradient">
			Vender
			<?=@$count['Compra']?"<small>{$count['Compra']}</small>":""?>
		</div>
		<div class='iconHolder'>
			<img src="images/icon-type-venda.png" />
		</div>
		<div class='roundBtn'>
			Encontre compradores
		</div>
	</a>
	
	<a class='opcao' href='listauctions.php?type=venda'>
		<div class="titleOnGradient">
			Comprar
			<?=@$count['Venda']?"<small>{$count['Venda']}</small>":""?>
		</div>
		<div class='iconHolder'>
			<img src="images/icon-type-compra.png" />
		</div>
		<div class='roundBtn'>
			Encontre vendedores
		</div>
	</a>
	
	<a class='opcao' href='listauctions.php?type=troca'>
		<div class="titleOnGradient">
			Trocar
			<?=@$count['Troca']?"<small>{$count['Troca']}</small>":""?>
		</div>
		<div class='iconHolder'>
			<img src="images/icon-type-troca.png" />
		</div>
		<div class='roundBtn'>
			Encontre quem deseja trocar
		</div>
	</a>
</div>
<?php
layBaixo();
