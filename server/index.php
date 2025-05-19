<?php

use modules\core\roteamento\Roteador;

require_once __DIR__ . '/vendor/autoload.php';

header('Content-type: application/json');
$roteador = new Roteador();
$roteador->registrarControladoresPasta("api/controllers");

$metodoHttp = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$roteador->despachar($metodoHttp, $caminho);