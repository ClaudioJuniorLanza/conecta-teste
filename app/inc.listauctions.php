<?php
if(!isset($includeContext)){
	die("Este arquivo não pode ser chamado diretamente.");
}

// Parâmetro obrigatório: PAGE
//     Meus-Anuncios.    $type
$usuarObj   = cUsuario::isLoggedOrRedirect();
$page       = @$params['page'] or die("Sem params[page].");
$title      = false;
$mobTitle   = false;
$displayAs  = false;
$showSeeAll = false;
$anuncList  = Array();
$interesses = false;
$menuSel    = false;

$filterList     = Array();
$filterSelected = @$_GET['f'];

$filterVarieList     = [];
$filterVarieSelected = @$_GET['v'];

if($page == 'Oportunidades'){
	$type       = $_GET['type'];
	$filterList = Array(
		'cotando'    => "Disponíveis",
		'arquivadas' => "Marcadas 'Sem Interesse'",
	);
	if(!$filterSelected || !in_array($filterSelected, array_keys($filterList))){
		$filterSelected = 'cotando';
	}
	
	$title      = "Buscando ";
	if($type == 'venda'){
		$interesseType = 'compra';
		$title .= "vendedores";
		$menuSel = 'oportunidades-comprar';
	}
	if($type == 'compra'){
		$interesseType = 'venda';
		$title .= "compradores";
		$menuSel = 'oportunidades-vender';
	}
	if($type == 'troca'){
		$interesseType = 'troca';
		$title = "Oportunidades de troca";
		$menuSel = 'oportunidades-trocar';
	}
	if($filterSelected && isset($filterList[$filterSelected])){
		if($filterSelected == 'cotando'){
			$title      = "Oportunidades Disponíveis";
		}
		elseif($filterSelected == 'arquivadas'){
			$title      = "Propostas que você marcou como 'Sem Interesse'";
		}
	}
	
	$interesses = $usuarObj->getInteresses();
	if(!$interesses[$interesseType]){
		layCima("Oportunidades de {$interesseType}", [
			'menuSel'=>$menuSel,
		]);
		echo '<div class="boxDestaque">';
		if($type == 'troca'){
			$_rawInteresse = $usuarObj->getInteresses(true);
			if($_rawInteresse['troca']){
				echo '  <b>Você precisa informar seus interesses de compra e de venda</b>';
				echo '  <li>Só assim poderemos calcular seus interesses de troca.</li>';
			}
			else{
				echo '  <b>Você disse não ter interesse em '.$interesseType.'. Isso está errado?</b><br />';
			}
		}
		else{
			echo '  <b>Você disse não ter interesse em '.$interesseType.'. Isso está errado?</b><br />';
		}
		echo "  <a href='meus-interesses.php' class='roundButton'><b><i class='fa fa-caret-right'></i> Atualize seus interesses agora mesmo!</b></a>";
		echo '</div>';
		layBaixo();
		die;
	}
	$interesses = ($type=='troca')?
		Array('userDefined'=>($interesses['compra']['userDefined'] || $interesses['venda']['userDefined'])):
		$interesses[$interesseType];
	
	$anuncList = Array();
	$mobTitle  = "Propostas e Oportunidades";
	$displayAs = 'proponente';
	
	// Vamos gerenciar as informações para os interesses:
	$anuncList = cAnuncio::getOportunidades($usuarObj, ucfirst($type), $filterSelected);
}
elseif($page == 'Meus-Anuncios'){
	$type       = $params['type'];
	$displayAs  = 'anunciante';
	$filterList = Array(
		'analise'   => "Em Análise",
		'cotando'   => "Em cotação",
		'conclusas' => "Concluídos",
	);
	$_xtraWhere = "";
	if(!$filterSelected){
		$filterSelected = 'cotando';
	}
	
	$title = "Meus anúncios de " . ucfirst($type);
	if($filterSelected && isset($filterList[$filterSelected])){
		if($filterSelected == 'analise'){
			$title      = "Anúncios Em Análise";
			$_xtraWhere = "and status = 'Em Análise'";
		}
		elseif($filterSelected == 'cotando'){
			$title      = "Anúncios publicados";
			$_xtraWhere = "and status = 'Ag. Propostas'";
		}
		elseif($filterSelected == 'conclusas'){
			$title      = "Anúncios encerrados";
			$_xtraWhere = "and status IN ('Concluído')";
		}
	}
	
	$anuncList = cAnuncio::multiLoad("where usuar_id='{$usuarObj->v('id')}' and negocio='{$type}' {$_xtraWhere} order by data_anuncio desc", 'varieObj;trocaVarieObj');
	$mobTitle  = "Meus Anúncios";
}

