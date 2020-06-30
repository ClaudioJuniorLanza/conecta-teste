<?php
/**
 * IMAGINACOM - Smart Resource Loader
 * ------------------------------------------
 * Esta classe tem por objetivo centralizar o carregamento de recursos adicionais
 * (css, js e plugins do jQuery) sem causar qualquer tipo de render-blocking, conseguindo
 * assim uma alta performance de acesso e uma alta pontuação no Google Pagespeed.
 *
 * 1. Esta classe libera as funções javascript loadJs, loadJquery e loadCss().
 * 2. Esta classe mescla diversos resources diferentes num arquivo unificado.
 * 3. Esta classe tem o poder de compilar arquivos scss, e minificar arquivos js e css.
 * 4. Esta classe tem o poder de encontrar automaticamente os recursos em locais pré-definidos.
 * 5. Esta classe gera e atualiza automaticamente o cache para os arquivos compilados/minificados.
 * 6. Esta classe pode servir o recurso de forma diferente do primeiro aos demais acessos.
 *
 * IMPORTANTE:
 * -------------------------------------------------
 *      Adicione $.noConflict(); no final do seu jquery.js e jquery.min.js.
 *      Você terá problemas se não o fizer.
 *
 *      Por padrão, os arquivos javascript ficam em $baseDir/js/core/.
 *      Para alterar, modifique ::$jsLoaderRelPath e ::$jsLoaderPath.
 *
 * Métodos de fácil uso:
 *     ::writeJsLoader    ()                          --> Disponibiliza as funções javascript para uso.
 *     ::writeRenderBlock($type, $fileList, $options) --> Gera o HTML com Render-Blocking
 *     ::writeInclude    ($type, $fileList, $options) --> Utiliza os métodos javascript para evitar render-blocking
 *     ::writeInline     ($type, $fileList, $options) --> Inclui o script diretamente no corpo para uma execução direta
 *     ::writeInlineOnceThenInclude    ($type, $fileList, $options)
 *     ::writeInlineOnceThenRenderBlock($type, $fileList, $options)
 *
 * Métodos menos comuns:
 *     ::writeGetFn  ($type, $fileList, $options) --> Compila e retorna o nome do arquivo gerado
 *     ::writeContent($type, $fileList, $options) --> Compila e retorna o conteúdo gerado como string
 *     ::neverWritten($onceId)                    --> Retorna 'true' apenas uma única vez por sessão para cada $onceId.
 *     ::cacheCleanUp($type, $options)            --> Limpa arquivos de cache antigos
 *
 * Configurações estáticas da classe:
 *     $autoPath:          true   --> Valor padrão para options[autoPath]
 *     $production:        'auto' --> Valor padrão para options[production]
 *     $doMinify:          'auto' --> Valor padrão para options[doMinify]
 *     $jsLoaderRelPath:   'auto' --> Localização do jsLoader relativa ao HTML;
 *     $jsLoaderPath:      'auto' --> Localização do jsLoader no filesystem;
 *     $autoWriteJsLoader: true   --> Escreve automaticamente sob demanda o jsLoader, uma única vez.
 *
 * Formatos de entrada para $type: 'js', 'jquery' ou 'css'.
 *
 * Formatos de entrada para $fileList:
 *     arquivo1,arquivo2,arquivo3           --> Busca por arquivos com extensão '.js' e '.min.js'.
 *     arquivo1.js,arquivo2.min.js,arquivo3 --> Quando você informa a extensão, a versão minificada não é buscada.
 *     arquivo1,swiper/swiper.min.js        --> Subpastas são permitidas.
 *     http://fonts.google.com/goforit.com  --> Apenas para ::writeInclude e ::writeRenderBlock
 *
 * Formatos de entrada para $options:
 *     (string)      Qualquer string não-vazia será sinônimo para 'relPath'
 *     relPath:      Path relativo ao html. Padrão: 'auto'. Ex: 'js/'
 *     basePath:     Relativo ao servidor.  Padrão: 'auto'. Ex: '$baseDir/js/'
 *     autoPath:     Se true, tenta localizar o recurso nos lugares mais prováveis.           Padrão: true.
 *     production:   Se false, exporta com nome legível, não minifica nem mescla os recursos. Padrão: self::$production
 *     doMinify:     Ativa o minificador javascript e css.                                    Padrão: $production
 *     replaceWhenInline: Substitui partes do CSS apenas quando ele for escrito inline.       Padrão: 'auto'
 *     cachePrefix:  Prefixo dos arquivos que serão gerados.             Padrão: '_c.'.
 *     cacheMapFile: Arquivo que centraliza informações sobre os caches. Padrão: '_c.mapFile.cache'
 *     dontCache:    Se true, não gera o arquivo de cache após sua geração (apenas para ::writeInline e ::writeContent).   Padrão: false
 *     fatal404:     Se true, throw Exception caso algum dos resources solicitados não for encontrado pelo filesystem.     Padrão: true
 *     cleanUp:      Se true, sempre que um resource for atualizado, os resources antigos serão excluídos automaticamente. Padrão: true
 *
 * Sobre o replaceWhenInline:
 *     No CSS, os recursos (imagens, fonts e @imports) são relativos a localização do css.
 *     Quando você escreve um css inline, esses recursos relativos ficam desorientados.
 *     Quando 'replaceWhenInline' for 'auto', a classe tentará fazer a correspondência automaticamente.
 *     Exemplo: Antes: "background: ../images/bg.jpg" --> Depois: "background: images/bg.jpg"
 *
 *     Se as correspondências não agradarem, envie um Array(replaceFrom, replaceTo) para ser utilizado
 *     como parâmetros em str_replace().
 *
 * Localização dos arquivos de $fileList:
 *     O ideal é sempre informar a localização dos arquivos no parâmetro $options['relPath'].
 *     Dessa forma, o arquivo final (compilado) ficará junto com os recursos originais, facilitando
 *     a sua manutenção e evitando problemas com paths relativos no css.
 *     Exemplo: writeInclude('jquery', 'jquery-3.0.0', Array('relPath'=>'libs/js/'));
 *     Exemplo: writeInclude('jquery', 'jquery-3.0.0', 'libs/js/');
 *
 *     A opção $autoPath permite ao sistema buscar automaticamente os recursos em lugares
 *     prováveis, evitando assim que o desenvolvedor tenha que localizar tudo manualmente.
 *     Lugares prováveis para 'js' e 'jquery': js/  e js/core.
 *     Lugares prováveis para 'css':           css/ e css/core.
 *     Exemplo: writeInclude('jquery', 'jquery-3.0.0'); // Vai buscar em js/ e js/core. O arquivo compilado ficará sempre em js/.
 *     Exemplo: writeInclude('css',    'normalize');    // Vai buscar em js/ e js/core. O arquivo compilado ficará onde o arquivo for encontrado*.
 *     * O javascript pode misturar (alguns arquivos em js/ e outros em js/core/, mas no css isso implica erro fatal)
 *
 *     Se você estiver trabalhando com ambientes separados, você pode utilizar o relPath para apontar
 *     para os recursos corretos.
 *     Exemplo: (/admin) writeInclude('jquery', 'jquery-3.0.0', '../') // Utiliza os recursos de ../js e ../js/core/
 *     Exemplo: (/blog)  writeInclude('css',    'template')            // Utiliza os recursos em /blog/css/ e /blog/css/core/
 *
 *     Você também pode querer localizar recursos diretamente, sem necessariamente colocá-los em pastas 'js/' e 'css/'.
 *     Exemplo: writeInclude('js', 'framework.js', 'myFramework/'); // Buscará o seu arquivo em 'myFramework/framework.js'*;
 *     * Se o arquivo não existir, a busca continuará em myFramework/js e myFramework/js/core.
 *     * Para desativar os caminhos adicionais, desative $autoPath.
 *
 *     Por fim, nem sempre o HTML escrito estará posicionado da mesma forma que o fileSystem. Então, você pode
 *     informar um caminho para o html e outro para o filesystem (o $autoPath será desativado automaticamente).
 *     Exemplo: writeInclude('css', 'normalize', ['relPath'=>'css/',    'basePath'=>'myresources/css/'])
 *     Exemplo: writeInclude('css', 'swiper',    ['relPath'=>'../css/', 'basePath'=>$baseDir.'/css/'])
 *     * O padrão de basePath é getcwd()/$relPath/.
 */

