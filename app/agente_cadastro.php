<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLogged();
if($usuarObj){
	if($usuarObj->isAgente() || $usuarObj->getAgente()){
		// Já é um agente, ou é um agente simulando login...
		dHelper2::redirectTo("agente_central.php");
		die;
	}
	
	siteTemplate::layCima("Torne-se um agente!"); ?>
	<p>
		Como você já está cadastrado na plataforma,
		<a href="#" onclick="$('#btnChatOnline').click(); return false;">
			fale conosco pelo WhatsApp
		</a> para informações sobre como se tornar um agente.
	</p>
	<?php
	siteTemplate::layBaixo();
	die;
}

siteTemplate::layCima("Torne-se um agente!"); ?>
	<script> loadJquery('../login/api.js') </script>
	
	<div class="cadastro">
		<h1>Torne-se um agente!</h1>
		<form method='post' id="formCadastro">
			<div class="card grayForm cadastro">
				<div class="title">
					Participe da rede que está mudando o mercado brasileiro.
				</div>
				<div class="form">
					<div class='row'>
						<span>Nome Completo</span>
						<div><input name='nome' /></div>
					</div>
					<div class='row'>
						<span>Telefone de contato</span>
						<div><?=dInput2::input("name='telefone' placeholder='(DDD) 99999-9999'", false, 'fone');?></div>
					</div>
					<div class='row'>
						<span>E-mail de contato</span>
						<div><input name='email' /></div>
					</div>
					<div class="chooseMethod">
						<div class="intro">
							Como você deseja fazer login?
						</div>
						<div class="loginOptions">
							<div id="loginWithGoogle"></div>
							<div id="loginWithFacebook"></div>
							<div id="loginWithPassword">
								<input type='password' name="senha" placeholder="Login com senha (opcional)" />
							</div>
							<input type='hidden' name='google_token' value="<?=@$_POST['google_token']?>" /><br />
							<input type='hidden' name='facebook_token' value="<?=@$_POST['facebook_token']?>" />
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
			<? if(dSystem::getGlobal('localHosted')): ?>
				<div id="debug">
					-- Debug for developers --
				</div>
			<? endif ?>
		</form>
	</div>
	<script>
		$(function(){
			// Ativa o Login com redes sociais...
			_handleLogin = function(provider, token, rawData){
				$("input[name="+provider+"_token]").val(token);
				return true;
			};
			_handleUncheck = function(provider){
				$("input[name="+provider+"_token]").val("");
				return true;
			};
			
			var lwg = loginWithImaginacom.create('loginWithGoogle', {
				providers:   'google',
				onLogin:     _handleLogin,
				onUncheck:   _handleUncheck,
				checked:     ($("input[name=google_token]").val().length>0)?'google':'0',
			});
			var lwf = loginWithImaginacom.create('loginWithFacebook', {
				providers:   'facebook',
				onLogin:     _handleLogin,
				onUncheck:   _handleUncheck,
				checked:     ($("input[name=facebook_token]").val().length>0)?'facebook':'',
			});
		});
	</script>
	<script>
		$(function(){
			var jqoForm        = $("#formCadastro");
			var jqoWarningBox  = $(".statusMessage");
			var jqoBtn         = $("#buttonNext");
			
			var _isLoading = false;
			jqoBtn.click(function(){
				if(_isLoading){
					return false;
				}
				
				_isLoading = true;
				jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
				jqoWarningBox.slideUp(function(){
					// Só começa depois de sumir com o WarningBox.
					jqoWarningBox.removeClass('error');
					
					$.post("ajax.agente_cadastro.php", {
						action:  'doSignup',
						theform: jqoForm.serializeArray(),
					}, function(ret){
						if(ret != 'OK'){
							jqoWarningBox.addClass('error').html(
								'<div><img src="images/icon-red-failed.png" /></div>'+
								'<span>'+ret+'</span>'
							).slideDown();
							jqoBtn.html("Cadastrar");
							_isLoading = false;
							return;
						}
						
						jqoForm.find("input").css('background', 'transparent').prop('readonly', true);
						jqoWarningBox.addClass('success').html(
							'<div><img src="images/icon-green-checked.png" /></div>'+
							'<span>Seu cadastro foi enviado pra análise!<br /><i class="fa fa-spinner fa-spin"></i> Carregando...</span>'
						).slideDown(function(){
							location.href='index.php?agente=welcome';
						});
						jqoBtn.animate({ opacity: 0 });
					}).fail(function(){
							jqoWarningBox.addClass('error').html(
								'<div><img src="images/icon-red-failed.png" /></div>'+
								'<span>Erro de conexão. Tente novamente.</span>'
							).slideDown();
							jqoBtn.html("Cadastrar");
							_isLoading = false;
							return;
					});
				});
				
				return false;
			});
		});
	</script>
	
<?php
siteTemplate::layBaixo();