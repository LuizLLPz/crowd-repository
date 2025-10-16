<?php

namespace services\social;

use GuzzleHttp\Client;
use models\social\Chats;
use models\social\Mensagens;
use modules\db\Database;
use services\core\NotificacaoService;

class MensagemService
{
    public static function enviar_mensagem(int $chatId, int $currentUserId, string $mensagem): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $novaMensagem = Mensagens::criarMensagem($chatId, $currentUserId, $mensagem);

            $participantes = \models\social\Chats::buscarParticipantesDoChat($chatId);
            $otherUserId = null;
            foreach ($participantes as $participante) {
                if ($participante['idUsuario'] != $currentUserId) {
                    $otherUserId = $participante['idUsuario'];
                    break;
                }
            }

            if ($otherUserId) {
                $remetente = \models\social\Usuario::buscar_usuario($currentUserId);
                NotificacaoService::disparar(
                    'nova-mensagem',
                    $otherUserId,
                    [
                        'idItem' => $chatId,
                        'nomeUsuarioRemetente' => $remetente->nomeUsuario,
                    ]
                );
            }

            $pdo->commit();

            return $novaMensagem;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