class dResLoader{
	static $production        = 'auto';
	static $doMinify          = 'auto';
	static $jsLoaderRelPath   = 'auto'; // Tries to identify $relPath automatically.               Only for DevMode.
	static $jsLoaderPath      = 'auto'; // Where are the 'js' files. Defaults to baseDir/js/core/. Only for Production.
	static $autoPath          = true;
	static $autoWriteJsLoader = true;
	static $_writtenJsLoader  = false;
	
	static Function neverWritten($onceId){
		$_sanitize = function($onceId){
			return preg_replace("/[^a-z0-9\.\,\-\_]/is", "", $onceId);
		};
		
		$exNW   = Array();
		if(isset($_SESSION['dResLoaderNW']) && strlen($_SESSION['dResLoaderNW']) < 1024){
			$exNW = explode("|", $_SESSION['dResLoaderNW']);
			$exNW = array_map($_sanitize, $exNW);
		}
		$onceId = $_sanitize($onceId);
		
		if(in_array($onceId, $exNW)){
			return false;
		}
		
		$exNW[] = $onceId;
		$_SESSION['dResLoaderNW'] = implode("|", $exNW);
		return true;
	}
	static Function getHash     ($type, $fileList, $options){
		if(!$fileList)
			return '';
		
		self::_handleOptionsAndList($type, $fileList, $options);
		
		$theHash = self::_getHashFromFileList($fileList);
		$theHash = substr($theHash, -15) . ($options['doMinify']?1:0 + $options['production']?2:0);
		
		if(!$options['production']){
			$prepend = implode(",", array_keys($fileList['theList']));
			$prepend = str_replace(Array(".min", ".scss", ".js"), "", $prepend);
			$prepend = substr($prepend, 0, 25);
			$theHash = $prepend.".".substr($theHash, -8);
			$theHash = str_replace('/', '.', $theHash);
		}
		
		return $theHash;
	}
	
	static Function writeJsLoader   ($options=Array(), $autoFlush=false){
		if($autoFlush){
			if(!self::$autoWriteJsLoader || self::$_writtenJsLoader){
				return '';
			}
		}
		self::$_writtenJsLoader = true;
		
		// Em produção (self::$production)
		// --> Vamos sempre incluir os arquivos inline, para um rápido carregamento.
		// --> Os loaders (loadJs e loadCss).min.js serão lidos de $jsLoaderPath/...
		// --> Se $jsLoaderPath for 'auto', utiliza-se o padrão $baseDir/js/core/.
		//
		// Em desenvolvimento (!self::$production):
		// --> Vamos incluir os arquivos usando RenderBlock, pra facilitar a análise do source code.
		// --> Para inserir do HTML, vamos usar $jsLoaderRelPath.
		//
		$isProduction = self::$production;
		if($isProduction === 'auto'){
			$isProduction = !dSystem::getGlobal('localHosted');
		}
		
		$jsPath  = self::$jsLoaderPath;
		if($jsPath == 'auto'){
			$jsPath = dSystem::getGlobal('baseDir') . '/js/core/';
		}
		if($isProduction){
			return
				"<script type='application/javascript'>".
				trim(file_get_contents("{$jsPath}loadJs.min.js"))."\r\n".
				trim(file_get_contents("{$jsPath}loadCss.min.js")).
				"</script>";
		}
		
		// DevMode:
		$relPath = self::$jsLoaderRelPath;
		if($relPath == 'auto'){
			// Procura em getcwd()/js/core;
			// Procura em getcwd()/../js/core;
			// Procura em getcwd()/../../js/core;
			// Se não encontrar, então escreva inline mesmo.
			$relPath = 'js/core/';
			if(!file_exists(getcwd()."/{$relPath}loadJs.js")){
				$relPath = '../js/core/';
			}
			if(!file_exists(getcwd()."/{$relPath}loadJs.js")){
				$relPath = '../../js/core/';
			}
			if(!file_exists(getcwd()."/{$relPath}loadJs.js")){
				// Não consigo encontrar os arquivos para inserir via RenderBlocking no HTML.
				// Vamos escrever inline mesmo.
				return
					"<script type='application/javascript'> " .
					file_get_contents("{$jsPath}js/core/loadJs.js") .
					file_get_contents("{$jsPath}js/core/loadCss.js").
					" </script>";
			}
		}
		
		return
			"<script type='application/javascript' src='{$relPath}loadJs.js'></script>\r\n".
			"<script type='application/javascript' src='{$relPath}loadCss.js'></script>\r\n";
	}
	
