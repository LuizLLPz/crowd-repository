<?php

namespace api\controllers;

use models\Usuario;
use modules\core\atributos\HttpGet;
use modules\core\tipos\ControllerBase;

class UsuarioController extends ControllerBase {
    #[HttpGet('/usuarios')]
    public function listar(): void
    {
       $resp = Usuario::buscarUsuarios();
       echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}