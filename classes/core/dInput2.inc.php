<?php
// 22/02/2016
// - Bugfixes: Parametros eram removidos em ao utilizar '###-###' e Javascript Fatal Error.
// 14/02/2016
// - Novo script para number_mask melhorado e sem bugs. Depende apenas do jQuery.
// 25/05/14
// - Bugfix: Resolvidos casos onde uniqid não era realmente único.
// 07/03/11
// - inputRead agora pode receber .confirm sem passar pelo callback, para OK atrasado.
// 31/01/10
// - Removido todo uso de ereg*, para se tornar compatível com PHP6.
// 23/10/09 v2.3
// - Novo método: selectWrite()  (Em conjuntom com a biblioteca DHTMLX)
// * Em select(), o parâmetro $header pode ser um array
// 11/09/09 v2.2
// - Novos métodos:
//   selectIfr()
//   inputIfr()
//   spanIfr()
// 02/08/09 v2.1
// - Revisados todos os possíveis parâmetros para Select e SelectStr:
//   1 'descricao1,descricao2,descricao3,descricao4' (Opcional: Terminar com :[separador])
//   2 'valor1=descricao1,valor2=descricao2,valor3=descricao3:,'
//   3 Array(descricao1, descricao2, descricao3, descricao4)
//   4 Array(valor1=>descricao1, valor2=>descricao2, valor3=>descricao3)
//   8 Array(valor1=>Array('descricao1'))
//   5 Array(Array(valor1, descricao1), Array(valor2, descricao2))
//   6 Array(Array(0, descricao1), Array(1, descricao2))
//   7 Array(Array('id'=>'valor1', 'texto'=>'valor2'))
//   9 Array(Array('valor1'=>'descricao1'), Array('valor2'=>'descricao2'))
// 23/07/09
// - selectStr agora não duplica mais o string na lista.
// 27/05/09
// - SelectStr agora tenta detectar se você enviou options apenas em
//   Array('um', 'dois', 'tres'). Nesse caso, o "valor" será igual à
//   "descrição", não mais ao índice.
// 07/08/08
// - Corrigido bug com reload da página no inputRead
// 24/06/08
// - Corrigido bug com checkbox e radio quando um ID era forçado
// 09/11/07
// - Adicionado o método 'checkboxImg'
// - Adicionado o método 'radioImg'
// - New-age como modifiers e inputRead podem receber ações 'onclick'
// 19/10/07
// - Options pode ser: Descrição=valor,Desc=val,Etc=etc
// - Checkbox agora assume valor padrão como sendo '1'
// 10/10/07
// - Adicionado o método 'inputRead' e máscaras 'numberMask'
// 03/10/07
// - Adicionado o método 'selectStr'
// - Adicionada opção de passar $options separados por vírgula
// 09/07/07
// - Adicionadas máscaras 'date' e 'datetime' para input()
// 03/07/07
// - Adicionado parâmetro $header para select().

class dInput2{
	static $masksLoaded = Array();
	static $newAgeInfo  = Array();
	static $uniqId      = Array();
	
