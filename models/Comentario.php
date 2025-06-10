<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Comentario extends Entidade
{
    public string $nomeTabela = "Comentario";

    public int $id;
    public int $idNovidade;
    public string $comentario;
    public int $quantidadeLikes;

    public static function listar(int $idNovidade): array {
        $pdo = Database::getConnection();

        $sqlString = (new Comentario()->select) . " WHERE idNovidade = :idNovidade ORDER BY dataCriacao DESC";
        $stmt = $pdo->prepare($sqlString);
        $stmt->execute([':idNovidade' => $idNovidade]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function criar_comentario(Comentario $comentario, int $idUsuario): string {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Comentario (idNovidade, idUsuario, comentario, dataCriacao) 
        VALUES (:idNovidade, :idUsuario, :comentario, now())";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':idNovidade' => $comentario->idNovidade,
            ':idUsuario' => $idUsuario,
            ':comentario' => $comentario->comentario,
        ]);
        $comentario->id = $pdo->lastInsertId();

        $pdo->commit();

        return "{$_ENV["CORS_ORIGIN"]}/novidade/{comentario->id}/";
    }
}