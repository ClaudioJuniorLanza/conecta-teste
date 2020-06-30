<?php
require_once "config.php";

// Padrão esperado:
//   $obj->embedFile('viewFile.php', 'iFakeClass')
//   viewFile/iFakeClass/id/filename.ext
//   viewFile/iFakeClass/id/flag/filename.ext
// 
// Flags esperadas:
//   viewFile/iFakeClass/id/fd/filename.ext --> FD: Force Download
// 

$parts    = @explode("/", @$_SERVER['PATH_INFO']);
$class    = @$parts[1];
$id       = intval(@$parts[2]);
$filename = @$parts[3];
$flag     = @$parts[4];
if($flag){
	$tmp      = $filename;
	$flag     = strtoupper($tmp);
	$filename = $flag;
}

// Validações:
if(!$class)    die("Invalid request.x1");
if(!$id)       die("Invalid request.x2");
if(!$filename) die("Invalid request.x3");

// Tabela de comparação para fakeClass:
# if($class == 'iFakeClass')  $class = 'iRealClass';
# if($class == 'iFakeClass2') $class = 'iRealClass2';
# if($class == 'iFakeClass3') $class = 'iRealClass3';

if(!class_exists ($class)) die("Invalid request.x4");

$obj = $class::load($id);
if($obj->v('rel') == 'dEmail' && !dUsuario::isLogged()){
	die(
		"Você precisa estar logado no painel administrativo para acessar este link.<br />".
		"<a href='".dSystem::getGlobal('baseUrl')."admin/'>Clique aqui</a> para realizar seu login.</a>"
	);
}

@method_exists($obj, 'downloadFile') or die("Invalid request.x5");
$obj->downloadFile(true)             or die("Invalid request.x7");

// Verificação futura, pois ainda não há bloqueio de acentos nos uploads.
// if($filename != $obj->getValue('filename')) die("Invalid request.x8");

$id   = $obj->getPrimaryValue();
$rfn  = dSystem::getGlobal('baseUrl')."fotos/{$class}-{$id}.dat";
$fn   = "{$_BaseDir}/fotos/{$class}-{$id}.dat";

if($flag == 'FD'){
	// Flag: Force download.
	$obj->downloadFile();
	die;
}

$ext  = strtolower(preg_replace("/.+\./", "", $obj->getValue('filename')));
$tipo = false;
switch($ext){
	case 'gif':
	case 'jpg':
	case 'png':
	case 'bmp':
		$tipo = 'image';
		break;
	
	case 'swf':
		$tipo = 'flash';
		break;
	
	default:
		$tipo = 'link';
}

if($tipo == 'image'){
	header  ("Content-Type: image/{$ext}");
	readfile($fn);
	die;
}
if($tipo == 'flash'){
	header("Location: {$rfn}");
	# -- ou --
	# header("Content-Type: application/x-shockwave-flash");
	# readfile($fn);
	die;
}
if($tipo == 'link'){
	$obj->downloadFile();
	die;
}
