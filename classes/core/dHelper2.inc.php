<?php
class dHelper2{
	// Métodos restritos ao Conecta Sementes:
	static Function formataRenasem($renasem){
		// Formato do RENASEM:
		// --> UF/XXXXX/AAAA
		//
		// --> Como lidar com erros?
		// 1. Vamos remover todos os parâmetros
		// 2. Vamos validar os 4 ultimos digitos (AAAA)
		// 3. Vamos validar os 2 primeiros caracteres (UF)
		// 4. Vamos fazer PADDING-LEFT nos numeros restantes
		// 5. Vamos reformatar e buscar no banco de dados.
		
		$renasem = strtoupper($renasem);
		$renasem = preg_replace("/[^0-9A-Z]/", "", $renasem);
		$uf      = substr($renasem, 0, 2);
		$number  = substr($renasem, 2, -4);
		$year    = substr($renasem, -4);
		if(preg_match("/[0-9]/",  $uf)){
			// Renasem deve começar com o UF.
			return false;
		}
		if(preg_match("/[^0-9]/", $number)){
			// O número do RENASEM não pode ter letras...
			return false;
		}
		if($year < 2004 || $year > date('Y')){
			// Renasem só existe de 2004 até o ano atual.
			return false;
		}
		
		$number = str_pad($number, 5, "0", STR_PAD_LEFT);
		return "{$uf}-{$number}/{$year}";
	}
	static Function csDropCategoria(){
		return array_merge(
			Array(
				Array("N/A", "NÃO APLICÁVEL"),
			),
			explode(",", "Genética,Básica,C1,C2,S1,S2")
		);
	}
	static Function csDropEmbalagem(){ // --> Pode ser digitado fora desta lista.
		// Deve ser utilizada com selectStr, para permitir "(Outra...")
		// Ao atulizar, revisar dHelper2::calculaCustoHa() para identificar o que é embalagem/kilograma.
		return Array(
			"Saco 10 Kg",
			"Saco 20 Kg",
			"Saco 25 Kg",
			"Saco 40 Kg",
			"Big Bag 800 Kg",
			"Big Bag 1000 Kg",
			"Big Bag 5 milhões de Sementes",
			"Big Bag 5.5 milhões de Sementes",
			"55 Mil sementes / Saco",
			"60 Mil sementes / Saco",
			"200 mil Sementes / Saco",
		);
	}
	static Function csDropPeneira(){
		return array_merge(
			Array(
				Array("N/A", "NÃO APLICÁVEL"),
			),
			explode(",",
				"P1,P2,P3,P 4.5 mm,P 4.75 mm,P 5.0 mm,P 5.25 mm,P 5.50 mm,P 5.75 mm,".
				"P 6.0 mm,P 6.25 mm,P 6.5 mm,P 6.75 mm,P 7.0 mm,R1L,R2L,R3L,R4L,R5L,".
				"R1M,R2M,R3M,R4M,R5M,R1S,R2S,R3S,R4S,R5S,C1L,C2L,C3L,C4L,C5L,".
				"C1M,C2M,C3M,C4M,C5M,C1S,C2S,C3S,C4S,C5S,".
				"P 2.77 mm, P 2.7 mm, P 2.3 mm,P 2.5 mm,P < 2.3 mm"
			)
		);
	}
	static Function csDropPMS(){
		return Array(
			Array("N/A", "NÃO APLICÁVEL"),
			"Até 80,99",
			"81 a 86,99",
			"87 a 92,99",
			"93 a 98,99",
			"99 a 104,99",
			"105 a 110,99",
			"111 a 116,99",
			"117 a 122,99",
			"123 a 128,99",
			"129 a 134,99",
			"135 a 140,99",
			"141 a 146,99",
			"147 a 152,99",
			"153 a 158,99",
			"159 a 164,99",
			"165 a 170,99",
			"171 a 176,99",
			"177 a 182,99",
			"183 a 188,99",
			"189 a 194,99",
			"195 a 200,99",
			"201 a 206,99",
			"207 a 212,99",
			"213 a 218,99",
			"219 a 224,99",
			"225 a 230,99",
			"231 a 236,99",
			"237 a 242,99",
			"243 a 248,99",
			"249 a 254,99",
			"a partir de 255",
		);
	}
	static Function csDropGerminacao(){
		return Array(
			"De Acordo com MAPA",
			"< 80",
			"80 a 84",
			"85 a 89",
			"90 a 94",
			"95 a 100",
		);
	}
	static Function csDropVigorEA48h(){
		return Array(
			"NÃO APLICÁVEL",
			"< 65",
			"65 a 69",
			"70 a 74",
			"75 a 79",
			"80 a 84",
			"85 a 89",
			"90 a 94",
			"95 a 100",
		);
	}
	static Function csDropTratamentoIndustrial(){
		return array_merge(
			Array(
				Array("N/A", "NÃO APLICÁVEL"),
			),
			explode(",", "Sim,Não")
		);
	}
	static Function csDropFrete(){
		return explode(",", "CIF,FOB");
	}
	static Function csDropRegiao(){
		return Array(
			"AL",
			"AM",
			"BA",
			"GO",
			"GO (Sudoeste)",
			"MA",
			"MG",
			"MS Norte",
			"MS Sul",
			"MT NORTE",
			"MT SUL",
			"PA",
			"Paraná Alto",
			"Paraná Baixo",
			"PI",
			"RO",
			"RR",
			"RS",
			"RS 101",
			"SC",
			"SP (Micro 301)",
			"SP (Sul)",
			"Sudoeste GO",
			"TO",
		);
	}
	static Function csDropFormaPgto(){
		return explode(",",
			"À vista,".
			// "Safra,".
			"Entrada+30,".
			"Entrada+30+60,".
			"Entrada+30+60+90,".
			"Entrada 20% + Retirada 80%"
		);
	}
	static Function csListCulturas(){
		// Lista apenas as culturas principais, sem suas sub-variações.
		return [
			'alfafa',
			'algodão',
			'amendoim', // +1
			'arroz', // +3
			'aveia', // +4 +1 avena brevis roth
			'azevém', // +2
			'capim', // +22 +4 brachiaria)
			'cevada',
			'feijão', // +1
			'girassol',
			'grama', // +1
			'milheto',
			'milho',
			'soja',
			'sorgo',
			'trigo',
		];
	}
	static Function csGetCulturaSimples($culturaFull){
		// Ex: Amendoim, Amendoin Forrageiro --> Amendoin
		//     Arroz, Arroz Irrigado, Sequeiro, Vermelho, Perene --> Arroz
		//     Aveia, Aveia Perene, Aveia-amarela/Aveia-branca, Aveia-Sativa, etc... --> Aveita
		$culturaFull = strtolower(dHelper2::removeAccents($culturaFull));
		$culturaFull = str_replace(["braquiaria", "brachiaria"], "capim brachiaria", $culturaFull);
		$culturaFull = str_replace("Avena brevis", "Aveia Brevis", $culturaFull);
		foreach(self::csListCulturas() as $cultura){
			$cultura = strtolower(dHelper2::removeAccents($cultura));
			if(preg_match("/^{$cultura}/", $culturaFull)){
				return $cultura;
			}
		}
		return $culturaFull;
	}
	
	static Function csRegiaoITSToCidade($regiaoITS){
		$tabela = Array(
			"AL"             => ["Maceió", "AL"],
			"AM"             => ["Manaus", "AM"],
			"BA"             => ["Salvador", "BA"],
			"GO"             => ["Goiania", "GO"],
			"MA"             => ["São Luís", "MA"],
			"MG"             => ["Belo Horizonte", "MG"],
			"MS Norte"       => ["Campo Grande", "MS"],
			"MS Sul"         => ["Campo Grande", "MS"],
			"MT NORTE"       => ["Cuiabá", "MT"],
			"MT SUL"         => ["Cuiabá", "MT"],
			"PA"             => ["Belém", "PA"],
			"Paraná Alto"    => ["Londrina", "PR"],
			"Paraná Baixo"   => ["Curitiba", "PR"],
			"PI"             => ["Teresina", "PI"],
			"RO"             => ["Porto Velho", "RO"],
			"RR"             => ["Boa Vista", "RR"],
			"RS"             => ["Porto Alegre", "RS"],
			"RS 101"         => ["Porto Alegre", "RS"],
			"SC"             => ["Florianópolis", "SC"],
			"SP (Micro 301)" => ["São Paulo", "SP"],
			"SP (Sul)"       => ["São Paulo", "SP"],
			"Sudoeste GO"    => ["Goiania", "GO"],
			"GO (Sudoeste)"  => ["Goiania", "GO"],
			"TO"             => ["Palmas", "TO"],
		);
		
		return array_key_exists($regiaoITS, $tabela)?$tabela[$regiaoITS]:false;
	}
	
