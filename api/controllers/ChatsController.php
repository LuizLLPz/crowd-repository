<?php

namespace api\controllers;

use models\social\Chats;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;

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