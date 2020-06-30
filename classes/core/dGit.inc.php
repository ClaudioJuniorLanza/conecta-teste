<?php
/**
	dGit v0.2 (04/03/2015)
		Class to abstract git commands, without using command line, but
		using similar sintax and functionality.
	
	Examples:
		$git = new dGit('.git');
		$git->branch();     // local branches
		$git->branch('-a'); // all branches (local + remote)
		$git->branch('-r'); // remote branches
		$git->remote();     // remotes
		$git->tag();        // tags
		
		// More:
		$git->getKnownRefs();
		$git->php_history($branch, $limit);
	
	Source:
		PHP-GIT*: "César D. Rodas <crodas@member.fsf.org>".
		Source:   www.phpclasses.org/package/5310
		
		* Original source WAS MODIFIED so it could handle merges in the history.
*/

define("OBJ_COMMIT", 1);
define("OBJ_TREE", 2);
define("OBJ_BLOB", 3);
define("OBJ_TAG", 4);
define("OBJ_OFS_DELTA", 6);
define("OBJ_REF_DELTA", 7);
define("GIT_INVALID_INDEX", 0x02);
define("PACK_IDX_SIGNATURE", "\377tOc");

class dGit extends GitBase{
	Function __construct($path){
		$this->setRepo($path);
	}
	
	static Function getVersion(){
		return 0.2;
	}
	
	// Compatible with command-line:
	public Function branch($params=Array()){
		// Parâmetros implementados:
		//     (nenhum): Apenas branches locais
		//     -r:       Apenas branches remotos
		//     -a:       Locais + Remotos
		//     --raw:    Retorna [branch]=>sha1
		// 
		// Retorno esperado:
		//     Array('master', 'branch', 'remotes/origin/master', 'remotes/produ/master')
		// 
		
		$showAll     = in_array('-a', $params);
		$showRemote  = ($showAll||in_array('-r', $params));
		$showLocal   = ($showAll||!$showRemote);
		$showRaw     = in_array('--raw', $params);
		
		$rawBranches = Array();
		$rawBranches['heads'] = $this->getRefInfo("heads");
		
		$allRemotes  = $this->remote();
		if($allRemotes) foreach($allRemotes as $remote){
			$rawBranches['remotes/'.$remote] = $this->getRefInfo('remotes/'.$remote);
		}
		
		$branches = Array();
		foreach($rawBranches as $location=>$allBranches){
			if(!$showLocal  && $location=='heads'){
				continue;
			}
			if(!$showRemote && $location!='heads'){
				continue;
			}
			
			$location = ($location=='heads')?"":"{$location}/";
			foreach($allBranches as $branch=>$sha1){
				if($showRaw){
					$branches[$location.$branch] = $sha1;
					continue;
				}
				
				if(substr($sha1, 0, 4) == "ref:"){
					$branches[] = $location.$branch." -> ".substr($sha1, 5+5); // "ref: refs/"
					continue;
				}
				$branches[] = $location.$branch;
			}
		}
		
		return $branches;
	}
	public Function remote($params=Array()){
		// Retorno esperado:
		//     Array('origin', 'johndoe')
		return $this->_getFoldersIn('refs/remotes');
	}
	public Function tag   ($params=Array()){
		// Retorno esperado:
		//     Array('v1', 'v2')
		return $this->_getFilesIn('refs/tags');
	}
	
