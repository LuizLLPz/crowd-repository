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

    public function broadcastNewMessage(array $messageData)
    {
        $chatId = $messageData['chatId'];
        $messageJson = json_encode([
            'type' => 'nova_mensagem_chat',
            'payload' => $messageData
        ]);

        // Get all users in this chat
        $chatParticipants = Chats::buscarParticipantesDoChat($chatId);

        foreach ($chatParticipants as $participant) {
            $userId = $participant['idUsuario'];
            $connections = $this->connectionManager->getConnectionsByUserId($userId);
            foreach ($connections as $conn) {
                $conn->send($messageJson);
            }
        }
    }

    public function broadcastReactionUpdate(array $reactionData)
    {
        $mensagemId = $reactionData['mensagemId'];
        // Assuming we can get chatId from mensagemId, or it's passed in reactionData
        // For simplicity, let's assume reactionData contains chatId
        $chatId = $reactionData['chatId'] ?? null;

        if (!$chatId) {
            // If chatId is not directly available, we might need to fetch it from the Mensagens table
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
            foreach ($connections as $conn) {
                $conn->send($reactionJson);
            }
        }
    }
}
