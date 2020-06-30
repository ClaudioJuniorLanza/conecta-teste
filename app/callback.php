<?php

$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['REQUEST_URI'], 1));

var_dump($method);
var_dump($request);

$input = file_get_contents("php://input");
$json = json_encode(utf8_encode($input), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

//echo $method . " " . $_SERVER['REQUEST_URI'] . " " . $json;

switch ($method) {
    case 'PUT':
        do_something_with_put($request);
        break;
    case 'POST':
        do_something_with_post($request);
        header("HTTP/1.0 418 I'm A Teapot");
        break;
    case 'GET':
        do_something_with_get($request);
        break;
    default:
        handle_error($request);
        break;
}