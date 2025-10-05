<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

class Recompensa extends Entidade
{
    public int $id;
    public int $idCampanha;
    public int $nivel;
    public string $nomeTabela = "Recompensa";
    public string $nomeNivel;
    public int $valorDoacao;
    public string $vantagens;
    public string $corRecompensa;

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Recompensa[]
     */
    public static function buscarRecompensaPorIdCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                nivel,
                nomeNivel,
                valorDoacao,
                vantagens,
                corRecompensa
            FROM 
                Recompensa
            WHERE 
                idCampanha = :idCampanha;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }



}