	// Not compatible with command-line:
	public $refCache  = Array();
	public Function php_history($startSha1, $params=Array()){
		# echo "<pre style='padding: 5px; margin: 5px; background: rgba(0, 180, 180, 0.30); border: 1px solid #660'>";
		# echo "<b>->php_history($startSha1, ".str_replace(Array("\n", "\t"), " ", var_export($params, true)).")</b>\r\n";
		$params += Array(
			'--merges'     =>false,   // ok
			'--no-merges'  =>false,   // ok
			'--min-parents'=>false,   // ok
			'--max-parents'=>false,   // ok
			'--max-count'  =>false,   // ok - Qual o limite máximo para percorrer o histórico?
			'--not'        =>false,   // ok - Não exibe os parents que também estão nos branches informados.
				// Se FALSE,   não aplica nenhum filtro.
				// Se TRUE,    serão retornados apenas os commits que não estão em $curBranch ou que não estão conectados a nenhum branch.
				// Se ARRAY(), serão retornados apenas os commits que não estiverem nas branchs listadas
				// Ps: Se for informado $curBranch (php_history($curBranch)), este nunca será ignorado.
			
			'--branches'   =>false,   // ok - Identifica se o commit em questão está presente em quais branches? True=Todos; Array(); Ps: $curBranch sempre constará.
				// A informação $commit['branches'] só será preenchida com o que estiver listado neste parâmetro.
				// Se FALSE,   não tenta preencher (preenche apenas com $curBranch, se fornecido)
				// Se TRUE,    tenta preencher da forma mais completa possível (com todos os branchs conhecidos)
				// Se ARRAY(), tenta preencher limitado aos branches em questão
			
			'--sort'       =>'time',  // ok - por enquanto, apenas 'time' ou false são aceitos.
			
			':only_preload'=>false,   // ok - ** internal **
		);
		if($params['--merges'])    $params['--min-parents'] = 2;
		if($params['--no-merges']) $params['--max-parents'] = 1;
		
		$params['--branches'] = ($params['--branches']===true)?
			$this->branch(Array("-a")):
			$params['--branches'];
		
		$params['--not'] = ($params['--not']===true)?
			$this->branch(Array("-a")):
			$params['--not'];
		
		// Detecta se o parâmetro informado em $startSha1 é $curBranch, ou se é um sha1 perdido.
		$curBranch = false;
		$known     = $this->getKnownRefs();
		if(array_key_exists($startSha1, $known['branch-to-sha1'])){
			$curBranch = $startSha1;
			$startSha1 = $known['branch-to-sha1'][$startSha1];
		}
		
		// Para utilizar --branches e --not, preciso fazer preload nas branches desejadas.
		$_preload = ($params['--branches'] && $params['--not'])?
			array_unique(array_merge($params['--branches'], $params['--not'])):
			($params['--branches']?$params['--branches']:$params['--not']);
		
		// Vamos carregar $this->refCache com as informações sobre branchs e tags corretas (preload)
		if($_preload) foreach($_preload as $_preloadBranch){
			# echo "* Preloading {$_preloadBranch}...\r\n";
			if($curBranch && $_preloadBranch == $curBranch){
				// Não precisamos fazer preload no branch atual, pois ele será carregado na sequencia.
				continue;
			}
			$this->php_history($_preloadBranch, Array(':only_preload'=>true));
		}
		
		// Vamos subir cada commit para carregar suas informações.
		$count    = 0;
		$history  = Array();
		$loadRefs = Array(Array('sha1'=>$startSha1, 'children'=>false));
		while(($loadItem = array_shift($loadRefs))){
			$loadSha1 = $loadItem['sha1'];
			
			// Carrega o commit em questão e adiciona/monta 'tags', 'parent' e 'children'
			if(isset($this->refCache[$loadSha1])){
				// Já estava em cache, vamos adicionar 'children' e 'branches'.
				if($loadItem['children']){
					if(!in_array($loadItem['children'], $this->refCache[$loadSha1]['children']))
						$this->refCache[$loadSha1]['children'][] = $loadItem['children'];
				}
				if($curBranch){
					if(!in_array($curBranch, $this->refCache[$loadSha1]['branches']))
						$this->refCache[$loadSha1]['branches'][] = $curBranch;
				}
			}
			else{
				// Primeiro encontro com o commit em questão, precisamos carregá-lo.
				$tmpObject = $this->getObject($loadSha1);
				if(!$tmpObject){
					# echo "<i>* Não consegui carregar obeto {$loadSha1}, ignorando.</i>\r\n";
					continue;
				}
				
				$this->refCache[$loadSha1]             = $tmpObject;
				$this->refCache[$loadSha1]['sha1']     = $loadSha1;
				$this->refCache[$loadSha1]['children'] = Array();
				$this->refCache[$loadSha1]['branches'] = Array();
				$this->refCache[$loadSha1]['tags']     = isset($known['sha1-to-tag'][$loadSha1])?$known['sha1-to-tag'][$loadSha1]:Array();
				if($loadItem['children']){
					$this->refCache[$loadSha1]['children'][] = $loadItem['children'];
				}
				if($curBranch && !in_array($curBranch, $this->refCache[$loadSha1]['branches'])){
					$this->refCache[$loadSha1]['branches'][] = $curBranch;
				}
			}
			
			# echo "<b>{$loadSha1}</b> ".substr($this->refCache[$loadSha1]['comment'], 0, 40)."\r\n";
			
			// Continua subindo pelos 'parents' (desde que não estejam em '--not'), até --max-count.
			foreach(array_reverse($this->refCache[$loadSha1]['parent']) as $idx=>$parentSha1){
				// Verifica se está no filtro '--not'
				if($params['--not'] && isset($this->refCache[$parentSha1])){
					$_allow = true;
					foreach($params['--not'] as $notBranch){
						if(in_array($notBranch, $this->refCache[$parentSha1]['branches'])){
							$_allow = false;
						}
					}
					if(!$_allow){
						# echo "* Não vou seguir o parent (--not={$notBranch}).\r\n";
						continue;
					}
				}
				
				// Adiciona o parent em questão na lista 'to-load'
				# echo "* Adicionando à lista (parent nº ".($idx+1)."): {$parentSha1} (".@implode(",", $this->refCache[$parentSha1]['branches']).")<br />";
				array_unshift($loadRefs, Array('sha1'=>$parentSha1, 'children'=>$loadSha1));
			}
			if(!$this->refCache[$loadSha1]['parent']){
				# echo "<i>Não tenho mais parents</i><br />";
			}
			
			if($params['--max-parents'] && sizeof(@$this->refCache[$loadSha1]['parent']) > $params['--max-parents']){
				# echo "<i>Não vou incluir no histórico para respeitar --max-parents</i>\r\n";
				continue;
			}
			if($params['--min-parents'] && sizeof(@$this->refCache[$loadSha1]['parent']) < $params['--min-parents']){
				# echo "<i>Não vou incluir no histórico para respeitar --min-parents</i>\r\n";
				continue;
			}
			
			// Se for :preload, não precisaria preparar para retornar.
			if(!$params[':only_preload']){
				$history[$loadSha1]             = $this->refCache[$loadSha1];
				# $history[$loadSha1]['branches'] = $params['--branches']?
					# array_intersect($params['--branches'], $history[$loadSha1]['branches']):
					# $curBranch?Array($curBranch):Array();
			}
			
			// Verifica se atingimos o --max-count
			$count++;
			if($params['--max-count'] && $count >= $params['--max-count']){
				# echo "<i>Atingido --max-count, interrompendo.</i>\r\n";
				break;
			}
		}
		
		// Se for :only_preload
		if($params[':only_preload']){
			# echo "<i>Retornando por ser :only_preload</i>\r\n";
			# echo "</pre>";
			return true;
		}
		
		// Reordenar os commits:
		if($params['--sort'] == 'time'){
			$allTimes = Array();
			foreach($history as $item){
				if(!isset($item['time'])){
					$allTimes[] = false;
					continue;
				}
				$allTimes[] = $item['time'];
			}
			for($x = 0; $x < sizeof($allTimes); $x++){
				if(!$allTimes[$x]){
					$allTimes[$x] = ($x>0)?$allTimes[$x-1]:(((sizeof($allTimes)-1)>($x+1))?$allTimes[$x+1]:time());
				}
			}
			array_multisort($allTimes, SORT_DESC, $history);
		}
		
		# echo "<i>Retornando \$history</i>\r\n";
		# echo '</pre>';
		
		return $history;
	}
	public Function getKnownRefs(){
		$branch2sha1 = Array();
		$sha12branch = Array();
		$tag2sha1    = Array();
		$sha12tag    = Array();
		
		$knownBranches = $this->branch(Array("-a", "--raw"));
		$knownTags     = $this->tag();
		foreach($knownBranches as $branch=>$sha1){
			if(substr($sha1, 0, 5) == "ref: "){
				$sha1 = $knownBranches[substr($sha1, 10)];
			}
			$branch2sha1[$branch] = $sha1;
			$sha12branch[$sha1][] = $branch;
		}
		foreach($knownTags as $tag){
			$sha1 = $this->getFileContents("refs/tags/{$tag}");
			$tag2sha1[$tag]    = $sha1;
			$sha12tag[$sha1][] = $tag;
		}
		
		return Array(
			'head-to-sha1'  =>$this->getFileContents("HEAD"),
			'branch-to-sha1'=>$branch2sha1,
			'sha1-to-branch'=>$sha12branch,
			'tag-to-sha1'   =>$tag2sha1,
			'sha1-to-tag'   =>$sha12tag
		);
	}
	
