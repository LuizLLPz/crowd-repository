<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Categoria extends Entidade
{
    public string $nomeTabela = "Categoria";

    public string $titulo;
    public int $id;

    public static function buscarCategorias(?string $titulo = null): array {
        $pdo = Database::getConnection();

        $sql = new Categoria()->select;

        if ($titulo)  {
            $sql .= " WHERE titulo LIKE :titulo";
        }
        $stmt = $pdo->query($sql);
        if ($titulo) {
            $stmt->bindValue(':titulo', '%'.$titulo.'%');
        }
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}