// Agora vamos montar o filtro por variedades, e filtrar evetivamente, se necessário.
$_varieKeys   = [];
$_varieValues = [];
$_outputList  = [];
foreach($anuncList as $anuncObj){
	if(in_array($anuncObj->v('varie_id'), $_varieKeys))
		continue;
	
	$_varieKeys[]   = $anuncObj->v('varie_id');
	$_varieValues[] = $anuncObj->v('varieObj')->v('variedade');
}
array_multisort($_varieValues, $_varieKeys);
$filterVarieList = array_combine($_varieKeys, $_varieValues);

// Vamos filtrar por variedades, se necessário.
if($filterVarieSelected){
	$_oList = $anuncList;
	foreach($anuncList as $idx=>$anuncObj){
		if($anuncObj->v('varie_id') != $filterVarieSelected){
			unset($anuncList[$idx]);
		}
	}
	
	if(!sizeof($anuncList)){
		$anuncList = $_oList;
	}
}


layCima(false, Array(
	'extraCss'   =>['list-v2'],
	'extraJquery'=>'anunc-v2',
	'menuSel'    =>$menuSel,
));
?>
	<div class="mobileBar">
		<?=$mobTitle?>
	</div>

	<?php if($filterList): ?>
		<div class="barTop searchBar">
			<div>
				<b>Mostrar</b>
				<?=dInput2::select("id='dropFilterBy'", $filterList, $filterSelected);?>
			</div>
			<?php if($filterVarieList): ?>
				<div>
					<b>Variedade:</b>
					<?=dInput2::select("id='dropFilterByVarie'", $filterVarieList, $filterVarieSelected, '', "Todas");?>
				</div>
			<?php endif ?>
		</div>
	<?php endif ?>

	<h1><?=$title?></h1>
	
	<?php if($page == 'Oportunidades' && $filterSelected == 'cotando'): ?>
		<?php if(!$interesses['userDefined']): ?>
			<div class="boxDestaque">
				<b>⭐ Que tal ver apenas o que importa?</b><br />
				<li>Veja  apenas os anúncios que te interessam;</li>
				<li>Deixa que a gente busca as melhores oportunidades pra você!</li>
				<li>Ajuste suas preferências em <b><a href='meus-interesses.php'>Minha Conta <i class='fa fa-caret-right'></i> Meus Interesses</a></b>.</li>
				<a href='meus-interesses.php' class='roundButton'><b><i class='fa fa-caret-right'></i> Defina seus interesses agora mesmo!</b></a>
			</div>
		<? else: ?>
			<div class="boxDestaque">
				<b>Exibindo apenas apenas seus interesses.</b><br />
				<!--<li>Todos os tipos de sementes</li>-->
				<!--<li>Todos os tipos de embalagens</li>-->
				<!--<li>Todos as regiões</li>-->
				<a href='meus-interesses.php' class='roundButton'><b><i class='fa fa-caret-right'></i> Mantenha seus interesses em dia</b></a>
			</div>
		<?php endif ?>
	<?php endif ?>

	<?php if($anuncList): ?>
		<div class="anuncListV2">
			<?php foreach($anuncList as $anuncObj){
				/* @var cAnuncio $anuncObj */
				/* @var cProposta $propoObj */
				$anuncObj->renderAnuncio($usuarObj);
			} ?>
		</div>
	<? else: ?>
		<div style="padding: 16px">
			<?php if($displayAs == 'anunciante'): ?>
				<?php if($filterSelected): ?>
					Nenhum anúncio encontrado.<br />
				<? else: ?>
					Nenhum anúncio aqui.<br />
				<?php endif ?>
				<br />
				<a class='roundButton' href="newauction.php?type=<?=$type?>">
					<i class='fa fa-caret-right'></i> Crie seu anúncio grátis agora mesmo!
				</a>
			<? else:
				$createType = ($type != 'troca')?
					($type=='compra'?'venda':'compra'):
					'troca';
				?>
				Não há nenhuma oportunidade disponível neste momento.<br />
				<br />
				Você receberá novas oportunidades por e-mail.<br />
				<br />
				<a class='roundButton' href="newauction.php?type=<?=$createType?>">
					<i class='fa fa-caret-right'></i> Crie seu anúncio grátis agora mesmo!
				</a>
			<?php endif ?>
		</div>
	<?php endif ?>

	<div id='debug'></div>
<?php
layBaixo();
