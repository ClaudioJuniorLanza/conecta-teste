<?php
header("Content-type: image/png");

@$id = basename($_SERVER['REQUEST_URI']);
@$fh = fopen("_log2.txt", "a");
@fwrite($fh, date('Y-m-d H:i:s')."\t".base64_decode($id)."\t{$_SERVER['REMOTE_ADDR']}\t{$_SERVER['HTTP_USER_AGENT']}\r\n");
@fclose($fh);

// Create a 55x30 image
$im    = imagecreatetruecolor(55, 30);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
imagefill($im, 10, 10, $white);
imagepng($im);
imagedestroy($im);
