<?php

namespace models\core;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Evento extends Entidade
{
    public string $nomeTabela = "Evento";
    public int $id;
    public string $codigo;
    public string $titulo;
    public ?string $descricao;

    public static function buscar(?string $pesquisa = null): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Evento";
        if ($pesquisa) {
            $sql .= " WHERE titulo LIKE :pesquisa OR codigo LIKE :pesquisa";
        }
        $stmt = $pdo->prepare($sql);
        if ($pesquisa) {
            $stmt->bindValue(':pesquisa', '%' . $pesquisa . '%');
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function buscarPorId(int $id): ?Evento
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM Evento WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $evento = $stmt->fetch();
        return $evento ?: null;
    }

    public static function buscarPorCodigo(string $codigo): ?Evento
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM Evento WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $evento = $stmt->fetch();
        return $evento ?: null;
    }

    public static function criar(Evento $evento): int
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Evento (codigo, titulo, descricao) VALUES (:codigo, :titulo, :descricao)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo' => $evento->codigo,
            ':titulo' => $evento->titulo,
            ':descricao' => $evento->descricao,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function atualizar(Evento $evento): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Evento SET codigo = :codigo, titulo = :titulo, descricao = :descricao WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $evento->id,
            ':codigo' => $evento->codigo,
            ':titulo' => $evento->titulo,
            ':descricao' => $evento->descricao,
        ]);
    }

    public static function deletar(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM Evento WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