	static Function writeInline     ($type, $fileList, $options=Array()){
		if(!$fileList)
			return '';
		
		if($type == 'jquery')
			$type = 'js';
		
		self::_handleOptionsAndList($type, $fileList, $options);
		
		$options['getReturn'] = true;
		
		$content = self::write($type, $fileList, $options);
		if($type == 'css'){
			// Se for css, vamos analisar o conteúdo de 'replaceWhenInline'.
			if($options['replaceWhenInline'] == 'auto'){
				// Casos comuns:
				//   Replace deve ser de '../images/' para 'images/'.
				//   Ou seja, temos que reverter os sub-diretórios...
				//   Ex: relPath=css/        --> Replace ../images/ for images/
				//   Ex: relPath=css/swiper/ --> Replace ../../images/ for images/
				$relPath = $options['relPath'];
				if(self::$autoPath){
					$relPath = $options['_autoRelPath'];
				}
				
				$nDepth  = substr_count($relPath, '/');
				if($nDepth){
					$replaceFrom = str_repeat("../", $nDepth);
					// echo "Replacing {$replaceFrom} to empty.\r\n";
					$content     = str_replace($replaceFrom, "", $content);
				}
			}
			elseif($options['replaceWhenInline']){
				$content = str_replace($options['replaceWhenInline'][0], $options['replaceWhenInline'][1], $content);
			}
		}
		
		if(!$options['production']){
			$ret  = "<!-- dResLoader::writeInline($type) -->\r\n";
			$ret .= ($type=='js')?
				"<script type='application/javascript'>\r\n".$content."\r\n</script>":
				"<style type='text/css'>\r\n".$content."\r\n</style>";
			$ret .= "\r\n";
			$ret .= "<!-- /dResLoader::writeInline -->\r\n";
			return $ret;
		}
		
		return ($type=='js')?
			"<script type='application/javascript'>\r\n".$content."\r\n</script>":
			"<style type='text/css'>\r\n".$content."\r\n</style>";
	}
	static Function writeInclude    ($type, $fileList, $options=Array()){
		if(!$fileList){
			return '';
		}
		
		$isJquery = (strtolower($type) == 'jquery');
		if($isJquery){
			$type = 'js';
		}
		
		// Se fileList for uma string começada com 'http://' ou 'https://', então
		// vamos carregá-la usando o loadCss ou loadJs.
		if(is_string($fileList) && (substr($fileList, 0, 7) == 'http://' || substr($fileList, 0, 8) == 'https://')){
			if($type=='css'){
				return
					self::writeJsLoader(Array(), true).
					"<script> loadCss('{$fileList}'); </script>";
			}
			if($isJquery){
				return
					self::writeJsLoader(Array(), true).
					"<script> loadJquery('{$fileList}'); </script>";
			}
			
			return "<script type='application/javascript' src='{$fileList}' async></script>";
		}
		
		// Caso contrário, vamos incluir o arquivo utilizando o javascript.
		self::_handleOptionsAndList($type, $fileList, $options);
		
		$jsLoader = self::writeJsLoader(Array(), true);
		
		if(!$options['production']){
			// Se for ambiente de desenvolvimento, vamos chamar cada arquivo individualmente.
			$toWrite = Array();
			$toWrite[] = "<!-- dResLoader::writeInclude -->\r\n";
			$toWrite[] = "<script>\r\n";
			foreach($fileList['theList'] as $fn=>$info){
				if($type == 'js'){
					$toWrite[] = "\tload".($isJquery?'Jquery':'Js')."('{$info['rel']}?".time()."');\r\n";
				}
				elseif($type == 'css' && substr($info['rel'], -5) != '.scss'){
					$toWrite[] = "loadCss('{$info['rel']}?".time()."');\r\n";
				}
				elseif($type == 'css'){
					// Arquivos SCSS não podem ser incluídos diretamente, eles sempre precisam ser
					// compilados, mesmo que individualmente.
					
					$_pseudoFileList = $fn;
					$_pseudoOptions  = $options;
					self::_handleFileList($type, $_pseudoFileList, $_pseudoOptions);
					
					$_fn       = self::writeGetFn($type, $_pseudoFileList, $_pseudoOptions);
					$toWrite[] = "loadCss('{$_fn}'); // {$fn}\r\n";
				}
			}
			$toWrite[] = "</script>\r\n";
			$toWrite[] = "<!-- /dResLoader::writeInclude -->\r\n";
			
			return $jsLoader.implode("", $toWrite);
		}
		
		$options['getReturn'] = false;
		$fn = self::write($type, $fileList, $options);
		return $jsLoader."<script> load".($isJquery?'Jquery':ucfirst($type))."('{$fn}'); </script>";
	}
	static Function writeRenderBlock($type, $fileList, $options=Array()){
		if(!$fileList)
			return '';
		
		if($type == 'jquery'){
			$type = 'js';
		}
		
		if(is_string($fileList) && (substr($fileList, 0, 7) == 'http://' || substr($fileList, 0, 8) == 'https://')){
			if($type=='css'){
				return "<link rel='stylesheet' type='text/css' href='{$fileList}' />";
			}
			return "<script type='application/javascript' src='{$fileList}'></script>";
		}
		
		$_attrParams = $options;
		self::_handleOptionsAndList($type, $fileList, $options);
		if(!$options['production']){
			$toWrite = Array();
			$toWrite[] = "<!-- dResLoader::writeRenderBlock -->\r\n";
			
			$_attrParams = $_attrParams?
				(is_string($_attrParams)?$_attrParams:(json_encode($_attrParams, JSON_UNESCAPED_UNICODE))):
				'';
			$_attrParams = htmlspecialchars($_attrParams);
			
			foreach($fileList['theList'] as $fn=>$info){
				if($type == 'js'){
					$toWrite[] = "<script src='{$info['rel']}?".time()."' type='application/javascript'></script>\r\n";
				}
				elseif($type == 'css' && substr($info['rel'], -5) != '.scss'){
					$toWrite[] = "<link rel='stylesheet' href='{$info['rel']}?".time()."' />\r\n";
				}
				elseif($type == 'css'){
					// Arquivos SCSS não podem ser incluídos diretamente, eles sempre precisam ser
					// compilados, mesmo que individualmente.
					
					$_pseudoFileList = $fn;
					$_pseudoOptions  = $options;
					self::_handleFileList($type, $_pseudoFileList, $_pseudoOptions);
					
					$_fn       = self::writeGetFn($type, $_pseudoFileList, $_pseudoOptions);
					$toWrite[] = "<link rel='stylesheet' href='{$_fn}' data-filelist='".implode(",", array_keys($_pseudoFileList['theList']))."' data-params='{$_attrParams}' />\r\n";
				}
			}
			$toWrite[] = "<!-- /dResLoader::writeRenderBlock -->\r\n";
			return implode("", $toWrite);
		}
		
		$options['getReturn'] = false;
		$fn = self::write($type, $fileList, $options);
		return ($type=='js')?
			"<script type='text/javascript' src='{$fn}'></script>":
			"<link rel='stylesheet' type='text/css' href='{$fn}'></script>";
	}
	static Function writeInlineOnceThenInclude    ($type, $fileList, $options=Array()){
		if(!$fileList)
			return '';
		
		$onceId = self::getHash($type, $fileList, $options);
		if(self::neverWritten($onceId)){
			return
				self::writeInline ($type, $fileList, $options)."\r\n".
				self::writeInclude($type, $fileList, $options);
		}
		return self::writeInclude($type, $fileList, $options);
	}
	static Function writeInlineOnceThenRenderblock($type, $fileList, $options=Array()){
		if(!$fileList)
			return '';
		
		$onceId = self::getHash($type, $fileList, $options);
		if(self::neverWritten($onceId)){
			return
				self::writeInline ($type, $fileList, $options)."\r\n".
				self::writeInclude($type, $fileList, $options);
		}
		
		return self::writeRenderBlock($type, $fileList, $options);
	}
	static Function writeGetFn  ($type, $fileList, $options=Array()){
		self::_handleOptionsAndList($type, $fileList, $options);
		if(!$fileList)
			return '';
		
		$options['getReturn'] = false;
		return self::write($type, $fileList, $options);
	}
	static Function writeContent($type, $fileList, $options=Array()){
		self::_handleOptionsAndList($type, $fileList, $options);
		if(!$fileList)
			return '';
		
		if($type == 'jquery')
			$type = 'js';
		
		$options['getReturn'] = true;
		return self::write($type, $fileList, $options);
	}
	static Function write       ($type, $fileList, $options=Array()){
		self::_handleOptionsAndList($type, $fileList, $options);
		if(!$fileList)
			return '';
		
		$type        = strtolower($type);
		$relPath     = $options['relPath'];
		$basePath    = $options['basePath'];
		$cachePrefix = $options['cachePrefix'];
		$dontCache   = $options['dontCache'];
		$getReturn   = $options['getReturn'];
		
		if(self::$autoPath){
			$relPath = $options['_autoRelPath'];
			$basePath = $options['_autoBasePath'];
		}
		
		// Step 1: Check if file is cached.
		// Step 2: Load, compress scss and possibly minify each file.
		// Step 3: Merge all files and save cache (output)
		$hash    = self::getHash($type, $fileList, $options);
		$cfile   = $basePath.$cachePrefix.$hash.'.'.$type;
		$rfile   = $relPath .$cachePrefix.$hash.'.'.$type;
		
		// echo "Buscando cache em {$cfile}... ";
		if(!$dontCache && file_exists($cfile)){
			// echo "Found!\r\n";
			return $getReturn?
				file_get_contents($cfile):
				$rfile;
		}
		// echo "Not found.\r\n";
		
		$content = self::_generateContent($type, $fileList, $options);
		if($dontCache && $getReturn){
			// echo "Dont' cache && getReturn, returning...\r\n";
			return $content;
		}
		
		// echo "Creating cache file with contents and return ".($getReturn?"<b>contents</b>":"<b>file name</b>")."....\r\n";
		self::_cacheCreate($type, $fileList, $options, $content);
		
		return $getReturn?$content:$rfile;
	}
	
