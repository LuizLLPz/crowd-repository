<?php
namespace models\core;

use modules\db\Database;
use PDO;

class Notificacao
{
    const TIPO_NOVA_NOVIDADE = 'nova_novidade';
    const TIPO_NOVO_APOIO = 'novo_apoio';
    const TIPO_NOVO_COMENTARIO = 'novo_comentario';
    const TIPO_NOVA_MENSAGEM_CHAT = 'nova_mensagem_chat';

    public int $idNotificacao;
    public int $idUsuario;
    public string $titulo;
    public string $descricao;
    public string $tipo;
    public bool $lida = false;
    public ?string $link = null;
    public ?int $idItem = null;

    public static function criar(Notificacao $notificacao): Notificacao
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Notificacao (idUsuario, titulo, descricao, tipo, lida, idItem, dataCriacao) 
                VALUES (:idUsuario, :titulo, :descricao, :tipo, :lida, :idItem, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $notificacao->idUsuario,
            ':titulo' => $notificacao->titulo,
            ':descricao' => $notificacao->descricao,
            ':tipo' => $notificacao->tipo,
            ':lida' => (int)$notificacao->lida,
            ':idItem' => $notificacao->idItem
        ]);

        $notificacao->idNotificacao = $pdo->lastInsertId();
        return $notificacao;
    }

    public static function buscarPorUsuario(int $idUsuario, ?bool $lido = null, int $limite = 20): array
    {
        $pdo = Database::getConnection();
        $params = [':idUsuario' => $idUsuario];

        $sql = "SELECT * FROM Notificacao WHERE idUsuario = :idUsuario";

        if ($lido !== null) {
            $sql .= " AND lido = :lido";
            $params[':lido'] = (int)$lido;
        }

        $sql .= " ORDER BY dataCriacao DESC LIMIT :limite";
        $params[':limite'] = $limite;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function marcarComoLida(int $idNotificacao, int $idUsuario): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Notificacao SET lida = TRUE WHERE idNotificacao = :idNotificacao AND idUsuario = :idUsuario";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':idNotificacao' => $idNotificacao,
            ':idUsuario' => $idUsuario
        ]);
    }

    public static function marcarTodasComoLidas(int $idUsuario): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Notificacao SET lida = TRUE WHERE idUsuario = :idUsuario AND lida = FALSE";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':idUsuario' => $idUsuario]);
    }
}