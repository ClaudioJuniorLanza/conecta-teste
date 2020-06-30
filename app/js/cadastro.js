$(function(){
	var jqoForm        = $("#formCadastro");
	var jqoInpRenasem  = $("#inputRenasem");
	var jqoPenRenasem  = $("#blockEditRenasem");
	var jqoRenasStatus = $("#blockRenasemStatus");
	var jqoRenasFound  = $("#blockRenasemFound");
	var jqoWarningBox  = $(".statusMessage");
	var jqoBtn         = $("#buttonNext");
	
	// Setup inicial:
	var _inStep        = 1;     // 1 --> Digitando RENASEM, 2 --> Preenchendo dados cadastrais
	var _isLoading     = false; // Impede mais cliques no botão.
	var _envRenasem    = false;
	var _changeRenasem = function(){
		if(_isLoading){
			return false;
		}
		
		jqoWarningBox.hide();
		jqoRenasFound.slideUp();
		jqoRenasStatus.css('color', '').slideUp();
		jqoPenRenasem.hide();
		jqoInpRenasem
			.css({background: '', fontWeight: ''})
			.prop('readonly', false)
			.focus();
		
		jqoBtn.html("Continuar");
		
		_envRenasem = false;
		_inStep     = 1;
		return false;
	};
	
	jqoBtn.click(function(){
		if(_isLoading){
			// Do nothing.
			return false;
		}
		if(_inStep == 1){
			_isLoading = true;
			jqoWarningBox.hide();
			jqoRenasStatus.find("div").html("<i class='fa fa-spinner fa-spin'></i> Buscando RENASEM...");
			jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
			jqoRenasStatus.slideDown(
				function(){
					$.post("ajax.cadastro.php", {
						action:  'checkRenasem',
						renasem: jqoInpRenasem.val(),
					}, function(ret){
						$("#debug").html(ret);
						ret = $.parseJSON(ret.substr(ret.indexOf("JSON=") + 5));
						
						if(typeof ret == 'object'){
							if(!ret.renasem){
								// Renasem não encontrado!
								jqoRenasStatus
									.css('color', '#900')
									.find("div")
									.html("<i class='fa fa-times'></i> Renasem não encontrado.");
								
								jqoWarningBox.addClass('error').html(
									"<div><img src='images/icon-red-failed.png' /></div>" +
									"<span>" +
									"   RENASEM não encontrado.<br />" +
									"   Seu cadastro não pode prosseguir.<br />" +
									"   <a href='#' onclick=\"$('#btnChatOnline').click(); return false;\"><small>Clique aqui para obter ajuda</small></a>" +
									"</span>"
								).fadeIn();
								
								jqoBtn.html("Continuar");
								jqoInpRenasem.focus();
								_envRenasem = false;
								_isLoading  = false;
								
								return;
							}
							
							jqoPenRenasem.show();
							jqoRenasStatus
								.css('color', '#090')
								.find("div")
								.html("<i class='fa fa-check'></i> Renasem encontrado!");
							
							if(ret.tipo == 'pf'){
								$("input[name=nome]", jqoForm).val(ret.razao);
							}
							
							jqoInpRenasem
								.css({background: 'transparent', fontWeight: 'bold'})
								.prop('readonly', true)
								.val(ret.renasem);
							
							jqoRenasFound.find(".foundRazaoSocial b").html(ret.razao);
							jqoRenasFound.slideDown(function(){
								// Quando terminar de carregar, dê foco no primeiro campo.
								if(ret.tipo == 'pj'){
									jqoRenasFound.find("input[name='nome']").first().focus();
								}
								else{
									jqoRenasFound.find("input[name='telefone']").first().focus();
								}
							});
							
							_envRenasem = ret.renasem;
							_inStep     = 2;
						}
						jqoBtn.html("Cadastrar");
						_isLoading = false;
					}).fail(function(){
						jqoPenRenasem.show();
						jqoRenasStatus
							.css('color', '#900')
							.find("div")
							.html("<i class='fa fa-times'></i> Renasem encontrado!");
						
						_isLoading = false;
					});
				}
			);
		}
		if(_inStep == 2){
			jqoBtn.html("<i class='fa fa-spinner fa-spin'></i>");
			jqoWarningBox.slideUp(function(){
				// Só começa depois de sumir com o WarningBox.
				jqoWarningBox.removeClass('error');
				
				$.post("ajax.cadastro.php", {
					action:  'doSignup',
					renasem: _envRenasem,
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
						'<span>Seu cadastro foi aprovado!<br /><i class="fa fa-spinner fa-spin"></i> Carregando...</span>'
					).slideDown(function(){
						location.href='index.php?tutor=yes';
					});
					jqoBtn.animate({ opacity: 0 });
				}).fail(function(){
					_isLoading = false;
					console.log("Failed.");
				});
			});
		}
		
		return false;
	});
	
	jqoPenRenasem.click(_changeRenasem);
	$(".foundRazaoSocial a").click(_changeRenasem);
});