<?php // built on 11/01/2020 19:38
class cRefDistancia extends dDbRow3{
	static Function buildStruct(){
		self::autoStruct('c_ref_distancias', [
			'ext'            =>Array(), // Classes externas por name. Ex: [pedidObj]=>'ePedido'
			'dump'           =>false,   // Se true, faz dump no código que pode ser copiado/colado na classe.
			'allowInProducao'=>true,
		]);
		
		self::setDefaultValue('data_create', date('d/m/Y H:i:s'));
		self::setAuditing(Array('dAuditObjeto', 'cbAuditCallback'));
	}
	
	static $cache = []; // [Origem][Distância] => Distância
	static Function ensureAllDistances($origens, $destino){
		// Certifica que tem a distância de TODAS as origens para o destino
		// no banco de dados antes de retornar.
		$destino = strtolower(dHelper2::removeAccents($destino));
		$origens = array_map(function($origem){
			return strtolower(dHelper2::removeAccents($origem));
		}, $origens);
		
		$missing = []; // Destinos missing (to fetch).
		foreach($origens as $origem){
			if(!self::getDistance($origem, $destino, false)){
				$missing[] = $origem;
			}
		}
		
		if($missing){
			$db         = dDatabase::start();
			$allResults = self::getDistanceFromGoogle($missing, $destino);
			foreach($allResults as $idx=>$foundDistance){
				$origem        = $missing[$idx];
				$foundDistance = $foundDistance?$foundDistance:1;
				
				$db->query("INSERT IGNORE INTO c_ref_distancias SET
			        data_create = '".date('Y-m-d H:i:s')."',
			        origem = '".addslashes($origem)."',
			        destino = '".addslashes($destino)."',
			        distancia = '".dHelper2::forceFloat($foundDistance)."'");
				self::$cache[$origem][$destino] = $foundDistance;
			}
		}
	}
	static Function getDistance($origem, $destino, $fallbackToGoogle=true){
		$origem  = strtolower(dHelper2::removeAccents($origem));
		$destino = strtolower(dHelper2::removeAccents($destino));
		if(isset(self::$cache[$origem][$destino])){
			return self::$cache[$origem][$destino];
		}
		
		$db            = dDatabase::start();
		$foundDistance = $db->singleResult("SELECT distancia FROM c_ref_distancias WHERE origem='" . addslashes($origem) . "' AND destino='" . addslashes($destino) . "'");
		if($foundDistance){
			self::$cache[$origem][$destino] = $foundDistance;
			return $foundDistance;
		}
		
		if(!$fallbackToGoogle){
			return false;
		}
		
		$foundDistance = intval(self::getDistanceFromGoogle($origem, $destino));
		$foundDistance = $foundDistance?$foundDistance:1;
		
		$cacheObj = new cRefDistancia;
		$cacheObj->v('origem',    $origem);
		$cacheObj->v('destino',   $destino);
		$cacheObj->v('distancia', $foundDistance);
		$cacheObj->save();
		
		return $foundDistance;
	}
	static Function getDistanceFromGoogle($origem, $destino){
		// Origem  --> Pode ser múltiplo
		// Destino --> Destino único!
		if(!is_array($origem)){
			$ret = self::getDistanceFromGoogle($from, $destino);
			return $ret[0];
		}
		
		$origins = array_map('rawurlencode', $origem);
		$destino = rawurlencode($destino);
		
		if(dSystem::getGlobal('localHosted')){
			echo "<b>Fazendo consulta ao Google.</b><br />";
		}
		$apiUrl = "https://maps.googleapis.com/maps/api/distancematrix/json".
				"?origins=".implode("|", $origins).
				"&destinations={$destino}".
				"&units=metric".  // Retorna distância em METROS.
				"&mode=driving".
				"&language=pt-BR".
				"&key=AIzaSyBjOLOmBkLkAKQbX2fiWDGYSknP7sr0FS0";
		
		dSystem::log('NOTICE', "Realizando consulta ao Google Maps API:", $apiUrl);
		$request = file_get_contents($apiUrl);
		
		$request = json_decode($request, true);
		$return  = [];
		if(@$request['rows']){
			foreach($request['rows'] as $row){
				$return[] = $row['elements'][0]['distance']['value'] / 1000;
			}
		}
		
		return $return;
	}
}
