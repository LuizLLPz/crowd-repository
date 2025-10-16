<?php

namespace modules\sockets\handlers;

use Ratchet\ConnectionInterface;
use modules\sockets\ConnectionManager;
use models\social\Mensagens;
use models\social\Chats;

class ChatHandler
{
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function handle(ConnectionInterface $from, $message)
    {
        // This handler will primarily be used for broadcasting messages
        // sent via the HTTP API, but can also handle direct WS messages
        // if we decide to implement that later.
        // For now, we'll focus on the broadcast part.
    }

    public function broadcastNewMessage(array $messageData, int $senderId)
    {
        error_log("CHAT_HANDLER: Broadcasting nova mensagem: " . print_r($messageData, true));
        $chatId = $messageData['chatId'];
        $messageJson = json_encode([
            'type' => 'nova_mensagem_chat',
            'payload' => $messageData
        ]);

        $chatParticipants = \models\social\Chats::buscarParticipantesDoChat($chatId);
        error_log("CHAT_HANDLER: Participantes encontrados: " . print_r($chatParticipants, true));

        foreach ($chatParticipants as $participant) {
            $userId = $participant['idUsuario'];
            if ($userId == $senderId) {
                continue;
            }

            error_log("CHAT_HANDLER: Tentando enviar para Usuario ID: {$userId}");
            $connections = $this->connectionManager->getConnectionsByUserId($userId);
            if ($connections) {
                foreach ($connections as $conn) {
                    $conn->send($messageJson);
                    error_log("CHAT_HANDLER: Mensagem enviada para Usuario ID: {$userId}");
                }
            } else {
                error_log("CHAT_HANDLER: Nenhuma conexão encontrada para Usuario ID: {$userId}");
            }
        }
    }

    public function broadcastReactionUpdate(array $reactionData)
    {
        $mensagemId = $reactionData['mensagemId'];
        $chatId = $reactionData['chatId'] ?? null;

        if (!$chatId) {
            $mensagem = \models\social\Mensagens::buscarMensagemPorId($mensagemId);
            if ($mensagem) {
                $chatId = $mensagem['chatId'];
            }
        }

        if (!$chatId) {
            error_log("ChatHandler: Could not determine chatId for reaction update on mensagemId {$mensagemId}");
            return;
        }

        $reactionJson = json_encode([
            'type' => 'atualizacao_reacao_chat',
            'payload' => $reactionData
        ]);

        $chatParticipants = \models\social\Chats::buscarParticipantesDoChat($chatId);

        foreach ($chatParticipants as $participant) {
            $userId = $participant['idUsuario'];
            $connections = $this->connectionManager->getConnectionsByUserId($userId);
            if ($connections) {
                foreach ($connections as $conn) {
                    $conn->send($reactionJson);
                }
            }
        }
    }
}
