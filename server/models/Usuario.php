<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Usuario extends Entidade
{
    public string $nomeTabela = "Usuario";
    public int $idUsuario;
    public string $nome;


    /**
     * @return Usuario[]
     */
    public static function buscarUsuarios(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(new Usuario()->select);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC, Usuario::class);
    }

}