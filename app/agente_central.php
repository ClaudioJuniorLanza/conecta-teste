<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect(true);

if($usuarObj->v('agente_pending')){
	layCima("Cadastro aguardando liberação!");
	?>
		<div class='barTop'></div>
		<h1>Cadastro bem sucedido!</h1>
		<div class='greenBox'>
			<div class="destaque">
				Seja bem vindo!
			</div><br />
			Entraremos em contato com instruções
			<span>assim que seu cadastro for aprovado.</span>
		</div>
	<?php
	layBaixo();
	die;
}

if(!$usuarObj->v('agente_vendedor') &&  $usuarObj->v('agente_captador')){
	dHelper2::redirectTo("agente_captador.php");
	die;
}
if( $usuarObj->v('agente_vendedor') && !$usuarObj->v('agente_captador')){
	dHelper2::redirectTo("agente_vendedor.php");
	die;
}

layCima("Central do Agente", ['menuSel'=>'agente_central']);
?>
	<h1>Central do Agente</h1>
	<ul>
		<li><a href="agente_clientes.php">Exibir <b>Meus Clientes</b></a></li>
		<li><a href="agente_captador.php">Acessar interface <b>Captador</b></a></li>
		<li><a href="agente_vendedor.php">Acessar interface <b>Vendedor</b></a></li>
	</ul>
	<script>
		$(function(){
			$("nav.menuList a[rel=agente_central]").next().slideDown();
		});
	</script>
<?php
layBaixo();
