<?php

namespace api\controllers;

use models\social\ChatsUsuarios;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;

class ChatsUsuariosController extends ControllerBase
{
    #[HttpGet('/chatsusuarios')]
    public function listar(): void
    {
        $resp = ChatsUsuarios::buscarChatsUsuarios();
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}