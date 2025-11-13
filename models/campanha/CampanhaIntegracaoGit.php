<?php

namespace models\campanha;

use modules\core\tipos\Entidade;
use modules\db\Database;

class CampanhaIntegracaoGit extends Entidade
{
    public string $nomeTabela = "CampanhaIntegracaoGit";
    public int $id;
    public int $idCampanha;
    public string $urlRepositorio;
    public string $dataCadastro;

    public static function salvar(CampanhaIntegracaoGit $integracao): void
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO CampanhaIntegracaoGit (idCampanha, urlRepositorio) VALUES (:idCampanha, :urlRepositorio)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $integracao->idCampanha,
            ':urlRepositorio' => $integracao->urlRepositorio,
        ]);
    }

    public static function buscarPorCampanha(int $idCampanha): ?self
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM CampanhaIntegracaoGit WHERE idCampanha = :idCampanha";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, self::class);
        return $stmt->fetch() ?: null;
    }
}