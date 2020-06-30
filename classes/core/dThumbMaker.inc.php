<?php
// Version 2.911 (04/11/2013)
// - Bugfix: Notice trying to unload class without loading any file on it.
// Version 2.9 (19/12/2012)
// - Added methods 'removeBackup' and 'unload', to give user more control over memory usage
// - Aded  method 'resizeTouchInside'
// - Added method 'resizeTouchOutside'
//   resizeTouchInside ($W, $H, $fillWithBackground=false, $align=Array('center', 'middle'))
//   resizeTouchOutside($W, $H)
// - Method 'resizeTouch' will be deprecated in dThumbMaker2
// - Some cleanup
// Version 2.8 (21/07/2012)
// - Added method 'resizeTouch', that resizes to match exactly width OR height, what comes first.
//   resizeTouch($W, $H, $fillWithBackground=false, $align=Array('center', 'middle'))
// Version: 2.7 (07/11/2011)
// - Added method 'resizeToFit', that resizes to exact size using crop features.
// Version: 2.6 (06/04/2011)
// - Added method 'resizeMinSize' to ensure that an image will be larger than you need.
// - Added parameter $forceMinSize to cropCenter method, to avoid black borders being created.
// Version: 2.5.2 (15/03/2010)
// * Replaced use of deprecated EREG* to PREG*.
// Version: 2.5.1 (05/11/2009)
// - Fixed warning messages when importing mal-formed bitmap image
// Version: 2.5  (28/11/2007)
// - Added property 'bgColor', to handle with invisible gifs
// Version: 2.42 (29/09/2007)
// - Fixed method Build(null) when saving to other format than jpg
// Version: 2.41 (23/12/2006)
// - Added method 'getFileType'
// Version: 2.4 (14/12/2006)
// - Added methods 'flip', 'flipV', 'flipH' and 'rotate90'
// Version: 2.31 (02/09/2006)
// - Added methods 'getWidth', 'getHeight' and 'getIm'
// Version: 2.3  (01/07/2006)
// - Added methods 'addBorder', 'crop' and 'cropCenter'
// Version: 2.21 (06/09/2005)
// - Fixed argument 'opacity' in watermark: If argument is given, the GIF or PNG transparency won't work.
// Version: 2.2 (28/08/2005)
// - Added support to BMP and WBMP images
// - Added argument 'opacity' to addWaterMark
// - Added method makeCaricature
// - If build(NULL) the raw image stream will be output directly.

/***************************************************************
	dThumbMaker 2.95
	
	Easily resample an incoming file
	Release date: 06/09/2005
	Author: Alexandre Tedeschi (d)
	Email:  alexandrebr AT gmail DOT com
	
Methods:
° getVersion
° loadFile - Returns TRUE on success, STRING otherwise
° getWidth, getHeight, getIm
° resizeToFit, resizeMinSize, resizeMaxSize, resizeExactSize
º resizeTouchInside, resizeTouchOutside
° addWaterMark, addBorder, crop, cropCenter, makeCaricature
° flipV, flipH, rotate90
° createBackup, restoreBackup
° build - Returns TRUE on success, FALSE otherwise
***************************************************************/

if(!defined('IMAGETYPE_BMP'))
	define('IMAGETYPE_BMP', 6);

if(!defined('IMAGETYPE_WEBP'))
	define('IMAGETYPE_WEBP', 18);

class dThumbMaker{
	Function getVersion(){
		return "2.95";
	}
	
	var $info;
	var $backup;
	var $bgColor;
	
	// System methods:
	Function __construct($origFilename=false){
		if($origFilename)
			$this->loadFile($origFilename);
	}
	Function __destruct(){ /** Need to be manually called if PHP<5 **/
		$this->unload();
	}
	Function unload(){
		// Releases all memory used so far.
		$this->removeBackup();
		if($this->info && isset($this->info['im'])){
			@imagedestroy($this->info['im']);
		}
		$this->info = false;
	}
	
