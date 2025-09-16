<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Chats;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\Http;
use modules\core\utils\Utils;
use services\integrations\email\EmailService;

class ChatsController extends ControllerBase
{
    #[HttpGet('/chats')]
    public function listar(): void
    {
        $resp = Chats::buscarChats();
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }


    #[HttpGet('/chatsdeusuario')]
    public function listarChatsDeUsuario(): void
    {
        $idUsuario = $_GET['idUsuario'];
        $resp = Chats::buscarChatsDeUsuario($idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}