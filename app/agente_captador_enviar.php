<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();
if(!$usuarObj->v('agente_captador')){
	// Sem permissões.
	dHelper2::redirectTo("agente_central.php");
	die;
}

$dropClientes = $db->singleQuery("select id,nome from c_usuarios where agente_id='{$usuarObj->v('id')}' order by nome");

$allLines     = [];
$errorMessage = [];
$succMsg      = [];
$clienObj     = false;
$canSubmit    = true; // Para revisão: Posso mostrar o botão de confirmação!?
if(@$_POST['raw_xls']){
	call_user_func(function() use (&$usuarObj, &$allLines, &$clienObj, &$errorMessage, &$canSubmit){
		$clienObj = @$_POST['clien_id']?
			cUsuario::load($_POST['clien_id']):
			false;
		
		if(!$clienObj || !$usuarObj->isAgenteOf($clienObj)){
			$errorMessage[] = "Você não selecionou, ou não tem autoridade sobre o cliente.";
			return false;
		}
			
		$allLines = explode("\n", $_POST['raw_xls']);
		$allLines = array_filter($allLines, function($row){
			return strlen(trim($row));
		});
		$allLines = array_map(function($line){
			return explode("\t", rtrim($line, "\r\n"));
		}, $allLines);
		
		// Validação de consistência:
		if($allLines[0][0] != "INFORME A DATA"){
			$errorMessage[] = "Dados inconsistentes¹: Copie a partir da célula 'Informe a data', até a última linha da coluna 'Local de Origem'";
		}
		elseif($allLines[0][12] != "Local de Origem (Retirada do Produto) e Pagamento"){
			$errorMessage[] = "Dados inconsistentes²: Copie a partir da célula 'Informe a data', até a última linha da coluna 'Local de Origem'";
		}
		elseif($allLines[1][0]  != "Hoje (Publicação)"){
			$errorMessage[] = "Dados inconsistentes³: Copie a partir da célula 'Informe a data', até a última linha da coluna 'Local de Origem'";
		}
		elseif($allLines[1][15]  != "Preço/Kg"){
			$errorMessage[] = "Dados inconsistentes³¹: Copie a partir da célula 'Informe a data', até a última linha da coluna 'Local de Origem'";
		}
		elseif(sizeof($allLines) < 2){
			$errorMessage[] = "Você não enviou dados suficientes: Envie ao menos uma linha com dados.";
		}
		
		if($errorMessage){
			return false;
		}
		
		// Tudo certo, supostamente.. Vamos continuar!
		$_padronizar = function($lista){
			// Padroniza dHelper2::csDrop*****, tornando-o compatível com in_array.
			return array_map(function($item){
				if(is_array($item)){
					return $item[1];
				}
				return $item;
			}, $lista);
		};
		$knownLists = [
			'Produtos'           => dDatabase::start()->singleColumn("SELECT LOWER(concat(cultura,'-',variedade)) FROM c_ref_variedades"),
			'Categoria'          => $_padronizar(dHelper2::csDropCategoria()),
			'Germinação'         => $_padronizar(dHelper2::csDropGerminacao()),
			'Embalagem'          => $_padronizar(dHelper2::csDropEmbalagem()),
			'Vigor E.A. 48h'     => $_padronizar(dHelper2::csDropVigorEA48h()),
			'Peneira'            => $_padronizar(dHelper2::csDropPeneira()),
			'Tratam. Industrial' => $_padronizar(dHelper2::csDropTratamentoIndustrial()),
			'PMS'                => $_padronizar(dHelper2::csDropPMS()),
			'UFs'                => array_keys(dHelper2::getUfList()),
			'ColunasOpcionais'   => ['Categoria','Germinação','Embalagem','Vigor E.A. 48h','Peneira','Tratam. Industrial','PMS'],
			'ColunasLista'       => ['Categoria','Germinação','Embalagem','Vigor E.A. 48h','Peneira','PMS'],
		];
		
		$_header  = $allLines[1];
		$hashList = []; // Cada item terá um hash. Se o hash for duplicado, gera um erro.
		$allLines = array_map(function($row) use (&$_header, &$knownLists, &$hashList, &$canSubmit){
			$item      = array_combine($_header, $row);
			$errorList = [];
			
			// Campos obrigatórios:
			foreach($_header as $column){
				$_isRequired = !in_array($column, $knownLists['ColunasOpcionais']);
				if($_isRequired && empty($item[$column])){
					$errorList[] = "Colunas {$column} é obrigatória.";
					break;
				}
			}
			
			if(!$errorList){
				// Validação de datas:
				if($item['Hoje (Publicação)'] != date('d/m/Y')){
					$errorList[] = "Coluna <b>Data Hoje</b> não tem a data de hoje. Você mandou o arquivo certo?";
				}
				if(dHelper2::brDateToUsDate($item['Expirar em']) < date('Y-m-d', strtotime("+1 week"))){
					$errorList[] = "Data de expiração (<b>Expirar em</b>) muito próxima. O mínimo é 1 semana.";
				}
			
				// Listas: Cultura e Cultivar
				$_checkProduto = mb_strtolower("{$item['Cultura']}-{$item['Variedade/Cultivar']}");
				if(!in_array($_checkProduto, $knownLists['Produtos'])){
					$errorList[] = "Cultura/Cultivar não encontrados. <a href='agente_captador_tabela_apoio.php' target='_blank'>Escreva igual consta na <b>Tabela de Apoio</b></a>";
				}
				
				// Listas: Todas as colunas que são listas conhecidas
				foreach($knownLists['ColunasLista'] as $_column){
					if(!in_array($item[$_column], $knownLists[$_column])){
						$errorList[] = "A coluna <b>{$_column}</b> tem um texto inválido. <a href='agente_captador_tabela_apoio.php' target='_blank'>Escreva igual consta na <b>Tabela de Apoio</b></a>";
					}
				}
				
				// Listas: Tratamento Industrial:
				$item['tratam_texto'] = "";
				if(!in_array($item['Tratam. Industrial'], $knownLists['Tratam. Industrial'])){
					$item['tratam_texto']       = $item['Tratam. Industrial'];
					$item['Tratam. Industrial'] = "Sim";
				}
				
				// Validação de UF:
				$item['UF'] = strtoupper($item['UF']);
				if(strlen($item['UF']) != 2){
					$errorList[] = "A coluna UF deve conter apenas <b>2 caracteres</b>";
				}
				elseif(!in_array($item['UF'], $knownLists['UFs'])){
					$errorList[] = "UF não foi reconhecido com um UF válido.";
				}
				
				// Validação de Quantidade e Preço/Kg:
				// --> Os valores virão do Excel, então "." SEMPRE será milhar, e "," SEMPRE será decimal.
				foreach(['Quantidade (Kg)', 'Preço/Kg'] as $_column){
					$_tmp = str_replace("R$ ", "", $item[$_column]);
					$_tmp = str_replace([".", ","], ["", "."], $_tmp);
					$_tmp = dHelper2::forceFloat($_tmp, 3);
					if(!$_tmp || $_tmp < 0){
						$errorList[] = "A coluna {$_column} não apresenta um número válido...";
					}
					else{
						$item[$_column] = $_tmp;
					}
				}
			}
			
			// Validação Unique:
			$hash = md5(implode("|", $item));
			if(in_array($hash, $hashList) !== false){
				$errorList[] = "Linha duplicada (já informada nesta mesma planilha)";
			}
			else{
				$hashList[] = $hash;
			}
			
			// $item['hash']      = $hash;
			$item['errorList'] = $errorList;
			if($errorList){
				$canSubmit = false;
			}
			return $item;
		}, array_slice($allLines, 2));
	});
}
if(@$_GET['reviewed'] == 'yes'){
	$clienObj = @$_POST['clien_id']?
		cUsuario::load($_POST['clien_id']):
		false;
	if(!$clienObj || !$usuarObj->isAgenteOf($clienObj)){
		$errorMessage[] = "Você não selecionou é o cliente, ou não tem autoridade sobre o mesmo.";
		return false;
	}
	
	$setCodigoBase = substr(time(), -5);
	
	// Como ficará o código?
	// ($time)0($idx). Ex: 1023800, 1023801, 1023802, 1023803, 1023804, ....
	
	while($db->singleResult("select id from c_anuncios where codigo='{$setCodigoBase}01'")){
		$setCodigoBase = $setCodigoBase++;
	}
	foreach($_POST['addRow'] as $idx=>$addItem){
		$addItem = json_decode($addItem, true);
		if(!$addItem){
			continue;
		}
		
		$lineNumber = ($idx+1);
		$setCodigo = $setCodigoBase."0".$lineNumber;
		
		$varieId = $db->singleResult("select id from c_ref_variedades where cultura='".addslashes($addItem['Cultura'])."' and variedade='".addslashes($addItem['Variedade/Cultivar'])."' limit 1");
		if(!$varieId){
			$errorMessage[] = "Falha ao importar linha #{$lineNumber}: Cultura={$addItem['Cultura']} e Variedade={$addItem['Variedade/Cultivar']} não encontrados.. Esse erro não deveria ocorrer. O envio dos itens após esta linha foi interrompido.";
			break;
		}
		
		$dataExpire = $addItem['Expirar em'];
		if(strlen($dataExpire) == 10){
			$dataExpire .= " 23:59:59";
		}
		
		$anuncObj = new cAnuncio;
		$anuncObj->v('usuar_id',         $clienObj->v('id'));
		$anuncObj->v('codigo',           $setCodigo);
		$anuncObj->v('negocio',          'Venda');
		$anuncObj->v('data_anuncio',      date('d/m/Y H:i'));
		$anuncObj->v('data_ini_cotacao',  date('d/m/Y H:i'));
		$anuncObj->v('autoexpire_data',  $dataExpire);
		$anuncObj->v('status',           'Ag. Propostas');  // 'Em Análise','Ag. Propostas','Concluído','Cancelado'
		$anuncObj->v('varie_id',         $varieId);
		$anuncObj->v('categoria',        $addItem['Categoria']);
		$anuncObj->v('germinacao',       $addItem['Germinação']);
		$anuncObj->v('embalagem',        $addItem['Embalagem']);
		$anuncObj->v('vigor_ea48h',      $addItem['Vigor E.A. 48h']);
		$anuncObj->v('peneira',          $addItem['Peneira']);
		$anuncObj->v('tratam_indust',    $addItem['Tratam. Industrial']);
		$anuncObj->v('tratam_texto',     $addItem['tratam_texto']);
		$anuncObj->v('pms',              $addItem['PMS']);
		$anuncObj->v('quantidade',       $addItem['Quantidade (Kg)']);
		$anuncObj->v('frete',            'FOB');
		$anuncObj->v('valor_por_kg',     $addItem['Preço/Kg']);
		$anuncObj->v('uf',               $addItem['UF']);
		$anuncObj->v('cidade',           $addItem['Cidade']);
		$anuncObj->v('forma_pgto',       $addItem['Forma de Pgto.']);
		if($anuncObj->save()){
			$succMsg[] = "Linha {$lineNumber} importada com sucesso!";
		}
		else{
			$errorMessage[] = "Falha ao importar linha #{$lineNumber}: ".implode(", ", $anuncObj->listErrors(true)).".<br />Nenhum anúncio foi importado após esta linha.";
			break;
		}
	}
}

