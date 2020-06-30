<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if(!$usuarObj->v('agente_vendedor')){
	dHelper2::redirectTo("agente_central.php");
	die;
}

layCima("Agente Vendedor", ['menuSel'=>'agente_vendedor', 'extraCss'=>'agente']);
?>
	<div class="agente vendedor fullHeight">
		<div>
			<h1>Central do Agente</h1>
			<p>Como você deseja começar?</p>
			<ul>
				<li><a href="agente_clientes.php">Gerenciar meus clientes</a></li>
				<li><a href="agente_vendedor_cotacao.php">Iniciar uma cotação rápida</a></li>
			</ul>
		</div>
		<div class='onBottom'>
			<a href="agente_vendedor_cotacao.php" class="accentBotao" style="width: 100%">COTAÇÃO RÁPIDA</a>
		</div>
	</div>
<?php
layBaixo();
