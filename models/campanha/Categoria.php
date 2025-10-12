<?php

namespace models\campanha;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Categoria extends Entidade
{
    public string $nomeTabela = "Categoria";

    public string $titulo;
    public int $id;

    public static function buscarCategorias(?string $titulo = null): array {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM Categoria";

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

    public static function buscarPorId(int $id): ?Categoria
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM Categoria WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $categoria = $stmt->fetch();
        return $categoria ?: null;
    }

    public static function criar(Categoria $categoria): int
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Categoria (titulo) VALUES (:titulo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $categoria->titulo,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function atualizar(Categoria $categoria): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Categoria SET titulo = :titulo WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $categoria->id,
            ':titulo' => $categoria->titulo,
        ]);
    }

    public static function deletar(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM Categoria WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}