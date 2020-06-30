<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();

$gotoAnuncio = @($_GET['gotoAnuncio']);
if(@$_GET['clien_id']){
	// Mudou de cliente desejado..
	// Isso é necessário pra impedir o botão de "Voltar" entrar em loop infinito.
	if(!$usuarObj->agenteGetActingAs() || $usuarObj->agenteGetActingAs()->v('id') != $_GET['clien_id']){
		$usuarObj->agenteActAs(cUsuario::load($_GET['clien_id']));
		dHelper2::redirectTo("ver-anuncio.php?codigo={$gotoAnuncio}");
	}
}

$dropClientes = $db->singleQuery("select id,nome from c_usuarios where agente_id='{$usuarObj->v('id')}' order by nome");

layCima("Gerenciar clientes", [
	'menuSel'=>'agente_clientes',
	'extraCss'=>['agente'],
]);
?>
	<div class="agente">
		<h1>Selecione ou cadastre o cliente:</h1>
		<p>
			Para ver detalhes do anúncio e fazer uma proposta, você precisa
			cadastrar o seu cliente.<br />
		</p>
		<br />
		<? if($usuarObj->agenteGetActingAs()): ?>
			<a href="ver-anuncio.php?codigo=<?=$gotoAnuncio?>" class='roundBotao'>
				<i class='fa fa-caret-right'></i> Continuar como <b><?=$usuarObj->agenteGetActingAs()->v('nome')?></b>
			</a><br /><br />
		<? endif ?>
		<a href="agente_cliente_edit.php?add=new&gotoAnuncio=<?=$gotoAnuncio?>" class='roundBotao'>
			<i class='fa fa-caret-right'></i> Cadastrar <b>novo cliente</b></b>
		</a><br />
		<br />
		<div style="display: flex; max-width: 360px; align-items: center">
			<span style="padding: 8px"><i class='fa fa-caret-right'></i></span>
			<span style="flex-grow: 1"><?=dInput2::select("id='chooseCliente' style='padding: 8px 16px; font-size: 16px; width: 100%; max-width: 360px'", $dropClientes, false, false, " Selecionar cliente já cadastrado"); ?></span>
		</div>
	</div>
	<script>
		$(function(){
			$("#chooseCliente").on('change', function(){
				var clienId = $(this).val();
				if(!clienId){
					return false;
				}
				
				if(!confirm("Você vai começar a agir em nome desse cliente.")){
					return false;
				}
				
				dHelper2.changeUrl('clien_id', clienId);
			});
		});
	</script>
<?php
layBaixo();
