<?php
class siteTemplate{
	static $title;
	static $settings;
	static $cache;
	static Function layCima($title, $settings=Array()){
		$siteName = dConfiguracao::getConfig('CORE/NOME_DO_SITE');
		$settings = dHelper2::addDefaultToArray($settings, Array(
			'menuSel'      =>basename($_SERVER['PHP_SELF']),
			
			'extraCss'     =>Array(),
			'extraJquery'  =>Array(),
			'extraHeaders' =>Array(),
		));
		
		$adminObj = dUsuario::isLogged(); // Se houver, o auto-login precisa acontecer logo no início, senão ocorre NOTICE no meio do template.
		$usuarObj = cUsuario::isLogged();
		if($usuarObj){
			$count = cAnuncio::loadCounts($usuarObj);
		}
		else{
			$count    = Array(
				'anuncios'=>0,
				'propostas'=>['recebidas'=>0, 'enviadas'=>0],
				'oportunidades'=>['Compra'=>0, 'Venda'=>0, 'Troca'=>0],
			);
		}

		// Configurações dinâmicas do template:
		self::$title    = &$title;
		self::$settings = &$settings;
		extract($settings, EXTR_REFS);
		
		$allHeaders[] = "<meta charset='utf-8'>";
		$allHeaders[] = "<title>".($title?"{$title} - {$siteName}":"{$siteName}")."</title>";
		$allHeaders[] = "<meta http-equiv='X-UA-Compatible' content='IE=edge'>";
		$allHeaders[] = "<meta name='format-detection' content='telephone=no'>";
		$allHeaders[] = "<meta name='viewport' content='width=device-width,initial-scale=1'>";
		$allHeaders[] = "<base href='".dSystem::getGlobal(dSystem::getEnv('isSSL')?'baseUrlSSL':'baseUrl')."app/' />";
		
		// Resources:
		// dResLoader::$production = true;
		$allHeaders[] = dResLoader::writeInclude('jquery',  'jquery-1.8.3,jquery-dInput2,jquery-dHelper2,jquery-dClickOutside', '../');
		$allHeaders[] = dResLoader::writeInclude('css',     'font-awesome', '../');
		
		$allHeaders[] = dResLoader::writeRenderBlock('css', 'https://fonts.googleapis.com/css?family=Exo:300,400,700');
		
		$allHeaders[] = dResLoader::writeInclude('jquery',  $extraJquery);
		$allHeaders[] = dResLoader::writeRenderBlock('css',     array('normalize', 'template'));
		$allHeaders[] = dResLoader::writeRenderBlock('css',     $extraCss);
		$allHeaders = array_merge($allHeaders, $extraHeaders);
	?><!DOCTYPE html>
<html lang='pt-BR'>
<head>
	<?=implode("\r\n\t\t", array_filter($allHeaders)); ?>
	<? if(dSystem::getGlobal('localHosted')): ?>
		<?=dResLoader::writeInclude('jquery', 'jquery-dEasyRefresh', '..'); ?>
		<script> $(function(){ dEasyRefresh.relPath = '../'; dEasyRefresh.toHerePath = 'app/'; }); </script>
	<? endif ?>
	<link rel="apple-touch-icon" sizes="180x180" href="../images/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../images/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../images/favicons/favicon-16x16.png">
	<link rel="manifest" href="../images/favicons/site.webmanifest">
	<link rel="mask-icon" href="../images/favicons/safari-pinned-tab.svg" color="#44b954">
	<link rel="shortcut icon" href="../images/favicons/favicon.ico">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-config" content="../images/favicons/browserconfig.xml">
	<meta name="theme-color" content="#FFFFFF">
	<meta property="og:image:width" content="279">
	<meta property="og:image:height" content="279">
	<meta property="og:title" content="Conecta Sementes">
	<meta property="og:description" content="Conectando produtores de sementes e distribuidoras de Insumos.">
	<meta property="og:url" content="http://ec2-34-211-129-181.us-west-2.compute.amazonaws.com/">
	<meta property="og:image" content="http://ec2-34-211-129-181.us-west-2.compute.amazonaws.com/images/favicons/logo-og.jpg">
	
	<? if(!dSystem::getGlobal('localHosted')): ?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','GTM-KSP5N2K');</script>
		
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KSP5N2K"
		height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->	
	
		<!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-41073200-13"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());
		
		  gtag('config', 'UA-41073200-13');
		</script>
	<? endif ?>
</head>

<body>
	<div id='iefix-wrapper'>
		<div id='fullscreen-wrapper'>
			<? if(dSystem::getGlobal('localHosted')): ?>
				<div style="position: fixed; padding: 8px; z-index: 200; bottom: 0; right: 0; font-size: 11px; font-family: Arial; background: rgba(255, 255, 255, .9);" id="debugResponsive"></div>
			<? endif ?>
			<header>
				<div class='desktopTop'>
					<a href="../" class='logo'>
						<img src="images/logolarge2.png" />
					</a>
					<div class='options'>
						<?php if(dUsuario::isLogged()): ?>
							<div style='position: relative'>
								<a href="#" class='admSimulateBtn'>Alternar usuário <i class='fa fa-caret-down'></i></a>
								<div id='admDropUsers' style='display: none'>
									<div class="search">
										<input type="text" id="searchUser">
									</div>
									<div class="list">
										<?php foreach(cUsuario::multiLoad(['onlyFields'=>'id,nome,renasem']) as $_otherObj): ?>
											<a href="#" rel="<?=$_otherObj->v('id')?>" <?=($_otherObj->v('id')==(cUsuario::isLogged()?cUsuario::isLogged()->v('id'):false))?"class='sel'":""?>>
												<b><?=$_otherObj->v('nome')?></b>
												<small><?=$_otherObj->v('renasem')?></small>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
							<script>
								$(function(){
									$(".admSimulateBtn").click(function(){
										$('#admDropUsers').toggle().find("input").focus();
										return false;
									});
									$("#admDropUsers input").on('input', function(){
										$this = $(this);
										// Vamos filtrar.
										var searchFor = $.trim($this.val());
										if(!searchFor.length){
											$("#admDropUsers .list a").show();
										}
										else{
											$("#admDropUsers .list a").each(function(){
												var myText = $(this).text().toLowerCase();
												(myText.indexOf(searchFor) !== -1)?
											        $(this).show():
											        $(this).hide();
											});
										}
									});
									$("#admDropUsers .list a").click(function(){
										var jqoThis = $(this);
										jqoThis.css('background-color', '#EEE');
										$("small", jqoThis).html("<i class='fa fa-spinner fa-spin'></i> Simulando login...");
										$.post("ajax.template.php", {
											acao: 'simularLogin',
											userId: jqoThis.attr('rel'),
										}, function(ret){
											if(ret == 'OK'){
												location.reload();
											}
											else{
												alert(ret);
											}
										})
										
										return false;
									});
									$("#admDropUsers").dClickOutside({ ignoreList: $(".admSimulateBtn") }, function(){ $("#admDropUsers").hide(); });
								})
							</script>
						<?php endif ?>
						<a href="#" onclick="$('#btnChatOnline').click(); return false;"><img src="images/bt-ajuda.png" /></a>
					</div>
				</div>
				<div class='mobileTop'>
					<a href='#' class='hamb' onclick="$('.leftMenu').slideToggle(); return false;">
						<img src="images/bt-bars.png" />
					</a>
					<div class='logo'>
						<img src="images/logosmall.png" />
					</div>
					<div class="options">
						<a href="#" onclick="$('#btnChatOnline').click(); return false;"><img src="images/bt-help.png" /></a>
						<!--<a href="#"><img src="images/bt-user.png" /></a>-->
					</div>
				</div>
			</header>
			<main>
				<!--<div class='sides'>-->
				<!--</div>-->
				<div class='mainContent'>
					<div class="leftMenu" style='display: none'>
						<div class='armazem'>
							<div class='icoHolder'>
								<div><img src="images/ico-armazem.png" /></div>
								<span>Meu Armazém!</span>
							</div>
							<div class='options'>
								<?php if($usuarObj): ?>
									<b><?=$usuarObj->v('nome')?></b>.<br />
									<small>
										<?php if($usuarObj->getAgente()): ?>
											<a href="agente_cliente_stop_acting_as.php">Voltar para Central do Agente</a>
										<?php else: ?>
											<a href="logout.php">Sair</a>
										<?php endif ?>
									</small>
								<?php else: ?>
									<a href="cadastro.php">Cadastre-se</a> ou faça <a href="login.php">Login</a>!
								<?php endif?>
							</div>
						</div>
						
						<nav class="menuList">
							<?php if(!$usuarObj): ?>
								<a href="agente_cadastro.php">Quero ser Agente</a>
								<a href="cadastro.php">Quero me Cadastrar</a>
							<?php else: // Usuário logado ?>
								<?php if(!cUsuario::isLogged(true)->isAgente()): ?>
									<a href="agente_cadastro.php">Quero ser Agente</a>
								<?php else: ?>
									<?php if(cUsuario::isLogged(true)->v('agente_pending')): ?>
										<a href="agente_central.php">Quero ser Agente</a>
									<?php else: ?>
										<a href="#" class='toggleNext' rel='agente_central'>Central do Agente</a>
										<div class="submenu" style='display: none'>
											<a href="agente_clientes.php">Meus Clientes</a>
											<?php if(cUsuario::isLogged(true)->v('agente_captador')): ?>
												<a href="agente_captador.php">Central Captador</a>
											<?php endif ?>
											<?php if(cUsuario::isLogged(true)->v('agente_vendedor')): ?>
												<a href="agente_vendedor.php">Central Vendedor</a>
											<?php endif ?>
										</div>
									<?php endif ?>
								<?php endif ?>
								
								<?php if($usuarObj->isComerciante()): ?>
									<a href="newauction.php">Criar Anúncio</a>
									<a href="myauctions.php">
										Meus Anúncios
										<?php if($count['anuncios']): ?>
											<span class="right">
												<span class="count"><?=$count['anuncios']?></span>
											</span>
										<?php endif ?>
									</a>
									
									<a href="#" class='toggleNext'>
										Propostas
										<span class="right">
											<?php if($count['propostas']['recebidas']+$count['propostas']['enviadas']): ?>
												<span class="count"><?=$count['propostas']['recebidas']+$count['propostas']['enviadas']?></span>
											<?php endif ?>
											<span class='arrow'><i class='fa fa-caret-right'></i></span>
										</span>
									</a>
									<div class="submenu" rel='propostas' style='display: none'>
										<a href="propostas.php?t=recebidas" rel='propostas-recebidas'>
											Recebidas
											<?php if($count['propostas']['recebidas']): ?>
												<span class="right">
													<span class="count"><?=$count['propostas']['recebidas']?></span>
												</span>
											<?php endif ?>
										</a>
										<a href="propostas.php?t=enviadas" rel='propostas-enviadas'>
											Enviadas
											<?php if($count['propostas']['enviadas']): ?>
												<span class="right">
													<span class="count"><?=$count['propostas']['enviadas']?></span>
												</span>
											<?php endif ?>
										</a>
									</div>
									
									<a href="#" class='toggleNext'>
										Oportunidades
										<span class="right">
											<?php if($count['oportunidades']['Venda']+$count['oportunidades']['Compra']+$count['oportunidades']['Troca']): ?>
												<span class="count"><?=@$count['oportunidades']['Venda']+@$count['oportunidades']['Compra']+@$count['oportunidades']['Troca']?></span>
											<?php endif ?>
											<span class="arrow">
												<i class='fa fa-caret-right'></i>
											</span>
										</span>
										
										<small class='arrow'></small>
									</a>
									<div class='submenu' style='display: none'>
										<a href="listauctions.php?type=venda" rel='oportunidades-comprar'>
											Para comprar
											<?php if($count['oportunidades']['Venda']): ?>
												<span class="right">
													<span class="count"><?=@$count['oportunidades']['Venda']?></span>
												</span>
											<?php endif ?>
										</a>
										<a href="listauctions.php?type=compra" rel='oportunidades-vender'>
											Para vender
											<?php if($count['oportunidades']['Compra']): ?>
												<span class="right">
													<span class="count"><?=@$count['oportunidades']['Compra']?></span>
												</span>
											<?php endif ?>
										</a>
										<a href="listauctions.php?type=troca" rel='oportunidades-trocar'>
											Para trocar
											<?php if($count['oportunidades']['Troca']): ?>
												<span class="right">
													<span class="count"><?=@$count['oportunidades']['Troca']?></span>
												</span>
											<?php endif ?>
										</a>
									</div>
								<?php endif ?>
								
								<a href="#" class='toggleNext'>
									Minha Conta
									<span class="right">
										<small class='arrow'><i class='fa fa-caret-right'></i></small>
									</span>
								</a>
								<div class="submenu" style='display: none'>
									<?php if($usuarObj->isComerciante()): ?>
										<a href="meus-interesses.php">Meus Interesses</a>
									<?php endif ?>
									<a href="myaccount.php">Alterar Senha</a>
								</div>
								
							<?php endif ?>
						</nav>
						
						<div class="getHelp">
							<div class='roundTop'></div>
							<div class='middle'>
								<b>Tem dúvidas?</b>
								<span>
									Fale agora mesmo<br />
									com um consultor!<br />
									<a href="#" onclick="window.open('https://wa.me/5543996630909?text=Preciso%20de%20ajuda%20no%20Conecta%20Sementes.'); return false;" id='btnChatOnline'><img src="images/bt-chatonline.png" /></a>
								</span>
							</div>
							<div class='roundBottom'></div>
						</div>
						
					</div>
					<div class="mainBody">
						<?php if($usuarObj && $usuarObj->getAgente()): ?>
							<div class="boxActingAs">
								AGENTE:
								Agindo em nome de <b><?=$usuarObj->v('nome')?></b>. <a href="agente_cliente_stop_acting_as.php">(Parar)</a>
							</div>
						<?php endif ?>
						
	<?php }
	/** > Conteúdo vem aqui. < **/
	static Function layBaixo(){
		$title    = &self::$title;
		$settings = &self::$settings;
	?>
					</div>
				</div>
				<!--<div class='sides'>-->
				<!--</div>-->
			</main>
			<footer class="rodape">
				<div class='sobre'>
					<b>© Conecta Sementes</b><br />
					Todos os direitos reservados.
				</div>
				<div class='agencia'>
					<a href="https://www.imaginacom.com/" target='_blank'>imaginacom</a>
				</div>
			</footer>
		</div>
	</div>
	<?php if($settings['menuSel']): ?>
		<script>
			var menuSel = '<?=$settings['menuSel']?>';
			$(function(){
				var jqoToLight = $("nav a[href^='"+menuSel+"'],nav a[rel='"+menuSel+"']");
				jqoToLight.each(function(){
					$(this).addClass('sel');
					var _jqoSubmenu = $(this).closest('.submenu');
					if(_jqoSubmenu.length){
						_jqoSubmenu.show();
						_jqoSubmenu.prev().addClass('sel').addClass('shown');
					}
				})
			});
		</script>
	<?php endif ?>
	<?=dResLoader::writeInline('js', 'base'); ?>
</body>
</html>
	<?php
	}
}

function layCima($title, $menuSel = false, $settings = Array()) {
	siteTemplate::layCima($title, $menuSel, $settings);
}

function layBaixo() {
	siteTemplate::layBaixo();
}
