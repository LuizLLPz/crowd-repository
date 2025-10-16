<?php

namespace models\social;

use modules\core\tipos\Entidade;
use modules\db\Database;

use services\integrations\google\GoogleCloudStorageService;

class Chats extends Entidade
{
    public int $idChat;
    public string $nomeTabela = "Chats";
    public ?string $criadoEm = null;

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Chats[]
     */
    public static function buscarChats(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM Chats");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function buscarChatsDeUsuario(int $idUsuario): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                c.idChat,
                u1.idUsuario AS idUsuarioPrincipal,
                u1.nomeUsuario AS nomeUsuarioPrincipal,
                u1.caminhoImagem AS urlFotoUsuarioPrincipal,
                u2.nomeUsuario AS nomeOutroParticipante,
                u2.idUsuario AS idOutroParticipante,
                u2.caminhoImagem AS urlFotoOutroParticipante,
                c.criadoEm,
                (
                    SELECT m.mensagem
                    FROM Mensagens m
                    WHERE m.chatId = c.idChat
                    ORDER BY m.criadoEm DESC
                    LIMIT 1
                ) AS ultimaMensagem,
                (
                    SELECT m.lidoEm
                    FROM Mensagens m
                    WHERE m.chatId = c.idChat
                    ORDER BY m.criadoEm DESC
                    LIMIT 1
                ) AS lidoUltimaMensagem,
                (
                    SELECT m.criadoEm
                    FROM Mensagens m
                    WHERE m.chatId = c.idChat
                    ORDER BY m.criadoEm DESC
                    LIMIT 1
                ) AS dataEnvioUltimaMensagem
            FROM Chats c
            INNER JOIN ChatsUsuarios cu1 
                ON cu1.chatId = c.idChat AND cu1.usuarioId = :idUsuario1
            INNER JOIN Usuario u1 
                ON u1.idUsuario = cu1.usuarioId
            INNER JOIN ChatsUsuarios cu2 
                ON cu2.chatId = c.idChat AND cu2.usuarioId <> :idUsuario2
            INNER JOIN Usuario u2 
                ON u2.idUsuario = cu2.usuarioId;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario1' => $idUsuario,
            ':idUsuario2' => $idUsuario
        ]);
        $chats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($chats as &$chat) {
            if (!empty($chat['urlFotoUsuarioPrincipal'])) {
                $chat['urlFotoUsuarioPrincipal'] = GoogleCloudStorageService::getSignedUrl($chat['urlFotoUsuarioPrincipal']);
            }
            if (!empty($chat['urlFotoOutroParticipante'])) {
                $chat['urlFotoOutroParticipante'] = GoogleCloudStorageService::getSignedUrl($chat['urlFotoOutroParticipante']);
            }
        }

        return $chats;
    }
    public static function criar(array $userIds): array
    {
        $pdo = Database::getConnection();

        sort($userIds);

        $sqlCheck = 'SELECT DISTINCT cu1.chatId FROM ChatsUsuarios cu1 INNER JOIN ChatsUsuarios cu2 ON cu1.chatId = cu2.chatId WHERE cu1.usuarioId = :user1 AND cu2.usuarioId = :user2';
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':user1' => $userIds[0], ':user2' => $userIds[1]]);
        $existingChat = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

        if ($existingChat) {
            return self::buscarChatPorId($existingChat['chatId']);
        }

        $sql = "INSERT INTO Chats () VALUES ()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $idChat = $pdo->lastInsertId();

        $sql = "INSERT INTO ChatsUsuarios (chatId, usuarioId) VALUES (:chatId, :usuarioId)";
        $stmt = $pdo->prepare($sql);
        foreach ($userIds as $userId) {
            $stmt->execute([':chatId' => $idChat, ':usuarioId' => $userId]);
        }

        return self::buscarChatPorId($idChat);
    }

    public static function buscarChatPorId(int $idChat): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Chats WHERE idChat = :idChat";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idChat' => $idChat]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function buscarParticipantesDoChat(int $chatId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT cu.usuarioId AS idUsuario FROM ChatsUsuarios cu WHERE cu.chatId = :chatId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':chatId' => $chatId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}