	static Function cacheCleanUp($type, $options=Array()){
		// Options para o cacheCleanUp:
		//   [basePath]
		//   [cacheMapFile]
		//   [cachePrefix]
		//   [dryRun]
		//   [verbose]
		$options += Array(
			'relPath'     =>'',                 // Desnecessário para este método, mas necessário para o _handleFileList.
			'basePath'    =>getcwd()."/{$type}/",
			'cachePrefix' =>'_c.',              // Define o prefixo para o output (obrigatório, exceto para writeInline)
			'cacheMapFile'=>'_c.mapFile.cache', // Lista de caches existentes (css/_c.mapFile.cache)
			'dryRun'      =>false,
			'verbose'     =>false,
		);
		
		$basePath     = $options['basePath'];
		$cachePrefix  = $options['cachePrefix'];
		$cacheMapFile = $options['cacheMapFile'];
		$verbose      = $options['verbose'];
		$dryrun       = $options['dryRun'];
		
		if($basePath == 'auto'){
			$basePath = $options['_autoBasePath'];
		}
		if(!$basePath){
			throw new Exception("Can't call cacheCleanUp without setting basePath manually.");
		}
		
		$ifn      = "{$basePath}{$cacheMapFile}";
		$rawIndex = file($ifn, FILE_SKIP_EMPTY_LINES);
		$index    = Array();
		
		foreach($rawIndex as $line){
			$line = rtrim($line, "\r\n");
			if(!$line)
				continue;
			
			$_tmp    = explode("\t", $line);
			$index[] = Array('hash'=>$_tmp[0], 'theList'=>$_tmp[1], 'flags'=>explode("+", $_tmp[2]));
		}
		$changed = false;
		
		if($verbose){
			echo "Performing cacheCleanUp():<br />\r\n";
		}
		$keepingHash = Array();
		foreach($index as $idx=>$item){
			$_hash     = $item['hash'];
			$_fileList = rtrim($item['theList']);
			
			$_options  = $options;
			$_options['doMinify']   = in_array('minified',   $item['flags']);
			$_options['production'] = in_array('production', $item['flags']);
			$_options['fatal404']   = false; // // Desativa o erro fatal para resources não encontrados.
			if($verbose){
				echo "| Hash={$_hash}, FileList={$_fileList}<br />\r\n";
			}
			
			// Primeira verificação:
			// --> Se estiver no índice e não na pasta, remova do índice.
			$_tryFn    = "{$basePath}{$cachePrefix}{$_hash}.{$type}";
			if(!file_exists($_tryFn)){
				if($verbose){
					echo "| --> Está no índice, mas não na pasta. Excluindo.<br />\r\n";
				}
				if(!$dryrun){
					unset($index[$idx]);
				}
				$changed = true;
				continue;
			}
			
			// Segunda verificação:
			// --> Se o hash estiver desatualizado, remova do índice e exclua o arquivo.
			$_flBackup      = $_fileList;
			self::_handleOptionsAndList($type, $_fileList, $_options);
			$hash = self::getHash($type, $_fileList, $_options);
			$_fileList = $_flBackup;
			
			
			if($_hash != $hash){
				if($verbose){
					echo "| --> O Hash foi alterado, o novo hash é {$hash}. Excluindo o arquivo e removendo do índice.<br />\r\n";
				}
				if(!$dryrun){
					unlink($_tryFn);
				}
				unset($index[$idx]);
				$changed = true;
				continue;
			}
			
			if(in_array($_hash.$_fileList, $keepingHash)){
				if($verbose){
					echo "| --> O hash está duplicado, vamos ignorar essa entrada duplicada.\r\n";
				}
				unset($index[$idx]);
				$changed = true;
				continue;
			}
			
			// echo "| --> Nada ocorreu, provavelmente este item permanecerá.\r\n";
			$keepingHash[] = $_hash.$_fileList;
		}
		
		if($changed){
			$output = '';
			foreach($index as $item){
				if(!$item)
					continue;
				
				$output .= "{$item['hash']}\t{$item['theList']}\t".implode("+", $item['flags'])."\r\n";
			}
			
			if(!$dryrun){
				if($verbose){
					echo "* O índice foi atualizado.\r\n<br />";
					// echo $output;
				}
				file_put_contents($ifn, $output);
			}
			elseif($verbose){
				echo "Dry-run foi solicitado, nenhum arquivo foi modificado.<br />\r\n";
			}
		}
		
		// Vamos buscar por arquivos de cache que estão na pasta, mas não estão no índice:
		$knownFiles = Array();
		foreach($index as $item){
			if(!$item)
				continue;
			
			$knownFiles[] = "{$basePath}{$cachePrefix}{$item['hash']}.{$type}";
		}
		$exFiles  = glob("{$basePath}{$cachePrefix}*.{$type}");
		$toDelete = array_diff($exFiles, $knownFiles);
		if($toDelete){
			if($verbose){
				echo "Excluindo arquivos que estao na pasta mas nao no indice: <br />\r\n";
				echo "- ".implode(" <br />\r\n", $toDelete)."\r\n";
			}
			if(!$dryrun){
				array_map('unlink', $toDelete);
			}
		}
	}
	