layCima("Enviar Anúncios", [
	'menuSel' =>'agente_captador',
	'extraCss'=>'agente'
]); ?>
	<div class="agente">
		<? if($errorMessage): ?>
			<div class="displayErrorBox">
				<?=implode("<br />", $errorMessage)?>
			</div>
		<? endif ?>
		
		<? if(@$_GET['reviewed']): ?>
			<? if($succMsg): ?>
				<div class="displaySuccessBox">
					<?=implode("<br />", $succMsg)?>
				</div>
			<? endif ?>
			O que você deseja fazer agora?<br />
			<ul>
				<li><a href="agente_captador.php">Voltar para a página inicial</a></li>
				<li><a href="agente_captador_enviar.php">Enviar mais uma planilha?</a></li>
				<li><a href="agente_captador_manage.php?clien_id=<?=$clienObj->v('id')?>">Gerenciar os anúncios?</a></li>
			</ul>
			
		<? elseif($allLines && !$errorMessage): ?>
			<form method="post" action="agente_captador_enviar.php?reviewed=yes">
				<input type="hidden" name="clien_id" value="<?=$clienObj->v('id')?>" />
				<h1>Revise os dados antes de confirmar</h1>
				<table cellpadding='4' cellspacing='0' style='border-collapse: collapse' border='1' class='reviewInsertData'>
					<thead>
						<tr>
							<th><b>Anunciante</b></th>
							<?php
								foreach(array_keys($allLines[0]) as $column){
									if($column == "tratam_texto"){
										// Exceção: Será mostrada junto com o Tratam Industrial.
										continue;
									}
									if($column == "errorList"){
										echo "<th><b>Status</b></th>";
										continue;
									}
									echo "<th><b>{$column}</b></th>";
								}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($allLines as $line){
							echo "<tr".($line['errorList']?" class='withError'":"").">";
							echo "	<td>".htmlspecialchars($clienObj->v('nome'))."</td>";
							foreach($line as $column=>$value){
								if($column == "tratam_texto"){
									// Exceção:
									// --> Já foi exibido junto com o tratamento industrial.
									continue;
								}
								if($column == "Tratam. Industrial"){
									echo "<td>";
									echo $value;
									if(@$line['tratam_texto']){
										echo ": ".htmlspecialchars($line['tratam_texto']);
									}
									echo "</td>";
									continue;
								}
								if($column == "errorList"){
									echo "<td>";
									echo $line['errorList']?
										"<b style='color: #F00'>".implode("<br />", $line['errorList'])."</b>":
										"<b style='color: #080'>Pronta para ser adicionada</b>";
									
									// O ErrorList vai armazenar o <textarea> que será importado.
									if(!$line['errorList']){
										echo dInput2::textarea("name='addRow[]' style='display: none'", json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
									}
									echo "</td>";
									continue;
								}
								
								echo "<td>{$value}</td>";
							}
							echo "</tr>";
						}
						?>
					</tbody>
				</table>
				<? if($canSubmit): ?>
					<br />
					<button onclick="return confirm('Após confirmar, AGUARDE. O processo pode demorar até 5 minutos. Não saia da página, não clique em nada: Apenas aguarde até a mensagem de sucesso.\n\nPronto para continuar?');">Iniciar envio <i class='fa fa-play'></i></button>
				<? else: ?>
					<p style='color: #F00'>
						Um ou mais itens na lista acima apresenta erros.<br />
						Resolva esses erros, e envie a planilha novamente.<br />
					</p>
				<? endif ?>
				<br />
				<br />
			</form>
		<? endif ?>
		
		<? if(!@$_GET['reviewed'] || !$allLines || ($allLines && !$canSubmit)): ?>
			<h1>Enviar Planilha Preenchida</h1>
			<ul>
				<li>Preencha as colunas <b>usando a <a href="agente_captador_tabela_apoio.php" target="_blank">tabela de apoio</a></b>;</li>
				<li>Lembre-se: O sistema <b>não consegue identificar</b> dados escritos diferente da tabela de apoio;</li>
				<li>Selecione o conteúdo da planilha (incluindo cabeçalho), e cole abaixo utilizando <b>CTRL+C</b> e <b>CTRL+V</b>;</li>
				<li>Após enviar, você terá a <b>chance de revisar</b> os dados antes da confirmação final.</li>
			</ul>
			
			<form class="grayForm" method="post" action="agente_captador_enviar.php" style="text-align: left">
				<div style="margin-bottom: 12px">
					Selecione o cliente:
						<?=dInput2::select("name='clien_id'", $dropClientes, @$_POST['clien_id'], false, "-- Selecione --"); ?>
						<a href="agente_cliente_edit.php?add=new" style='color: #080; text-decoration: none;'><i class='fa fa-plus-circle'></i></a>
				</div>
				<div>
					Envie o conteúdo da planilha:
					<small>
						<a href="images/excel_help.png" target='_blank' style='text-decoration: none;'><i class='fa fa-question-circle'></i> Como?</a>
					</small>
					<div>
						<?=dInput2::textarea("name='raw_xls' placeholder='INFORME A DATA &nbsp; &nbsp; &nbsp; PRODUTO ANUNCIADO &nbsp; &nbsp; &nbsp; FICHA TÉCNICA &nbsp; &nbsp; &nbsp; Local de Origem (Retirada do Produto) e Pagamento' style='white-space: nowrap; width: 100%; height: 120px'", @$_POST['raw_xls']); ?>
					</div>
				</div>
				<div align='center'>
					<button>Confirmar e Revisar os Dados</button>
				</div>
			</form>
		<? endif ?>
	</div>
<?php
layBaixo();