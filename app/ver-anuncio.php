<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect();
$usuarObj->isComercianteOrRedirect();

$anuncObj = cAnuncio::load(@$_GET['codigo'], 'codigo');

// $anuncObj->getPropoObj($usuarObj)->delete();

if(!$anuncObj){
	dHelper2::redirectTo('myauctions.php');
	die;
}

$isProponente = $anuncObj->v('usuar_id') != $usuarObj->v('id');
if($isProponente){
	// Marca como "Lida".
	// Nos testes, o "markAsRead" só será refletido quando o anuncio for recarregado.
	$anuncObj->markAsRead($usuarObj);
}

layCima("Anúncio #{$anuncObj->v('codigo')}", Array(
	'extraCss'   =>['list-v2'],
	'extraJquery'=>'anunc-v2',
));
?>

<h1>Anúncio #<?=$anuncObj->v('codigo')?></h1>
<div class="anuncListV2">
	<?php
	$anuncObj->renderAnuncio($usuarObj, [
		'expandPropostas'=>true,
	]);
	?>
</div>
<?php
layBaixo();
