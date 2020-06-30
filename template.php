<?php
class siteTemplate{
	static $title;
	static $settings;
	static $cache;
	static Function layCima($title, $settings=Array()){
		$siteName = dConfiguracao::getConfig('CORE/NOME_DO_SITE');
		$settings = dHelper2::addDefaultToArray($settings, Array(
			'menuSel'      =>basename($_SERVER['PHP_SELF']),
			'isHome'       =>true,
			
			'extraCss'     =>Array(),
			'extraJquery'  =>Array(),
			'extraHeaders' =>Array(),
		));
		
		// Configurações dinâmicas do template:
		self::$title    = &$title;
		self::$settings = &$settings;
		extract($settings, EXTR_REFS);
		
		$allHeaders[] = "<meta charset='utf-8'>";
		$allHeaders[] = "<title>".($title?"{$title} - {$siteName}":"{$siteName}")."</title>";
		$allHeaders[] = "<meta http-equiv='X-UA-Compatible' content='IE=edge'>";
		$allHeaders[] = "<meta name='format-detection' content='telephone=no'>";
		$allHeaders[] = "<meta name='viewport' content='width=device-width,initial-scale=1'>";
		$allHeaders[] = "<base href='".dSystem::getGlobal(dSystem::getEnv('isSSL')?'baseUrlSSL':'baseUrl')."' />";
		
		// Resources:
		// dResLoader::$production = true;
		$allHeaders[] = dResLoader::writeInclude('jquery',  'jquery-1.8.3');
		$allHeaders[] = dResLoader::writeInclude('jquery',  dSystem::getGlobal('localHosted')?'jquery-dEasyRefresh':'');
		$allHeaders[] = dResLoader::writeInclude('jquery',  $extraJquery);
		$allHeaders[] = dResLoader::writeRenderBlock ('css',     'normalize,template');
		$allHeaders[] = dResLoader::writeRenderBlock ('css',     $extraCss);
		$allHeaders[] = dResLoader::writeInclude('css',     'font-awesome');
		$allHeaders[] = dResLoader::writeRenderBlock('css', 'https://fonts.googleapis.com/css?family=Exo:300,400,700');
		$allHeaders = array_merge($allHeaders, $extraHeaders);
	?><!DOCTYPE html>
<html lang='pt-BR'>
<head>
	<?=implode("\r\n\t\t", array_filter($allHeaders)); ?>
	<link rel="apple-touch-icon" sizes="180x180" href="images/favicons/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="images/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="images/favicons/favicon-16x16.png">
	<link rel="manifest" href="images/favicons/site.webmanifest">
	<link rel="mask-icon" href="images/favicons/safari-pinned-tab.svg" color="#44b954">
	<link rel="shortcut icon" href="images/favicons/favicon.ico">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-config" content="images/favicons/browserconfig.xml">
	<meta name="theme-color" content="#FFFFFF">
	<meta property="og:image:width" content="279">
	<meta property="og:image:height" content="279">
	<meta property="og:title" content="Conecta Sementes">
	<meta property="og:description" content="Conectando produtores de sementes e distribuidoras de Insumos.">
	<meta property="og:url" content="http://ec2-34-211-129-181.us-west-2.compute.amazonaws.com/">
	<meta property="og:image" content="http://ec2-34-211-129-181.us-west-2.compute.amazonaws.com/images/favicons/logo-og.jpg">
	
	<?php if(!dSystem::getGlobal('localHosted')): ?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','GTM-KSP5N2K');</script>
		
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KSP5N2K"
		height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->	
		
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-41073200-12"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());
		
		  gtag('config', 'UA-41073200-12');
		</script>
	<?php endif ?>
</head>

<body>
	<div id='iefix-wrapper'>
		<div id='fullscreen-wrapper'>
			<?php if(dSystem::getGlobal('localHosted')): ?>
				<div style="position: fixed; padding: 8px; z-index: 200; bottom: 0; right: 0; font-size: 11px; font-family: Arial; background: rgba(255, 255, 255, .9);" id="debugResponsive"></div>
			<?php endif ?>
			<?php if($isHome): ?>
				<header class='fitScreen'>
					<div class="alignCenterWithSides">
						<div class="leftSide"></div>
						<div class="topDesktop">
							<div class="logo">
								<img src="images/logolarge.png" />
							</div>
							<div class='loginOptions'>
								<div class='options'>
									<a href="app/cadastro.php"><img src="images/bt-criesuaconta.png" class='signup' /></a>
									<a href="app/login.php"><img src="images/bt-login.png" class='login' /></a>
								</div>
								<div class='txtRequired'>
									<!--* obrigatório RENASEM-->
									&nbsp;
								</div>
							</div>
						</div>
						<div class="rightSide"></div>
					</div>
					<div class='banner'>
						<h1>Agricultura em Conexão</h1>
						<h2>
							Negociação de sementes
							<span>Produtores de sementes e Distribuidoras de Insumos</span>
						</h2>
						<div class="apps">
							<div class='intro'>
								&nbsp;
							</div>
							<div class='appLinks'>
								<img src="images/bt-appstore.png" />
								<img src="images/bt-googleplay.png" />
							</div>
						</div>
					</div>
				</header>
			<?php endif ?>
			
			<main>
				<?php if($isHome): ?>
				<div class="blockVejaComo">
					<h2>
						Veja como é fácil
						<span>conectar-se a nossa rede</span>
					</h2>
					<div class='steps'>
						<div class="step">
							<div><img src="images/ico-prancheta.png" /></div>
							<b>1. Usuários Verificados</b>
							<span>
								Faça seu cadastro com
								<span>seu número RENASEM.</span>
							</span>
						</div>
						<div class="step">
							<div><img src="images/ico-oportunidade.png" /></div>
							<b>2. Oportunidades de Negócios</b>
							<span>
								Crie anúncios de venda, compra
								<span>ou troca de sementes.</span>
							</span>
						</div>
						<a href="app/cadastro.php" class="step">
							<div><img src="images/ico-cotacao.png" /></div>
							<b>3. Participe de Cotações</b>
							<span>
								Seja notificado das demandas
								<span>e faça sua proposta.</span>
							</span>
						</a>
					</div>
				</div>
				
				<div class="blockPalmaDaMao">
					<h2>Tudo na palma da sua mão!</h2>
					<h3>
						Otimize o tempo e recurso.
						<span>Aumente a eficiência dos seus negócios.</span>
					</h3>
					
					<div class='twoCols'>
						<div class='image'>
							<img src="images/mobile-sample2.jpg" />
						</div>
						<div class='about'>
							<h3>Serviços</h3>
							<p>
								A Conecta Sementes é uma empresa voltada à intermediação, consultoria e assessoria em negócios de sementes como licenciamentos,
								produção, desenvolvimento e comercialização.<br />
								<br />
								Possuímos uma grande rede de contato, desta forma antecipamos as necessidades de nossos parceiros através de uma plataforma on-line,
								onde é compartilhada as oportunidades de comercialização de sementes, através de nossos Agentes Autônomo de Venda.<br />
								<br />
								Toda intermediação de um produto, ocorre de forma individualizada, personalizada e sigilosa, preservando a relação comercial entre o vendedor e o cliente comprador.
								Oferecemos também assessoria para licenciamentos, produção, desenvolvimento e comercialização de sementes.
							</p>
						</div>
					</div>
				</div>
				<?php endif ?>
	<?php }
	/** > Conteúdo vem aqui. < **/
	static Function layBaixo(){
		$title    = &self::$title;
		$settings = &self::$settings;
	?>
				<?php if($settings['isHome']): ?>
					<div class="blockFaleConosco">
						<h2>Fale Conosco</h2>
						<h3>Quer saber mais sobre nossos serviços?</h3>
						<p>
							Descubra como podemos ajudar sua empresa a obter mais eficiência
							através da nossa plataforma de venda, compra e troca de sementes.
						</p>
						<div class='btChat'>
							<a href="#" onclick="window.open('https://wa.me/5543996630909?text=Preciso%20de%20ajuda%20no%20Conecta%20Sementes.'); return false;" id='btnChatOnline'><img src="images/bt-chatonline.png" alt="Chat On-Line" /></a>
						</div>
						<div class='emailHolder'>
							<b>E-mail: </b>
							<a href="mailto:comercial@conectasementes.com.br">comercial@conectasementes.com.br</a>
						</div>
						<div class='socialNetworks'>
							<b>Redes Sociais</b>
							<map name="SocialMap">
								<area target='_blank' href="https://www.facebook.com/conecta.sementes.7" shape="rect" coords="0, 0, 40, 38">
								<area target='_blank' href="https://twitter.com/ConectaSementes" shape="rect" coords="54, 0, 94, 38">
								<area target='_blank' href="https://www.instagram.com/conectasementes/" shape="rect" coords="109, 0, 149, 38">
								<area target='_blank' href="https://www.linkedin.com/company/conecta-sementes/" shape="rect" coords="164, 0, 204, 38">
							</map>
							<img src="images/social-networks.png" usemap="#SocialMap" />
						</div>
					</div>
				<?php endif ?>
			</main>
			<?php if($settings['isHome']): ?>
				<footer class="rodape">
					<div class='sobre'>
						<b>© Conecta Sementes</b><br />
						Todos os direitos reservados.<br />
						<div class="termos">
							<a href="termos-de-uso.php" target='_blank'>Termos e Condições</a> | <a href="politica-privacidade.php" target='_blank'>Política de Privacidade</a>
						</div>
					</div>
					<div class='agencia'>
						<a href="https://www.imaginacom.com/" target='_blank'>imaginacom</a>
					</div>
				</footer>
			<?php endif ?>
		</div>
	</div>
    <?php
        if(@$_COOKIE['XDEBUG_SESSION']){
            echo "<a href=\"#\" style=\"position: fixed; bottom: 0; left: 0; background: rgba(255, 255, 255, .9); color: #000; text-decoration: none; padding: 8px;\" onclick=\"$.post('admin/ajax.template.php', { action: 'disableXDebug' }); $(this).fadeOut(); return false;\">Desconectar XDEBUG</a>";
        }
    ?>
	<?php if($settings['menuSel']): ?>
		<script>
			var menuSel = '<?=$settings['menuSel']?>';
			$(function(){
				$("nav a[href^='"+menuSel+"'],nav a[rel='"+menuSel+"']").addClass('sel');
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