	// Common form objects
	static Function &input    ($parameters, $value='', $mask=false){
		if($mask){
			self::writeMasksJs('numberMask');
			switch($mask){
				case 'date':
				case 'datetime':
					self::writeMasksJs('date');
					$parameters = preg_replace("/maxlength=['\"][0-9]+['\" ]/", "", $parameters);
					$parameters = preg_replace("/size=['\"][0-9]+['\" ]/",      "", $parameters);
					$parameters .= " dmask='{$mask}' size='".(($mask=='date')?8:15)."' maxlength='".(($mask=='date')?10:19)."'";
					break;
				
				case 'cpf_cnpj':
				case 'cpf':
				case 'cnpj':
				case 'moeda':
					$parameters .= " dmask='{$mask}'";
					break;
				
				case 'fone':
					$parameters = preg_replace("/size=['\"][0-9]+['\" ]/",      "", $parameters);
					$parameters .= " dmask='fone' size='11'";
					break;
				
				case 'cep':
					$parameters = preg_replace("/size=['\"][0-9]+['\" ]/",      "", $parameters);
					$parameters.= " dmask='cep' size='7'";
					break;
				
				default:
					$parameters .= " dmask='numeric' dmask-format='{$mask}'";
					break;
			}
		}
		
		$isNamed = preg_match("/name=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		$name    = $isNamed?$out[1]:false;
		if(is_object($value) && $isNamed){
			$value = $value->getValue($name);
		}
		if(!preg_match("/type=/i", $parameters)){
			$parameters = "type=\"text\" ".$parameters;
		}
		
		if($mask == 'date' || $mask == 'datetime'){
			// Recupera extra 'onclick' para os parametros
			$xtra_onclick   = '';
			$match  = preg_match("/onclick=([\"\'])(.+?)\\1/i",  $parameters, $out);
			if($match){
				$xtra_onclick = $out[2];
				$parameters = preg_replace("/onclick=([\"\'])(.+?)\\1/i", "", $parameters);
			}
		}
		
		$ret     = "<input ".$parameters." value=\"".htmlspecialchars($value)."\" />";
		if($mask == 'date'){
			$jsCall = "displayDatePicker('$name', false, 'dmy', '/');";
			$ret   .= " <a href='#' onclick=\"{$jsCall}; {$xtra_onclick}; return false;\"><img src='images/calendar.gif' border='0' /></a>";
		}
		if($mask == 'datetime'){
			$jsCall = "displayDatePicker('$name', false, 'dmy hi', '/');";
			$ret   .= " <a href='#' onclick=\"{$jsCall}; {$xtra_onclick}; return false;\"><img src='images/calendar.gif' border='0' /></a>";
		}
		
		return $ret;
	}
	static Function &select   ($parameters, $options, $selected=false, $option_parameters="", $header=false, $autoAdd=false){
		if(is_object($selected)){
			$match = preg_match("/name=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
			if($match)
				$selected = $selected->getValue($out[1]);
			else
				$selected = false;
		}
		
		$ret  = "<select";
		if($parameters)
			$ret .= " $parameters";
		$ret .= ">\r\n";
		
		if(is_array($header))
			$ret .= "\t<option {$option_parameters} value='{$header[0]}'>".htmlspecialchars($header[1])."</option>\r\n";
		elseif($header)
			$ret .= "\t<option {$option_parameters} value=''>".htmlspecialchars($header)."</option>\r\n";
		
		if($options === false)
			$options = Array();
		
		// Padronizar $options
		if(!is_array($options)){
			// 1 opcao1,opcao2,opcao3,opcao4
			// 2 valor=descricao,valor2=descricao2,valor3=descricao3 (Opcional: Terminar com :?)
			$useSeparator = ',';
			if(substr($options, -2, 1) == ':'){
				$useSeparator = substr($options, -1);
				$options      = substr($options, 0, -2);
			}
			
			$newOpt = Array();
			$allOpt = explode($useSeparator, $options);
			foreach($allOpt as $item){
				$tmp = explode("=", $item, 2);
				$newOpt[] = (sizeof($tmp) > 1)?
					Array($tmp[0], $tmp[1]):
					Array($item, $item);
			}
			$options = &$newOpt;
		}
		elseif(sizeof($options)){
			// 3 Array(opcao1, opcao2, opcao3, opcao4)
			// 4 Array(valor1=>descricao1, valor2=>descricao2, valor3=>descricao3)
			// 5 Array(Array(valor1, descricao1), Array(valor2, descricao2))
			// 6 Array(Array(0, descricao1), Array(1, descricao2))
			// 7 Array(Array('id'=>'valor1', 'texto'=>'valor2'))
			// 8 Array(Array('valor1'), Array('valor2'))
			$_tmpKeys = array_keys($options);
			$_useIdx  = false;
			for($x = 0; $x < sizeof($_useIdx); $x++){
				if((string)$_tmpKeys[$x] != (string)$x){
					$_useIdx = true;
					break;
				}
			}
			
			foreach($options as $idx=>$value){
				if(is_array($value) && sizeof($value) > 1){
					$k             = array_keys($value);
					$options[$idx] = Array($value[$k[0]], $value[$k[1]]);
				}
				elseif(is_array($value)){
					$k = array_keys($value);
					if($k[0] === 0){
						$options[$idx] = ($_useIdx)?
							Array($idx,          $value[$k[0]]):
							Array($value[$k[0]], $value[$k[0]]);
					}
					else{
						$options[$idx] = Array($k[0], $value[$k[0]]);
					}
				}
				else{
					// $valor1=>$descricao1
					$options[$idx] = ($_useIdx)?
						Array($idx,   $value):
						Array($value, $value);
				}
			}
		}
		
		// Se não exister o $selected, adicionar automaticamente no fim da lista?
		if($autoAdd && $selected){
			$autoFound = false;
			foreach($options as $item){
				if(strtolower($item[0]) == strtolower($selected)){
					$autoFound = true;
					break;
				}
			}
			if(!$autoFound){
				$options[] = Array($selected, $selected);
			}
		}
		
		if(sizeof($options)){
			foreach($options as $item){
				$value = &$item[0];
				$descr = &$item[1];
				
				$ret .= "\t<option";
				$ret .= ($option_parameters)?
					" $option_parameters":
					"";
				
				if(strtolower($selected) == strtolower($value))
					$ret .= " selected='selected'";
				
				$ret .= ' value="'.htmlspecialchars($value).'">'.htmlspecialchars($descr)."</option>\r\n";
			}
		}
		
		$ret .= "</select>";
		return $ret;
	}
	static Function &checkbox ($parameters, $selected=false, $label=false){
		if(is_object($selected)){
			$match  = preg_match("/name=[\"\'](.+?)[\"\' >]/i",  $parameters, $out);
			if($match)
				$selected = $selected->getValue($out[1]);
			else
				$selected = false;
		}
		if($label){
			$match = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
			if($match){
				$id = $out[1];
			}
			else{
				$id = self::getUnique('cb_');
				$parameters .= " id='{$id}'";
			}
		}
		
		$ret  = "<input type='checkbox'";
		if($parameters)
			$ret .= " $parameters";
		if($selected)
			$ret .= " checked='checked'";
		if(stripos($parameters, "value=") === false)
			$ret .= " value='1'";
		$ret .= ">";
		
		if($label){
			$ret .= "<label for='$id'>$label</label>";
		}
		return $ret;
	}
	static Function &textarea ($parameters, $value=''){
		if(is_object($value)){
			$match = preg_match("/name=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
			if($match)
				$value = $value->getValue($out[1]);
		}
		
		$ret  = "<textarea $parameters>".htmlspecialchars($value)."</textarea>";
		return $ret;
	}
	static Function &radio    ($parameters, $selected=false, $label=false){
		if(is_object($selected)){
			$match  = preg_match("/name=[\"\'](.+?)[\"\' >]/i",  $parameters, $out );
			$match2 = preg_match("/value=[\"\'](.+?)[\"\' >]/i", $parameters, $out2);
			if($match && $match2)
				$selected = ($selected->getValue($out[1])==$out2[1]);
			else
				$selected = false;
		}
		if($label){
			$match = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
			if($match){
				$id = $out[1];
			}
			else{
				$id = self::getUnique('ra_');
				$parameters .= " id='{$id}'";
			}
		}
		
		$ret  = "<input type='radio'";
		if($parameters)
			$ret .= " $parameters";
		if($selected)
			$ret .= " checked='checked'";
		$ret .= ">";
		
		if($label){
			$ret .= "<label for='$id'>$label</label>";
		}
		return $ret;
	}
	
	// New-age form objects
	static Function &inputRead  ($parameters, $value='', $js_cb_yes='',   $js_cb_no='',          $js_cb_txt=''){
		// About javascript callback:
		// - Parameters sent: 1=UniqueId, 2=Name, 3=New value 4=Old Value
		// - You can change the value at element "dIROText"+UniqueId
		// - You can change the text  at element "dIROSpan"+UniqueId
		// - Callback MUST return TRUE [as OK] or FALSE [as ERROR].
		
		self::writeMasksJs('inputRead');
		
		// Recupera o nome, e remove o atributo dos parâmetros
		$name   = '';
		$match  = preg_match("/name=[\"\'](.+?)[\"\' >]/i",  $parameters, $out);
		if($match){
			$name = $out[1];
			if(is_object($value))
				$value = $alue->getValue($name);
			$parameters = preg_replace("/name=[\"\'](.+?)[\"\' >]/i", "", $parameters);
		}
		
		// Recupera extra 'onclick' para os parametros
		$xtra_onclick   = '';
		$match  = preg_match("/onclick=([\"\'])(.+?)\\1/i",  $parameters, $out);
		if($match){
			$xtra_onclick = $out[2];
			$parameters = preg_replace("/onclick=([\"\'])(.+?)\\1/i", "", $parameters);
		}
		
		if(!isset($GLOBALS['_dIRO_Count']))
			$GLOBALS['_dIRO_Count'] = 1;
		$uniqueId   = $GLOBALS['_dIRO_Count']++;
		$params     = "{$name}::::".rawurlencode($js_cb_yes)."::::".rawurlencode($js_cb_no)."::::".rawurlencode($js_cb_txt)."::::".rawurlencode($parameters);
		
		$ret = 
			"<span id='dIROSpan{$uniqueId}'>$value</span> <span id='dIROButt{$uniqueId}'> <a href='#' onclick=\"dIRO.Change('$uniqueId', '$params'); {$xtra_onclick}; return false;\">(editar)</a></span>".
			"<input type='text' name='$name' value=\"".htmlspecialchars($value)."\" id='dIROInput{$uniqueId}' style='display: none' />".
			"<script> document.getElementById('dIROInput{$uniqueId}').value = \"".htmlspecialchars($value)."\"; </script>";
		
		return $ret;
	}
	static Function &selectStr  ($parameters, $options,  $selected=false, $option_parameters="", $header=false, $plusStr=" (mais...)"){
		self::writeMasksJs('selectStr');
		
		if(is_object($selected)){
			$match = preg_match("/name=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
			if($match)
				$selected = $selected->getValue($out[1]);
			else
				$selected = false;
		}
		
		// Recupera ID se existente
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = self::getUnique('dI2_SStr');
			$parameters .= " id='{$id}'";
		}
		
		// Recupera extra 'onclick' para os parametros
		$xtra_onclick   = '';
		$match  = preg_match("/onclick=([\"\'])(.+?)\\1/i",  $parameters, $out);
		if($match){
			$xtra_onclick = $out[2];
			$parameters = preg_replace("/onclick=([\"\'])(.+?)\\1/i", "", $parameters);
		}
		
		$ret  = self::select($parameters, $options, $selected, $option_parameters, $header, true);
		$ret .= ($plusStr[0]==' '?' ':'')."<a href='#' onclick=\"_dInput2_appendItem('{$id}'); {$xtra_onclick}; return false;\">{$plusStr}</a>";
		return $ret;
	}
	static Function &selectWrite($parameters, $options=false,  $selected=false, $option_parameters="", $header=false, $settings=Array()){
		if(!@$parameters){
			die(
				"dInput2 Ajuda para uso do selectWrite(\$parameters, \$options,  \$selected=false, \$option_parameters='', \$header=false, \$settings=Array())<br />".
				"1. Copiar todos os arquivos de 'classes/dhtmlx/codebase' para 'js/dhtmlx/'<br />".
				"2. Se houver path relativo, definir: <b>dInput2::\$newAgeInfo['dhtmlx_path'] = '../'</b>.<br />".
				"3. Settings disponíveis: scriptHandler=false, autoComplete=false, readOnly=false, jsCallback=false (onchange), jsOnKey, jsOnBlur<br />".
				"<hr />".
				"Para acessar o objeto DHTMLX, buscar por: dISW_{\$id}<br />".
				"Parâmetros enviados para o jsCallback(obj, id, value, text)<br />".
				"Métodos úteis do objeto DHTMLX:<br />".
				"getSelectedValue(), getSelectedText(), getSelectedIndex()<br />".
				"getActualValue(), getComboText(), setComboText(val)<br />".
				"openSelect(), closeAll(), selectOption(idx), getIndexByValue(val)<br />".
				"<hr />".
				"Exemplo de XML:<br />".
				"<pre>".
				htmlspecialchars(base64_decode(
					"PD94bWwgdmVyc2lvbj0iMS4wIiA/Pg0KPGNvbXBsZXRlPg0KCTxvcHRpb24gdmFsdWU9IjEiPjwhW0NEQVRBW29uZSZuYnNwO29uZSZuYnNwO29uZSZuY".
					"nNwO29uZSZuYnNwO29uZSZuYnNwO29uZSZuYnNwO29uZSZuYnNwO29uZSZuYnNwO29uZSZuYnNwO29uZV1dPjwvb3B0aW9uPg0KCTxvcHRpb24gdmFsdW".
					"U9IjIiIGltZ19zcmM9Ii4uL2NvbW1vbi9pbWFnZXMvYm9va3MuZ2lmIj50d288L29wdGlvbj4NCgk8b3B0aW9uIHZhbHVlPSIzIiBpbWdfc3JjPSIuLi9".
					"jb21tb24vaW1hZ2VzL2Jvb2tzX2NhdC5naWYiPnRocmVlPC9vcHRpb24+DQoJPG9wdGlvbiB2YWx1ZT0iNCIgc2VsZWN0ZWQ9IjEiPmZvdXI8L29wdGlv".
					"bj4NCgk8b3B0aW9uIHZhbHVlPSI1Ij5maXZlPC9vcHRpb24+DQoJPG9wdGlvbiB2YWx1ZT0iNiI+c2l4PC9vcHRpb24+DQoJPG9wdGlvbiB2YWx1ZT0iN".
					"yI+c2V2ZW48L29wdGlvbj4NCjwvY29tcGxldGU+"
				))
			);
		}
		
		if(!isset(self::$newAgeInfo['dhtmlx_path']))
			self::$newAgeInfo['dhtmlx_path'] = '';
		
		self::writeMasksJs('selectWrite');
		if(!isset($settings['scriptHandler'])) $settings['scriptHandler'] = false;
		if(!isset($settings['autoComplete']))  $settings['autoComplete']  = false;
		if(!isset($settings['readOnly']))      $settings['readOnly']      = false;
		if(!isset($settings['jsCallback']))    $settings['jsCallback']    = false;
		if(!isset($settings['jsOnKey']))       $settings['jsOnKey']       = false;
		if(!isset($settings['jsOnBlur']))      $settings['jsOnBlur']      = false;
		
		if($settings['scriptHandler'])
			$settings['autoComplete'] = true;
		
		// Recupera ID se existente
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = self::getUnique('dI2_SStr');
			$parameters .= " id='{$id}'";
		}
		
		$ret  = self::select($parameters, $options, $selected, $option_parameters, $header, true);
		$ret .= "<script type='text/javascript'>\r\n";
		$ret .= "dISW_{$id} = dhtmlXComboFromSelect('{$id}');\r\n";
		$ret .= "dISW_{$id}.enableFilteringMode(".($settings['autoComplete']?"true":"false").", ".($settings['scriptHandler']?"'{$settings['scriptHandler']}'":"false").", false);\r\n";
		$ret .= "dISW_{$id}.enableOptionAutoPositioning(true);\r\n";
		$ret .= "dISW_{$id}.enableOptionAutoWidth(true);\r\n";
		$ret .= "dISW_{$id}.enableOptionAutoHeight(true);\r\n";
		$ret .= "dISW_{$id}.readonly(".($settings['readOnly']?"true":"false").");\r\n";
		if($settings['jsCallback'])
			$ret .= "dISW_{$id}.attachEvent('onChange', function(){ {$settings['jsCallback']}('{$id}', dISW_{$id}, dISW_{$id}.getSelectedValue(), dISW_{$id}.getSelectedText()) });\r\n";
		if($settings['jsOnKey'])
			$ret .= "dISW_{$id}.attachEvent('onKeyPressed', function(){ {$settings['jsOnKey']}('{$id}', dISW_{$id}, dISW_{$id}.getSelectedValue(), dISW_{$id}.getSelectedText()) });\r\n";
		if($settings['jsOnBlur'])
			$ret .= "dISW_{$id}.attachEvent('onBlur', function(){ {$settings['jsOnKey']}('{$id}', dISW_{$id}, dISW_{$id}.getSelectedValue(), dISW_{$id}.getSelectedText()) });\r\n";
		$ret .= "</script>";
		return $ret;
	}

	static Function &checkboxImg($parameters, $selected=false, $label=false, $img_off=false, $img_on=false){
		self::writeMasksJs('checkboxImg');
		
		if(!$img_off) $img_off = 'images/iconhidden.gif';
		if(!$img_on)  $img_on  = 'images/iconvisible.gif';
		
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = self::getUnique('dI2_CBImg');
			$parameters .= " id='{$id}'";
		}
		
		// Recupera o nome, e remove o atributo dos parâmetros
		$name   = '';
		$match  = preg_match("/name=[\"\'](.+?)[\"\' >]/i",  $parameters, $out);
		if($match){
			$name = $out[1];
			$parameters = preg_replace("/name=[\"\'](.+?)[\"\' >]/i", "", $parameters);
		}
		
		// Recupera o valor
		$value   = '';
		$match  = preg_match("/value=[\"\'](.+?)[\"\' >]/i",  $parameters, $out);
		if($match){
			$value = $out[1];
			$parameters = preg_replace("/value=[\"\'](.+?)[\"\' >]/i", "", $parameters);
		}
		
		// Recupera extra 'onclick' para os parametros
		$xtra_onclick   = '';
		$match  = preg_match("/onclick=([\"\'])(.+?)\\1/i",  $parameters, $out);
		if($match){
			$xtra_onclick = $out[2];
			$parameters = preg_replace("/onclick=([\"\'])(.+?)\\1/i", "", $parameters);
		}
		
		$inputtype = "hidden"; // change to 'text' to debug
		
		$ret  = "<script> dI2_CBI.setImgs('{$id}', '{$img_on}', '{$img_off}'); </script>";
		
		$ret .= ($selected)?
			"<input type='$inputtype' name='$name' value='$value' size='2' id='{$id}' />":
			"<input type='$inputtype' name='$name' value='$value' size='2' id='{$id}' disabled='disabled'/>";
		
		$ret .= "<a href='#' onclick=\"dI2_CBI.swap('$id'); {$xtra_onclick}; return false;\" style='cursor: default'><img border='0' src='".($selected?$img_on:$img_off)."' id='{$id}icon' align='middle' /></a>"; 
		if($label)
			$ret .= "<span onclick=\"dI2_CBI.swap('$id'); {$xtra_onclick}; return false;\" style='cursor: default'>$label</span>";
		
		return $ret;
	}
	static Function &radioImg   ($parameters, $selected=false, $label=false, $img_off=false, $img_on=false){
		self::writeMasksJs('checkboxImg');
		
		if(!$img_off) $img_off = 'images/iconhidden.gif';
		if(!$img_on)  $img_on  = 'images/iconvisible.gif';
		
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = self::getUnique('dI2_RDImg');
			$parameters .= " id='{$id}'";
		}
		
		// Recupera o nome, e remove o atributo dos parâmetros
		$name   = '';
		$match  = preg_match("/name=[\"\'](.+?)[\"\' >]/i",  $parameters, $out);
		if($match){
			$name = $out[1];
			$parameters = preg_replace("/name=[\"\'](.+?)[\"\' >]/i", "", $parameters);
		}
		
		// Recupera o valor
		$value   = '';
		$match  = preg_match("/value=[\"\'](.+?)[\"\' >]/i",  $parameters, $out);
		if($match){
			$value = $out[1];
			$parameters = preg_replace("/value=[\"\'](.+?)[\"\' >]/i", "", $parameters);
		}
		
		// Recupera extra 'onclick' para os parametros
		$xtra_onclick   = '';
		$match  = preg_match("/onclick=([\"\'])(.+?)\\1/i",  $parameters, $out);
		if($match){
			$xtra_onclick = $out[2];
			$parameters = preg_replace("/onclick=([\"\'])(.+?)\\1/i", "", $parameters);
		}
		
		$ret  = "";
		if(!isset(self::$newAgeInfo['rbNames'])){
			self::$newAgeInfo['rbNames'] = Array();
		}
		if(!in_array($name, self::$newAgeInfo['rbNames'])){
			self::$newAgeInfo['rbNames'][] = $name;
			$ret .= "<input name='$name' type='hidden' disabled='disabled'>";
		}
		
		$ret .= "<script> dI2_CBI.addRadio('$id', '$name', \"".addslashes($value)."\"); dI2_CBI.setImgs('$id', '$img_on', '$img_off'); </script>";
		$ret .= "<a href='#' style='cursor: default' onclick=\"dI2_CBI.setRadio('$id'); {$xtra_onclick}; return false;\">";
		$ret .= "<img src='$img_off' id='$id' border='0' align='middle' alt='' />";
		$ret .= "</a>";
		
		if($label)
			$ret .= "<span onclick=\"dI2_CBI.setRadio('$id');\" style='cursor: default'>$label</span>"; 
		
		if($selected){
			$ret .= "<script type='text/javascript'><!--\n";
			$ret .= "dI2_CBI.setRadio('$id');\n";
			$ret .= "// -->\n";
			$ret .= "</script>";
		}
		
		return $ret;
	}
	