	static Function _handleOptionsAndList(&$type, &$fileList, &$options){
		self::_handleOptions ($type, $options);
		self::_handleFileList($type, $fileList, $options);
	}
	static Function _handleOptions (&$type, &$options){
		// Este método tem por objetivo padronizar as $options,
		// substituindo seus valores 'auto' pelos valores reais.
		if(is_bool($options)){
			$options = Array();
		}
		if(is_string($options) && strlen($options)){
			$options = Array(
				'relPath'=>$options,
			);
		}
		if(array_key_exists('_handled', $options)){
			return $options;
		}
		
		$options += Array(
			'relPath'     =>'auto',             // Relativo ao HTML. Será utilizado sempre como $relPath.$type
			'basePath'    =>'auto',             // Onde estão os arquivos de origem.             Padrão: getcwd().$relPath.$type.
			'autoPath'    =>self::$autoPath,    // Detecta automaticamente em $relPath/$type ou $relPath/$type/core
			
			'cachePrefix' =>'_c.',              // Define o prefixo para o output (obrigatório, exceto para writeInline)
			'cacheMapFile'=>'_c.mapFile.cache', // Lista de caches existentes (css/_c.mapFile.cache)
			
			'production'  =>self::$production,  // Se false, não trabalha nos javascript, e não minifica o css.
			'doMinify'    =>self::$doMinify,    // Ativa/desativa o compilador SCSS e JsMinifier. Padrão: $production
			'replaceWhenInline'=>'auto',        // Substitui alguma parte do recurso quando for escrito inline.
			
			'getReturn'   =>false,  // Retorna o conteúdo
			'dontCache'   =>false,  // Se true e em conjunto com o getReturn, não armazena o arquivo em cache.
			'cleanUp'     =>true,   // Limpa arquivos antigos automaticamente se um novo cache for gerado.
			'fatal404'    =>true,   // Dispara Fatal Exception se não encontrar algum dos resources solicitados.
			
			'_autoRelPath' =>false, // Uma vez processado o fileList, este é o resultado automático para relPath.
			'_autoBasePath'=>false, // Uma vez processado o fileList, este é o resultado automático para basePath.
			
			'_handled'    =>true,   // Handled!
		);
		
		if($options['production'] === 'auto'){
			$options['production'] = !dSystem::getGlobal('localHosted');
		}
		if($options['doMinify']   === 'auto'){
			$options['doMinify'] = $options['production'];
		}
		
		// Padroniza relPath e basePath com '/' no final.
		if($options['relPath']  != 'auto' && strlen($options['relPath'])){
			$options['relPath'] = rtrim($options['relPath'], '/').'/';
		}
		if($options['basePath'] != 'auto'){
			$options['basePath'] = rtrim($options['basePath'], '/').'/';
		}
		
		// Define relPath e basePath se não houver autoPath
		// Se houver relPath e basePath, desativa o autoPath
		// Não pode haver $autoPath se basePath não for automático (throw Exception)
		// Se houver autoPath, relPath e basePath podem acabar se mantendo como 'auto'
		if(!$options['autoPath']){
			if($options['relPath']  == 'auto'){
				$options['relPath'] = '';
			}
			if($options['basePath'] == 'auto'){
				$options['basePath'] = getcwd() . '/' . $options['relPath'];
			}
		}
		elseif($options['relPath']  != 'auto' && $options['basePath'] != 'auto'){
			$options['autoPath'] = false;
		}
		elseif($options['basePath'] != 'auto'){
			throw new Exception("dResLoader: basePath was set but relPath was not. You MUST inform relPath param.");
		}
	}
	static Function _handleFileList(&$type, &$fileList, &$options){
		// This method updates $fileList to a standardized version of itself.
		//// Inputs allowed are:
		// - Array(file1, file2, file3)
		// - 'file1,file2,file3'
		// - 'file1.ext,file2.ext,file3.ext'
		//
		// Output will be:
		//   [theList]  => Array(fn=>, rel=>, searchMethod=>)
		//   [_handled] => true
		//
		if(is_array($fileList) && array_key_exists('_handled', $fileList)){
			return;
		}
		
		if(!is_array($fileList)){
			$fileList = array_map('trim', explode(",", $fileList));
		}
		$fileList = array_filter($fileList);
		if(!$fileList){
			return false;
		}
		
		$output = Array('theList'=>Array(), '_handled'=>true); // [file] => (fn=>..., rel=>...)
		$type   = strtolower($type);
		
		$allSufix = ($type == 'css')?
			Array('', '.min.css', '.css', '.scss'):
			Array('', '.min.js',  '.js');
		
		if(!$options['production']){
			$allSufix = ($type == 'css')?
				Array('', '.css', '.min.css', '.scss'):
				Array('', '.js',  '.min.js');
		}
		
		$_getFilesWithSufix = function($fn)        use (&$type, &$allSufix){
			$_searchSufixes = true;
			if    ($type == 'js'  && strtolower(substr($fn, -3)) == '.js'){
				$_searchSufixes = false;
			}
			elseif($type == 'css' && preg_match("/\.(css|scss)$/i", $fn)){
				$_searchSufixes = false;
			}
			
			$ret = Array();
			if(!$_searchSufixes){
				$ret[] = $fn;
				return $ret;
			}
			foreach($allSufix as $ext){
				$ret[] = $fn.$ext;
			}
			return $ret;
		};
		$_findResource      = function($fn, $path) use (&$type, &$_getFilesWithSufix){
			if(!file_exists($path) || !is_dir($path)){
				// Path or directory not found.
				return false;
			}
			
			foreach($_getFilesWithSufix($fn) as $tryFn){
				if(file_exists($path.$tryFn) && !is_dir($path.$tryFn))
					return $tryFn;
			}
			return false;
		};
		
		$_setRelPath  = Array();
		$_setBasePath = Array();
		
		// Lugares para procurar o recurso:
		// 0º: Se basePath existir manualmente, utilize APENAS ele.
		// 1º: Se relPath!='auto', utilize ele.
		// 2º: Utilize $type/
		// 3º: Utilize $type/core/
		foreach($fileList as $file){
			// Busca: Opção '0' (!autoPath ou basePath)
			if($options['basePath'] != 'auto'){
				$_found = $_findResource($file, $options['basePath']);
				if($_found){
					$_relPath  = ($options['relPath'] == 'auto')?"":$options['relPath'];
					
					$output['theList'][$file] = Array(
						'rel'=>$_relPath.$_found,
						'fn' =>$options['basePath'].$_found,
						'method'=>'0',
					);
					$_setRelPath[]  = $_relPath;
					$_setBasePath[] = $options['relPath'];
					continue;
				}
				else{
					if(!$options['fatal404']){
						$output['theList'][$file] = Array(
							'rel'      =>'',
							'fn'       =>'',
							'_notFound'=>true,
						);
						continue;
					}
						
					throw new Exception("Resource {$file} not found at basePath={$options['basePath']} (method=0)");
				}
			}
			
			// Busca: Opção '1' ($relPath/)
			if($options['relPath'] != 'auto'){
				$_relPath = ($options['relPath'] == 'auto')?"":$options['relPath'];
				$tryPath  = getcwd().'/'.$_relPath;
				$_found   = $_findResource($file, $tryPath);
				if($_found){
					$output['theList'][$file] = Array(
						'rel'=>$_relPath.$_found,
						'fn' =>$tryPath .$_found,
						'method'=>'1',
					);
					$_setRelPath[]  = $_relPath;
					$_setBasePath[] = $tryPath;
					continue;
				}
			}
			
			if(!$options['autoPath']){
				if(!$options['fatal404']){
					$output['theList'][$file] = Array(
						'rel'      =>'',
						'fn'       =>'',
						'_notFound'=>true,
					);
					continue;
				}
				throw new Exception("Resource {$file} not found at basePath={$options['basePath']}, and options[autoPath] is disabled.");
			}
			
			// Busca: Opção '2' ($type/)
			$_relPath  = ($options['relPath'] == 'auto')?"{$type}/":"{$options['relPath']}{$type}/";
			$tryPath = getcwd().'/'.$_relPath;
			$_found  = $_findResource($file, $tryPath);
			if($_found){
				$output['theList'][$file] = Array(
					'rel'=>$_relPath.$_found,
					'fn' =>$tryPath.$_found,
					'method'=>'2',
				);
				$_setRelPath[]  = $_relPath;
				$_setBasePath[] = $tryPath;
				continue;
			}
			
			// Busca: Opção '3' ($type/core)
			$_found  = $_findResource($file, $tryPath.'core/');
			if($_found){
				if($type == 'js'){
					// Se for javascript, pode unificar tudo em js/, não precisa
					// separar para o js/core, pois não faz diferença relative paths.
					$output['theList']['core/'.$file] = Array(
						'rel'=>$_relPath.'core/'.$_found,
						'fn' =>$tryPath .'core/'.$_found,
						'method'=>'3.1',
					);
					$_setRelPath[]  = $_relPath;
					$_setBasePath[] = $tryPath;
				}
				else{
					// Se for CSS *faz diferença* se está em css/ ou css/core/.
					$output['theList'][$file] = Array(
						'rel'=>$_relPath.'core/'.$_found,
						'fn' =>$tryPath .'core/'.$_found,
						'method'=>'3.2',
					);
					$_setRelPath[]  = $_relPath.'core/';
					$_setBasePath[] = $tryPath .'core/';
				}
				continue;
			}
			
			if(!$options['fatal404']){
				$output['theList'][$file] = Array(
					'rel'      =>'',
					'fn'       =>'',
					'_notFound'=>true,
				);
				continue;
			}
			throw new Exception("Resource file '{$file}' not found at '{$_relPath}' and similar.");
		}
		
		$fileList     = $output;
		$_setRelPath  = array_unique($_setRelPath);
		$_setBasePath = array_unique($_setBasePath);
		
		if($type == 'css' && (sizeof($_setRelPath)>1 || sizeof($_setBasePath)>1)){
			throw new Exception("Some css resources are in different folders and can't be merged.");
		}
		
		if($_setRelPath){
			$options['_autoRelPath']  = $_setRelPath[0];
		}
		if($_setBasePath){
			$options['_autoBasePath'] = $_setBasePath[0];
		}
	}
	
