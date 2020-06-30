<?php
require_once "config.php";
require_once "template.php";

$usuarObj  = cUsuario::isLoggedOrRedirect();
$success   = false;
$errorList = Array();

if($_POST){
	$usuarObj->v('responsavel_nome', $_POST['responsavel_nome']);
	$usuarObj->v('fone1', $_POST['fone1']);
	$usuarObj->v('email', $_POST['email']);
	$success = $usuarObj->save();
}

layCima(false, Array()); ?>
	<div class="mobileBar">
		Meus dados
	</div>
	<h1>Mantenha seus dados atualizados</h1>

	<? if($success): ?>
		<div class='greenBox'>
			<div class="destaque">
				Dados atualizados com sucesso.
			</div>
		</div>
	<? else: ?>
		<? if($errorList): ?>
			<b>Verifique os seguintes problemas:</b><br />
			- <?=implode("<br />- ", $errorList); ?><br />
			<br />
		<? endif ?>
		<form method='post' id="formLogin">
			<div class="card grayForm">
				<div class="title">
					Verifique e confirme
				</div>
				<div class="form">
					<div class='row'>
						<span>Nome:</span>
						<div><b><?=$usuarObj->v('nome')?></b></div>
					</div>
					<? if($usuarObj->isComerciante()): ?>
						<div class='row'>
							<span>Nome do responsável:</span>
							<div><?=dInput2::input("name='responsavel_nome'", $usuarObj->v('responsavel_nome'))?></div>
						</div>
					<? endif ?>
					<div class='row'>
						<span>Telefone:</span>
						<div><?=dInput2::input("name='fone1'", $usuarObj->v('fone1'), 'fone')?></div>
					</div>
					<div class='row'>
						<span>E-mail:</span>
						<div><?=dInput2::input("name='email'", $usuarObj->v('email'))?></div>
					</div>
				</div>
				<div class='centerMe'>
					<button class='btnGreen' id='btnEntrar'>Salvar</button>
				</div>
			</div>
			<div align='center'>
				<a href="#" class='linkGray' onclick="$('#btnChatOnline').click(); return false;">Modificar outros dados?</a>
			</div>
		</form>
		<script>
			$(function(){
				var jqoBtn     = $("#btnEntrar");
				var _isLoading = false;
				jqoBtn.click(function(){
					if(_isLoading){
						return false;
					}
					_isLoading = true;
					jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
					return true;
				});
			})
		</script>
	<? endif ?>
		
<?php
layBaixo();
