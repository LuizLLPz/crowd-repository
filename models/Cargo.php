<?php

namespace models;

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

        $sql = new Cargo()->select;

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

?>