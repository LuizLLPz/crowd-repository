<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

class Envolvido extends Entidade
{
    public int $idEnvolvido;
    public int $idCampanha;
    public int $idUsuario;
    public string $nomeTabela = "Envolvido";
    public string $papel;

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Envolvido[]
     */
    public static function buscarEnvolvidoPorIdCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                e.idEnvolvido,
                e.idCampanha,
                e.idUsuario,
                e.papel,
                u.nomeUsuario,
                u.telefone,
                u.linkedin,
                u.github,
                u.instagram,
                u.caminhoImagem,
                u.descricao,
                c.titulo AS cargo
            FROM Envolvidos e
            INNER JOIN Usuario u 
                ON e.idUsuario = u.idUsuario
            LEFT JOIN Cargo c
                ON u.idCargo = c.id
            WHERE e.idCampanha = :idCampanha;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }



}