	static Function _generateContent(&$type, &$fileList, &$options){
		$allContents  = Array();
		$isProduction = self::$production;
		if($isProduction === 'auto'){
			$isProduction = !dSystem::getGlobal('localHosted');
		}
		foreach($fileList['theList'] as $fn=>$info){
			$isScss     = ($type=='css' && substr($info['fn'], -5) == '.scss');
			$isMinified = ($type=='css')?
				(substr($info['fn'], -8)=='.min.css'):
				(substr($info['fn'], -7)=='.min.js');
			
			// Se for Minified, apenas leia.
			// Se for SCSS, compile.
			// Se for Javascript, minify it.
			$content = file_get_contents($info['fn']);
			if($isMinified){
				// Do nothing, write it as-is.
			}
			elseif($isScss){
				// Needs to be compiled everytime.
				// Compiler will handle 'doMinify' option.
				
				// Em produção, sourceMap deve ser ignorado.
				// ::writeInline não funciona bem com o sourceMap e deve ser ignorado.
				$settings = (!$isProduction && !$options['getReturn'])?
					Array('sourceFile' => $info['rel']):
					array();
				
				$content = self::compileCss($content, $options, $settings);
			}
			elseif(!$isMinified && $options['doMinify']){
				// Let's minify it.
				$content = ($type == 'css')?
					self::compileCss($content, $options):
					self::compileJs ($content, $options);
			}
			
			$allContents[] = rtrim($content, " \t\r\n;");
		}
		return implode(($type=='js')?";\r\n":"\r\n", $allContents);
	}
	static Function compileCss($content, &$options, $settings=array()){
		$directory = __DIR__;
		require_once "{$directory}/scssphp/scss.inc.php";
		
		$settings += Array(
			'sourceFile' => '',
		);
		
		try{
			$scss = new Leafo\ScssPhp\compiler();
			$basePath = $options['basePath'];
			if($basePath == 'auto'){
				$basePath = $options['_autoBasePath'];
			}
			
			$scss->setImportPaths($basePath);
			
			// Descomente a linha abaixo para impedir @import
			// $scss->setImportPaths(false);
			
			// Descomente as linha abaixo para pré-definir variáveis do SCSS:
			// $scss->setVariables(array(
			//  	'mainColor' => 'red',
			// ));
			
			$options['doMinify']?
				$scss->setFormatter('Leafo\\ScssPhp\\Formatter\\Crunched'):
				$scss->setFormatter('Leafo\\ScssPhp\\Formatter\\Expanded');
			
			// 2018-06-13: SourceMaps para ambiente de desenvolvimento.
			if($settings['sourceFile'] && !$options['getReturn']){
				$scss->setSourceMap(Leafo\ScssPhp\Compiler::SOURCE_MAP_INLINE);
				$scss->setSourceMapOptions(array(
					'sourceMapBasepath' => dirname($settings['sourceFile']).'/',
				));
				$content = $scss->compile($content, $settings['sourceFile']);
			}
			else{
				$content = $scss->compile($content);
			}
		}
		catch(Exception $e){
			echo "<pre style='display: inline-block; margin: auto; border: 1px solid red'>";
			echo "Scss compiler failed: {$e->getMessage()}\r\n";
			if(preg_match("/on line ([0-9]+)/", $e->getMessage(), $out)){
				$errorLine = $out[1];
				$range     = 6;
				$showRange = Array($errorLine-$range, $errorLine+$range);
				
				$allLines  = explode("\n", $content);
				if($showRange[0] < 1){
					$showRange[0] = 1;
				}
				if($showRange[1] > sizeof($allLines)){
					$showRange[1] = sizeof($allLines);
				}
				
				echo str_repeat("=", 80)."\r\n";
				for($x = $showRange[0]; $x <= $showRange[1]; $x++){
					echo ($x==$errorLine)?"<b style='background: yellow; color: red'>":"";
					echo str_pad($x, 3, ' ', STR_PAD_RIGHT)." ";
					echo ($x==$errorLine)?">> ":"   ";
					echo str_pad(rtrim($allLines[$x-1], "\r\n"), 80-7, ' ', STR_PAD_RIGHT)."\n";
					echo ($x==$errorLine)?"</b>":"";
				}
				echo str_repeat("=", 80)."\r\n";
				echo "</pre>";
			}
			die;
		}
		
		return $content;
	}
	static Function compileJs ($content, &$options){
		if(!$options['doMinify']){
			return $content;
		}
		
		$directory = __DIR__;
		require_once "{$directory}/minifyjs/minifyjs.inc.php";
		
		try{
			$minifier = new MatthiasMullie\Minify\JS($content);
			$content  = $minifier->minify();
		}
		catch(Exception $e){
			echo "Minifier compiler failed: {$e->getMessage()}\r\n";
			if(preg_match("/on line ([0-9]+)/", $e->getMessage(), $out)){
				$errorLine = $out[1];
				$range     = 4;
				$showRange = Array($errorLine-$range, $errorLine+$range);
				
				$allLines  = explode("\n", $content);
				if($showRange[0] < 1){
					$showRange[0] = 1;
				}
				if($showRange[1] > sizeof($allLines)){
					$showRange[1] = sizeof($allLines);
				}
				
				echo str_repeat("=", 80)."\r\n";
				for($x = $showRange[0]; $x <= $showRange[1]; $x++){
					echo str_pad($x, 3, ' ', STR_PAD_RIGHT)." ";
					echo ($x==$errorLine)?">> ":"   ";
					echo rtrim($allLines[$x-1], "\r\n")."\n";
				}
				echo str_repeat("=", 80)."\r\n";
			}
			die;
		}
		
		return $content;
	}
	