	// Métodos compartilhados (Core)
	static Function usDateToBrDate($datetime){
		$parts    = explode(" ", $datetime);
		if(sizeof(explode("-", $parts[0])) != 3){
			return false;
		}
		
		return (sizeof($parts) == 2)?
			date('d/m/Y H:i:s', strtotime(implode(" ", $parts))):
			date('d/m/Y', strtotime($parts[0]));
	}
	static Function brDateToUsDate($datetime){
		$parts    = explode(" ", $datetime);
		$parts[0] = explode("/", $parts[0]);
		if(sizeof($parts[0]) != 3){
			return false;
		}
		$parts[0] = "{$parts[0][2]}-{$parts[0][1]}-{$parts[0][0]}";
		
		return (sizeof($parts) == 2)?
			date('Y-m-d H:i:s', strtotime(implode(" ", $parts))):
			date('Y-m-d', strtotime($parts[0]));
	}
	static Function brDateToTimestamp($time){
		return strtotime(self::brDateToUsDate($time));
	}
	static Function weekToString($weekDay){
		if($weekDay === false){
			$ret = Array();
			for($x = 0; $x <= 6; $x++){
				$ret[] = Array($x, self::weekToString($x));
			}
			return $ret;
		}
		
		// A ser utilizado em conjunto com date('w');
		if($weekDay == 0) return "Domingo";
		if($weekDay == 1) return "Segunda-feira";
		if($weekDay == 2) return "Terça-feira";
		if($weekDay == 3) return "Quarta-feira";
		if($weekDay == 4) return "Quinta-feira";
		if($weekDay == 5) return "Sexta-feira";
		if($weekDay == 6) return "Sábado";
		dSystem::notifyAdmin('HIGH', "Inconsistência no código",
			"weekToString espera 0 para domingo e 6 para sábado.\r\n".
			"Recebemos {$weekDay}. O problema deve ser resolvido.",
			"Isso é um erro crítico.",
			true
		);
		die;
		return false;
	}
	static Function dateToFriendly($date, $options=Array()){
		$options = self::addDefaultToArray($options, Array(
			'addWeekday'   =>true,  // Adiciona 'Terça-feira'
			'addSufix'     =>true,  // Adiciona ' (daqui xx semanas)'
			'maxDays'      =>false, // Limita o número de dias. Ex: 3 para "depois-de-amanhã" ou "7" para "semana que vem" . Se ultrapassado, retorna false.
			'returnArray'  =>false, // Retorna um array contendo 'daysDiff','weeksDiff' (+1 para amanhã, -1 para ontem) e 'sufix'
		));
		
		$now       = strtotime("today 00:00:00");
		$timestamp = strtotime($date);
		$daysDiff  = floor(($timestamp-$now)/(60*60*24));
		$weeksDiff = ($daysDiff>0)?floor($daysDiff/7):ceil($daysDiff/7);
		
		if($options['maxDays'] && abs($daysDiff) > $options['maxDays']){
			return false;
		}
		
		$sufix = "";
		if($weeksDiff < -1){
			$sufix = "há ".(-$weeksDiff)." semanas";
		}
		elseif($weeksDiff <  0){
			$sufix = "semana passada";
		}
		elseif($weeksDiff >  1){
			$sufix = "daqui {$weeksDiff} semanas";
		}
		elseif($weeksDiff >  0){
			$sufix = "semana que vem";
		}
		elseif($daysDiff == -2){
			$sufix = 'ante-ontem';
		}
		elseif($daysDiff == -1){
			$sufix = 'ontem';
		}
		elseif($daysDiff == 0){
			$sufix = 'hoje';
		}
		elseif($daysDiff == 1){
			$sufix = 'amanhã';
		}
		elseif($daysDiff == 2){
			$sufix = 'depois de amanhã';
		}
		
		$weekDay = self::weekToString(date('w', $timestamp));
		
		if($options['returnArray']){
			return Array('daysDiff'=>$daysDiff, 'weeksDiff'=>$weeksDiff, 'weekday'=>$weekDay, 'sufix'=>$sufix);
		}
		elseif($options['addWeekday'] && $options['addSufix']){
			return $sufix?
				"{$weekDay} ({$sufix})":
				$weekDay;
		}
		elseif($options['addWeekday']){
			return $weekDay;
		}
		elseif($options['addSufix']){
			return $sufix;
		}
		
		dSystem::notifyAdmin('MED', "Código inconsistente (dHelper2::dateToFriendly)",
			"O conjunto de opções informadas levou a um retorno VAZIO.\r\n".
			print_r($options, true)."\r\n".
			"\r\n".
			"Vou retornar vazio para não dar problema, mas deve ser verificado."
		);
		return '';
	}
	static Function timeToFriendly($time, $options=Array()){
		$options = self::addDefaultToArray($options, Array(
			'hidePrefix' =>false,          // Esconde o texto "em ..." ou "há ..."
			'maxInterval'=>24*30,          // Em horas.
			'dateParam'  =>'d/m/y H\hi\m', // Será encaminhado diretamente para date() quando estiver fora do interval
		));
		$now      = time();
		$time     = is_string($time)?strtotime($time):$time;
		$isFuture = ($now<$time);
		$diff     =  abs($now-$time);
		
		if($options['maxInterval'] && $diff > 60*60*$options['maxInterval']){
			// Limite de maxInterval.
			return date($options['dateParam'], $time);
		}
		
		
		if($diff < 10){
			return "agora";
		}
		elseif($diff < 30){
			$str = "instantes";
		}
		elseif($diff < 60){
			$str = "alguns segundos";
		}
		elseif($diff < 60*60){
			$m   = round($diff/60);
			$str = "{$m} minuto".($m>1?'s':'');
		}
		elseif($diff < 60*60*24){
			$h   = round($diff/60/60);
			$str = "{$h} hora".($h>1?'s':'');
		}
		else{
			// Dias
			$d   = round($diff/60/60/24);
			$h   = ($diff / 60 / 60) - ($d * 60 * 60 );
			if($h < 6){
				$str = "{$d} dia".(($d>1)?'s':'');
			}
			elseif($h < 18){
				$str = "{$d} dia".(($d>1)?'s':'')." e meio";
			}
			else{
				$str = "quase ".($d+1)." dias";
			}
		}
		
		if($options['hidePrefix']){
			return $str;
		}
		
		return ($isFuture?"em ":"há ").$str;
	}
	static Function getProximoDiaUtil($usDate, $options=Array()){
		// Pega o próximo dia útil a partir de $usDate.
		// - Utiliza webservice IMAGINACOM/calendario (imaginacomToken)
		// - Se falhar, utiliza apenas informações em cache      (apenas cacheFolder)
		// - Se falhar, utiliza webservice CALENDARIO.COM.BR     (calendarioToken e cacheFolder)
		// - Se falhar, calcula internamente                     (considerando apenas finais de semana)
		
		// Tipos de feriados que podem ser ignorados:
		// 	- Feriado Nacional
		// 	- Feriado Estadual
		// 	- Feriado Municipal
		// 	- Facultativo
		// 	- Dia Convencional   --> Ignorado por padrão
		$options += Array(
			'uf'             =>false,
			'cidade'         =>false,
			'ignore'         =>'Dia Convencional', // String, separado por vírgulas.
			
			'imaginacomToken'=>'IMAGINACOMTOKEN',
			'calendarioToken'=>'YWxleGFuZHJlYnJAZ21haWwuY29tJmhhc2g9MTA2Njg1NzQ5',
			'cacheFolder'    =>'auto', // Ex: baseDir/dat. Serão gerados arquivos no padrão "calendario-year-uf-cidade.xml"
			'timeout'        =>3, // Timeout para cada webservice. Se ambos falharem, são 6 segundos até o fallback interno.
			
			'verbose'        =>false,
		);
		
		$_verb       = function($str) use ($options){
			if(!$options['verbose'])
				return;
			
			echo "{$str}<br />\r\n";
		};
		
		$_verb("Iniciando cálculo de próximo dia útil...");
		$context     = stream_context_create(Array('http'=>Array(
			'timeout'=>3, // Timeout de 3 segundos. Como são dois webservices, pode demorar até 6 segundos antes do fallback final.
		)));
		$useUF       = preg_replace("/[^A-Z]/", "", substr(strtoupper($options['uf']), 0, 2));
		$useCity     = substr(dHelper2::removeAccents($options['cidade']), 0, 100);
		if($options['imaginacomToken']){
			$_verb("Tentando obter a informação mais atualizada através do webservice da IMAGINACOM.");
			$wsUrl = "http://ws.imaginacom.com/calendario/proximo_dia_util.php".
				"?token={$options['imaginacomToken']}".
				"&dia={$usDate}".
				"&uf=".urlencode($useUF).
				"&cidade=".urlencode($useCity).
				"&ignore=".urlencode($options['ignore']);
			
			$ret = @file_get_contents($wsUrl, 0, $context);
			if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", trim($ret))){
				$_verb("Retornando o conteúdo que veio da IMAGINACOM.");
				return $ret;
			}
			
			$_verb("Webservice da IMAGINACOM falhou:<pre style='border: 1px dotted #888; padding: 8px; margin: 8px;'>".htmlspecialchars($ret)."</pre>");
			dSystem::log('MED', "dHelper2::getProximoDiaUtil() - Webservice imaginacom retornou algo inconsistente.",
				"URL={$wsUrl}\r\n".
				"Fallback para:".($options['calendarioToken']?'calendarioToken':'Interno (apenas finais de semana)')
			);
		}
		
		$time          = strtotime("{$usDate} 12:00:00");
		$knownFeriados = Array();
		if($options['cacheFolder']){
			if($options['cacheFolder'] == 'auto'){
				$options['cacheFolder'] = dSystem::getGlobal('baseDir').'/dat';
			}
			
			$_verb("Falling back to cacheFolder");
			$useYear = date('Y', $time);
			
			if($useCity && $useUF){
				$_verb("Using city and uf");
				$_paramCity  = strtoupper(dHelper2::stringToUrl($useCity));
				$useFilename = $options['cacheFolder']."/calendario-{$useYear}-{$useUF}-{$_paramCity}.xml";
				$apiParams   = "ano={$useYear}&estado={$useUF}&cidade={$_paramCity}";
			}
			elseif($useUF){
				$_verb("Using only uf");
				$useFilename = $options['cacheFolder']."/calendario-{$useYear}-{$useUF}.xml";
				$apiParams   = "ano={$useYear}&estado={$useUF}";
			}
			else{
				$_verb("Using only nacional");
				$useFilename = $options['cacheFolder']."/calendario-{$useYear}.xml";
				$apiParams   = "ano={$useYear}";
			}
			
			$_verb("UseFilename={$useFilename}");
			if(!file_exists($useFilename) && $options['calendarioToken']){
				$_verb("- Buscando o banco de dados mais atual do calendario.com.br...");
				// Download it.
				$body = @file_get_contents("http://www.calendario.com.br/api/api_feriados.php?{$apiParams}&token={$options['calendarioToken']}", 0, $context);
				if(substr($body, 0, 5) == '<?xml'){
					// Download bem sucedido.
					$_verb("Download bem sucedido.");
					file_put_contents($useFilename, $body);
				}
				else{
					$_verb("Download falhou, vamos considerar apenas dados sobre finais de semana.");
				}
			}
			if( file_exists($useFilename)){
				$_verb("Carregando o database em cache do calendario.com.br...");
				$xmlData = simplexml_load_string(file_get_contents($useFilename));
				$xmlData = json_decode(json_encode($xmlData), 1);
				if(!isset($xmlData['event'])){
					$xmlData['event'] = Array();
				}
				if(!isset($xmlData['event'][0])){
					$xmlData['event'] = Array($xmlData['event']);
				}
				
				$ignoreFeriados = explode(",", strtolower($options['ignore']));
				$rawFeriados = $xmlData['event'];
				foreach($rawFeriados as $item){
					if(in_array(strtolower($item['type']), $ignoreFeriados)){
						continue;
					}
					
					$knownFeriados[dHelper2::brDateToUsDate($item['date'])] = $item;
				}
			}
		}
		
		$_verb("Realizando o cálculo de próximo dia útil...");
		$nextDiaUtil = $time;
		while(true){
			$usDate     = date('Y-m-d', $nextDiaUtil);
			$_isFDS     = (date('w', $nextDiaUtil) == 0 || date('w', $nextDiaUtil) == 6);
			$_isFeriado = isset($knownFeriados[$usDate]);
			if($_isFDS || $_isFeriado){
				$nextDiaUtil += 60*60*24;
				continue;
			}
			break;
		}
		
