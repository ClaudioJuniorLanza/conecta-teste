<?php
require_once "config.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->agenteStopActing();
dHelper2::redirectTo("agente_clientes.php");
