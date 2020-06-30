<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();

$clienObj = cUsuario::load($_GET['id']);
$usuarObj->isAgenteOfOrDie($clienObj);

if(!$usuarObj->agenteCanActAs($clienObj)){
	die("Você não pode agir como este cliente.");
}
if(@$_GET['confirm']){
	$usuarObj->agenteActAs($clienObj);
	(@$_GET['gotoAnuncio'])?
		dHelper2::redirectTo("ver-anuncio.php?codigo={$_GET['gotoAnuncio']}"):
		dHelper2::redirectTo("index.php");
}

layCima("Gerenciar clientes", [
	'menuSel'=>'agente_clientes',
	'extraCss'=>['agente'],
]);
?>
	<div class="agente">
		<div class="backLine">
			<a href="agente_clientes.php"><i class='fa fa-caret-left'></i> Voltar</a>
		</div>
		
		<h1>Agir como: <span style='color: #00F'><?=$clienObj->v('nome');?></span></h1>
		<p>Antes de continuar, você deve estar ciente do seguinte:</p>
		<ul>
			<li>Você terá a liberdade de <b>agir em nome do cliente</b>;</li>
			<li>Você poderá receber, enviar e até mesmo <b>aceitar</b> propostas;</li>
			<li>Você será responsabilizado pelas ações que fizer em nome do cliente;</li>
		</ul>
		<a href="<?=$_SERVER['REQUEST_URI']?>&confirm=yes" class='roundBotao'><i class='fa fa-caret-right'></i> Estou ciente - Continuar</a>
	</div>
<?php
layBaixo();