	private Function _getAnythingIn($path, $params=Array()){
		$baseDir     = $this->_dir.'/'.$path.'/';
		$allFiles    = glob($baseDir.'*');
		$ret         = Array();
		$onlyFiles   = in_array('--only-files',   $params);
		$onlyFolders = in_array('--only-folders', $params);
		foreach($allFiles as $file){
			if($onlyFolders){
				if(is_dir($file))
					$ret[] = substr($file, strlen($baseDir));
			}
			elseif($onlyFiles){
				if(!is_dir($file))
					$ret[] = substr($file, strlen($baseDir));
			}
			else{
				$ret[] = substr($file, strlen($baseDir));
			}
		}
		return $ret;
	}
	private Function _getFilesIn($path){
		return $this->_getAnythingIn($path, Array('--only-files'));
	}
	private Function _getFoldersIn($path){
		return $this->_getAnythingIn($path, Array('--only-folders'));
	}
}



abstract class GitBase{
    protected $_dir = false;
    protected $branch;
    protected $refs;
    private $_cache_obj;
    private $_index = array();
    private $_fp;

    final public    function getObject($id,&$type=null,$cast=null){
        if (isset($this->_cache_obj[$id])) {
            $type = $this->_cache_obj[$id][0];
            return $this->_cache_obj[$id][1];
        }
		
        $name = substr($id, 0, 2)."/".substr($id, 2);
        if (($content = $this->getFileContents("objects/$name")) !== false) {
            /* the object is in loose format, less work for us */
            $content = gzinflate(substr($content, 2));
            if (($i=strpos($content, chr(0))) !== false) {
                list($type, $content) = explode(chr(0), $content, 2);
            }
			else {
                $type    = $content;
                $content = "";
            }
            list($type, $size) = explode(' ', $type);
            switch ($type) {
            case 'blob':
                $type = OBJ_BLOB;
                break;
            case 'tree':
                $type = OBJ_TREE;
                break;
            case 'commit':
                $type = OBJ_COMMIT;
                break;
            case 'tag':
                $type = OBJ_TAG;
                break;
            default:
                $this->throwException("Unknow object type $type");
            }
            if ($size != 0) {
                $content = substr($content, 0, $size);
            }
        }
		else {
            $obj = $this->_getPackedObject($id);
            if ($obj === false) {
                return false;
            }
            $content = $obj[1];
            $type    = $obj[0]; 
        }
        

        if ($cast != null) {
            $ttype = $cast;
        }
		else {
            $ttype = $type;
        }
		
        switch($ttype) {
        case OBJ_TREE:
            $obj = $this->parseTreeObject($content);
            break;
        case OBJ_COMMIT:
            $obj = $this->parseCommitObject($content);
            break;
        case OBJ_TAG:
            $obj            = $this->simpleParsing($content, 4);
            $obj['comment'] = trim(strstr($content, "\n\n")); 
            if (!isset($obj['object'])) {
                $this->throwException("Internal error, expected object");
            }
            $commit = $this->getObject($obj['object'], $c_type); 
            if ($c_type != OBJ_COMMIT) {
                $this->throwException("Unexpected object type");
            }
            $obj['Tree'] = $this->getObject($commit['tree']);
            break;
        case OBJ_BLOB:
            $obj = & $content;
            break;
        default:
            $this->throwException("Invalid type. Unknown $ttype.");
            return false;
        }
        $this->_cache_obj[$id] = array($type, $obj); 
        return $obj;
    }
    
