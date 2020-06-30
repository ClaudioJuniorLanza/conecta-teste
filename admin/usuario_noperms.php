<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLoggedOrRedirect();

dAL::layTop(array('bodyTitle' => "Algo estanho acontece..."));

echo "Aparentemente, você não tem permissão suficiente no sistema para executar a ação desejada.<br />";
echo "Nossos técnicos já foram notificados para identificar o que está acontecendo.<br />";
echo "<br />";
echo "Se você realmente acha que deveria ter permissão para fazer o que você estava tentando, então ";
echo "por favor, entre em contato com seu supervidor, ou diretamente com o suporte técnico IMAGINACOM.<br />";

dAL::layBottom();