	// Cache:
	//   Padrão de filenames: css/_c.$hash.css
	//   Padrão de mapFile:   css/_c.mapFile.cache
	//   Formato de mapFile:  $hash \t $theList separada por vírgulas \t flag1+flag2 \r\n
	static Function _cacheGetFn (&$type, &$fileList, &$options){
		$basePath     = $options['basePath'];
		$cachePrefix  = $options['cachePrefix'];
		if($basePath == 'auto'){
			$basePath = $options['_autoBasePath'];
		}
		
		$hash = self::getHash($type, $fileList, $options);
		return "{$basePath}{$cachePrefix}{$hash}.{$type}";
	}
	static Function _cacheCreate(&$type, &$fileList, &$options, &$content){
		$basePath     = $options['basePath'];
		$cacheMapFile = $options['cacheMapFile'];
		$cachePrefix  = $options['cachePrefix'];
		if($basePath == 'auto'){
			$basePath = $options['_autoBasePath'];
		}
		
		$ifn  = "{$basePath}{$cacheMapFile}";
		$hash = self::getHash($type, $fileList, $options);
		$cfn  = "{$basePath}{$cachePrefix}{$hash}.{$type}";
		
		$flags= Array();
		if($options['doMinify'])
			$flags[] = 'minified';
		if($options['production'])
			$flags[] = 'production';
		
		$theList = implode(",", array_keys($fileList['theList']));
		
		// echo "Creating cache for '{$theList}', hash={$hash}. (cacheFn={$cfn})<br />\r\n";
		file_put_contents($cfn, $content);
		
		$fh  = fopen($ifn, "a");
		fwrite($fh, "{$hash}\t{$theList}\t".implode("+", $flags)."\r\n");
		fclose($fh);
		
		if($options['cleanUp'])
			self::cacheCleanUp($type, $options);
	}
	static Function _getHashFromFileList($fileList, $check_content=false){
		// Recebe um parâmetro já tratado por _handleFileList, e gera um hash para
		// aquele conjunto de arquivos (ou arquivo único). Útil para saber se
		// um arquivo foi modificado na lista de arquivos desejada.
		
		$etag = '';
		foreach($fileList['theList'] as $fileName=>$info){
			$fn         = $info['fn'];
			$fileExists = !array_key_exists('_notFound', $info);
			
			if($check_content){
				$etag .= $fileExists?
					md5_file($fn):
					md5("{$fileName}00");
			}
			else{
				if(!$fileExists){
					$size  = 0;
					$mtime = 0;
				}
				else{
					$size  = filesize($fn);
					$mtime = filemtime($fn);
				}
				
				$etag .= md5($fileName.$mtime.$size);
			}
		}
		
		return md5($etag);
	}
}
