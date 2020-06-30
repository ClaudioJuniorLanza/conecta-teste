<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if(!$usuarObj->v('agente_captador')){
	// Sem permissões.
	dHelper2::redirectTo("agente_central.php");
	die;
}

layCima("Central do Captador", [
	'menuSel' =>'agente_captador',
	'extraCss'=>'agente'
]); ?>
		<div class="agente">
			<h1>Central do Agente - Captação</h1>
			<p>
				Sua tarefa é <b>captar</b> anúncios de venda, e mantê-los atualizados.
			</p>
			<br />
			<ol>
				<li><a href="agente_cliente_edit.php?add=new">Cadastre os clientes</a> (anunciantes)</li>
				<li>Baixe a <a href="download/Planilha-Modelo.xlsx" class='roundBotao menor'><i class='fa fa-file-excel-o'></i> Planilha Modelo</a></li>
				<li>Preencha a planilha apenas com anúncios <b>novos</b>!</li>
				<li><a href="agente_captador_enviar.php">Envie a planilha com os novos anúncios!</a></li>
				<li><a href="agente_captador_manage.php">Gerencie seus anúncios</a> para mantê-los sempre atualizados</li>
			</ol>
		</div>
<?php
layBaixo();