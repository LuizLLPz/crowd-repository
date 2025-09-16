<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\ChatsUsuarios;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\Http;
use modules\core\utils\Utils;
use services\integrations\email\EmailService;

class ChatsUsuariosController extends ControllerBase
{
    #[HttpGet('/chatsusuarios')]
    public function listar(): void
    {
        $resp = ChatsUsuarios::buscarChatsUsuarios();
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}