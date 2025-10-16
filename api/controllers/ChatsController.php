<?php

namespace api\controllers;

use models\social\Chats;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;

use modules\core\tipos\http\atributos\HttpPost;

use services\social\ChatService;

class ChatsController extends ControllerBase
{
    #[HttpPost('/chats')]
    public function criar(): void
    {
        try {
            $otherUserId = $this->getBody()->usuarioId;
            $currentUserId = self::$usuarioAutenticado->idUsuario;

            $chat = ChatService::criar_chat($currentUserId, $otherUserId);

            echo json_encode($chat, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Erro ao criar chat: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
        }
    }

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