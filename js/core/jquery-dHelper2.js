dHelper2 = {
	forceFloat: function(number, decimals){
		// Convert moeda to float.
		if(typeof decimals == 'undefined')
			decimals = 2;
		
		var decMultiply = Math.pow(10, decimals); // Multiplicador para arrendondar para as casas decimais.
		if(typeof number == 'number'){
			// Respeitar as casas decimais:
			number = number * decMultiply;
			number  = Math.round(number);
			return number / decMultiply;
		}
		if(typeof number != 'string'){
			return false;
		}
		
		number = $.trim(number);
		if(!number.length){
			return false;
		}
		
		number = number.replace(/,/g, ".");
		
		var parts  = number.split(".");
		if(parts.length == 1){
			return parseInt(parts[0]);
		}
		
		var decPart = parts.pop();
		var intPart = parts.join("");
		var final   = intPart + "."  + decPart;
		final = parseFloat(final);
		final = final*decMultiply;
		final = Math.round(final);
		return final/decMultiply;
	},
	moeda: function(n, decimals){
		if(typeof decimals == 'undefined')
			decimals = 2;
		
		var decMultiply = Math.pow(10, decimals); // Multiplicador para arrendondar para as casas decimais.
		
		n = dHelper2.forceFloat(n, decimals);
		if(isNaN(n)){
			return false;
		}
		
		var isNegative = (n < 0);
		if(isNegative){
			n = n * -1;
		}
		
		n = Math.round(n*decMultiply)/decMultiply;
		var parts = (''+n).split(".");
		if(!parts[1] || !parseInt(parts[1])){
			parts[1] = '0'.repeat(decimals);
		}
		else{
			parts[1] = parts[1] + '0'.repeat(decimals - parts[1].length);
		}
		
		// Adiciona "." como separador dos milhares.
		if(parts[0].length > 3){
			var newVal = [];
			var oldVal = parts[0];
			
			while(oldVal.length > 3){
				newVal.push(oldVal.substr(oldVal.length - 3));
				oldVal = oldVal.substr(0, oldVal.length - 3);
			}
			if(oldVal.length){
				newVal.push(oldVal);
			}
			newVal.reverse();
			parts[0] = newVal.join(".");
		}
		
		return (isNegative?"-":"")+(decimals?parts.join(","):parts[0]);
	},
	escapeHtml: function(str){
		if(!str || !str.length){
			return str;
		}
		var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
		return str.replace(/[&<>"']/g, function(m) { return map[m]; });
	},
	removeAccents: function(str){
		var mapFrom = "àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ²³&";
		var mapTo   = "aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY23e";
		for(var i = 0; i < mapFrom.length; i++){
			var _from = mapFrom[i];
			var _to   = mapTo[i];
			while(str.indexOf(_from) != -1)
				str = str.replace(_from, _to);
		}
		return str;
	},
	changeUrl: function(paramName, paramValue){
		var url  = window.location.href;
		var hash = location.hash;
		url      = url.replace(hash, '');
		
		var setParam     = (paramValue.length)?(paramName + "=" + paramValue):"";
		var parts        = url.split("?");
		var _url         = parts[0];
		var _queryString = parts[1]?parts[1]:'';
		var _regexp      = RegExp("(^|\&)" + paramName + "=(.*?)(\&|$)");
		
		// Opção a: QueryString atual é vazia. Let's just append it.
		if(!_queryString.length){
			_queryString = setParam;
		}
		
		// Opção b) QueryString não está vazia, mas não tem paramName. Let's just append it.
		if(!_regexp.test(_queryString)){
			_queryString += setParam?("&"+setParam):"";
		}
		
		// Opção c) QueryString já possuía paramName, então precisamos alterar o valor.
		else{
			// Opções existentes:
			// paramValue=sample
			// paramValue=sample&other=value
			// other=value&paramValue=sample
			// other=value&paramValue=sample&other=value
			if(setParam){
				// Apenas mudou o valor, não tem segredo.
				_queryString = _queryString.replace(_regexp, "$1" + setParam + "$3");
			}
			else{
				var _match     = _regexp.exec(_queryString);
				// 0=Full String, 1=before&, 2=Content, 3=&after
				var _hasBefore = _match[1];
				var _hasAfter  = _match[3];
				
				if(!_hasBefore && !_hasAfter){
					// paramValue=sample
					_queryString = "";
				}
				else if(_hasBefore && _hasAfter){
					// other=value&paramValue=sample&other=value
					_queryString = _queryString.replace(_regexp, "&");
				}
				else{
					_queryString = _queryString.replace(_regexp, "");
				}
			}
		}
		
		window.location.href = _url + (_queryString?"?"+_queryString:"") + hash;
	},
	validarCpf: function(strCPF){
		var Soma;
		var Resto;
		Soma = 0;
		if(strCPF == "00000000000"){
			return false;
		}
		
		for(i = 1; i <= 9; i++){
			Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
		}
		Resto = (Soma * 10) % 11;
		
		if((Resto == 10) || (Resto == 11)){
			Resto = 0;
		}
		if(Resto != parseInt(strCPF.substring(9, 10))){
			return false;
		}
		
		Soma = 0;
		for(i = 1; i <= 10; i++){
			Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i);
		}
		Resto = (Soma * 10) % 11;
		
		if((Resto == 10) || (Resto == 11)){
			Resto = 0;
		}
		if(Resto != parseInt(strCPF.substring(10, 11))){
			return false;
		}
		
		return true;
	}
};

$(function(){
	$.fn.moeda = function(){
		return dHelper2.moeda($(this).val());
	};
	$.fn.serializeObject = function () {
		// Similar a serializeArray, mas retorna como {key: value}.
	    var o = {};
	    var a = this.serializeArray();
	    $.each(a, function () {
	        if (o[this.name] !== undefined) {
	            if (!o[this.name].push) {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(this.value || '');
	        } else {
	            o[this.name] = this.value || '';
	        }
	    });
	    return o;
	};
});
