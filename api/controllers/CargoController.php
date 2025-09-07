<?php

namespace api\controllers;

use models\Cargo;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;

class CargoController extends ControllerBase
{
    #[HttpGet('/cargos')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $resp = Cargo::buscarCargos($pesquisa);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}

?>