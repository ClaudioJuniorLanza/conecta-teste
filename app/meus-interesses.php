<?php
require_once "config.php";
require_once "template.php";

$usuarObj     = cUsuario::isLoggedOrRedirect();
$usuarObj->isComercianteOrRedirect();
$exInteresses = $usuarObj->getInteresses(true);

layCima("Meus Interesses", Array(
	'extraCss'    => ['meus-interesses'],
	'extraJquery' => 'meus-interesses',
)); ?>
	<h1>Meus Interesses</h1>
	<div class="stdPage">
		<div class="boxDestaque">
			<b>Como funciona:</b><br />
			<li>Marque os negócios de seu interesse;</li>
			<li>Você <b>só verá oportunidades</b> relevantes;</li>
			<li>Receba no seu e-mail <b>APENAS</b> oportunidades relevantes pra você;</li>
			<small>Suas alterações serão salvas automaticamente.</small>
		</div>
		
		<? foreach(Array('compra'=>'comprar', 'venda'=>'vender') as $grupo=>$interesse):
			$ativar = $exInteresses[$grupo]['ativar'];
			$exListOnly   = [];
			$exListExcept = [];
			$culturaList  = dHelper2::csListCulturas();
			if(@$exInteresses[$grupo]['onlyStr']){
				foreach($exInteresses[$grupo]['onlyStr'] as $_onlyStr){
					$exListOnly[] = ['rel'=>"STR:{$_onlyStr}", 'top'=>'', 'main'=>mb_convert_case($_onlyStr, MB_CASE_TITLE, "UTF-8")];
				}
			}
			if(@$exInteresses[$grupo]['onlyIds']){
				$_list = cRefVariedade::multiLoad("where id IN('".implode("', '", $exInteresses[$grupo]['onlyIds'])."') order by cultura,variedade");
				foreach($_list as $_varieObj){
					$exListOnly[] = ['rel'=>"ID:{$_varieObj->v('id')}", 'top'=>$_varieObj->v('cultura'), 'main'=>$_varieObj->v('variedade')];
				}
			}
			
			if(@$exInteresses[$grupo]['excetoStr']){
				foreach($exInteresses[$grupo]['excetoStr'] as $_onlyStr){
					$exListExcept[] = ['rel'=>"STR:{$_onlyStr}", 'top'=>'', 'main'=>mb_convert_case($_onlyStr, MB_CASE_TITLE, "UTF-8")];
				}
			}
			if(@$exInteresses[$grupo]['excetoIds']){
				$_list = cRefVariedade::multiLoad("where id IN('".implode("', '", $exInteresses[$grupo]['excetoIds'])."') order by cultura,variedade");
				foreach($_list as $_varieObj){
					$exListExcept[] = ['rel'=>"ID:{$_varieObj->v('id')}", 'top'=>"{$_varieObj->v('cultura')}:", 'main'=>$_varieObj->v('variedade')];
				}
			}
			?>
			<!-- Tenho interesse em ... -->
			<div class="wannaGroup">
				<b><?=dInput2::checkbox("name='interesses[{$grupo}][ativo]' value='1' onclick=\"$('.subGroups[rel={$interesse}]').slideToggle()\"", @$exInteresses[$grupo]['ativar'], " Tenho interesse em {$interesse}...");?></b>
				<div class="subGroups" rel='<?=$interesse?>' style="display: <?=$ativar?'flex':'none'?>">
					<div class="group">
						<div class="groupType">Variedade/Cultivar</div>
						<div class="groupDefault">
							<label class="row main">
								<div class="middle">
									<div class="showAll">
										Mostre todas as variedades<br />
										<small>Você verá todas as oportunidades.</small>
									</div>
									<div class="showOnly" style='display: none'>
										Mostre todas as variedades<br />
										<small>Informe apenas o que você deseja ver.</small>
									</div>
								</div>
								<span class="right">
									<?=dInput2::checkbox("name='interesses[{$grupo}][tudo]' class='sliderSwitch'", $exInteresses[$grupo]['tudo']); ?>
								</span>
							</label>
							<div class="exceptions">
								<div class="showAll" style='display: none'>
									<div class="separator" style="display: <?=$exListExcept?'block':'none'?>">
										Mostrando tudo, exceto...
									</div>
									<div class="rowsHolder" rel='exceto' style="max-height: 271px; overflow-Y: auto">
										<? foreach($exListExcept as $exceptItem): ?>
											<div class="row" rel="<?=htmlspecialchars($exceptItem['rel'])?>">
												<a href='#' class="left deleteBtn">
													<i class='fa fa-fw fa-times'></i>
												</a>
												<div class="middle">
													<small><?=$exceptItem['top']?></small>
													<?=htmlspecialchars($exceptItem['main'])?>
												</div>
											</div>
										<? endforeach ?>
									</div>
								</div>
								<div class="showOnly" style='display: none'>
									<form class="row addInteresse">
										<div class="left addBtn">
											<i class='fa fa-fw fa-plus'></i>
										</div>
										<div class="middle">
											<small>Adicionar interesse:</small>
											<input name='interesseAdd' list="cultivarList" placeholder="Cultura ou Variedade" />
										</div>
										<div class="right">
											<button><i class='fa fa-fw fa-check'></i> Adicionar</button>
										</div>
									</form>
									<div class="separator" style="display: <?=$exListOnly?'block':'none'?>">
										Sua lista:
									</div>
									<div class="rowsHolder" rel='only' style="max-height: 210px; overflow-Y: auto">
										<? foreach($exListOnly as $_onlyItem): ?>
											<div class="row" rel="<?=htmlspecialchars($_onlyItem['rel'])?>">
												<a href='#' class="left deleteBtn">
													<i class='fa fa-fw fa-times'></i>
												</a>
												<div class="middle">
													<small><?=$_onlyItem['top']?></small>
													<?=htmlspecialchars($_onlyItem['main'])?>
												</div>
											</div>
										<? endforeach ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="group">
						<div class="groupType">Embalagem</div>
						<div class="groupDefault">
							<? foreach(dHelper2::csDropEmbalagem() as $embalaStr): ?>
								<label class='row'>
									<span class="left">
										<?=dInput2::checkbox("name='interesses[{$grupo}][embalagem][]' value='{$embalaStr}'", !in_array($embalaStr, $exInteresses[$grupo]['notEmbalagem'])); ?>
									</span>
									<span class="middle">
										<?=$embalaStr?>
									</span>
								</label>
							<? endforeach ?>
						</div>
					</div>
					<div class="group groupRegioes">
						<div class="groupType">
							<?=($interesse=='vender')?
								"Regiões atendidas":
								"Origem da semente"
							?>
						</div>
						<div class="groupDefault">
							<div class="twoColumns">
								<? foreach(array_chunk(dHelper2::csDropRegiao(), ceil(sizeof(dHelper2::csDropRegiao())/4)) as $_column): ?>
									<div class="column">
										<? foreach($_column as $regiaoStr): ?>
											<label class='row'>
												<span class="left">
													<?=dInput2::checkbox("name='interesses[{$grupo}][regiao][]' value='{$regiaoStr}'", !in_array($regiaoStr, $exInteresses[$grupo]['notRegiao'])); ?>
												</span>
												<span class="middle">
													<?=$regiaoStr?>
												</span>
											</label>
										<? endforeach ?>
									</div>
								<? endforeach ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		<? endforeach; ?>
		<div class="wannaGroup">
			<b><?=dInput2::checkbox("name='interesses[troca][ativo]' value='1' onclick=\"$('.trocarDisclaimer').slideToggle()\"", @$exInteresses['troca'], " Tenho interesse em trocar...");?></b>
			<div class="trocarDisclaimer" style="display: <?=@$exInteresses['troca']?'block':'none'?>">
				Seus interesses serão calculados com base no que você deseja comprar/vender.
			</div>
		</div>
	</div>

	<datalist id="cultivarList" class='cultivarList1'>
		<? foreach(dHelper2::csListCulturas() as $culturaStr): ?>
			<option value="<?=mb_convert_case($culturaStr, MB_CASE_TITLE, "UTF-8")?>" />
		<? endforeach; ?>
		<? foreach(cRefVariedade::multiLoad("order by variedade") as $varieObj): ?>
			<option value="<?=$varieObj->v('variedade')?>" />
		<? endforeach; ?>
	</datalist>

<?php
layBaixo();