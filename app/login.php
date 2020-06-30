<?php
require_once "config.php";
require_once "template.php";

layCima(false, Array()); ?>
	<script> loadJquery('../login/api.js') </script>

	<h1>Faça mais negócios na plataforma</h1>
	<form method='post' id="formLogin">
		<div class="card grayForm">
			<div class="title">
				Digite seus dados de acesso
			</div>
			<div class="form">
				<div class='row'>
					<span>Renasem ou Email</span>
					<div>
						<?=dInput2::input("name='login' class='centerMe'"); ?>
					</div>
				</div>
				<div class='row'>
					<span>Senha</span>
					<div><input name='senha' type='password' class='centerMe' value="<?=dUsuario::isLogged()?'conecta':''?>" /></div>
				</div>
				<div class="row">
					<label>
						<input type='checkbox' name='rememberMe' value='1'> Lembrar de mim neste dispositivo.<br />
						<small>Não utilize essa opção em dispositivos compartilhados.</small>
					</label>
				</div>
			</div>
			<div class='loginMessage' style='display: none'>
			</div>
			<div class='centerMe'>
				<button class='btnGreen' id='btnEntrar'>Entrar</button>
			</div>
			<div style="border-top: 1px solid #888; width: 80%; margin: 32px auto 0 auto; padding-top: 0px; position: relative">
				<span style='position: absolute; top: -14px; background: #EEE; padding: 0 12px; display: inline-block'>ou</span>
				<span style="color: transparent; padding: 0 12px; display: inline-block">ou</span><br />
				<div class="loginOptions">
					<div id="loginWithGoogle"></div>
					<div id="loginWithFacebook"></div>
				</div>
			</div>
		</div>
		<div align='center'>
			Não tenho senha. Quero me <a href="cadastro.php">cadastrar</a>.<br />
			<a href="#" class='linkGray' onclick="$('#btnChatOnline').click(); return false;">Esqueceu a senha?</a>
		</div>
	</form>
	<script>
		var _isLoading = false;
		$(function(){
			var jqoBtn     = $("#btnEntrar");
			jqoBtn.click(function(){
				_isLoading = true;
				jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
				$.post("ajax.login.php", {
					login:    $("#formLogin [name=login]").val(),
					senha:    $("#formLogin [name=senha]").val(),
					remember: $("#formLogin [name='rememberMe']").is(":checked")?'1':'0',
				}, function(ret){
					$("#formLogin .loginMessage").html("Falha ao verificar senha.").fadeIn();
					if(ret.substr(0, 3) == 'OK='){
						$("#formLogin .loginMessage").html("Bem vindo!");
						location.href = ret.substr(3);
						return;
					}
					$("#formLogin .loginMessage").html(ret);
					jqoBtn.html("Entrar");
					_isLoading = false;
				}).fail(function(){
					$("#formLogin .loginMessage").html("Falha ao verificar senha.").fadeIn();
					_isLoading = false;
					jqoBtn.html("Entrar");
				});
				return false;
			});
			
			var lwg;
			var lwf;
			
			// Ativa o Login com redes sociais...
			_handleLogin   = function(provider, token, rawData){
				_isLoading = true;
				jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
				
				$.post("ajax.login.php", {
					provider: provider,
					token:    token,
					remember: $("#formLogin [name='rememberMe']").is(":checked")?'1':'0',
				}, function(ret){
					$("#formLogin .loginMessage").html("Falha ao verificar senha.").fadeIn();
					if(ret.substr(0, 3) == 'OK='){
						$("#formLogin .loginMessage").html("Bem vindo!");
						location.href = ret.substr(3);
						return;
					}
					$("#formLogin .loginMessage").html(ret);
					jqoBtn.html("Entrar");
					_isLoading = false;
					
					if(provider == 'facebook'){
						lwf.setChecked('facebook', false);
					}
					if(provider == 'google'){
						lwg.setChecked('google', false);
					}
				}).fail(function(){
					$("#formLogin .loginMessage").html("Falha ao verificar senha.").fadeIn();
					_isLoading = false;
					jqoBtn.html("Entrar");
					
					if(provider == 'facebook'){
						lwf.setChecked('facebook', false);
					}
					if(provider == 'google'){
						lwg.setChecked('google', false);
					}
				});
				
				return false;
			};
			
			lwg = loginWithImaginacom.create('loginWithGoogle', {
				providers:   'google',
				onLogin:     _handleLogin,
			});
			lwf = loginWithImaginacom.create('loginWithFacebook', {
				providers:   'facebook',
				onLogin:     _handleLogin,
			});
		});
	</script>
<?php
layBaixo();
