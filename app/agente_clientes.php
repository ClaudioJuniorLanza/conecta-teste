<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();

$allClientes = cUsuario::multiLoad("where agente_id='{$usuarObj->v('id')}' order by nome");

layCima("Gerenciar clientes", [
	'menuSel'=>'agente_clientes',
	'extraCss'=>['agente'],
]);
?>
	<div class="agente">
		<h1>Meus Clientes</h1>
		<p>
			<b>Esses são os clientes que você representa.</b><br />
			Você pode <b>agir em nome deles</b>, e será o responsável por todas as suas ações.<br />
		</p>
		<br />
		<a href="agente_cliente_edit.php?add=new" class="roundBotao">Cadastrar novo cliente</a><br />
		<br />
		<table width='100%' style='border-collapse: collapse;' border='1' cellpadding='4'>
			<tr>
				<td><b>Cliente</b></td>
				<td><b>Telefone</b></td>
				<td><b>Cidade/UF</b></td>
				<td>Ações</td>
			</tr>
			<? foreach($allClientes as $clienObj): ?>
				<tr>
					<td><?=htmlspecialchars($clienObj->v('nome'))."<br /><small>".htmlspecialchars($clienObj->v('responsavel_nome'))."</small>"?></td>
					<td><?=dHelper2::formataTelefone($clienObj->v('fone1'))?></td>
					<td><?=htmlspecialchars($clienObj->v('cidade').'/'.$clienObj->v('uf'))?></td>
					<td>
						<a href="agente_cliente_edit.php?id=<?=$clienObj->v('id')?>"   class='roundBotao menor'><i class='fa fa-caret-right'></i> Editar cadastro</a>
						<a href="agente_cliente_act_as.php?id=<?=$clienObj->v('id')?>" class='btnActInNameOf roundBotao menor'><i class='fa fa-caret-right'></i> Agir em nome...</a>
					</td>
				</tr>
			<? endforeach ?>
		</table>
	</div>
<?php
layBaixo();