	// Protected:
    final protected function throwException($str){
        throw new Exception ($str);
    }
    final protected function getFileContents($path, $relative=true, $raw=false){
        if ( $relative ) {
            $path = $this->_dir."/".$path;
        }
        if (!is_file($path)) {
            return false;
        }
        return $raw ? file_get_contents($path) :  trim(file_get_contents($path));
    }
    final protected function setRepo($dir){
        if (!is_dir($dir)) {
            $this->throwException("$dir is not a valid dir");
        }
        $this->_dir   = $dir; 
        $this->branch = null;
        if (($head=$this->getFileContents("HEAD")) === false) {
            $this->_dir = false;
            $this->throwException("Invalid repository, there is not HEAD file");
        }
        if (!$this->_loadBranchesInfo()) {
            $this->_dir = false;
            $this->throwException("Imposible to load information about branches");
        }
        return true;
    }
    final protected function getRefInfo($path="heads"){
        $files = glob($this->_dir."/refs/".$path."/*");
        $ref   = array(); 
        // temporary variable to store name
        $oldref = array();
        foreach ($files as $file) {
            $name = substr($file, strrpos($file, "/")+1);
            $id   = $this->getFileContents($file, false);
            if (isset($oldref[$name])) {
                continue;
            }
            $ref[$name]    = $id;
            $oldref[$name] = true;
        }
        $file = $this->getFileContents("packed-refs");
        if ($file !== false) {
            $this->refs = $this->simpleParsing($file, -1, ' ', false);
            $path       = "refs/$path";
            foreach ($this->refs as $name =>$sha1) {
                if (strpos($name, $path) === 0) {
                    $id = substr($name, strrpos($name, "/")+1);
                    if (isset($oldref[$id])) {
                        continue;
                    }
                    $oldref[$id] = $id;
                    $ref[$id]    = $sha1;
                }
            }
        }
        return $ref;
    }
	final protected function parseCommitObject($object_text){
        $commit              = $this->simpleParsingMultiArray($object_text, 4);
		if(array_key_exists('committer', $commit))
			$commit['committer'] = $commit['committer'][0];
		if(array_key_exists('tree', $commit))
			$commit['tree'] = $commit['tree'][0];
        $commit['comment']   = trim(strstr($object_text, "\n\n")); 
		
		if(isset($commit['author'])){
			$rexp = "/(.*?) <(.+)?\> +([0-9]+) +(\+|\-[0-9]+)/i";
			preg_match($rexp, $commit["author"][0], $data);
			if (count($data) == 5){
				$data[3]         += (($data[4] / 100) * 3600);
				$commit['author'] = Array(
					'name' =>$data[1],
					'email'=>$data[2],
				);
				$commit['time'] = $data[3];
			}
		}
		else{
			
		}
		
		
		if(!isset($commit['parent']))
			$commit['parent'] = Array();
        return $commit;
    }
    final protected function parseTreeObject(&$data){
        $data_len = strlen($data);
        $i        = 0;
        $return   = array();
        while ($i < $data_len) {
            $pos = strpos($data, "\0", $i);
            if ($pos === false) {
                return false;
            }
			
            list($mode, $name) = explode(' ', substr($data, $i, $pos-$i), 2);
			
            $node         = new stdClass;
            $node->id     = $this->sha1ToHex(substr($data, $pos+1, 20));
            $node->name   = $name;
            $node->is_dir = $mode[0] == 4; 
            $node->perm   = intval(substr($mode, -3), 8);
            $i            = $pos + 21;
			
            $return[$node->name] = $node;
        }
        return $return;
    }
    final protected function hexToSha1($sha1){
        if (strlen($sha1) != 40) {
            return false;
        }
        $bin = "";
        for ($i=0; $i < 40; $i+=2) {
            $bin .= chr(hexdec(substr($sha1, $i, 2)));
        }
        return $bin;
    }
    final protected function sha1ToHex($sha1){
        $str = "";
        for ($i=0; $i < 20; $i++) {
            $e   = ord($sha1[$i]); 
            $hex = dechex($e);
            if ($e < 16) {
                $hex = "0".$hex;
            }
            $str .= $hex;
        }
        return $str;
    }
    final protected function getNumber($bytes){
        $c = unpack("N", $bytes);
        return $c[1];
    }
    final protected function patchDeltaHeaderSize(&$delta, $pos){
        $size = $shift = 0;
        do {
            $byte = ord($delta[$pos++]);
            if ($byte == null) {
                $this->throwException("Unexpected delta's end.");
            }
            $size |= ($byte & 0x7f) << $shift;
            $shift += 7;
        } while (($byte & 0x80) != 0);
        return array($size, $pos);
    }
    final protected function patchObject(&$base, &$delta){
        list($src_size, $pos) = $this->patchDeltaHeaderSize($delta, 0);
        if ($src_size != strlen($base)) {
            $this->throwException("Invalid delta data size");
        }
        list($dst_size, $pos) = $this->patchDeltaHeaderSize($delta, $pos);

        $dest       = "";
        $delta_size = strlen($delta);
        while ($pos < $delta_size) {
            $byte = ord($delta[$pos++]);
            if ( ($byte&0x80) != 0 ) {
                $pos--;
                $cp_off = $cp_size = 0;
                /* fetch start position */
                $flags = array(0x01, 0x02, 0x04, 0x08);
                for ($i=0; $i < 4; $i++) {
                    if ( ($byte & $flags[$i]) != 0) {
                        $cp_off |= ord($delta[++$pos]) << ($i * 8);
                    }
                }
                /* fetch length  */
                $flags = array(0x10, 0x20, 0x40);
                for ($i=0; $i < 3; $i++) {
                    if ( ($byte & $flags[$i]) != 0) {
                        $cp_size |= ord($delta[++$pos]) << ($i * 8);
                    }
                }
                /* default length */
                if ($cp_size === 0) {
                    $cp_size = 0x10000;
                }
                $part = substr($base, $cp_off, $cp_size);
                if (strlen($part) != $cp_size) {
                    $this->throwException("Patching error: expecting $cp_size 
                            bytes but only got ".strlen($part));
                }
                $pos++;
            }
			else if ($byte != 0) {
                $part = substr($delta, $pos, $byte);
                if (strlen($part) != $byte) {
                    $this->throwException("Patching error: expecting $byte bytes but only got ".strlen($part));
                } 
                $pos += $byte;
            }
			else {
                $this->throwException("Invalid delta data at position $pos");
            }
            $dest .= $part;
        }
        if (strlen($dest) != $dst_size) {
            $this->throwException("Patching error: Expected size and patched
                    size missmatch");
        }
        return $dest;
    }
    final protected function simpleParsing($text, $limit=-1, $sep=' ', $findex=true, $multiArray=false){
        $return = array();
        $i      = 0;
        foreach (explode("\n", $text) as $line) {
            if ($limit != -1 && $limit < ++$i ) {
                break; 
            }
            $info = explode($sep, $line, 2);
            if (count($info) != 2) {
                continue;
            }
            list($first, $second) = $info; 

            $key          = $findex ? $first : $second;
			$multiArray?
				($return[$key][] = $findex ? $second : $first):
				($return[$key]   = $findex ? $second : $first);
        }
        return $return;
    }
	final protected function simpleParsingMultiArray($text, $limit=-1, $sep=' ', $findex=true){
		return $this->simpleParsing($text, $limit, $sep, $findex, true);
	}
    final protected function getTreeDiff($tree1,$tree2Id=null,$prefix=''){
        $tree1 = $this->getObject($tree1);
        if ($tree2Id == null) {
            $tree2 = array();
        }
		else {
            $tree2 = $this->getObject($tree2Id);
        }

        $new = $changed = $del = array();
        foreach ($tree1 as $key => $desc) {
            $name = $prefix.$key;
            if ( isset($tree2[$key]) ) {
                $file2 = & $tree2[$key];
                if ($tree2[$key]->id != $desc->id) {
                    if ($desc->is_dir) {
                        $diff = $this->getTreeDiff($desc->id, $file2->id, $key.'/');

                        list($c1, $n1, $d1) = $diff;

                        $changed = array_merge($changed, $c1);
                        $new     = array_merge($new, $n1);
                        $del     = array_merge($del, $d1);
                    }
					else {
                        $changed[] = array($name, $tree2[$key]->id, $desc->id);
                    }
                } 
            }
			else {
                if ($desc->is_dir) {
                        $diff = $this->getTreeDiff($desc->id, null, $key.'/');

                        list($c1, $n1, $d1) = $diff;

                        $changed = array_merge($changed, $c1);
                        $new     = array_merge($new, $n1);
                        $del     = array_merge($del, $d1);
                }
				else {
                    $new[] = array($name, $desc->id);
                }
            }
        }
        if ($tree2Id != null) { 
            foreach ($tree2 as $key => $desc) {
                if (!isset($tree1[$key])) {
                    $del[] = array($prefix.$key, $desc->id.'/');
                }
            }
        }
        return array($changed, $new ,$del);
    }
    
	// Private:
    final private   function _loadBranchesInfo(){
        $this->branch = $this->getRefInfo('heads');
        return count($this->branch)!=0;
    }
	final private function _getIndexInfo($path){
        if (isset($this->_index[$path])) {
            return $this->_index[$path];
        }
        $content = $this->getFileContents($path, false, true);
        $version = 1;
        $hoffset = 0;
        if (substr($content, 0, 4) == PACK_IDX_SIGNATURE) {
            $version = $this->getNumber(substr($content, 4, 4));
            if ($version != 2) {
                $this->throwException("The pack-id's version is $version, PHPGit 
                        only supports version 1 or 2,please update this  
                        package, or downgrade your git repo"); 
            }
            $hoffset = 8;
        }
        $indexes = unpack("N*", substr($content, $hoffset, 256*4));
        $nr      = 0;
        for ($i=0; $i < 256; $i++) {
            if (!isset($indexes[$i+1])) {
                continue;
            }
            $n =  $indexes[$i+1];
            if ($n < $nr) {
                $this->throwException("corrupt index file ($n, $nr)\n");
            }
            $nr = $n;
        }   
        $_offset = $hoffset + 256 * 4;
        if ($version == 1) {
            $offset = $_offset;
            for ($i=0; $i < $nr; $i++) {
                $field     = substr($content, $offset, 24);
                $id        = unpack("N", $field);
                $key       = $this->sha1ToHex(substr($field, 4));
                $tmp[$key] = $id[1];
                $offset   += 24;
            }
            $this->_index[$path] = $tmp;
        }
		else if ($version == 2) {
            $offset = $_offset;
            $keys   = $data = array();
            for ($i=0; $i < $nr;  $i++) {
                $keys[]  = substr($content, $offset, 20);
                $offset += 20;
            } 
            for ($i=0; $i < $nr; $i++) {
                $offset += 4;
            }
            for ($i=0; $i < $nr; $i++) {
                $data[]  = $this->getNumber(substr($content, $offset, 4));
                $offset += 4;
            }
            $this->_index[$path] = array_combine($keys, $data);
        }
        return $this->_index[$path];
    }
    final private function _getPackedObject($id){
        /* load packages */
		$_id = $id;
        foreach (glob($this->_dir."/objects/pack/*.idx") as $findex) {
            $index = $this->_getIndexInfo($findex);
            $id    = $this->hextosha1($id);
            if(isset($index[$id])) {
                $start = $index[$id];
                /* open pack file */
                $pack_file = substr($findex, 0, strlen($findex)-3)."pack";
                if (!isset($this->_fp[$pack_file])) {
                    $this->_fp[$pack_file] = fopen($pack_file, "rb");
                }
                $fp = & $this->_fp[$pack_file];
				
                $object =  $this->_unpackObject($fp, $start);
				
				return $object;
            }
        }
		
		# echo "_getPackedObject() - Não consegui encontrar objeto ($_id, $index, $findex).<br />";
		
        return false;
    }
    final private function _unpackObject($fp, $start){
        /* offset till the start of the object */
        fseek($fp, $start, SEEK_SET);
        /* read first byte, and get info */
        $header  = ord(fread($fp, 1));
        $type    = ($header >> 4) & 7;
        $hasnext = ($header & 128) >> 7; 
        $size    = $header & 0xf;
        $offset  = 4;
        /* read size bytes */
        while ($hasnext) {
            $byte = ord(fread($fp, 1)); 
            $size   |= ($byte & 0x7f) << $offset; 
            $hasnext = ($byte & 128) >> 7; 
            $offset +=7;
        }

        switch ($type) {
			case OBJ_COMMIT:
			case OBJ_TREE:
			case OBJ_BLOB:
			case OBJ_TAG:
				$obj = $this->_unpackCompressed($fp, $size);
				return array($type, $obj);
				break;
			case OBJ_OFS_DELTA:
			case OBJ_REF_DELTA:
				$obj = $this->_unpackDelta($fp, $start, $type, $size);
				return array($type, $obj);
				break;
			default:
				$this->throwException("Unkown object type $type");
        }
    }
    final private function _unpackCompressed($fp, $size){
        $out = "";
        do {
            $cstr         = fread($fp, $size>4096 ? $size : 4096);
            $uncompressed = gzuncompress($cstr);
            if ($uncompressed === false) {

                $this->throwException("fatal error uncompressing $packed/$size");
            } 
            $out .= $uncompressed; 
        } while (strlen($out) < $size);

        if ($size != strlen($out)) {
            $this->throwException("Weird error, the packed object has invalid size");
        }
        return $out;
    }
    final private function _unpackDelta($fp, $obj_start, &$type, $size){
        $delta_offset = ftell($fp);
        $sha1         = fread($fp, 20);
        if ($type == OBJ_OFS_DELTA) {
            $i      = 0;
            $c      = ord($sha1[$i]);
            $offset = $c & 0x7f;
            while (($c & 0x80) != 0) {
                $c       = ord($sha1[ ++$i ]);
                $offset += 1;
                $offset <<= 7;
                $offset |= $c & 0x7f;
            }
            $offset = $obj_start - $offset;
            $i++;
            /* unpack object */
            list($type, $base) = $this->_unpackObject($fp, $offset);
        }
		else {
            $base = $this->_getPackedObject($sha1);
            $i    = 20;
        }
        /* get compressed delta */
        fseek($fp, $delta_offset+$i, SEEK_SET);
        $delta = $this->_unpackCompressed($fp, $size); 

        /* patch the base with the delta */
        $obj = $this->patchObject($base, $delta);

        return $obj;
    }
}