	// Useful methods (but not necessary):
	Function setBgColor        ($r=false, $g=false, $b=false){
		$this->bgColor = ($r===false)?
			false:
			Array($r, $g, $b);
	}
	Function getWidth(){    // Returns image width
		return $this->info['origSize'][0];
	}
	Function getHeight(){   // Returns image height
		return $this->info['origSize'][1];
	}
	Function getFileType(){ // Returns the image extension (gif, jpg, png, swf, etc.)
		$it = $this->info['origSize'][2];
		switch($it){
			case IMAGETYPE_GIF:  $r = "gif";  break;
			case IMAGETYPE_JPEG: $r = "jpg";  break;
			case IMAGETYPE_PNG:  $r = "png";  break;
			case IMAGETYPE_BMP:  $r = "bmp";  break;
			case IMAGETYPE_WBMP: $r = "wbmp"; break;
			case IMAGETYPE_WEBP: $r = "webp"; break;
			default:
				$r = false;
				break;
		}
		
		return $r;
	}
	Function &getIm(){      // Returns image handler
		return $this->info['im'];
	}
	
	// Recommended in most cases:
	Function resizeTouch       ($W, $H, $fillWithBackground=false, $align=Array('center', 'middle'), $keepAlpha=false){
		// Deprecated, should NOT be used.
		return $this->resizeTouchInside($W, $H, $fillWithBackground, $align, $keepAlpha);
	}
	Function resizeTouchInside ($W, $H, $fillWithBackground=false, $align=Array('center', 'middle'), $keepAlpha=false){
		// Similar ao resizeMaxSize, no entanto também detecta imagens menores.
		// Um dos lados sempre terá a dimensão perfeita.
		$oW = $this->getWidth();
		$oH = $this->getHeight();
		
		if(!$W || !$H){
			// Faltou um parâmetro, então é sinônimo para resizeExactSize.
			($W)?
				$this->resizeExactSize($W, false, true, $keepAlpha):
				$this->resizeExactSize(false, $H, true, $keepAlpha);
		}
		elseif($oW > $W || $oH > $H){
			// A imagem é maior do que o desejado, então é sinônimo para resizeMaxSize().
			$this->resizeMaxSize($W, $H, true, $keepAlpha);
		}
		else{
			// A imagem é menor em ambos os lados, precisamos identificar qual lado
			// devemos grudar na borda, e esticar o outro proporcionalmente.
			
			$newWidthByHeight = (($oW*$H)/$oH);
			($newWidthByHeight > $W)?
				$this->resizeExactSize($W, false, true, $keepAlpha):
				$this->resizeExactSize(false, $H, true, $keepAlpha);
		}
		
		if(!$fillWithBackground){
			return true;
		}
		if(is_array($align)){
			$align = array_map('strtolower', $align);
			if(!in_array(@$align[0], Array('left', 'center', 'right')) || !in_array(@$align[1], Array('top',  'middle', 'bottom'))){
				// Invalid values for align...
				return false;
			}
		}
		elseif($align === true || $align === false){
			$align = Array('center', 'middle');
		}
		elseif(in_array($align, Array('top', 'middle', 'bottom'))){
			$align = Array('center', $align);
		}
		elseif(in_array($align, Array('left', 'center', 'right'))){
			$align = Array($align, 'middle');
		}
		else{
			// What is that argument? Object? Numeric?
			return false;
		}
		
		$oW = $this->getWidth();
		$oH = $this->getHeight();
		
		$imN = imagecreatetruecolor($W, $H);
		if($keepAlpha){
			imagealphablending($imN, false);
			imagesavealpha($imN, true);
		}
		elseif($this->bgColor){
			imagefill($imN, 0, 0, imagecolorallocate($imN, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
		}
		
		$startX = 0;
		$startY = 0;
		switch($align[0]){
			case 'left':
				$startX = 0;
				break;
			case 'center':
				$startX = intval(($W/2)-($oW/2));
				break;
			case 'right':
				$startX = $W - $oW;
				break;
			default:
				die("Code inconsistency (dThumbMaker, line ".(__LINE__)." --> resizeTouchBorder())");
		}
		switch($align[1]){
			case 'top':
				$startY = 0;
				break;
			case 'middle':
				$startY = intval(($H/2)-($oH/2));
				break;
			case 'bottom':
				$startY = $H - $oH;
				break;
			default:
				die("Code inconsistency (dThumbMaker, line ".(__LINE__)." --> resizeTouchBorder())");
		}
		
		imagecopy($imN, $this->info['im'], $startX, $startY, 0, 0, $oW, $oH);
		imagedestroy($this->info['im']);
		
		$this->info['im']          = $imN;
		$this->info['origSize'][0] = $W;
		$this->info['origSize'][1] = $H;
		
		return true;
	}
	Function resizeTouchOutside($W, $H, $keepAlpha=false){
		// Um dos lados deve tocar na borda correspondente.
		// O outro lado, deve ser REDUZIDO, de forma que TODA A IMAGEM caiba na saída.
		$oW = $this->getWidth();
		$oH = $this->getHeight();
		
		if(!$W || !$H){
			// Faltou um parâmetro, então é sinônimo para resizeExactSize.
			return ($W)?
				$this->resizeExactSize($W, false, true, $keepAlpha):
				$this->resizeExactSize(false, $H, true, $keepAlpha);
		}
		elseif($oW <= $W || $oH <= $H){
			// A imagem é menor do que o desejado, então é um sinônimo para resizeMinSize().
			return $this->resizeMinSize($W, $H, true, $keepAlpha);
		}
		
		// A imagem é maior em ambos os lados, precisamos identificar qual lado
		// devemos grudar na borda, e esticar o outro proporcionalmente.
		$newWidthByHeight = (($oW*$H)/$oH);
		return ($newWidthByHeight < $W)?
			$this->resizeExactSize($W, false, true, $keepAlpha):
			$this->resizeExactSize(false, $H, true, $keepAlpha);
	}
	Function resizeToFit       ($W, $H, $zoomMultiplier=false, $moveX=0, $moveY=0, $keepAlpha=false){
		if(!$zoomMultiplier)
			$zoomMultiplier = 1;
		
		$this->resizeTouchOutside($W*$zoomMultiplier, $H*$zoomMultiplier, $keepAlpha);
		$this->cropCenter        ($W, $H, $moveX, $moveY, true, $keepAlpha);
		return true;
	}
	
	// Not that recommended when dealing with CMS:
	Function resizeMinSize     ($minW, $minH=false, $constraint=true, $keepAlpha=false){
		$origSize = &$this->info['origSize'];
		
		$resizeByH = 
		$resizeByW = false;
		
		
		if($origSize[0] < $minW && $minW) $resizeByW = true;
		if($origSize[1] < $minH && $minH) $resizeByH = true;
		
		if($resizeByH && $resizeByW){
			$resizeByH = ($origSize[0]/$minW>$origSize[1]/$minH);
			$resizeByW = !$resizeByH;
		}
		if    ($resizeByW){
			if($constraint){
				$newW = $minW;
				$newH = ($origSize[1]*$minW)/$origSize[0];
			}
			else{
				$newW = $minW;
				$newH = $origSize[1];
			}
		}
		elseif($resizeByH){
			if($constraint){
				$newW = ($origSize[0]*$minH)/$origSize[1];
				$newH = $minH;
			}
			else{
				$newW = $origSize[0];
				$newH = $minH;
			}
		}
		else{
			$newW = $origSize[0];
			$newH = $origSize[1];
		}
		
		return $this->resizeExactSize($newW, $newH, true, $keepAlpha);
	}
	Function resizeMaxSize     ($maxW, $maxH=false, $constraint=true, $keepAlpha=false){
		$origSize = &$this->info['origSize'];
		$resizeByH =
		$resizeByW = false;
		
		if($origSize[0] > $maxW && $maxW) $resizeByW = true;
		if($origSize[1] > $maxH && $maxH) $resizeByH = true;
		
		if($resizeByH && $resizeByW){
			$resizeByH = ($origSize[0]/$maxW<$origSize[1]/$maxH);
			$resizeByW = !$resizeByH;
		}
		if    ($resizeByW){
			if($constraint){
				$newW = $maxW;
				$newH = ($origSize[1]*$maxW)/$origSize[0];
			}
			else{
				$newW = $maxW;
				$newH = $origSize[1];
			}
		}
		elseif($resizeByH){
			if($constraint){
				$newW = ($origSize[0]*$maxH)/$origSize[1];
				$newH = $maxH;
			}
			else{
				$newW = $origSize[0];
				$newH = $maxH;
			}
		}
		else{
			$newW = $origSize[0];
			$newH = $origSize[1];
		}
		
		return $this->resizeExactSize($newW, $newH, true, $keepAlpha);
	}
	Function resizeExactSize   ($W, $H, $constraint=true, $keepAlpha=false){
		$im       = &$this->info['im'];
		$origSize = &$this->info['origSize'];
		
		$newW = $origSize[0];
		$newH = $origSize[1];
		if($W && $H){
			$newW = $W;
			$newH = $H;
		}
		elseif($W){
			if($constraint){
				$newW = $W;
				$newH = ($origSize[1]*$W)/$origSize[0];
			}
			else{
				$newW = $W;
				$newH = $origSize[1];
			}
		}
		elseif($H){
			if($constraint){
				$newW = ($origSize[0]*$H)/$origSize[1];
				$newH = $H;
			}
			else{
				$newW = $origSize[0];
				$newH = $H;
			}
		}
		
		if($newW != $origSize[0] || $newH != $origSize[1]){
			$imN = imagecreatetruecolor($newW, $newH);
			if($keepAlpha){
				imagealphablending($imN, false);
				imagesavealpha($imN, true);
			}
			elseif($this->bgColor){
				imagefill($imN, 0, 0, imagecolorallocate($imN, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
			}
			
			imagecopyresampled($imN, $im, 0, 0, 0, 0, $newW, $newH, $origSize[0], $origSize[1]);
			imagedestroy($im);
			$this->info['im'] = $imN;
		}
		$this->info['origSize'][0] = $newW;
		$this->info['origSize'][1] = $newH;
		
		return true;
	}
	
	// Image manipulation:
	Function crop      ($startX, $startY, $endX=false, $endY=false, $keepAlpha=false){
		$im       = &$this->info['im'];
		$origSize = &$this->info['origSize'];
		
		if($endX == false)
			$endX = $origSize[0]-$startX;
		
		if($endY == false)
			$endY = $origSize[1]-$startY;
		
		$width  = $endX-$startX;
		$height = $endY-$startY;
		
		$imN = imagecreatetruecolor($width, $height);
		if($keepAlpha){
			imagealphablending($imN, false);
			imagesavealpha($imN, true);
		}
		elseif($this->bgColor){
			imagefill($imN, 0, 0, imagecolorallocate($imN, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
		}
		imagecopy($imN, $im, 0, 0, $startX, $startY, $width, $height);
		imagedestroy($im);
		
		$this->info['im'] = $imN;
		$this->info['origSize'][0] = $width;
		$this->info['origSize'][1] = $height;
		
		return true;
	}
	Function cropCenter($width, $height, $moveX=0, $moveY=0, $forceMinSize=true, $keepAlpha=false){
		if($forceMinSize)
			$this->resizeMinSize($width, $height);
		
		$origSize = &$this->info['origSize'];
		$centerX  = $origSize[0]/2;
		$centerY  = $origSize[1]/2;
		
		$topX = $centerX-$width/2;
		$topY = $centerY-$height/2;
		$endX = $centerX+$width/2;
		$endY = $centerY+$height/2;
		
		return $this->crop($topX+$moveX, $topY+$moveY, $endX+$moveX, $endY+$moveY, $keepAlpha);
	}
	Function flip($vertical=false, $keepAlpha=false){
		$origSize = &$this->info['origSize'];
		$im       = &$this->info['im'];
		
		$imN = imagecreatetruecolor($origSize[0], $origSize[1]);
		if($keepAlpha){
			imagealphablending($imN, false);
			imagesavealpha($imN, true);
		}
		elseif($this->bgColor){
			imagefill($imN, 0, 0, imagecolorallocate($imN, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
		}
		if($vertical)
			for($y = 0; $y <$origSize[1]; $y++)
				imagecopy($imN, $im, 0, $y, 0, $origSize[1] - $y - 1, $origSize[0], 1);
		else
			for($x = 0; $x < $origSize[0]; $x++)
				imagecopy($imN, $im, $x, 0, $origSize[0] - $x - 1, 0, 1, $origSize[1]);
		
		imagedestroy($im);
		$this->info['im'] = &$imN;
		return true;
	}
	Function flipV($keepAlpha=false){
		return $this->flip(true, $keepAlpha);
	}
	Function flipH($keepAlpha=false){
		return $this->flip(false, $keepAlpha);
	}
	Function rotate90($times=1, $keepAlpha=false){
		$origSize = &$this->info['origSize'];
		$im       = &$this->info['im'];
		$times    = ($times%4);
		if($times < 0)
			$times += 4;
		
		if($times == 1){     // 90º
			$newW = $origSize[1];
			$newH = $origSize[0];
			$imN  = imagecreatetruecolor($newW, $newH);
			if($keepAlpha){
				imagealphablending($imN, false);
				imagesavealpha($imN, true);
			}
			elseif($this->bgColor){
				imagefill($imN, 0, 0, imagecolorallocate($imN, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
			}
			
			for($x=0; $x<$newH; $x++)
				for($y=0; $y<$newW; $y++)
					imagecopy($imN, $im, $newW-$y-1, $x, $x, $y, 1, 1);
		}
		elseif($times == 2){ // 180º
			$this->flipH();
			$this->flipV();
			return true;
		}
		elseif($times == 3){ // 270º
			$newW = $origSize[1];
			$newH = $origSize[0];
			$imN  = imagecreatetruecolor($newW, $newH);
			if($keepAlpha){
				imagealphablending($imN, false);
				imagesavealpha($imN, true);
			}
			elseif($this->bgColor){
				imagefill($imN, 0, 0, imagecolorallocate($imN, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
			}
			
			for($x=0; $x<$newH; $x++)
				for($y=0; $y<$newW; $y++)
					imagecopy($imN, $im, $y, $newH-$x-1, $x, $y, 1, 1);
		}
		else{
			return true;
		}
		
		imagedestroy($im);
		$this->info['im'] = $imN;
		$this->info['origSize'][0] = $newW;
		$this->info['origSize'][1] = $newH;
		
		return true;
	}
	Function addBorder($fileName, $paddingX=0, $paddingY=0){
		$load = $this->_loadResource($filename);
		if(is_string($load)){
			return $load;
		}
		
		$im       = &$this->info['im'];
		$origSize = &$this->info['origSize'];
		
		$imB       = $load['im'];
		$origBSize = $load['origSize'];
		
		imagecopyresampled($im, $imB, $paddingX, $paddingY, 0, 0, $origSize[0]-$paddingX, $origSize[1]-$paddingY, $origBSize[0], $origBSize[1]);
		imagedestroy($imB);
		return true;
	}
	Function addWaterMark($fileName, $posX=0, $posY=0, $invertido=true, $opacity=100){
		$load = $this->_loadResource($fileName);
		if(is_string($load)){
			return $load;
		}
		
		$origSize = &$this->info['origSize'];
		$im       = &$this->info['im'];
		
		$origWSize = $load['origSize'];
		$imW       = $load['im'];
		
		if($invertido===true || (is_array($invertido)&&$invertido[0]))
			$posX = $origSize[0]-$origWSize[0]-$posX;
		if($invertido===true || (is_array($invertido)&&$invertido[1]))
			$posY = $origSize[1]-$origWSize[1]-$posY;
		
		($opacity != 100)?
			imagecopymerge($im, $imW, $posX, $posY, 0, 0, $origWSize[0], $origWSize[1], $opacity):
			imagecopy($im, $imW, $posX, $posY, 0, 0, $origWSize[0], $origWSize[1]);
		
		imagedestroy($imW);
		return true;
	}
	Function makeCaricature($colors=32, $opacity=70, $keepAlpha=false){
		$newim = imagecreatetruecolor($this->info['origSize'][0], $this->info['origSize'][1]);
		if($keepAlpha){
			imagealphablending($newim, false);
			imagesavealpha($newim, true);
		}
		elseif($this->bgColor){
			imagefill($newim, 0, 0, imagecolorallocate($newim, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]));
		}
		imagecopy($newim, $this->info['im'], 0, 0, 0, 0, $this->info['origSize'][0], $this->info['origSize'][1]);
		imagefilter($newim, IMG_FILTER_SMOOTH, 0);
		imagefilter($newim, IMG_FILTER_GAUSSIAN_BLUR);
		imagetruecolortopalette($newim, false, $colors);
		imagecopymerge($this->info['im'], $newim, 0, 0, 0, 0, $this->info['origSize'][0], $this->info['origSize'][1], $opacity);
		imagedestroy($newim);
		
		return true;
	}
	
	// Backup manipulation:
	Function createBackup(){
		$this->removeBackup(); // if any
		$this->backup = $this->info;
		$this->backup['im'] = $this->_cloneResource($this->info['im']);
		return true;
	}
	Function restoreBackup(){
		imagedestroy($this->info['im']);
		
		$this->info = $this->backup;
		$this->info['im'] = $this->_cloneResource($this->backup['im']);
		return true;
	}
	Function removeBackup(){
		if($this->backup){
			@imagedestroy($this->backup['im']);
			$this->backup = false;
		}
	}
	private Function _cloneResource($oldIm){
		$w = imagesx($oldIm);
		$h = imagesy($oldIm);
		if(imageistruecolor($oldIm)){
			$clone = imagecreatetruecolor($w, $h);
			imagealphablending($clone, false);
			imagesavealpha    ($clone, true);
		}
		else{
			$clone = imagecreate($w, $h);
			$trans = imagecolortransparent($oldIm);
			if($trans >= 0) {
				$rgb = imagecolorsforindex($oldIm, $trans);
				imagesavealpha($clone, true);
				$trans_index = imagecolorallocatealpha($clone, $rgb['red'], $rgb['green'], $rgb['blue'], $rgb['alpha']);
				imagefill($clone, 0, 0, $trans_index);
			}
		}
		
		imagecopy($clone, $oldIm, 0, 0, 0, 0, $w, $h);
		return $clone;
	}
	
	// Load and build:
	Function loadFile($origFilename){
		$load = $this->_loadResource($origFilename);
		if(is_string($load)){
			return $load;
		}
		
		$this->unload();
		$this->info = $load;
		// origFilename, origSize, im, isPng
		
		$this->backup = false;
		return true;
	}
	Function build   ($output_filename=false, $output_as=false, $quality=80){
		$origSize = &$this->info['origSize'];
		$im       = &$this->info['im'];
		
		if($output_filename===false){
			// Output filename wasn't found, let's overwrite original file.
			$output_filename = $this->info['origFilename'];
		}
		
		// Try to auto-determine output format
		if(!$output_as)
			$output_as = preg_replace("/.*\.(.+)/", "\\1", $output_filename);
		
		// Output directly to buffer
		if($output_filename===NULL){
			if    ($output_as == 'gif')  return imagegif ($im);
			elseif($output_as == 'png')  return imagepng ($im);
			elseif($output_as == 'wbmp') return imagewbmp($im);
			elseif($output_as == 'webp') return imagewebp($im, $output_filename, $quality);
			else /* default: jpeg     */ return imagejpeg($im, $output_filename, $quality);
		}
		else{
			if    ($output_as == 'gif')  return imagegif ($im, $output_filename);
			elseif($output_as == 'png')  return imagepng ($im, $output_filename);
			elseif($output_as == 'wbmp') return imagewbmp($im, $output_filename);
			elseif($output_as == 'webp') return imagewebp($im, $output_filename, $quality);
			else /* default: jpeg     */ return imagejpeg($im, $output_filename, $quality);
		}
	}
	private Function _loadResource($filename){
		if(!file_exists($filename)){
			return "Imagem não encontrada ou não acessível.";
		}
		
		$size = @getimagesize($filename);
		switch($size[2]){
			case IMAGETYPE_GIF  /*gif*/ : $imResource = imagecreatefromgif ($filename); break;
			case IMAGETYPE_JPEG /*jpg*/ : $imResource = imagecreatefromjpeg($filename); break;
			case IMAGETYPE_PNG  /*png*/ : $imResource = imagecreatefrompng ($filename); break;
			case IMAGETYPE_BMP  /*bmp*/ : $imResource = self::imagecreatefrombmp ($filename); break;
			case IMAGETYPE_WBMP /*wbmp*/: $imResource = imagecreatefromwbmp($filename); break;
			case IMAGETYPE_WEBP /*webp*/: $imResource = imagecreatefromwebp($filename); break;
			default:
				return "A borda precisa estar no formato GIF, JPG, PNG, WEBP, BMP ou WBMP.";
		}
		
		return Array(
			'origSize'     => $size,
			'origFilename' => $filename,
			'im'           => $imResource,
			'isPng'        => ($size[2] == IMAGETYPE_PNG),
		);
	}
	
	// Extended functionality:
	static Function imagecreatefrombmp($filename){
		if(function_exists('imagecreatefrombmp')){
			return imagecreatefrombmp($filename);
		}
		
		/*********************************************/
		/*    --- Source: PHP Manual ---             */
		/* Fonction: ImageCreateFromBMP              */
		/* Author:   DHKold                          */
		/* Contact:  admin@dhkold.com                */
		/* Date:     The 15th of June 2005           */
		/* Version:  2.0B                            */
		/*********************************************/
		if(!($f1 = fopen($filename, "rb")))
			return false;
		
		//1 : Chargement des entêtes FICHIER
		$FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
		if($FILE['file_type'] != 19778)
			return false;
		
		//2 : Chargement des entêtes BMP
		$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
		'/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
		'/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
		$BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
		if($BMP['size_bitmap'] == 0)
			$BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		
		$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
		$BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
		$BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] = 4-(4*$BMP['decal']);
		if ($BMP['decal'] == 4)
			$BMP['decal'] = 0;
		
		//3 : Chargement des couleurs de la palette
		$PALETTE = array();
		if ($BMP['colors'] < 16777216)
			$PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
		
		//4 : Création de l'image
		$IMG  = fread($f1,$BMP['size_bitmap']);
		$VIDE = chr(0);
		
		$res = imagecreatetruecolor($BMP['width'],$BMP['height']);
		$P = 0;
		$Y = $BMP['height']-1;
		while ($Y >= 0){
			$X=0;
			while ($X < $BMP['width']){
				if ($BMP['bits_per_pixel'] == 24)
					$COLOR = @unpack("V",substr($IMG,$P,3).$VIDE);
				elseif ($BMP['bits_per_pixel'] == 16){ 
					$COLOR = @unpack("n",substr($IMG,$P,2));
					$COLOR[1] = $PALETTE[$COLOR[1]+1];
				}
				elseif ($BMP['bits_per_pixel'] == 8){ 
					$COLOR = @unpack("n", $VIDE.substr($IMG,$P,1));
					$COLOR[1] = $PALETTE[$COLOR[1]+1];
				}
				elseif ($BMP['bits_per_pixel'] == 4){
					$COLOR = @unpack("n",$VIDE.substr($IMG,floor($P),1));
					if (($P*2)%2 == 0)
						$COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
					$COLOR[1] = $PALETTE[$COLOR[1]+1];
				}
				elseif ($BMP['bits_per_pixel'] == 1){
					$COLOR = @unpack("n",$VIDE.substr($IMG,floor($P),1));
						if (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
					elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
					elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
					elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
					elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8 )>>3;
					elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4 )>>2;
					elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2 )>>1;
					elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1 );
					$COLOR[1] = $PALETTE[$COLOR[1]+1];
				}
				else
					return false;
				imagesetpixel($res,$X,$Y,$COLOR[1]);
				$X++;
				$P += $BMP['bytes_per_pixel'];
			}
			$Y--;
			$P+=$BMP['decal'];
		}
		//Fermeture du fichier
		fclose($f1);
		return $res;
	}
}

