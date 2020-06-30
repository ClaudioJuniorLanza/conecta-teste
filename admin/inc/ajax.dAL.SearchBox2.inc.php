<?php
require_once dirname(__FILE__) . "/../config.php";

// Funções genéricas para dAL.SearchBox2.
$dDbRow3 = is_subclass_of($_POST['className'], 'dDbRow3');
$obj = false;
if ($dDbRow3) {
    $obj = $_POST['className']::load($_POST['id']) or die("Objeto não encontrado (1.1)");
} else {
    $obj = new $_POST['className'];
    $obj->loadFromDatabase($_POST['id']) or die("Objeto não encontrado (1.2)");
}


if ($_POST['action'] == 'ajaxDelete') {
    $obj->deleteFromDatabase();
    die("OK");
}
if ($_POST['action'] == 'inlineEdit') {
    if ($dDbRow3) {
        $ok = $obj->v($_POST['key'], $_POST['nv'])->save();
    } else {
        $obj->startUpdate();
        $obj->setValue($_POST['key'], $_POST['nv']);
        $ok = $obj->flushUpdate();
    }

    if ($ok) {
        if (!$dDbRow3) {
            $obj->loadFromDatabase($_POST['id']);
        }
        echo $obj->getValue($_POST['key']);
    } else {
        echo "ERROR=" . implode(", ", $obj->listErrors(true));
    }
    die;
}
if ($_POST['action'] == 'setPosition') {
    if ($obj->sortingMoveTo($_POST['newPos'])) {
        echo "OK";
    } else {
        echo implode("\r\n", $obj->listErrors(true));
    }
    die;
}
if ($_POST['action'] == 'moveAfter' || $_POST['action'] == 'moveBefore') {
    if ($dDbRow3) {
        $otherObj = $_POST['className']::load($_POST['otherId']) or die("Outro objeto não encontrado (1)");
    } else {
        $otherObj = new $_POST['className'];
        $otherObj->loadFromDatabase($_POST['otherId']) or die("Outro objeto não encontrado (2)");
    }

    $myPos = $obj->getValue('ordem');
    $otherPos = $otherObj->getValue('ordem');

    if ($_POST['action'] == 'moveAfter') {
        ($myPos < $otherPos) ?
            $obj->sortingMoveTo($otherPos) :
            $obj->sortingMoveTo($otherPos + 1);
    } else {
        ($myPos < $otherPos) ?
            $obj->sortingMoveTo($otherPos - 1) :
            $obj->sortingMoveTo($otherPos);
    }
    if (!$obj->listErrors()) {
        echo "OK";
    } else {
        echo implode("\r\n", $obj->listErrors(true));
    }
    die;
}
