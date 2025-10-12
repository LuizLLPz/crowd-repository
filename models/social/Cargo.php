<?php

namespace models\social;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Cargo extends Entidade
{
    public string $nomeTabela = "Cargo";

    public string $titulo;
    public int $id;
    public string $nomeIcone;
    

    public static function buscarCargos(?string $titulo = null): array {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM Cargo";

        if ($titulo)  {
            $sql .= " WHERE titulo LIKE :titulo";
        }
        $stmt = $pdo->prepare($sql);
        if ($titulo) {
            $stmt->bindValue(':titulo', '%'.$titulo.'%');
        }
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function buscarPorId(int $id): ?Cargo
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM Cargo WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $cargo = $stmt->fetch();
        return $cargo ?: null;
    }

    public static function criar(Cargo $cargo): int
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Cargo (titulo, nomeIcone) VALUES (:titulo, :nomeIcone)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $cargo->titulo,
            ':nomeIcone' => $cargo->nomeIcone,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function atualizar(Cargo $cargo): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Cargo SET titulo = :titulo, nomeIcone = :nomeIcone WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $cargo->id,
            ':titulo' => $cargo->titulo,
            ':nomeIcone' => $cargo->nomeIcone,
        ]);
    }

    public static function deletar(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM Cargo WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

?>