	/**
		Métodos IFR.
			
			Valores enviados via _GET:
				'uid' (necessária para retornar)
				'ac'  ('search' ou 'new')
				'id'  (apenas no caso de 'search')
			
			Ações esperadas do iframe:
				parent._fwi.setItem(uid, 'valor', 'texto')  # Texto apenas no caso de select ou span
				parent._fwi.hideIframe(uid)
				parent._fwi.refreshSize(uid)
	**/
	static Function &selectIfr  ($file_handler, $parameters, $options, $selected=false, $option_parameters="", $header=false, $plusStr=Array("(novo", " | ", "buscar)")){
		self::writeMasksJs('fieldWithIframe');
		if(is_object($selected)){
			$match = preg_match("/name=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
			if($match)
				$selected = $selected->getValue($out[1]);
			else
				$selected = false;
		}
		
		// Recupera ID se existente
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = uniqid('dI2_Fwi');
			$parameters .= " id='{$id}'";
		}
		
		$ret  = self::select($parameters, $options, $selected, $option_parameters, $header, true);
		$ret .= " <a href='#' onclick=\"_fwi.newItem ('{$id}'); return false\" id='{$id}_bn'>{$plusStr[0]}</a>";
		$ret .= $plusStr[1];
		$ret .= "<a href='#' onclick=\"_fwi.searchItem('{$id}'); return false\" id='{$id}_bs'>{$plusStr[2]}</a><br />";
		$ret .= "<script> _fwi.addItem('{$id}', 'select', '{$file_handler}'); </script>";
		$ret .= "<iframe id='{$id}_ifr' style='display: none; width: 100%' border='0' frameborder='0'></iframe>";
		
		return $ret;
	}
	static Function &inputIfr   ($file_handler, $parameters, $value='',           $plusStr=Array("(novo", " | ", "buscar)")){
		self::writeMasksJs('fieldWithIframe');
		
		// Recupera ID se existente
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = self::getUnique('dI2_Fwi');
			$parameters .= " id='{$id}'";
		}
		
