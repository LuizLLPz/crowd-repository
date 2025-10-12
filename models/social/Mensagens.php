<?php

namespace models\social;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Mensagens extends Entidade
{
    public int $idMensagem;
    public int $chatId;
    public int $usuarioId;
    public string $nomeTabela = "Mensagens";
    public ?string $criadoEm = null;
    public ?string $lidoEm = null;
    public string $mensagem = "";

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Mensagens[]
     */
    public static function buscarMensagens(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM Mensagens");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function buscarMensagensDeUmChat(int $idChat): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                m.*, 
                u.urlFoto, 
                (SELECT 
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'emoji', mr.emoji,
                            'count', COUNT(mr.emoji),
                            'usuarios', GROUP_CONCAT(us.nomeUsuario)
                        )
                    )
                 FROM MensagemReacoes mr
                 INNER JOIN Usuario us ON mr.usuarioId = us.idUsuario
                 WHERE mr.mensagemId = m.idMensagem
                 GROUP BY mr.emoji
                ) as reacoes
            FROM Mensagens m 
            INNER JOIN Usuario u ON m.usuarioId = u.idUsuario
            WHERE chatId = :idChat;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idChat' => $idChat]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function buscarMensagemPorId(int $idMensagem): ?array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Mensagens WHERE idMensagem = :idMensagem";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idMensagem' => $idMensagem]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function criarMensagem(int $idChat, int $idUsuario, string $mensagem): string
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        $sql = "INSERT INTO Mensagens (chatId, usuarioId, mensagem) 
        VALUES (:chatId, :usuarioId, :mensagem)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':chatId' => $idChat,
            ':usuarioId' => $idUsuario,
            ':mensagem' => $mensagem,
        ]);

        $pdo->commit();

        $idMensagem = $pdo->lastInsertId();

        // Fetch the newly created message with user photo
        $sql = "SELECT m.*, u.urlFoto FROM Mensagens m INNER JOIN Usuario u ON m.usuarioId = u.idUsuario WHERE m.idMensagem = :idMensagem";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idMensagem' => $idMensagem]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

}