<?php
require_once "config.php";
require_once "template.php";

// Pós-cadastro:
// - O javascript em agente_cadastro.php redireciona pra cá com ?agente=welcome
// - O javascript em agente.php          redireciona pra cá com ?tutor=yes
// - Ambos trazem pra cá o usuário *já logado* pelo ajax (cUsuario::setLogged($usuarObj))
//
// Pós-login:
// - Quem define o destino é o ajax.login.php, que direciona pra cá sem parâmetros (index.php)
//

$usuarObj      = cUsuario::isLoggedOrRedirect();

$isComerciante = ($usuarObj->isComerciante());
$isAgente      = ($usuarObj->isAgente());
$isPendente    = ($isAgente && $usuarObj->v('agente_pending'));
$isVendedor    = ($isAgente && !$isPendente && $usuarObj->v('agente_vendedor'));
$isCaptador    = ($isAgente && !$isPendente && $usuarObj->v('agente_captador'));

cAnuncio::checkExpired();

if($isComerciante && !$isAgente){
	dHelper2::redirectTo("newauction.php");
	die;
}
if(!$isComerciante && $isAgente){
	dHelper2::redirectTo("agente_central.php");
	die;
}

layCima("Bem vindo!");
?>
	<div class='barTop'>
	</div>
	<h1>Utilize o menu</h1>
	<p>
		Utilize o menu lateral para escolher sua ação.
	</p>
<?php
layBaixo();