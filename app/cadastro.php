<?php
require_once "config.php";
require_once "template.php";

if(cUsuario::isLogged()){
	dHelper2::redirectTo("index.php");
	die;
}

layCima(false, Array(
	'extraJquery'=>'cadastro',
)); ?>
	<div class="cadastro">
		<h1>Conecte-se a nossa rede!</h1>
		<form method='post' id="formCadastro">
			<div class="card grayForm cadastro">
				<div class="title">
					Faça seu Cadastro Gratuito
				</div>
				<div class="form">
					<div class='row'>
						<span>Informe seu RENASEM</span>
						<div class='twocols'>
							<input id='inputRenasem' name='renasem' placeholder="UF-00000/<?=date('Y')?>" class='centerMe' />
							<span id='blockEditRenasem' style='display: none'><a href='#'><i class='fa fa-pencil'> </i></a></span>
						</div>
					</div>
					<div class="row" id='blockRenasemStatus' style='display: none'>
						<span></span>
						<div class='renasemStatus'>
							<!--<i class='fa fa-spinner fa-spin'></i> Checando RENASEM...-->
						</div>
					</div>
					<div id='blockRenasemFound' style='display: none'>
						<div class='foundRazaoSocial'>
							<b>---</b><br />
							<a href="#">Não é você?</a>
						</div>
						<div class='row'>
							<span>Nome</span>
							<div><input name='nome' /></div>
						</div>
						<div cxlass='row'>
							<span>Telefone de contato</span>
							<div><?=dInput2::input("name='telefone' placeholder='(DDD) 99999-9999'", false, 'fone');?></div>
						</div>
						<div class='row'>
							<span>E-mail de contato</span>
							<div><input name='email' /></div>
						</div>
						<div class='row'>
							<span>Defina sua senha</span>
							<div><input type='password' name='senha' /></div>
						</div>
					</div>
				</div>
				
				<div class='statusMessage' style='display: none'> <!-- .success ou .error -->
					<!--<div><img src="images/icon-red-failed.png" /></div>-->
					<!--<span>RENASEM não encontrado.<br />Seu cadastro não poderá prosseguir.</span>-->
				</div>
				<div align='center' class='continueLine'>
					<button class='btnGreen' id='buttonNext'>Continuar</button>
					<div class='acceptTerms'>
						Ao cadastrar-me, declaro que aceito as <a href="../politica-privacidade.php" target='_blank'>Políticas de Privacidade</a>
						e os <a href="../termos-de-uso.php" target='_blank'>Termos e condições de uso do sistema</a>.
					</div>
				</div>
			</div>
			<div align='center'>
				Já tenho cadastro. Quero fazer <a href="login.php">Login</a>
			</div>
			<?php if(dSystem::getGlobal('localHosted') === 1): ?>
				<div id="debug">
					-- Debug for developers --
				</div>
			<?php endif ?>
		</form>
	</div>
	
	<script>
		$(function(){
			// Debug!
			// jqoInpRenasem.val("AL-00080/2014");
			// jqoBtn.click();
		});
	</script>
	
<?php
layBaixo();
