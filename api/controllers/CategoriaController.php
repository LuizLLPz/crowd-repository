<?php

namespace api\controllers;

use models\Categoria;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;

class CategoriaController extends ControllerBase
{
    #[HttpGet('/categorias')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $resp = Categoria::buscarCategorias($pesquisa);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}