		$_verb("Concluído, retornando {$usDate}");
		return $usDate;
	}
	static Function getDaysBetween($time1, $time2=false){ // time1=mais recente, time2=mais antigo
		// Input pode ser us-date ou timestamp (integer).
		// 
		// Retorna número de dias entre dois períodos de tempo.
		// Hora/Minuto/Segundo será ignorado.
		// 
		// * Quando há a mudança de horário de verão, há diferença de no máx. 1 hora (3600s).
		//   Isso significa cerca de 0.04 dias a mais OU a menos, dependendo se estiver
		//   entrando ou saindo do horário de verão.
		// 
		//   Para resolver isso, poderíamos padronizar a data de entrada como GMT, ou 
		//   então podemos simplesmente arredondar para um número inteiro: é isso que faremos.
		// 
		if(is_string($time1))
			$time1 = strtotime($time1);
		if(!$time2)
			$time2 = time();
		else if(is_string($time2))
			$time2 = strtotime($time2);
		
		$time1    = strtotime("12:00:00", $time1);
		$time2    = strtotime("12:00:00", $time2);
		$daysDiff = round(($time1-$time2)/(24*60*60));
		return $daysDiff;
	}
	static Function getAllDaysBetween  ($initDate, $endDate){
		if(strlen($initDate) != 10) die("Parametro invalido. Esperados 10 caracteres. ($initDate)");
		if(strlen($endDate)  != 10) die("Parametro invalido. Esperados 10 caracteres ($endDate).");
		
		$allDays = Array();
		$curDate = $initDate;
		if($initDate < $endDate){
			do{
				$allDays[] = $curDate;
				$curDate = date('Y-m-d', strtotime("{$curDate} +1 day"));
			} while($curDate <= $endDate);
		}
		elseif($initDate > $endDate){
			do{
				$allDays[] = $curDate;
				$curDate = date('Y-m-d', strtotime("{$curDate} -1 day"));
			} while($curDate >= $endDate);
		}
		
		return $allDays;
	}
	static Function getAllMonthsBetween($initDate, $endDate, $limit=false){
		$allMonths = Array();
		if(strlen($initDate) > 7) $initDate = substr($initDate, 0, 7);
		if(strlen($endDate)  > 7) $endDate  = substr($endDate,  0, 7);
		if(strlen($initDate) != 7) die("Parametro invalido. Esperados 7 caracteres. ($initDate)");
		if(strlen($endDate)  != 7) die("Parametro invalido. Esperados 7 caracteres ($endDate).");
		
		$limitCount = $limit?$limit:false;
		$curDate  = $initDate;
		if($initDate == $endDate){
			return Array($initDate);
		}
		elseif($initDate < $endDate){
			do{
				$allMonths[] = $curDate;
				$curDate = date('Y-m', strtotime("{$curDate}-01 +1 month"));
				if($limit){
					if(!--$limitCount){
						break;
					}
				}
			} while($curDate <= $endDate);
		}
		elseif($initDate > $endDate){
			do{
				$allMonths[] = $curDate;
				$curDate = date('Y-m', strtotime("{$curDate}-01 -1 month"));
				if($limit){
					if(!--$limitCount){
						break;
					}
				}
			} while($curDate >= $endDate);
		}
		
		return $allMonths;
	}
	static Function getFriendlyBetween ($initDate, $endDate, $options=Array()){
		if(strlen($initDate) != 10) die("Parametro invalido. Esperados 10 caracteres. ($initDate)");
		if(strlen($endDate)  != 10) die("Parametro invalido. Esperados 10 caracteres ($endDate).");
		
		$options += Array(
			'considerFirstDay'=>false,
			'returnArray'     =>false,
		);
		
		$initDate = strtotime($initDate." 12:00:00");
		$endDate  = strtotime($endDate ." 12:00:00".($options['considerFirstDay']?" +1 day":""));
		
		if($initDate > $endDate){
			$_tmp = $initDate;
			$initDate = $endDate;
			$endDate  = $_tmp;
		}
		
		$curDate    = $initDate;
		$fullMonths = 0; 
		while(true){
			$nextDate = strtotime("+1 month", $curDate);
			if(gmdate('Y-m-d', $nextDate) > gmdate('Y-m-d', $endDate)){
				// Some os dias parciais.
				$nDays = round(($endDate-$curDate)/(60*60*24));
				break;
			}
			
			$fullMonths++;
			$curDate = $nextDate;
		}
		
		$nYears  = intval($fullMonths/12);
		$nMonths = $fullMonths%12;
		
		if($options['returnArray']){
			return Array('nYears' =>$nYears, 'nMonths'=>$nMonths, 'nDays'=>$nDays);
		}
		
		$retStr = Array();
		if($nYears){
			$retStr[] = "{$nYears} ano".(($nYears>1)?"s":"");
		}
		if($nMonths){
			$retStr[] = "{$nMonths} ".(($nMonths>1)?"meses":"mês");
		}
		$retStr[] = "{$nDays} dia".(($nDays!=1)?"s":"");
		
		if(sizeof($retStr) > 1){
			$sof = sizeof($retStr);
			for($x = 0; $x < $sof; $x++){
				if($x+1 == $sof)
					$retStr[$x] = "e {$retStr[$x]}";
				elseif($x+1 < $sof-1)
					$retStr[$x] .= ",";
			}
		}
		
		return implode(" ", $retStr);
	}
	
	static Function removeAccents($string){
		$mapFrom = Array("à","á","â","ã","ä","ç","è","é","ê","ë","ì","í","î","ï","ñ","ò","ó","ô","õ","ö","ù","ú","û","ü","ý","ÿ","À","Á","Â","Ã","Ä","Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ñ","Ò","Ó","Ô","Õ","Ö","Ù","Ú","Û","Ü","Ý","²","³","&");
		$mapTo   = Array("a","a","a","a","a","c","e","e","e","e","i","i","i","i","n","o","o","o","o","o","u","u","u","u","y","y","A","A","A","A","A","C","E","E","E","E","I","I","I","I","N","O","O","O","O","O","U","U","U","U","Y","2","3","e");
		$string  = str_replace($mapFrom, $mapTo, $string);
		return $string;
	}
	static Function stringToTitle($string){
		// Opcionalmente, utilize mb_strtolower($string) antes deste método.
		// joão das neves => João das Neves
		$string   = mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
		$_strFrom = Array(" De ", " Do ", " Dos", " Da", " Das ", " E ", " O ", " A ", " Os ", " As ", " É ", " Se ", " Que ", " Com ", " Há ");
		$_strTo   = Array(" de ", " do ", " dos", " da", " das ", " e ", " o ", " a ", " os ", " as ", " é ", " se ", " que ", " com ", " há ");
		return str_replace($_strFrom, $_strTo, $string);
	}
	static Function stringToUrl($string, $allowChars=''){
		$string = self::removeAccents($string);
		$string = preg_replace("/[^A-Za-z0-9".preg_quote($allowChars)."]/", "_",  $string);
		$string = strtolower(preg_replace("/_+/", "_", $string));
		$string = trim($string, "_");
		return $string;
	}
	static Function strLimit($string, $maxChars, $dots='...'){
		// Retorna apenas palavras inteiras até o limite de {$maxChars},
		// e acrescenta '...' se tiver cortado alguma informação.
		// 
		if(strlen($string) < $maxChars)
			return $string;
		
		$words = explode(chr(0), wordwrap($string, $maxChars, chr(0)));
		$value = $words[0];
		if(strlen($value) > $maxChars){
			$value = substr($value, 0, $maxChars).$dots;
		}
		elseif(isset($words[1])){
			$value .= $dots;
		}
		
		return $value;
	}
	
	static Function writeWhatsappText($text, $settings=Array()){
		// Processa a formatação conhecida de Whatsapp, sendo:
		// *negrito*, _italico_, ~riscado~.
		// --> *Texto**Texto* => <b>Texto</b><b>Texto</b>
		// --> *Texto*Texto*  => <b>Texto*Texto</b>
		// --> *Texto*, etc.  => <b>Texto</b>, etc.
		// --> *Texto*etc     => *Texto*etc
		// Não funciona na quebra de linha
		// Não funciona se vier uma outra letra ou número na sequencia, sem espaços
		
		$tagMap = Array("*"=>"b", "_"=>"i", "~"=>"strike");
		foreach($tagMap as $char=>$tag){
			$char   = preg_quote($char);
			$theRegex = "/([^a-zA-Z0-9]|^){$char}(.+?){$char}([^a-zA-Z0-9]|$)/";
			do{
				$_oText = $text;
				$text   = preg_replace($theRegex, "\\1<b>\\2</b>\\3", $text);
			} while($_oText != $text);
		}
		return $text;
	}
	static Function writeRte($text, $settings=Array()){
		// Usage:
		//     dHelper2::writeRte($htmlBody, $maxWidth)
		//     dHelper2::writeRte($htmlBody, $settings)
		if(!is_array($settings) && is_numeric($settings)){
			$settings = Array('maxWidth'=>$settings);
		}
		elseif(!is_array($settings)){
			$settings = Array();
		}
		
		$settings = dHelper2::addDefaultToArray($settings, Array(
			'maxWidth'  =>420,
			'relPath'   =>'' ,
			'responsive'=>false,
		));
		
		$text = preg_replace_callback("/\[video:(.+?)\]/", function($args) use ($settings){
			$fulltag = $args[0];
			$objtag  = $args[1];
			$ret     = htmlspecialchars_decode($objtag);
			$ret     = str_ireplace("<br />", " ", $ret);
			$ret     = str_replace(Array("\r\n", "\r", "\n"), " ", $ret);
			$wf = preg_match("/width=[\"\']([0-9]+)[\"\']/i",  $ret, $w);
			$hf = preg_match("/height=[\"\']([0-9]+)[\"\']/i", $ret, $h);
			if($w && $h && $w[1] > $settings['maxWidth']){
				$ret = str_replace($w[1], $settings['maxWidth'], $ret);
				$ret = str_replace($h[1], intval(($settings['maxWidth']*$h[1])/$w[1]), $ret);
			}
			
			return $ret;
		}, $text);
		
		if($settings['responsive']){
			// Substituir "width: xxxx; height: yyyy" por "width: 100%; max-width: xxx; height: auto;"
			// Substituir "width=xxxx, height=yyyy" pelo mesmo acima.
			
			// Nem sempre o Youtube está retornando width e height juntos.
			// Ex: <iframe width="560" src="xxxx" height="315">. Vamos padronizar:
			$text = preg_replace("/(<.+?)width=[\"'](.+?)[\"'] (.+) height=[\"'](.+?)[\"'](.*?".">)/is", '\1 width="\2" height="\4" \3 \5', $text);
			$text = preg_replace("/(<.+?)height=[\"'](.+?)[\"'] (.+) width=[\"'](.+?)[\"'](.*?".">)/is", '\1 width="\4" height="\2" \3 \5', $text);
			
			// Se tivermos um ratio definido, vamos utilizar um macete!
			$text = preg_replace_callback("/width=[\"'](.+?)[\"'] height=[\"'](.+?)[\"']/is", function($match){
				$ratio = $match[1]/$match[2];
				$setH  = round(100/$ratio, 2);
				return "style='width: 100vw; height: {$setH}vw; max-width: {$match[1]}px; max-height: {$match[2]}px'";
			}, $text);
			
			// Se detectarmos vídeo do Youtube, tem macete!
			// <iframe width="560" height="315" src="https://www.youtube.com/embed/..." frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
			// <iframe style="width: 100vh; height: 56.25vh" ...>
			$text = preg_replace_callback("/<img(.+)>/", function($matches){
				$str = $matches[0];
				$str = preg_replace("/width: (.+?);/", "max-width: \\1; width: 100%;", $str);
				$str = preg_replace("/height: (.+?);/", "height: auto;", $str);
				return $str;
			}, $text);
		}
		
		$text = str_replace("../fotos/", "{$settings['relPath']}fotos/", $text);
		
		return $text;
	}
	static Function writePagination($settings, $s=false){
		if(is_object($settings)){
			$_tmp = $settings;
			$settings = $s;
			$s        = $_tmp;
			unset($_tmp);
		}
		$settings = self::addDefaultToArray($settings, Array(
			'pagesTotal'      =>$s->getPagesTotal(),
			'page'            =>$s->getPage(),
			'queryString'     =>isset($s->pagination['q_str'])?$s->pagination['q_str']:$s->strings['page'], // v3 : v2
			'nBefore'         =>5,
			'nAfter'          =>5,
			'wrapNSel'        =>Array('', ''),
			'wrapSel'         =>Array('', ''),
			'innerNSel'       =>Array('', ''),
			'innerSel'        =>Array('', ''),
			'classNSel'       =>'',
			'classSel'        =>'sel',
			'strBetween'      =>" \r\n",
			'strMinusPlus'    =>Array("&laquo;", "&raquo;"),
			'innerMinusPlus'  =>Array('', ''),
			'wrapMinusPlus'   =>Array('', ''),
			'classMinusPlus'  =>Array('arrow', 'arrow'),
		));
		extract($settings);
		
		$page = ($page<1)?1:$page;
		$page = ($page>$pagesTotal)?$pagesTotal:$page;
		
		// Prepare to write the links
		$link = ($_SERVER["QUERY_STRING"]?"$_SERVER[QUERY_STRING]":"");
		$link = str_replace(Array($queryString."={$page}&", "&".$queryString."=$page", $queryString."=$page"), "", $link);
		if($link){
			$link  = "?{$link}&{$queryString}=";
		}
		else{
			$link  = "?{$queryString}=";
		}
		$link = $_SERVER['PHP_SELF'].$link;
		// End of preparation
		
		if($page-$nBefore < 1){
			$nAfter  += ($nBefore-$page+1);
			$nBefore -= ($nBefore-$page+1);
		}
		if($page+$nAfter  > $pagesTotal){
			$nBefore += ($page+$nAfter)-$pagesTotal;
			$nAfter  -= ($page+$nAfter)-$pagesTotal;
		}
		
		$_elBefore = Array();
		$_elAfter  = Array();
		$_elArrow  = Array('first'=>'', 'last'=>'');
		$_elSel    = Array();
		
		if($page - $nBefore > 1){        // Seta 'First'
			$_elArrow['first'] = "{$wrapMinusPlus[0]}<a href='{$link}1'>{$innerMinusPlus[0]}{$strMinusPlus[0]}{$innerMinusPlus[1]}</a>{$wrapMinusPlus[1]}";
		}
		if($page+$nAfter < $pagesTotal){ // Seta 'Last'
			$_elArrow['last'] = "{$wrapMinusPlus[0]}<a href='{$link}{$pagesTotal}'>{$innerMinusPlus[0]}{$strMinusPlus[1]}{$innerMinusPlus[1]}</a>{$wrapMinusPlus[1]}";
		}
		
		// Elementos não selecionados (antes e depois)
		for($x = ($page-$nBefore>0?($page-$nBefore):1); $x < $page; $x++){
			$_elBefore[] = "{$wrapNSel[0]}<a href='{$link}{$x}'>{$innerNSel[0]}{$x}{$innerNSel[1]}</a>{$wrapNSel[1]}";
		}
		for($x = $page+1; $x <= ($page+$nAfter); $x++){
			$_elAfter[] = "{$wrapNSel[0]}<a href='{$link}{$x}'>{$innerNSel[0]}{$x}{$innerNSel[1]}</a>{$wrapNSel[1]}";
		}
		
		// Elemento atual
		$_elSel = "{$wrapSel[0]}<a href='{$link}{$page}'>{$innerSel[0]}{$page}{$innerSel[1]}</a>{$wrapSel[1]}";
		
		// Adiciona as classes
		$_elFinal = Array();
		
		if($_elArrow['first']){
			$_elFinal[] = $classMinusPlus[0]?
				"<span class='{$classMinusPlus[0]}'>{$_elArrow['first']}</span>":
				"{$_elArrow['first']}";
		}
		if($_elBefore){
			$_elFinal[] = $classNSel?
				"<span class='{$classNSel}'>".implode($strBetween, $_elBefore)."</span>":
				implode($strBetween, $_elBefore);
		}
		$_elFinal[] = "<span class='{$classSel}'>{$_elSel}</span>";
		if($_elAfter){
			$_elFinal[] = $classNSel?
				"<span class='{$classNSel}'>".implode($strBetween, $_elAfter)."</span>":
				implode($strBetween, $_elAfter);
		}
		if($_elArrow['last']){
			$_elFinal[] = $classMinusPlus[1]?
				"<span class='{$classMinusPlus[1]}'>{$_elArrow['last']}</span>":
				$_elArrow['last'];
		}
		
		return "\r\n".implode($strBetween, $_elFinal)."\r\n";
	}
	
	/**
	 * @param array $list Array contendo a lista original.
	 * @param $cbValue    Callback($row) retornando o comparador: Se for igual, será agrupado.
	 *                    Se for String e derivado de dDbRow3, será chamado ->v($cbValue).
	 *                    Se for String e um array multi-assoc, será utilizado $row[$cbValue]).
	 * @return array      Separa o Array original em sub-listas, agrupadas pelo $cbValue.
	 */
	static Function groupByValue(&$list, $cbValue){
		if(!sizeof($list)){
			return $list;
		}
		if(is_string($cbValue)){
			$cbValue = function($row) use ($cbValue){
				if(is_array($row)){
					return $row[$cbValue];
				}
				else if(is_subclass_of($row, 'dDbRow3')){
					return $row->v($cbValue);
				}
				return "???";
			};
		}
		
		$firstObj = self::getFirst($list);
		$lastVal  = $cbValue($firstObj);
		$newList  = [];
		foreach($list as $idx=>$itemObj){
			$newVal = $cbValue($itemObj);
			if($newVal != $lastVal){
				$lastVal = $newVal;
			}
			$newList[$lastVal][] = $itemObj;
		}
		return $newList;
	}

	static Function parseStringQuotes($str, $openQuotes=Array("'", '"'), $closeQuotes=Array("'", '"'), $escape=Array("\\")){
		// Retorna chunks de acordo com o exemplo:
		//   Input:  Eu sou "uma" string
		//   Output: [] Array('offset'=>0,  'length'=>7, 'delim'=>false,           'type'=>'outside') // Eu_sou_
		//           [] Array('offset'=>7,  'length'=>5, 'delim'=>Array('"', '"'), 'type'=>'inside')  // "uma"
		//           [] Array('offset'=>12, 'length'=>7, 'delim'=>false,           'type'=>'outside') // _string
		// 
		$openQuotes   = is_array($openQuotes) ?$openQuotes :Array($openQuotes);
		$closeQuotes  = is_array($closeQuotes)?$closeQuotes:Array($closeQuotes);
		$escape       = is_array($escape)     ?$escape     :Array($escape);
		$strLen       = strlen($str);
		$outOffsets   = Array();
		$_openIdx     = false;
		$_chunkLength = 0;
		$_checkNext   = function($offset, $checkList, $reverse=false) use (&$str){
			// _checkNext:
			// --> Verifica se o texto próximo ao cursor obedece ao que está sendo buscado.
			// --> Se checkList for Array,  vai retornar o index encontrado ou FALSE.
			// --> Se checkList for string, vai retornar TRUE/FALSE.
			if(is_string($checkList)){
				return (substr($str, $offset-(($reverse==true)?strlen($checkList):0), strlen($checkList)) == $checkList);
			}
			foreach($checkList as $idx=>$tryStr){
				if(substr($str, $offset-(($reverse==true)?strlen($tryStr):0), strlen($tryStr)) == $tryStr){
					return $idx;
				}
			}
			
			return false;
		};
		
		for($x = 0; $x < $strLen; $x++){
			if($_openIdx === false){
				if(($_openIdx = $_checkNext($x, $openQuotes)) !== false){
					$outOffsets[] = Array('offset'=>$x, 'delim'=>$openQuotes[$_openIdx], 'type'=>'open');
				}
			}
			elseif($_checkNext($x, $closeQuotes[$_openIdx])){
				//  Tinha um escape antes de supostamente encerrar a string?
				if($_checkNext($x, $escape, true) !== false){
					continue;
				}
				$outOffsets[] = Array('offset'=>$x+strlen($closeQuotes[$_openIdx]), 'delim'=>$closeQuotes[$_openIdx], 'type'=>'close');
				$_openIdx     = false;
			}
		}
		if(sizeof($outOffsets)%2){
			$outOffsets[] = Array('offset'=>$strLen, 'delim'=>false, 'type'=>'close');
		}
		
		$outChunks   = Array();
		$_last       = Array('offset'=>0, 'delim'=>false, 'type'=>false);
		foreach($outOffsets as $item){
			$isInside = ($_last['type']=='open' && $item['type']=='close');
			$length   = ($item['offset']-$_last['offset']);
			if($length){
				$outChunks[] = Array(
					'offset'=>$_last['offset'],
					'length'=>$length,
					'delim' =>$isInside?Array($_last['delim'], $item['delim']):false,
					'type'  =>$isInside?"inside":"outside",
				);
			}
			$_last = $item;
		}
		if($_last['offset'] < $strLen){
			$outChunks[] = Array(
				'offset'=>$_last['offset'],
				'length'=>$strLen-$_last['offset'],
				'delim'=>false,
				'type' =>'outside',
			);
		}
		
		return $outChunks;
	}
	static Function explode_outside_quotes    ($separator, $str, $limit=false,                $openQuotes=Array("'", '"'), $closeQuotes=Array("'", '"'), $escape=Array("\\")){
		// Equivalente ao método explode(), mas permite ignorar aspas, tags, etc..
		$allChunks = self::parseStringQuotes($str, $openQuotes, $closeQuotes, $escape);
		
		$buffer    = false;
		$output    = Array();
		array_map(function($chunk) use (&$output, &$buffer, &$str, &$separator, &$limit){
			$string = substr($str, $chunk['offset'], $chunk['length']);
			if($chunk['type'] == 'inside'){
				$buffer .= $string;
			}
			else{
				$parts   = explode($separator, $string);
				while(sizeof($parts) > 1){
					$buffer .= array_shift($parts);
					$output[] = $buffer;
					$buffer  = '';
				}
				$buffer .= array_shift($parts);
			}
		}, $allChunks);
		if($buffer !== false){
			$output[] = $buffer;
			$buffer   = false;
		}
		
		if($limit && sizeof($output) > $limit){
			return array_slice($output, 0, $limit);
		}
		
		return $output;
	}
	static Function str_replace_outside_quotes($search, $replace, $str, &$replace_count=0, $openQuotes=Array("'", '"'), $closeQuotes=Array("'", '"'), $escape=Array("\\")){
		// Equivalente ao método str_replace, mas permite ignorar aspas, tags, etc.
		$allChunks = self::parseStringQuotes($str, $openQuotes, $closeQuotes, $escape);
		$output    = '';
		foreach($allChunks as $chunk){
			$string = substr($str, $chunk['offset'], $chunk['length']);
			if($chunk['type'] == 'inside')
				$output .= $string;
			else{
				$tmpCount  = 0;
				$output   .= str_replace($search, $replace, $string, $tmpCount);
				$replace_count += $tmpCount;
			}
		}
		
		return $output;
	}
	static Function parseSql($str){
		// Recebe uma string SQL Query.
		// Retorna um Array(select, from, where, order by, group by, having, limit)
		// 
		// Limitações:
		// --> Não processa multi-querys;
		// --> Uso intensivo de memória
		// 
		$split = Array(
			'select'  =>false,
			'from'    =>false,
			'where'   =>false,
			'group by'=>false,
			'order by'=>false,
			'having'  =>false,
			'limit'   =>false,
		);
		
		// 1. Padroniza as keywords como [_select_], [_from_], etc..
		$allGroups = array_keys($split);
		$allChunks = dHelper2::parseStringQuotes($str, Array('"', "'", "("), Array('"', "'", ")"));
		# $allChunks = array_map(function($item) use ($str){
			# $item['string'] = substr($str, $item['offset'], $item['length']);
			# return $item;
		# }, $allChunks);
		# dDbRow3::dump($allChunks);
		
		$output    = '';
		foreach($allChunks as $chunk){
			$string = substr($str, $chunk['offset'], $chunk['length']);
			if($chunk['type'] == 'inside'){
				$output .= $string;
				continue;
			}
			
			foreach($allGroups as $tryGroup){
				$string = preg_replace_callback("/( |^|\t|\r|\n)({$tryGroup})( |\t|\r|\n)/is", function($matches){
					return $matches[1]."[_".strtolower(trim($matches[2]))."_]".$matches[3];
				}, $string, 1);
			}
			$output .= $string;
		}
		$str       = $output;
		
		// 2. Separa os grupos relacionados.
		$parts           = dHelper2::explode_outside_quotes("[_select_]", $str, 2, Array('"', "'", "("), Array('"', "'", ")"));
		$split['select'] = (sizeof($parts)>1)?$parts[1]:$parts[0];
		$curGroup        = array_shift($allGroups); // select
		
		while($tryGroup = array_shift($allGroups)){
			$parts = dHelper2::explode_outside_quotes("[_{$tryGroup}_]", $split[$curGroup],   2, Array('"', "'", "("), Array('"', "'", ")"));
			if(sizeof($parts)>1){
				$split[$curGroup] = $parts[0];
				$split[$tryGroup] = $parts[1];
				$curGroup = $tryGroup;
			}
		}
		
		return $split;
	}
	
	static Function getFirst(&$array){
		// Função otimizada para obter o primeiro item de um Array, mesmo num array não associativo.
		return current(array_slice($array, 0, 1));
	}
	static Function getLast(&$array){
		// Função otimizada para obter o primeiro item de um Array, mesmo num array não associativo.
		return current(array_slice($array, -1));
	}
	static Function arrayFilterRecursive($array, $callback=false){
        $result = array();
        foreach($array as $key=>$value){
            if(is_array($value)){
				$_tmp = self::arrayFilterRecursive($value, $callback);
				if(sizeof($_tmp)){
					$result[$key] = $_tmp;
				}
                continue;
            }
            if($callback && $callback($key, $value)){
                $result[$key] = $value;
                continue;
            }
			if(strlen($value)){
				$result[$key] = $value;
			}
        }
        return $result;
	}
	
	static Function formataCpfCnpj ($useCpfCnpj){
		$useCpfCnpj = preg_replace("/[^0-9]/", "", $useCpfCnpj);
		if(strlen($useCpfCnpj) == 11){ // CPF
			$useCpfCnpj = substr($useCpfCnpj,0,3).
				'.' . substr($useCpfCnpj,3,3).
				'.' . substr($useCpfCnpj,6,3).
				'-' . substr($useCpfCnpj,-2);
		}
		elseif(strlen($useCpfCnpj) == 14){
			// 10.981.438/0001-51
			$useCpfCnpj  =
				substr($useCpfCnpj,0,2).
				'.' . substr($useCpfCnpj,2,3).
				'.' . substr($useCpfCnpj,5,3).
				'/' . substr($useCpfCnpj,8,4).
				'-' . substr($useCpfCnpj,-2);
		}
		elseif(strlen($useCpfCnpj) == 15){
			// 010.981.438/0001-51
			$useCpfCnpj  =
				substr($useCpfCnpj,0,3).
				'.' . substr($useCpfCnpj,3,3).
				'.' . substr($useCpfCnpj,6,3).
				'/' . substr($useCpfCnpj,9,4).
				'-' . substr($useCpfCnpj,-2);
		}
		return $useCpfCnpj;
	}
	static Function formataCep     ($useCep){
		$useCep = preg_replace("/[^0-9]/", "", $useCep);
		if(strlen($useCep) == 8){
			$useCep = substr($useCep, 0, 5).
				'-' . substr($useCep,-3   );
		}
		return $useCep;
	}
	static Function formataTelefone($useFone, $forWhatsApp=false){
		// Se "forWhatsApp", retornará o número de telefone pronto para ser usado em "https://wa.me/(telefone)".
		if(!$useFone)
			return "";
		
		if($useFone[0] == "+"){
			return $useFone;
		}
		
		$tryFone = ltrim(preg_replace("/[^0-9]/", "", $useFone), '0');
		$sofFone = strlen($tryFone);
		
		if($forWhatsApp){
			// 4333453394    --> len=10. Fixo com DDD
			// 43999xxxxxx   --> len=11. Celular com DDD
			// 554333453394  --> len=12. Fixo com DDI+DDD
			// 5543999xxxxxx --> len=13. Celular com DDI+DDD
			if($sofFone == 10 || $sofFone == 11){
				return "55{$tryFone}";
			}
			return $tryFone;
		}
		
		if($sofFone ==  8){
			// 3333-3333
			return substr($tryFone, 0, 4)."-".substr($tryFone, 4);
		}
		if($sofFone ==  9){
			// 33333-3333
			return substr($tryFone, 0, 5)."-".substr($tryFone, 4);
		}
		if($sofFone == 10){
			// 43 3333-3333
			return "(".substr($tryFone, 0, 2).") ".substr($tryFone, 2, 4)."-".substr($tryFone, 6);
		}
		if($sofFone == 11){
			// 11 93333-3333
			return "(".substr($tryFone, 0, 2).") ".substr($tryFone, 2, 5)."-".substr($tryFone, 7);
		}
		
		return $useFone;
	}
	
	static Function getUfList    ($uf=false){
		$list = Array(
			'AC'=>'Acre',
			'AL'=>'Alagoas',
			'AP'=>'Amapá',
			'AM'=>'Amazonas',
			'BA'=>'Bahia',
			'CE'=>'Ceará',
			'DF'=>'Distrito Federal',
			'ES'=>'Espirito Santo',
			'GO'=>'Goiás',
			'MA'=>'Maranhão',
			'MT'=>'Mato Grosso',
			'MS'=>'Mato Grosso do Sul',
			'MG'=>'Minas Gerais',
			'PA'=>'Pará',
			'PB'=>'Paraiba',
			'PR'=>'Paraná',
			'PE'=>'Pernambuco',
			'PI'=>'Piauí',
			'RJ'=>'Rio de Janeiro',
			'RN'=>'Rio Grande do Norte',
			'RS'=>'Rio Grande do Sul',
			'RO'=>'Rondônia',
			'RR'=>'Roraima',
			'SC'=>'Santa Catarina',
			'SP'=>'São Paulo',
			'SE'=>'Sergipe',
			'TO'=>'Tocantins'
		);
		return $uf?
			$list[strtoupper($uf)]:
			$list;
	}
	static Function getMonthList ($n=false){
		$n = intval($n);
		$list = Array(
			1=>"janeiro",
			2=>"fevereiro",
			3=>"março",
			4=>"abril",
			5=>"maio",
			6=>"junho",
			7=>"julho",
			8=>"agosto",
			9=>"setembro",
			10=>"outubro",
			11=>"novembro",
			12=>"dezembro"
		);
		return $n?
			$list[$n]:
			$list;
	}
	static Function extensoData  ($data, $reduzido=false){
		// Normal:   dezesseis de outubro de dois mil e quatorze
		// Reduzido: 16 de outubro de 2014
		$parts = explode("/", $data, 3);
		if(sizeof($parts) != 3)
			return $data;
		
		$parts = array_map('intval', $parts);
		
		if($reduzido){
			return sprintf("%02d de %s de %04d", $parts[0], self::getMonthList($parts[1]), $parts[2]);
		}
		return sprintf("%s de %s de %s", 
			($parts[0]==1)?
				"primeiro":
				self::extensoNumero($parts[0]),
			self::getMonthList($parts[1]),
			self::extensoNumero($parts[2])
		);
	}
	static Function extensoValor ($valor){
		$singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
		$plural   = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhões");
		$c        = array("", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
		$d        = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa");
		$d10      = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove");
		$u        = array("", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove");
		$z        = 0;
		$rt       = false;

		$valor = number_format($valor, 2, ".", ".");
		$inteiro = explode(".", $valor);
		for($i=0;$i<count($inteiro);$i++)
			for($ii=strlen($inteiro[$i]);$ii<3;$ii++)
				$inteiro[$i] = "0".$inteiro[$i];

		// $fim identifica onde que deve se dar junção de centenas por "e" ou por "," ;)
		$fim = count($inteiro) - ($inteiro[count($inteiro)-1] > 0 ? 1 : 2);
		for ($i=0;$i<count($inteiro);$i++) {
			$valor = $inteiro[$i];
			$rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
			$rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
			$ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";
		
			$r = $rc.(($rc && ($rd || $ru)) ? " e " : "").$rd.(($rd &&$ru) ? " e " : "").$ru;
			$t = count($inteiro)-1-$i;
			$r .= $r ? " ".($valor > 1 ? $plural[$t] : $singular[$t]) : "";
			if ($valor == "000")$z++; elseif ($z > 0) $z--;
			if (($t==1) && ($z>0) && ($inteiro[0] > 0)) $r .= (($z>1) ? " de " : "").$plural[$t]; 
			if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) &&($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : ", ") : " ") . $r;
		}
		
		return($rt ? trim($rt) : "zero");
	}
	static Function extensoNumero($valor, $feminino=false){
		$valor    = intval($valor);
		$UNIDADES = $feminino?
			array(1 => 'uma', 2 => 'duas', 3 => 'três', 4 => 'quatro', 5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove'):
			array(1 => 'um',  2 => 'dois', 3 => 'três', 4 => 'quatro', 5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove');
		
		$DE11A19       = array(11 => 'onze', 12 => 'doze', 13 => 'treze', 14 => 'quatorze', 15 => 'quinze', 16 => 'dezesseis', 17 => 'dezessete', 18 => 'dezoito', 19 => 'dezenove');
		$DEZENAS       = array(10 => 'dez', 20 => 'vinte', 30 => 'trinta', 40 => 'quarenta', 50 => 'cinquenta', 60 => 'sessenta', 70 => 'setenta', 80 => 'oitenta', 90 => 'noventa');
		$CENTENA_EXATA = 'cem';
		$CENTENAS      = $feminino?
			Array(100 => 'cento', 200 => 'duzentas', 300 => 'trezentas', 400 => 'quatrocentas', 500 => 'quinhentas', 600 => 'seiscentas', 700 => 'setecentas', 800 => 'oitocentas', 900 => 'novecentas'):
			Array(100 => 'cento', 200 => 'duzentos', 300 => 'trezentos', 400 => 'quatrocentos', 500 => 'quinhentos', 600 => 'seiscentos', 700 => 'setecentos', 800 => 'oitocentos', 900 => 'novecentos');
		$MILHAR        = "mil";
		$MILHOES       = Array("milhão", "milhões");
		
		if($valor == 0){
			return 'zero';
		}
		elseif($valor >= 1 && $valor <= 9)
			return $UNIDADES[$valor]; // As unidades 'um' e 'dois' variam segundo o gênero
		
		else if($valor == 10)
			return $DEZENAS[$valor];
		
		else if($valor >= 11 && $valor <= 19)
			return $DE11A19[$valor];

		else if($valor >= 20 && $valor <= 99) {
			$dezena = $valor - ($valor % 10);
			$ret = $DEZENAS[$dezena];
			if($resto = $valor - $dezena)
				$ret .= ' e ' . self::extensoNumero($resto, $feminino);
			
			return $ret;
		}
		else if($valor == 100) {
			return $CENTENA_EXATA;
		}
		else if($valor >= 101 && $valor <= 999) {
			$centena = $valor - ($valor % 100);
			$ret = $CENTENAS[$centena]; // As centenas (exceto 'cento') variam em gênero
			if($resto = $valor - $centena)
				$ret .= ' e ' . self::extensoNumero($resto, $feminino);
			
			return $ret;
		}
		else if($valor >= 1000 && $valor <= 999999) {
			$milhar = floor($valor / 1000);
			$ret = self::extensoNumero($milhar, $feminino) . ' ' . $MILHAR; // 'Mil' é do gênero masculino
			$resto = $valor % 1000;
			if($resto && (($resto >= 1 && $resto <= 99) || $resto % 100 == 0))
				$ret .= ' e ' . self::extensoNumero($resto, $feminino);
			else if ($resto)
				$ret .= ', ' . self::extensoNumero($resto, $feminino);
			return $ret;
		}
		else if($valor >= 100000) {
			$milhoes = floor($valor / 1000000);
			$ret = self::extensoNumero($resto, $feminino) . ' ';
			$ret .= $milhoes == 1 ? self::$MILHOES[0] : self::$MILHOES[1];
			$resto = $valor % 1000000;
			if($resto && (($resto >= 1 && $resto <= 99) || $resto % 100 == 0))
				$ret .= ' e ' . self::extensoNumero($resto, $feminino);
			else if ($resto)
				$ret .= ', ' . self::extensoNumero($resto, $feminino);
			return $ret;
		}
	}
	static Function periodicidadeToInt($periodStr, $fatalOnNotFoud=false){
		if(is_numeric($periodStr)){
			return $periodStr;
		}
		if(!strlen($periodStr)){
			return false;
		}
		
		$period = false;
		switch(strtolower($periodStr)){
			case 'bi-semanal':    case 'bi-semanais':   
			case 'bisemanal':     case 'bisemanais': 
			case 'quinzenal':     case 'quinzenais':     $period = 0.5; break;
			case 'mensal':        case 'mensais':        $period = 1;   break;
			case 'bimestral':     case 'bimestrais':     $period = 2;   break;
			case 'trimestral':    case 'trimestrais':    $period = 3;   break;
			case 'quadrimestral': case 'quadrimestrais': $period = 4;   break;
			case 'semestral':     case 'semestrais':     $period = 6;   break;
			case 'anual':         case 'anuais':         $period = 12;  break;
			case 'bi-anual':      case 'bi-anuais':      
			case 'bianual':       case 'bianuais':       $period = 24;  break;
			case 'tri-anual':     case 'tri-anuais':     
			case 'trianual':      case 'trianuais':      $period = 36;  break;
			default:
				if($fatalOnNotFoud){
					dSystem::notifyAdmin('MED', "Periodicidade '{$periodStr}' desconhecida!",
						"Em dHelper2::periodicidadeToInt(), não sabemos como lidar com ".
						"a peridicidade '{$periodStr}', e como convertê-la em um número de meses.",
						true
					);
				}
		}
		return $period;
	}
	static Function intToPeriodicidade($period, $plural=false){
		if(!is_numeric($period))
			return $period;
		
		$periodStr = false;
		switch($period){
			case 0.5: $periodStr = $plural?'quinzenais'    :'quinzenal'; break;
			case 1:   $periodStr = $plural?'mensais'       :'mensal'; break;
			case 2:   $periodStr = $plural?'bimestrais'    :'bimestral'; break;
			case 3:   $periodStr = $plural?'trimestrais'   :'trimestral'; break;
			case 4:   $periodStr = $plural?'quadrimestrais':'quadrimestral'; break;
			case 6:   $periodStr = $plural?'semestrais'    :'semestral'; break;
			case 12:  $periodStr = $plural?'anuais'        :'anual'; break;
			case 24:  $periodStr = $plural?'bi-anuais'     :'bi-anual'; break;
			case 36:  $periodStr = $plural?'tri-anuais'    :'tri-anual'; break;
			default:
				if($fatalOnNotFoud){
					dSystem::notifyAdmin('LOW', "PeriodicidadeStr '{$period}' desconhecida!",
						"Em dHelper2::intToPeriodicidade(), não sabemos como lidar com ".
						"a peridicidade '{$period}', e como convertê-la de numérico para uma string."
					);
				}
		}
		return $periodStr;
	}
	
	static Function ucwordsBr($string){
		// Regras aplicadas para tornar maiúscula:
		// 1. Primeira letra da string;
		// 2. Qualquer palavra com mais de 3 letras;
		// 3. Qualquer palavra terminada com '.';
		// 4. Última palavra da sentença, mesmo que com até 3 letras.
		// 
		// Retorna, por exemplo:
		//   As Últimas Notícias que Você não Leu
		//   Título da Notícia que Será Exibida
		//   Maria das Dores Bernardes de Fátima
		//   João C. da Silva
		//   Nome da Empresa Ltda Me
		
		$allWords    = explode(" ", $string);
		$wordIdx     = -1;
		$maxIdx      = sizeof($allWords)-1;
		return implode(" ", array_map(function($str) use(&$wordIdx, &$maxIdx){
			$wordIdx++;
			$len = mb_strlen($str);
			if($wordIdx == 0 || $len > 3 || mb_substr($str, -1) == '.' || ($wordIdx == $maxIdx)){
				return
					mb_strtoupper(mb_substr($str, 0, 1)).
					mb_substr($str, 1);
			}
			return $str;
		}, $allWords));
	}
	static Function changeUrl($newValues, $useRequestUri=false, $replacePath=false){
		// newValues:     [page=>2]
		// useRequestUri: Opcional. Por padrão usa $_SERVER['REQUEST_URI'].
		// replacePath:   Mantém a Query String, mas bustitui o path do link retornado.
		
		if(!$useRequestUri)
			$useRequestUri = $_SERVER['REQUEST_URI'];
		
		$parts  = parse_url($useRequestUri);
		$params = Array();
		if(isset($parts['query']))
			parse_str($parts['query'], $params);
		
		if($replacePath){
			$parts['path'] = $replacePath;
		}
				
		$newParams = http_build_query(dHelper2::addDefaultToArray($newValues, $params));
		return $parts['path'].($newParams?"?{$newParams}":"");
	}
	static Function unparseUrl($parsed_url) { 
		// Input esperado: Resultado de parse_url
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
		$pass     = ($user || $pass) ? "{$pass}@" : ''; 
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
		return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
	} 
	static Function getBackURL($settings=Array()){
		$settings += Array(
			'fallbackUrl'=>false,
			'getAsJs'    =>false,
		);
		$fallbackUrl = $settings['fallbackUrl'];
		$getAsJs     = $settings['getAsJs'];
		
		if(!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['HTTP_REFERER'])){
			return $getAsJs?
				"history.go(-1);":
				$fallbackUrl;
		}
		
		$host    = $_SERVER['HTTP_HOST'];
		$referer = $_SERVER['HTTP_REFERER'];
		$parsed  = parse_url($referer);
		if(strtolower($parsed['host']) != strtolower($host)){
			if($getAsJs){
				return $fallbackUrl?
					"location.href='".addslashes($fallbackUrl)."';":
					"location.href='/';";
			}
			
			return $fallbackUrl;
		}
		
		return $getAsJs?
			"history.go(-1);":
			$referer;
	}
	
	static Function getAllPossibilitiesArray($toMix){
		// Input esperado:($toMix):
		// $toMix['Grupo A'] => Array('A1', 'A2', 'A3')
		// $toMix['Grupo B'] => Array('B1', 'B2', 'B3')
		// $toMix['Grupo C'] => Array('C1', 'C2', 'C3')
		//
		// Retorno esperado:
		// [] = A1,B1,C1
		// [] = A1,B1,C2
		// [] = A1,B1,C3
		// ...
		// [] = A3,B3,C1
		// [] = A3,B3,C2
		// [] = A3,B3,C3
		
		// Etapas:
		// $curList = Grupo C;
		// Move para Grupo B;
		// ==> curList = [itens grupo b, itens grupo c]
		// Move para Grupo A:
		// ==> curList = [itens grupo a, itens grupo b, itens grupo c]
		
		$currentMix = array_pop($toMix);
		foreach($currentMix as $idx=>$value){
			$currentMix[$idx] = Array($value);
		}
		$allGroups   = array_keys($toMix);
		while($group = array_pop($allGroups)){
			$newMix  = Array();
			foreach($toMix[$group] as $prependItem){
				for($x = 0; $x < sizeof($currentMix); $x++){
					$newMix[] = array_merge(array($prependItem), $currentMix[$x]);
				}
			}
			$currentMix = $newMix;
		}
		
		return $currentMix;
	}
	static Function getAllPossibilities($toMix, $prefix=''){
		// Input esperado:($toMix):
		// $toMix['Grupo A'] => Array('A1', 'A2', 'A3')
		// $toMix['Grupo B'] => Array('B1', 'B2', 'B3')
		// $toMix['Grupo C'] => Array('C1', 'C2', 'C3')
		
		// Retorno esperado:
		// A1,B1,C1
		// A1,B1,C2
		// A1,B1,C3
		// A1,B2,C1
		// A1,B2,C2
		// A1,B2,C3
		// ...
		// A3,B3,C1
		// A3,B3,C2
		// A3,B3,C3
		$curCateg = array_shift($toMix);
		if($toMix){
			$ret = Array();
			foreach($curCateg as $addprefix){
				$ret = array_merge($ret, self::getAllPossibilities($toMix, $prefix.$addprefix.','));
			}
			return $ret;
		}
		return array_map(function($item) use ($prefix){
			return $prefix.$item;
		}, $curCateg);
	}
	static Function forceFloat($number, $decimals=2){
		if(trim($number) === ''){
			$number = false;
		}
		elseif(is_numeric($number)){
			return round($number, $decimals);
		}
		else{
			$number = strtr($number, ",", ".");
			$parts  = explode(".", $number);
			if(sizeof($parts) > 1){
				$decimal = array_pop($parts);
				$number  = implode("", $parts);
				if($decimal){
					$number .= ".{$decimal}";
				}
				$number = round($number, $decimals);
			}
			else{
				$number = intval($parts[0]);
			}
			unset($parts);
		}
		return $number;
	}
	static Function moeda($number, $decimals=2){
		return number_format(self::forceFloat($number), $decimals, ',', '.');
	}
	static Function geraOpcoesTroco($valorBase, $prefixo="R$ ", $showDecimals=false){
		$opcao    = Array();
		$opcao[]  = ceil($valorBase / 2)  * 2;    // Troco para notas de 2
		$opcao[]  = ceil($valorBase / 5)  * 5;    // Troco para notas de 5
		$opcao[]  = ceil($valorBase / 10) * 10;   // Troco para notas de 10
		$opcao[]  = ceil($valorBase / 10) + 10;   // Troco para notas de 10+20
		$opcao[]  = ceil($valorBase / 50) * 50;   // Troco para notas de 50
		$opcao[]  = ceil($valorBase / 100) * 100; // Troco para notas de 100
		foreach($opcao as $idx=>$val){
			if($val <= $valorBase){
				unset($opcao[$idx]);
			}
		}
		$opcao = array_unique($opcao);
		sort($opcao);
		$opcao = array_map(function($val) use (&$prefixo, $showDecimals){
			return Array($val, $prefixo.dHelper2::moeda($val, $showDecimals?2:0));
		}, $opcao);
		return $opcao;
	}
	static Function addDefaultToArray($newVal, $defaultVal){
		if(sizeof(func_get_args()) > 2){
			$args          = array_slice(func_get_args(), -2, 2);
			$leftArgs      = array_slice(func_get_args(), 0, -2);
			$leftArgs[]    = call_user_func_array(Array('dHelper2', 'addDefaultToArray'), $args);
			return call_user_func_array(Array('dHelper2', 'addDefaultToArray'), $leftArgs);
		}
		
		if(!is_array($newVal) || !array_keys($newVal))
			return $defaultVal;
		
		foreach($defaultVal as $key=>$value){
			if(!array_key_exists($key, $newVal)){
				$newVal[$key] = $defaultVal[$key];
			}
			elseif(is_array($newVal[$key])){
				$newVal[$key] = self::addDefaultToArray($newVal[$key], $defaultVal[$key]);
			}
		}
		
		return $newVal;
	}
	
	static Function &putOnGrid(&$list, $maxCols, $getEmptyCells=true){
		// Uso:
		//   foreach(dHelper2::putOnGrid($lista, 3) as $row=>$cols)
		//     foreach($cols as $item)
		//       $item: Array(isLeft, isRight, isTop, isBottom, width, idx, item)
		$length   = sizeof($list);
		$maxRows  = ceil($length/$maxCols);
		$allRows  = Array();
		$useWidth = floor(100/$maxCols);
		$idx      = 0;
		for($row = 1; $row <= $maxRows; $row++){
			for($col = 1; $col <= $maxCols; $col++){
				$isEmpty = !(($idx+1)<=$length);
				if($isEmpty && !$getEmptyCells)
					break 2;
				
				$allRows[$row][$col] = Array(
					'isLeft'  =>($col==1),
					'isRight' =>($col==$maxCols),
					'isTop'   =>($row==1),
					'isBottom'=>($row==$maxRows),
					'isFirst' =>(($idx+1)==1),
					'isLast'  =>(($idx+1)==$length),
					'width'   =>($maxCols == 3 && $col == 2)?false:$useWidth,
					'idx'     =>$idx,
					'item'    =>$isEmpty?false:$list[$idx],
				);
				$idx++;
			}
		}
		
		return $allRows;
	}
	static Function &putOnTable(&$list, $maxCols, $callback, $settings=Array()){
		// callback($item, $info);
		$settings = self::addDefaultToArray($settings, Array(
			'allowEmpty'=>false,
			'table'     =>"width='100%' cellpadding='0' cellspacing='0' border='0'",
			'tr'        =>"",
			'td'        =>"",         
			'margin'    =>'25 auto',
			'return'    =>false,
		));
		$margin = $settings['margin']?
			explode(" ", str_replace("px", "", $settings['margin'])):
			Array(false, false);
		
		$_write = function($str) use (&$settings, &$retStr){
			if($settings['return'])
				$retStr .= $str;
			else
				echo $str;
		};
		
		$_write("<table".($settings['table']?" {$settings['table']}":"").">");
		foreach(self::putOnGrid($list, $maxCols) as $row=>$cols){
			$_write("<tr {$settings['tr']}>");
			foreach($cols as $col=>$info){
				$_write("<td {$settings['td']}>");
				
				if($info['item'] || $settings['allowEmpty']){
					$cbStr = call_user_func($callback, $info['item'], $info);
					if(!is_bool($cbStr))
						$_write($cbStr);
				}
				
				$_write("</td>");
				if(!$info['isRight'] && $margin[1]){
					// Espaçamento entre colunas
					$_write("<td".(($margin[1]!='auto')?" width='{$margin[1]}'":"")."><b></b></td>");
				}
			}
			$_write("</tr>");
			if(!$info['isBottom'] && $margin[0]){
				// Espaçamento entre linhas
				$_write("<tr height='{$margin[0]}'><td colspan='".($maxCols*2-1)."'><b></b></td></tr>");
			}
		}
		$_write("</table>");
		return $retStr;
	}
	
	static Function dynamicImageGetLink($imgFile, $string, $param_str=''){
		// Formato final:
		//     img.title/hash/param_str/string-b64.png
		$hash = substr(md5($imgFile.$param_str.$string.dSystem::getGlobal('hashkey')), -6);
		$url  = 
			"{$imgFile}/{$hash}/".
			(strlen($param_str)?"{$param_str}/":"").
			base64_encode($string).".png";
		
		return "<img src='{$url}' alt='".htmlspecialchars($string)."' border='0' />";
	}
	static Function dynamicImageLoadData($imgFile){
		$parts = explode("/", $_SERVER['PATH_INFO']);
		if(sizeof($parts) == 4){
			$uhash  = $parts[1];
			$uparam = $parts[2];
			$ustr   = base64_decode(substr($parts[3], 0, -4));
		}
		elseif(sizeof($parts) == 3){
			$uhash  = $parts[1];
			$uparam = '';
			$ustr   = base64_decode(substr($parts[2], 0, -4));
		}
		else{
			return false;
		}
		
		$realHash = substr(md5($imgFile.$uparam.$ustr.dSystem::getGlobal('hashkey')), -6);
		if($uhash != $realHash)
			return false;
		
		return Array(
			'param' =>$uparam,
			'string'=>$ustr
		);
	}
	
	// dSerialize e dUnserialize
	// --> Mesma funcionalidade do serialize, só que human-readable.
	// --> Útil para inserir informações no database como se fosse no-sql.
	static Function dSerialize($var){
		if(is_array($var) && !sizeof($var)){
			return "array()";
		}
		return var_export($var, true);
	}
	static Function dUnserialize($str){
		// To-do:
		// --> Adicionar alguma segurança aqui, de modo que apenas variáveis sejam
		//     executadas, e se tiver qualquer outra coisa, recuse a execução.
		return eval("return ".$str.";");
	}
	
	static Function randomNumberBasedOnString($str){
		// Pseudo-random (gera número de 0 até 100)
		$random = abs(crc32($str));  // Generate a 32-bit number based on the string
		$random = ($random%101);        // Limita o range
		return $random;
	}
	static Function randomTextBasedOnString($string, $textos=false){
		// Sempre que determinada string for enviada como parâmetro, o mesmo texto será exibido.
		// Assim, embora seja um texto aleatório, ele terá uma certa consistência sempre que for exibido.
		// Se $textos for um número, o retorno será outro número, de ZERO a ($textos)-1.
		
		// $seed: número entre 0 e 100
		// $rand: número entre 0 e $maxIdx
		// 
		// Como converter de $seed para $rand:
		//   $seed: 0 - 100
		//   $rand: 0 - $maxIdx
		// 
		// $seed  $rand
		//  100  $maxIdx
		// 
		// $seed /= 100; (0, 0.5, 1.0)
		
		$seed    = self::randomNumberBasedOnString($string)/100;
		$maxIdx  = (is_numeric($textos)?$textos:sizeof($textos))-1;
		$randIdx = round($seed * $maxIdx);
		if(is_numeric($textos)){
			return $randIdx;
		}
		return $textos[$randIdx];
	}
	
	static Function dSendMail($to, $subject, $message, $headers=false, $options=Array()){
		// Funcionalidades:
		// - Se headers vier em branco, utilizar noreply@dominio.com.br como remetente.
		// - Se não houver $options['dontCopy'], enviar uma cópia oculta para IMAGINACOM.
		// - Se não houver $options['dontSave'], salvar uma cópia no banco de dados.
		// - Se não houver $options['dontUseTemplate'], utilize o template em $_BaseDir/mailTemplate/template.html
		// - Se houver $options['attach'],   anexar os arquivos informados.
		// - Se houver $options['dontSend'], retorne o objeto dSendMail2 criado, sem enviar a mensagem.
		// - Se não existir $_BaseDir/mailTemplate/template.php, tente criar com valores padrão
		die("Not implemented yet.");
	}
	static Function includePage($filename, $params=Array()){
		// Inclui uma página do servidor, passando parâmetros para a mesma.
		// Esses parâmetros estarão disponíveis no contexto da página em questão.
		
		// Ps:
		//   Para retornar alguma variável da página incluída para o sistema,
		//   definir $return = xxxxxx; na execução. O ideal seria "return ($return=xxx);";
		
		$includeContext = true;
		$return         = Array();
		require $filename;
		return $return;
	}
	static Function redirectTo ($url, $topHeader=false){
		if(dSystem::getGlobal('localHosted')){
			echo "<script type='application/javascript'>\r\n";
			echo "window.onload = function(){\r\n";
			echo "	if(document.body && document.body.innerHTML){\r\n";
			echo "		document.body.innerHTML += \r\n";
			echo "			\"<div style='margin: 35px; padding: 10px; background: #FEE; font: 14px Arial; border: 1px solid #900'>\"+\r\n";
			echo "			\"	<b><u>dHelper2::redirectTo()</u></b><br />\"+\r\n";
			echo "			\"	<div style='margin-left: 25px; margin-top: 10px'>\"+\r\n";
			echo "			\"		DevMode detectado, interrompendo redirecionamento para análise.<br />\"+\r\n";
			echo "			\"		Continuar para: <a href='{$url}'>{$url}</a>.\"+\r\n";
			echo "			\"	</div>\"+\r\n";
			echo "			\"</div>\";\r\n";
			echo "	}\r\n";
			echo "	else{\r\n";
			echo "		window.location.href='{$url}';\r\n";
			echo "	}\r\n";
			echo "};\r\n"; 
			echo "</script>\r\n";
			die;
		}
		if($topHeader)
			header($topHeader);
		header("Location: {$url}");
	}
	static Function handleUserCache    ($etag, $ttl=false){
		// Etag  --> Forneça em MD5, sem aspas (ex: md5_file('content.js'))
		// Ttl   --> Se < 0, sempre vai expirar.
		//           Se false, utilizará padrão do navegador.
		//           Se > 0, vai expirar após $ttl segundos.
		//           Se for string, será utilizado em 'Cache-Control: $ttl'
		// 
		// Cabeçalhos escritos aqui:
		//   Cache-Control: (no-cache | max-age=$ttl | public)
		//   Etag: "{$etag}"
		//   Last-Modified: date('r', $mtime)
		// 
		// Cabeçalhos lidos aqui:
		//   If-Modified-Since: (Comparação com $mtime)
		//   In-None-Match:     (Comparação com $etag)
		// 
		// Entendendo o cache:
		// - https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
		// - https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
		// 
		// Variações de Cache-Control:
		//   $ttl<0     = no-cache: Sempre consultar o servidor, mas pode armazenar para uso com o eTag
		//   $ttl=false = public:   Pode armazenar em cache, utilizando padrões do navegador.
		//   $ttl>0     = max-age:  Quantidade de segundos cujo
		//   string       no=store: Nunca armazenar qualquer tipo de tag, nem mesmo para uso com o eTag
		//   string       private:  Cache exclusivo para aquele usuário, não pode ser armazenado por proxys ou cdns.
		// 
		// * Os campos 'Last-Modified' e 'If-Modified-Since' são redundantes com o uso do eTag, e não serão verificados.
		// * Os campos 'Expires' e 'Pragma' são deprecated, mas se não forem informados, podem ser gerados automaticamente
		//   pelo Apache e isso pode gerar valores que anulam os valores informados anteriormente.
		// * Se você utilizar o 'Vary:', o Chrome nunca vai atingir o Memory Caching, apenas o Disk Caching.
		
		if($etag){
			$etag = '"'.$etag.'"';
			header("ETag: {$etag}");
		}
		
		if(is_string($ttl)){
			header("Cache-Control: {$ttl}");
		}
		elseif($ttl < 0){
			header("Cache-Control: no-cache");
		}
		elseif($ttl > 0){
			header("Expires: ".gmdate('r', time() + $ttl));
			header("Pragma: cache");
			header("Cache-Control: public, max-age={$ttl}");
		}
		else{
			header("Cache-Control: public");
		}
		
		$isUserCached = ($etag && isset($_SERVER['HTTP_IF_NONE_MATCH']) && $etag == $_SERVER['HTTP_IF_NONE_MATCH']);
		if($isUserCached){
			header('HTTP/1.1 304 Not Modified');
			die;
		}
	}
	static Function getHashFromFileList($fileList, $check_content=false){
		// Recebe uma lista de arquivos no filesystem e gera um hash único para
		// aquele conjunto de arquivos (ou arquivo único). Útil para saber se
		// um arquivo foi modificado na lista de arquivos desejada.
		
		if(!is_array($fileList))
			$fileList = Array($fileList);
		
		$etag = '';
		foreach($fileList as $fn){
			$fileName = basename($fn);
			
			if($check_content){
				return file_exists($fn)?
					md5_file($fn):
					md5("{$fileName}00");
			}
			
			if(!file_exists($fn)){
				$size  = 0;
				$mtime = 0;
			}
			else{
				$size  = filesize($fn);
				$mtime = filemtime($fn);
			}
			
			$etag .= md5($fileName.$mtime.$size);
		}
		
		return md5($etag);
	}
	
	static $printedStyle = false;
	static Function dump(&$vars, $maxDepth=10, $_printedUids=Array()){
		if(!self::$printedStyle){
			echo "<style>\r\n";
			echo ".dHelper2_Dump    { font: 11px Arial }\r\n";
			echo ".dHelper2_Dump td { font: 11px Arial; padding: 3px }\r\n";
			echo "</style>\r\n";
			echo "<script>
			if(typeof $ == 'undefined'){(function () {
				var s = document.createElement('script');
				s.type = 'application/javascript';
				s.async = true;
				s.src = '//code.jquery.com/jquery-2.1.0.min.js';
				var x = document.getElementsByTagName('script')[0];
				x.parentNode.insertBefore(s, x);
			})();}
			
			dDbRow3DumpToggle = function(jqoDiv){
				var _initLeft  = $(window).scrollLeft();
				var _initWidth = jqoDiv.closest('td').width();
				jqoDiv.animate({ width: 'toggle', height: 'toggle' }, { queue: false });
				if(!jqoDiv.is(':hidden')){
					$('body').animate({ scrollLeft: jqoDiv.offset().left - 150 }, { queue: false });
				}
			};
			</script>";
			self::$printedStyle = true;
		}
		$bgC = '#F4F4FF';
		
		echo "<div class='dHelper2_Dump' rel='{$maxDepth}'>";
		if($maxDepth <= 0){
			echo "<i>Max depth reached</i>";
		}
		elseif(is_bool($vars)){
			echo "<small style='color: #008;'>".($vars?'true':'false')."</small>";
		}
		elseif(is_null($vars)){
			echo "<small style='color: #008;'>null</small>";
		}
		elseif(is_numeric($vars)){
			echo "<font color='#007F7F'>{$vars}</font>";
		}
		elseif(is_string($vars)){
			echo "<font color='#0000FF'>".nl2br(htmlspecialchars($vars))."</font>";
		}
		elseif(is_array($vars)){
			if(!$vars){
				echo "<div style='padding: 3px; background: {$bgC}; border-top: 1px dotted #000'>Array()</div>";
			}
			else{
				// Vamos exibir um Array...
				// Será que este Array() pode ser exibio como se fosse uma tabela?
				// Ex:  id | codigo | nome | email | etc
				//      1  | 12345  | xxx  | yyyyy | zzzzzzz
				// 
				// * Se: Todos os itens do Array() também forem Array(), e tiverem exatamente as mesmas keys
				$_looksLikeTable = function(&$vars){
					$_first = reset($vars);
					if(!is_array($_first)){
						// O primeiro item não é um Array, então com certeza não é tabela.
						return false;
					}
					
					$_akeys = array_keys($_first);
					if(sizeof($_akeys)?is_numeric($_akeys[0]):false){
						// Em $vars[0][$key], $key não é uma string, ou seja, não tem nome de coluna.
						return false;
					}
					
					if(sizeof($vars) < 8){
						$_useSample = &$vars;
					}
					else{
						$_useSample = array_merge(
							array_slice($vars, 0, 5),
							array_slice($vars, -3)
						);
					}
					
					$_nCols = sizeof($_first);
					foreach($_useSample as $_useIdx=>$_useVal){
						if(!is_array($_useVal)){
							return false;
						}
						if(sizeof($_useVal) != $_nCols){
							return false;
						}
					}
					
					return true;
				};
				
				$_showAsTable = $_looksLikeTable($vars);
				if($_showAsTable){
					echo "<table style='background: {$bgC}; border-collapse: collapse; border: 0px solid #500' border='1' cellspacing='0'>";
					$_showTitle = true;
					$_showIdx   = 0;
					foreach($vars as $varKey=>$varValue){
						if($_showTitle){
							echo "<thead>";
							echo "<tr>";
							echo "<td>[...]</td>";
							echo "<td><b>[".implode("]</b></td><td><b>[", array_keys($varValue))."]</b></td>";
							echo "</tr>";
							echo "</thead>";
							$_showTitle = false;
						}
						
						echo "<tr style='background: ".(($_showIdx++%2)?'#CCC':'#DDD')."'>";
						echo "<td>".htmlspecialchars($varKey)."</td>";
						foreach($varValue as $_key=>$_val){
							echo "<td title='[{$varKey}][{$_key}]'>";
							self::dump($_val, $maxDepth-1);
							echo "</td>";
						}
						echo "</tr>";
					}
					echo "</table>";
				}
				else{
					echo "<table style='background: {$bgC}; border-collapse: collapse; border: 0px solid #500' border='0' cellspacing='0'>";
					foreach($vars as $key=>$subVars){
						echo "<tr valign='top'>";
						echo "	<td style='border-top: 1px dotted #000; border-right: 1px dotted #000; color: #888'>";
						if(is_string($key)){
							echo "[<span style='color: #666'>".htmlspecialchars($key)."</span>]<br />";
						}
						else{
							echo "[<span style='color: #088'>{$key}</span>]<br />";
						}
						# if(is_array($subVars)){
							# echo "<small>".(strlen(preg_replace("/(\r?\n|\t|  +)/", "", print_r($subVars, true)))+1)." bytes</small>";
						# }
						echo "</td>";
						if(is_array($subVars)){
							echo "	<td style='border: 0; padding: 0; background: #CCC'>";
							self::dump($subVars, $maxDepth-1);
							echo "	</td>";
						}
						else{
							echo "	<td style='border-top: 1px dotted #000'>";
							self::dump($subVars, $maxDepth-1);
							echo "	</td>";
						}
						echo "</tr>";
					}
					# echo "<tr valign='top'>";
					# echo "	<td colspan='2' bgcolor='#EEEEEE' style='padding: 0px; font: 7px Arial; border-bottom: 1px solid #000'>";
					# echo "		Array (".sizeof($vars).")<br />";
					# echo "	</td>";
					# echo "</tr>";
					echo "</table>";
				}
			}
		}
		elseif(is_object($vars) && method_exists($vars, '__dump')){
			@$vars->__dump($maxDepth, $_printedUids);
		}
		elseif(is_object($vars) && is_callable($vars)){
			$show = print_r($vars, true);
			$show = preg_replace("/=> Array\r?\n.+\(\r?\n/", "=> Array:\r\n", $show);
			$show = preg_replace("/=> Array:\r?\n +\)\r?\n/", "=> Array()", $show);
			$show = preg_replace("/\r?\n +\)\r?\n/", "", $show);
			$show = preg_replace("/Object\r?\n.*\(\r?\n/", "Object:\r\n", $show);
			$show = explode("\n", $show);
			$_inStatic = false;
			$_inParam  = false;
			$params = Array();
			$use    = Array();
			foreach($show as $line){
				if(!$_inStatic && !$_inParam && strpos($line, '[static]') !== false){
					$_inStatic = true;
					continue;
				}
				if(!$_inParam && strpos($line, '[parameter]') !== false){
					$_inStatic = false;
					$_inParam  = true;
					continue;
				}
				if(!$_inParam && !$_inStatic){
					continue;
				}
				
				if(preg_match("/\[(.+)\] =>/", $line, $out)){
					if($_inStatic)
						$use[] = '$'.$out[1];
					if($_inParam)
						$params[] = $out[1];
				}
			}
			
			$use    = implode(", ", array_map(function($item){ return "<span style='color: #B00'>{$item}</span>"; }, $use));
			$params = implode(", ", array_map(function($item){ return "<span style='color: #B00'>{$item}</span>"; }, $params));
			$show   = implode("\n", $show);
			
			# echo "<pre style='background: #CFC; font: 12px Courier New'>{$show}</pre>";
			echo "function({$params})";
			if($use){
				echo " use ({$use})";
			}
			echo "{ ... }";
		}
		elseif(is_object($vars)){
			$class = new ReflectionClass(get_class($vars));
			$dumpS = $class->getStaticProperties();
			$dump  = get_object_vars($vars);
			
			$meth     = Array();
			$methS    = Array();
			$meth_Raw = $class->getMethods();
			foreach($meth_Raw as $item){
				$params = Array();
				foreach($item->getParameters() as $paramName){
					$params[] = "<small style='color: #F00'>\${$paramName->name}</small>";
				}
				
				if($item->isStatic()){
					$methS[] ="::{$item->name}(".implode(", ", $params).")";
				}
				else{
					$meth[] ="-&gt;{$item->name}(".implode(", ", $params).")";
				}
			}
			
			echo "<table style='background: #CCFFCC; border: 1px solid #080; box-shadow: -1px 1px 3px #888888;' cellspacing='0'>";
			echo "	<tr valign='top'>";
			echo "		<td colspan='2' bgcolor='#CCCCFF' style='position: relative'>";
			echo "			<i>object</i> ".get_class($vars)."<br />";
			echo "		</td>";
			echo "	</tr>";
			echo "	<tr>";
			echo "		<td style='border-right: 1px solid #888; background: #EEE'><b>Valores:</b><br />";
			echo "		<td style='background: #DDD'><b>Métodos:</b><br />";
			echo "	</tr>";
			echo "	<tr valign='top'>";
			echo "		<td style='border-right: 1px solid #888; background: #EEE'>";
			echo "<table cellpadding='0' cellspacing='0'>";
			foreach($dumpS as $fieldKey=>$fieldValue){
				echo "<tr valign='top'>";
				echo "	<td style='border-bottom: 1px dotted #000'>::{$fieldKey}</td>";
				echo "	<td style='border-bottom: 1px dotted #000'>";
				if(is_object($fieldValue) && $fieldValue === $vars){
					echo "<small style='color: #F00'>\$this</small>";
				}
				else{
					self::dump($fieldValue, $maxDepth-1);
				}
				echo "	</td>";
				echo "</tr>";
			}
			foreach($dump  as $fieldKey=>$fieldValue){
				echo "<tr valign='top'>";
				echo "	<td style='border-bottom: 1px dotted #000'>-&gt;{$fieldKey}</td>";
				echo "	<td style='border-bottom: 1px dotted #000'>";
				self::dump($fieldValue, $maxDepth-1);
				echo "	</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "		</td>";
			echo "		<td style='background: #DDD'>";
			echo "<table cellpadding='0' cellspacing='0'>";
			foreach($methS as $fieldValue){
				echo "<tr valign='top'>";
				echo "	<td style='border-bottom: 1px dotted #000'>{$fieldValue}</td>";
				echo "</tr>";
			}
			foreach($meth  as $fieldValue){
				echo "<tr valign='top'>";
				echo "	<td style='border-bottom: 1px dotted #000'>{$fieldValue}</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "		</td>";
			echo "	</tr>";
			echo "</table>";
		}
		else{
			echo "<pre style='background: #CCC' title='Tipo de dados desconhecido'>";
			print_r($vars);
			echo "</pre>";
		}
		echo "</div>";
	}
}
