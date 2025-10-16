<?php

namespace models\social;

use modules\core\tipos\Entidade;
use modules\db\Database;

class MensagemReacoes extends Entidade
{
    public int $id;
    public int $mensagemId;
    public int $usuarioId;
    public string $emoji;
    public string $nomeTabela = "MensagemReacoes";

    public static function adicionarReacao(int $mensagemId, int $usuarioId, string $emoji): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO MensagemReacoes (mensagemId, usuarioId, emoji) VALUES (:mensagemId, :usuarioId, :emoji)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':mensagemId' => $mensagemId,
            ':usuarioId' => $usuarioId,
            ':emoji' => $emoji,
        ]);
    }

    public static function removerReacao(int $mensagemId, int $usuarioId, string $emoji): bool
    {
        $pdo = Database::getConnection();
        $sql = "DELETE FROM MensagemReacoes WHERE mensagemId = :mensagemId AND usuarioId = :usuarioId AND emoji = :emoji";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':mensagemId' => $mensagemId,
            ':usuarioId' => $usuarioId,
            ':emoji' => $emoji,
        ]);
    }

    public static function buscarReacoesPorMensagem(int $mensagemId): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                emoji, 
                COUNT(emoji) as count, 
                GROUP_CONCAT(u.nomeUsuario) as usuarios
            FROM MensagemReacoes mr
            INNER JOIN Usuario u ON mr.usuarioId = u.idUsuario
            WHERE mr.mensagemId = :mensagemId
            GROUP BY emoji;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':mensagemId' => $mensagemId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}