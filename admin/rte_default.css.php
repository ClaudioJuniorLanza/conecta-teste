<?php
require_once "config.php";

header("Content-Type: text/css");
$useStyle = array(
	'font'       =>"12px Arial",
	'background' =>'FFF',
	'color'      =>'000',
	'link'       =>'00F',
	'margin'     =>"5px",
	'width'      =>false,
	'line-height'=>false,
);

foreach($_GET as $overKey=>$overValue){
	if($overValue)
		{$useStyle[$overKey] = $overValue;}
}
?>
body {
    font: <?=$useStyle['font']       ?>;
    background: #<?=$useStyle['background']?>;
    color: #<?=$useStyle['color']     ?>;
    margin: <?=$useStyle['margin']     ?>;
<? if($useStyle['width']): ?> width: <?=$useStyle['width']      ?>;
<? endif ?> <? if($useStyle['line-height']): ?> line-height: <?=$useStyle['line-height'] ?>;
<? endif ?>
}

a {
    color: #<?=$useStyle['link']      ?>;
}

p {
    margin: 0
}