		$ret  = self::input($parameters, $value);
		$ret .= " <a href='#' onclick=\"_fwi.newItem ('{$id}'); return false\" id='{$id}_bn'>{$plusStr[0]}</a>";
		$ret .= $plusStr[1];
		$ret .= "<a href='#' onclick=\"_fwi.searchItem('{$id}'); return false\" id='{$id}_bs'>{$plusStr[2]}</a><br />";
		$ret .= "<script> _fwi.addItem('{$id}', 'input', '{$file_handler}'); </script>";
		$ret .= "<iframe id='{$id}_ifr' style='display: none; width: 100%' border='0' frameborder='0'></iframe>";
		
		return $ret;
	}
	static Function &spanIfr    ($file_handler, $parameters, $value='', $text='', $plusStr=Array("(novo", " | ", "buscar)")){
		self::writeMasksJs('fieldWithIframe');
		
		// Altera o "Type" se existente, pois o campo deve ser hidden
		$parameters  = preg_replace("/type=[\"\'](.+?)[\"\' >]/i", "", $parameters);
		$parameters .= " type='hidden'";
		
		// Recupera ID se existente
		$isId = preg_match("/id=[\"\'](.+?)[\"\' >]/i", $parameters, $out);
		if($isId){
			$id = $out[1];
		}
		else{
			$id = self::getUnique('dI2_Fwi');
			$parameters .= " id='{$id}'";
		}
		
		$ret  = self::input($parameters, $value);
		$ret .= "<span id='{$id}_span'>{$text}</span>";
		$ret .= " <a href='#' onclick=\"_fwi.newItem ('{$id}'); return false\" id='{$id}_bn'>{$plusStr[0]}</a>";
		$ret .= $plusStr[1];
		$ret .= "<a href='#' onclick=\"_fwi.searchItem('{$id}'); return false\" id='{$id}_bs'>{$plusStr[2]}</a><br />";
		$ret .= "<script> _fwi.addItem('{$id}', 'span', '{$file_handler}'); </script>";
		$ret .= "<iframe id='{$id}_ifr' style='display: none; width: 100%' border='0' frameborder='0'></iframe>";
		
		return $ret;
	}
	
	static Function writeMasksJs($method){
		if(isset(self::$masksLoaded[$method]))
			return false;
		
		// Use dInput2::$masksLoaded['dInput2.js'] = true; in template.php to
		// avoid double-checking for it.
		
		self::$masksLoaded[$method] = true;
		if($method == 'dInput2.js'){
			// If it's not yet loaded here, we may try to load it, but it's not recommended.
			// Autoload should be discontinued by 2019-01-01, replaced by a loud ALERT() function.
			?>
			<script type='text/javascript'>
				$(function(){
					if(typeof dInput2 == 'undefined'){
						$.getScript('js/core/jquery-dInput2.js');
					}
					else if(typeof dInput2.numberMask == 'undefined'){
					}
				});
			</script>
			<?php
		}
		
		if($method == 'date'){
			// Créditos: http://www.nsftools.com/tips/DatePickerTest.htm
			?>
<script language="JavaScript" type="text/javascript">
var datePickerDivID   = "datepicker";
var iFrameDivID = "datepickeriframe";

var dayArrayShort   = new Array('D', 'S', 'T', 'Q', 'Q', 'S', 'S');
var dayArrayMed     = new Array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb');
var dayArrayLong    = new Array('Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado');
var monthArrayShort = new Array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
var monthArrayMed   = new Array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Junho', 'Julho', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
var monthArrayLong  = new Array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
 
var defaultDateSeparator = "/";        // common values would be "/" or "."
var defaultDateFormat = "mdy"    // valid values are "mdy", "dmy", and "ymd"
var dateSeparator = defaultDateSeparator;
var dateFormat = defaultDateFormat;

function displayDatePicker(dateFieldName, displayBelowThisObject, dtFormat, dtSep){
  var targetDateField = document.getElementsByName (dateFieldName).item(0);
 
  // if we weren't told what node to display the datepicker beneath, just display it
  // beneath the date field we're updating
  if (!displayBelowThisObject)
    displayBelowThisObject = targetDateField;
 
  // if a date separator character was given, update the dateSeparator variable
  if (dtSep)
    dateSeparator = dtSep;
  else
    dateSeparator = defaultDateSeparator;
 
  // if a date format was given, update the dateFormat variable
  if (dtFormat)
    dateFormat = dtFormat;
  else
    dateFormat = defaultDateFormat;
 
  var x = displayBelowThisObject.offsetLeft;
  var y = displayBelowThisObject.offsetTop + displayBelowThisObject.offsetHeight ;
 
  // deal with elements inside tables and such
  var parent = displayBelowThisObject;
  while (parent.offsetParent) {
    parent = parent.offsetParent;
    x += parent.offsetLeft;
    y += parent.offsetTop ;
  }
 
  drawDatePicker(targetDateField, x, y);
}
function drawDatePicker(targetDateField, x, y){
  var dt = getFieldDate(targetDateField.value );
 
  // the datepicker table will be drawn inside of a <div> with an ID defined by the
  // global datePickerDivID variable. If such a div doesn't yet exist on the HTML
  // document we're working with, add one.
  if (!document.getElementById(datePickerDivID)) {
    // don't use innerHTML to update the body, because it can cause global variables
    // that are currently pointing to objects on the page to have bad references
    //document.body.innerHTML += "<div id='" + datePickerDivID + "' class='dpDiv'></div>";
    var newNode = document.createElement("div");
    newNode.setAttribute("id", datePickerDivID);
    newNode.setAttribute("class", "dpDiv");
    newNode.setAttribute("style", "visibility: hidden;");
    document.body.appendChild(newNode);
  }
 
  // move the datepicker div to the proper x,y coordinate and toggle the visiblity
  var pickerDiv = document.getElementById(datePickerDivID);
  pickerDiv.style.position = "absolute";
  pickerDiv.style.left = x + "px";
  pickerDiv.style.top = y + "px";
  pickerDiv.style.visibility = (pickerDiv.style.visibility == "visible" ? "hidden" : "visible");
  pickerDiv.style.display = (pickerDiv.style.display == "block" ? "none" : "block");
  pickerDiv.style.zIndex = 10000;
 
  // draw the datepicker table
  refreshDatePicker(targetDateField.name, dt.getFullYear(), dt.getMonth(), dt.getDate());
}
function refreshDatePicker(dateFieldName, year, month, day){
  // if no arguments are passed, use today's date; otherwise, month and year
  // are required (if a day is passed, it will be highlighted later)
  var thisDay = new Date();
 
  if ((month >= 0) && (year > 0)) {
    thisDay = new Date(year, month, 1);
  } else {
    day = thisDay.getDate();
    thisDay.setDate(1);
  }
 
  // the calendar will be drawn as a table
  // you can customize the table elements with a global CSS style sheet,
  // or by hardcoding style and formatting elements below
  var crlf = "\r\n";
  var TABLE = "<table cols=7 class='dpTable'>" + crlf;
  var xTABLE = "</table>" + crlf;
  var TR = "<tr class='dpTR'>";
  var TR_title = "<tr class='dpTitleTR'>";
  var TR_days = "<tr class='dpDayTR'>";
  var TR_todaybutton = "<tr class='dpTodayButtonTR'>";
  var xTR = "</tr>" + crlf;
  var TD = "<td class='dpTD' onMouseOut='this.className=\"dpTD\";' onMouseOver=' this.className=\"dpTDHover\";' ";    // leave this tag open, because we'll be adding an onClick event
  var TD_title = "<td colspan=5 class='dpTitleTD'>";
  var TD_buttons = "<td class='dpButtonTD'>";
  var TD_todaybutton = "<td colspan=7 class='dpTodayButtonTD'>";
  var TD_days = "<td class='dpDayTD'>";
  var TD_selected = "<td class='dpDayHighlightTD' onMouseOut='this.className=\"dpDayHighlightTD\";' onMouseOver='this.className=\"dpTDHover\";' ";    // leave this tag open, because we'll be adding an onClick event
  var xTD = "</td>" + crlf;
  var DIV_title = "<div class='dpTitleText'>";
  var DIV_selected = "<div class='dpDayHighlight'>";
  var xDIV = "</div>";
 
  // start generating the code for the calendar table
  var html = TABLE;
 
  // this is the title bar, which displays the month and the buttons to
  // go back to a previous month or forward to the next month
  html += TR_title;
  html += TD_buttons + getButtonCode(dateFieldName, thisDay, -1, "&lt;") + xTD;
  html += TD_title + DIV_title + monthArrayLong[ thisDay.getMonth()] + " " + thisDay.getFullYear() + xDIV + xTD;
  html += TD_buttons + getButtonCode(dateFieldName, thisDay, 1, "&gt;") + xTD;
  html += xTR;
 
  // this is the row that indicates which day of the week we're on
  html += TR_days;
  for(i = 0; i < dayArrayShort.length; i++)
    html += TD_days + dayArrayShort[i] + xTD;
  html += xTR;
 
  // now we'll start populating the table with days of the month
  html += TR;
 
  // first, the leading blanks
  for (i = 0; i < thisDay.getDay(); i++)
    html += TD + "&nbsp;" + xTD;
 
  // now, the days of the month
  do {
    dayNum = thisDay.getDate();
    TD_onclick = " onclick=\"updateDateField('" + dateFieldName + "', '" + getDateString(thisDay) + "');\">";
    
    if (dayNum == day)
      html += TD_selected + TD_onclick + DIV_selected + dayNum + xDIV + xTD;
    else
      html += TD + TD_onclick + dayNum + xTD;
    
    // if this is a Saturday, start a new row
    if (thisDay.getDay() == 6)
      html += xTR + TR;
    
    // increment the day
    thisDay.setDate(thisDay.getDate() + 1);
  } while (thisDay.getDate() > 1)
 
  // fill in any trailing blanks
  if (thisDay.getDay() > 0) {
    for (i = 6; i > thisDay.getDay(); i--)
      html += TD + "&nbsp;" + xTD;
  }
  html += xTR;
 
  // add a button to allow the user to easily return to today, or close the calendar
  var today = new Date();
  var todayString = "Today is " + dayArrayMed[today.getDay()] + ", " + monthArrayMed[ today.getMonth()] + " " + today.getDate();
  html += TR_todaybutton + TD_todaybutton;
  html += "<button class='dpTodayButton' onClick='refreshDatePicker(\"" + dateFieldName + "\");'>mês atual</button> ";
  html += "<button class='dpTodayButton' onClick='updateDateField(\"" + dateFieldName + "\");'>fechar</button>";
  html += xTD + xTR;
 
  // and finally, close the table
  html += xTABLE;
 
  document.getElementById(datePickerDivID).innerHTML = html;
  // add an "iFrame shim" to allow the datepicker to display above selection lists
  adjustiFrame();
}
function getButtonCode(dateFieldName, dateVal, adjust, label){
  var newMonth = (dateVal.getMonth () + adjust) % 12;
  var newYear = dateVal.getFullYear() + parseInt((dateVal.getMonth() + adjust) / 12);
  if (newMonth < 0) {
    newMonth += 12;
    newYear += -1;
  }
 
  return "<button class='dpButton' onClick='refreshDatePicker(\"" + dateFieldName + "\", " + newYear + ", " + newMonth + ");'>" + label + "</button>";
}
function getDateString(dateVal){
  var dayString = "00" + dateVal.getDate();
  var monthString = "00" + (dateVal.getMonth()+1);
  dayString = dayString.substring(dayString.length - 2);
  monthString = monthString.substring(monthString.length - 2);
 
  switch (dateFormat) {
    case "dmy":
      return dayString + dateSeparator + monthString + dateSeparator + dateVal.getFullYear();
    case "ymd":
      return dateVal.getFullYear() + dateSeparator + monthString + dateSeparator + dayString;
    case "dmy hi":
	  return dayString + dateSeparator + monthString + dateSeparator + dateVal.getFullYear() + " 00:00";
    case "dmy his":
	  return dayString + dateSeparator + monthString + dateSeparator + dateVal.getFullYear() + " 00:00:00";
	
	case "mdy":
    default :
      return monthString + dateSeparator + dayString + dateSeparator + dateVal.getFullYear();
  }
}


/**
Convert a string to a JavaScript Date object.
*/
function getFieldDate(dateString){
  var dateVal;
  var dArray;
  var d, m, y;
 
  try {
    dArray = splitDateString(dateString);
    if (dArray) {
      switch (dateFormat) {
        case "dmy" :
        case "dmy hi":
        case "dmy his":
          d = parseInt(dArray[0], 10);
          m = parseInt(dArray[1], 10) - 1;
          y = parseInt(dArray[2], 10);
          break;
        case "ymd" :
          d = parseInt(dArray[2], 10);
          m = parseInt(dArray[1], 10) - 1;
          y = parseInt(dArray[0], 10);
          break;
        case "mdy" :
        default :
          d = parseInt(dArray[1], 10);
          m = parseInt(dArray[0], 10) - 1;
          y = parseInt(dArray[2], 10);
          break;
      }
      dateVal = new Date(y, m, d);
    } else if (dateString) {
      dateVal = new Date(dateString);
    } else {
      dateVal = new Date();
    }
  } catch(e) {
    dateVal = new Date();
  }
 
  return dateVal;
}
function splitDateString(dateString){
  var dArray;
  if (dateString.indexOf("/") >= 0)
    dArray = dateString.split("/");
  else if (dateString.indexOf(".") >= 0)
    dArray = dateString.split(".");
  else if (dateString.indexOf("-") >= 0)
    dArray = dateString.split("-");
  else if (dateString.indexOf("\\") >= 0)
    dArray = dateString.split("\\");
  else
    dArray = false;
 
  return dArray;
}
function updateDateField(dateFieldName, dateString){
  var targetDateField = document.getElementsByName (dateFieldName).item(0);
  if (dateString)
    targetDateField.value = dateString;
 
  var pickerDiv = document.getElementById(datePickerDivID);
  pickerDiv.style.visibility = "hidden";
  pickerDiv.style.display = "none";
 
  adjustiFrame();
  targetDateField.focus();
 
  if ((dateString) && (typeof(datePickerClosed) == "function"))
    datePickerClosed(targetDateField);
}
function adjustiFrame(pickerDiv, iFrameDiv){
  // we know that Opera doesn't like something about this, so if we
  // think we're using Opera, don't even try
  var is_opera = (navigator.userAgent.toLowerCase().indexOf("opera") != -1);
  if (is_opera)
    return;
  
  // put a try/catch block around the whole thing, just in case
  try {
    if (!document.getElementById(iFrameDivID)) {
      // don't use innerHTML to update the body, because it can cause global variables
      // that are currently pointing to objects on the page to have bad references
      //document.body.innerHTML += "<iframe id='" + iFrameDivID + "' src='javascript:false;' scrolling='no' frameborder='0'>";
      var newNode = document.createElement("iFrame");
      newNode.setAttribute("id", iFrameDivID);
      newNode.setAttribute("src", "javascript:false;");
      newNode.setAttribute("scrolling", "no");
      newNode.setAttribute ("frameborder", "0");
      document.body.appendChild(newNode);
    }
    
    if (!pickerDiv)
      pickerDiv = document.getElementById(datePickerDivID);
    if (!iFrameDiv)
      iFrameDiv = document.getElementById(iFrameDivID);
    
    try {
      iFrameDiv.style.position = "absolute";
      iFrameDiv.style.width = pickerDiv.offsetWidth;
      iFrameDiv.style.height = pickerDiv.offsetHeight ;
      iFrameDiv.style.top = pickerDiv.style.top;
      iFrameDiv.style.left = pickerDiv.style.left;
      iFrameDiv.style.zIndex = pickerDiv.style.zIndex - 1;
      iFrameDiv.style.visibility = pickerDiv.style.visibility ;
      iFrameDiv.style.display = pickerDiv.style.display;
    } catch(e) {
    }
 
  } catch (ee) {
  }
 
}
</script>
			<?php
		}
		if($method == 'numberMask'){
			// dInput2.numberMask is now part of jquery-dInput2.js.
			return self::writeMasksJs('dInput2.js');
		}
		if($method == 'selectStr'){ ?>
<script language="JavaScript" type='text/javascript'>
function _dInput2_appendItem(objId){
  var o = document.getElementById(objId);
  var n = document.createElement('option');
  var tmp = prompt("Digite o novo item:", '');
  if(!tmp)
	return;
  
  n.text = n.value = tmp;
  
  for(i = 0; i < o.options.length; i++){
    if(o.options[i].value == n.text){
      o.selectedIndex = i;
      return;
    }
  }
  
  try {
    o.add(n, o.options[o.options.length]);
  }
  catch(ex){
    o.add(n, o.options.length);
  }
  
  o.selectedIndex = o.options.length-1;
  $(o).change();
}
</script>
<?php
		}
		if($method == 'inputRead'){ ?>
<script language="JavaScript" type='text/javascript'>
function dIRO(){ }
dIRO.Init    = function(){
	if(!dIRO.List)     dIRO.List     = new Array();
	if(!dIRO.OldInner) dIRO.OldInner = new Array();
}
dIRO.Change  = function(uniqueId, parameters){
	dIRO.Init();
	
	if(parameters)
		dIRO.List[uniqueId] = parameters;
	
	c = document.getElementById('dIROSpan'+uniqueId);
	b = document.getElementById('dIROButt'+uniqueId);
	p = dIRO.List[uniqueId].split("::::");
	
	// p[0]=name p[1]=callback_yes p[2]=callback_no p[3]=callback_text p[4]=parameters
	st = c.offsetWidth+10;
	if(st == 10)
		st = 80;
	
	dIRO.OldInner[uniqueId] = c.innerHTML;
	// Strip Slashes
	while(dIRO.OldInner[uniqueId].indexOf('"') != -1)
		dIRO.OldInner[uniqueId] = dIRO.OldInner[uniqueId].replace('"', '&quot;');
	
	nV  = "<input type='text' id='dIROText"+uniqueId+"' value=\""+dIRO.OldInner[uniqueId]+"\" style='width: "+st+"px' onkeypress=\"return dIRO.KeyPress('"+uniqueId+"', (event.keyCode?event.keyCode:event.charCode));\" "+unescape(p[4])+" />";
	
	c.innerHTML = nV;
	if(p[3]){
		eval("var cb = "+p[3]);
		b.innerHTML = cb(uniqueId, 'okcancel');
	}
	else{
		b.innerHTML = dIRO.Options(uniqueId, 'okcancel');
	}
	
	document.getElementById('dIROText'+uniqueId).focus();
}
dIRO.Confirm = function(uniqueId, ignoreCallback){
	c = document.getElementById('dIROSpan'+uniqueId);
	b = document.getElementById('dIROButt'+uniqueId);
	f = document.getElementById('dIROText'+uniqueId);
	i = document.getElementById('dIROInput'+uniqueId);
	
	p = dIRO.List[uniqueId].split("::::");
	// p[0]=name p[1]=callback_yes p[2]=callback_no p[3]=callback_text p[4]=parameters
	
	// About callback:
	// - Parameters sent: 1=UniqueId, 2=Name, 3=New value 4=Old Value
	// - You can change new value at element "dIROText"+UniqueId
	// - Callback MUST return TRUE [as OK] or FALSE [as ERROR].
	
	// Do callback
	var ok = true;
	if(p[1] && !ignoreCallback){
		eval("var cb = "+unescape(p[1]));
		ok = cb(uniqueId, p[0], f.value, i.value);
	}
	if(ok){
		if(p[3]){
			eval("var cb = "+unescape(p[3]));
			b.innerHTML = cb(uniqueId, 'edit');
		}
		else{
			b.innerHTML = dIRO.Options(uniqueId, 'edit');
		}
		c.innerHTML = unescape(f.value);
		i.value = f.value;
	}
}
dIRO.Cancel  = function(uniqueId, oldInner, ignoreCallback){
	c = document.getElementById('dIROSpan'+uniqueId);
	b = document.getElementById('dIROButt'+uniqueId);
	f = document.getElementById('dIROText'+uniqueId);
	
	p = dIRO.List[uniqueId].split("::::");
	// p[0]=name p[1]=callback_yes p[2]=callback_no p[3]=callback_text p[4]=parameters
	
	c.innerHTML = unescape(dIRO.OldInner[uniqueId]);
	if(p[3]){
		eval("var cb = "+p[3]);
		b.innerHTML = cb(uniqueId, 'edit');
	}
	else{
		b.innerHTML = dIRO.Options(uniqueId, 'edit');
	}

	// Do callback
	if(p[2] && !ignoreCallback){
		eval("var cb = "+unescape(p[2]));
		cb(uniqueId, f.value);
	}
}
dIRO.Options = function(uniqueId, type){
	b = document.getElementById('dIROButt'+uniqueId);
	if(type == 'edit'){
		return " <a href='#' onclick=\"dIRO.Change('"+uniqueId+"'); return false;\">(editar)</a>";
	}
	if(type == 'okcancel'){
		nB  = " (<a href='#' onclick=\"dIRO.Confirm('"+uniqueId+"'); return false;\">ok</a>";
		nB += " ";
		nB += "<a href='#' onclick=\"dIRO.Cancel ('"+uniqueId+"'); return false;\">não</a>)";
		return nB;
	}
}
dIRO.KeyPress= function(uniqueId, code){
	if(code == 13){ // Enter!
		dIRO.Confirm(uniqueId);
		return false;
	}
	if(code == 27){ // ESC!
		dIRO.Cancel(uniqueId);
		return false;
	}
	return true;
}
</script>
<?php
		}
		if($method == 'checkboxImg'){ ?>
<script language="JavaScript" type='text/javascript'>
function dI2_CBI(){}
dI2_CBI.init       = function(){
	if(!dI2_CBI.config)    dI2_CBI.config    = new Array();
	if(!dI2_CBI.preloaded) dI2_CBI.preloaded = new Array();
	if(!dI2_CBI.radios)    dI2_CBI.radios    = new Array();
}
dI2_CBI.setImgs    = function(id, on, off){
	dI2_CBI.init();
	
	dI2_CBI.config[id] = new Array();
	dI2_CBI.config[id]['off'] = off;
	dI2_CBI.config[id]['on']  = on;
	
	dI2_CBI.preload(off);
	dI2_CBI.preload(on);
}
dI2_CBI.preload    = function(img){
	if(dI2_CBI.preloaded[img])
		return;
	
	new Image().src = img;
	dI2_CBI.preloaded[img] = true;
}
dI2_CBI.swap       = function(id){
	var obj = document.getElementById(id);
	dI2_CBI.setChecked(id, obj.disabled); 
}
dI2_CBI.setChecked = function(id, checked){
	var dInput_input = document.getElementById(id); 
	var dInput_image = document.getElementById(id+'icon'); 
	
	if(checked){
		dInput_input.disabled = false;
		dInput_input.checked  = true;
		dInput_image.src      = dI2_CBI.config[id]['on']; 
	} 
	else{ 
		dInput_input.disabled = true;
		dInput_input.checked  = false; // not really useful
		dInput_image.src      =dI2_CBI.config[id]['off']; 
	} 
}

dI2_CBI.addRadio   = function(id, name, value){
	dI2_CBI.init();
	
	if(!dI2_CBI.radios['names'])
		dI2_CBI.radios['names'] = new Array();
	
	if(!dI2_CBI.radios['names'][name])
		dI2_CBI.radios['names'][name] = new Array();
	
	if(!dI2_CBI.radios['ids'])
		dI2_CBI.radios['ids'] = new Array();

	if(!dI2_CBI.radios['ids'][id])
		dI2_CBI.radios['ids'][id] = new Array();

	dI2_CBI.radios['names'][name][dI2_CBI.radios['names'][name].length] = id;
	dI2_CBI.radios['ids']  [id]   = new Array();
	dI2_CBI.radios['ids']  [id]['name']    = name;
	dI2_CBI.radios['ids']  [id]['value']   = value;
}
dI2_CBI.setRadio   = function(id){
	var name  = dI2_CBI.radios['ids'][id]['name'];
	var value = dI2_CBI.radios['ids'][id]['value'];
	var obj   = document.getElementsByName(name)[0];
	obj.disabled = false;
	obj.value    = value;
	
	var allImgs = dI2_CBI.radios['names'][name];
	for(i=0; i < allImgs.length; i++){
		var imgs  = dI2_CBI.config[allImgs[i]];
		document.getElementById(allImgs[i]).src = (allImgs[i] != id)?
			imgs['off']:
			imgs['on'];
	}
}
</script>
<?php
		}
		if($method == 'fieldWithIframe'){ ?>
<script type='text/javascript' language='Javascript'>
function _fwi(){} // Field With Iframe
_fwi.$    = function(id){
	return document.getElementById(id);
}
_fwi.init = function(){
	if(_fwi.inited) return;
	_fwi.inited    = true;
	_fwi.list      = [];
	_fwi.maxheight = 450;
}
_fwi.addItem     = function(uid, type, handler){
	_fwi.init();
	
	if(type!='input' && type!='select' && type!='span') return alert("Tipo "+type+" não existe para _FWI->addItem.");
	_fwi.list[uid] = { type: type, handler: handler, status: false }
}
_fwi.getItem     = function(uid){
	if(_fwi.list[uid] && !_fwi.list[uid].ifr){
		_fwi.list[uid].ifr = _fwi.$(uid+'_ifr');
		_fwi.list[uid].inp = _fwi.$(uid);
		_fwi.list[uid].ifr.onload = function(){ _fwi.refreshSize(uid) };
	}
	return _fwi.list[uid];
}

_fwi.newItem    = function(uid){
	var obj = _fwi.getItem(uid);
	if(obj.status == 'new'){
		obj.status = false;
		return _fwi.hideIframe(uid);
	}
	obj.ifr.src = obj.handler+(obj.handler.indexOf("?")!=-1?"&":"?")+"uid="+uid+"&ac=new";
	obj.status  = 'new';
	_fwi.showIframe(uid);
}
_fwi.searchItem = function(uid){
	var obj = _fwi.getItem(uid);
	if(obj.status == 'search'){
		obj.status = false;
		return _fwi.hideIframe(uid);
	}
	obj.ifr.src = obj.handler+(obj.handler.indexOf("?")!=-1?"&":"?")+"uid="+uid+"&ac=search&id="+obj.inp.value;
	obj.status  = 'search';
	_fwi.showIframe(uid);
}

_fwi.setItem  = function(uid, value, text){
	var obj = _fwi.getItem(uid);
	if(obj.type == 'span'){
		_fwi.$(uid+'_span').innerHTML = text;
		_fwi.$(uid).value             = value;
	}
	else if(obj.type == 'select'){
		var opt = obj.inp.options;
		for(var i = 0; i < opt.length; i++){
			if(opt[i].value == value){
				obj.inp.selectedIndex = i;
				break;
			}
		}
		
		// Se chegou aqui, não existe o item. Crie-o então!
		var nopt = document.createElement('option');
		nopt.value = value;
		nopt.text  = text;
		try{ obj.inp.add(nopt, null); } catch(e){ obj.inp.add(nopt) }
		obj.inp.selectedIndex = obj.inp.options.length-1;
	}
	else if(obj.type == 'input'){
		_fwi.$(uid).value = value;
	}
	_fwi.hideIframe(uid);
}

_fwi.refreshSize = function(uid){ // Para ser chamada de DENTRO do iframe.
	//find the height of the internal page
	var obj        = _fwi.getItem(uid);
	if(!obj)
		return;
	
	obj.ifr.height = 50;
	var the_height = obj.ifr.contentWindow.document.body.scrollHeight;
	var scrolling  = false;
	
	if(the_height > _fwi.maxheight){
		the_height = _fwi.maxheight;
		scrolling = true;
	}
	if(the_height < 50){
		the_height = 50;
	}
	obj.ifr.height = the_height;
	obj.scrolling  = scrolling;
}
_fwi.showIframe = function(uid){
	for(var oid in _fwi.list){
		if(_fwi.list[oid].status)
			_fwi.hideIframe(oid);
	}
	_fwi.getItem(uid).ifr.style.display = 'block';
	_fwi.refreshSize(uid);
}
_fwi.hideIframe = function(uid){
	_fwi.getItem(uid).ifr.style.display = 'none';
}
</script>
<?php
		}
		if($method == 'selectWrite'){
			echo "<script> window.dhx_globalImgPath='".(self::$newAgeInfo['dhtmlx_path'])."js/dhtmlx/imgs/'; </script>\r\n";
			echo "<link rel='stylesheet' type='text/css' href='".(self::$newAgeInfo['dhtmlx_path'])."js/dhtmlx/dhtmlxcombo.css'>\r\n";
			echo "<script  src='".(self::$newAgeInfo['dhtmlx_path'])."js/dhtmlx/dhtmlxcommon.js'></script>\r\n";
			echo "<script  src='".(self::$newAgeInfo['dhtmlx_path'])."js/dhtmlx/dhtmlxcombo.js'></script>\r\n";
			echo "<script  src='".(self::$newAgeInfo['dhtmlx_path'])."js/dhtmlx/ext/dhtmlxcombo_whp.js'></script>\r\n";
		}
	}
	static Function getUnique($prefix){
		if(!self::$uniqId){
			self::$uniqId = Array(str_replace(".", "", uniqid('', true)), 1);
		}
		return $prefix.self::$uniqId[0].self::$uniqId[1]++;
	}
}
