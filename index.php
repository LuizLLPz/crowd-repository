<?php

use modules\core\roteamento\Roteador;
use modules\core\validacoes\ErrorHandler;

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: {$_ENV["CORS_ORIGIN"]}");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}
ErrorHandler::registrar();

$roteador = new Roteador();
$roteador->registrarControladoresPasta("api/controllers");

$metodoHttp = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$roteador->despachar($metodoHttp, $caminho);