<?php

namespace services\social;

use models\social\Chats;
use modules\db\Database;
use services\core\NotificacaoService;

class ChatService
{
    public static function criar_chat(int $currentUserId, int $otherUserId): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $chat = Chats::criar([$currentUserId, $otherUserId]);
            $pdo->commit();

            $remetente = \models\social\Usuario::buscar_usuario($currentUserId);

            NotificacaoService::disparar(
                'novo-chat',
                $otherUserId,
                [
                    'idItem' => $chat['idChat'],
                    'nomeUsuarioRemetente' => $remetente->nomeUsuario,
                ]
            );

            return $chat;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
