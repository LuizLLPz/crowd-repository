<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

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
        $stmt = $pdo->query(new Chats()->select);
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
                u2.nomeUsuario AS nomeOutroParticipante,
                u2.idUsuario AS idOutroParticipante,
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
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}