<?php

namespace repositories;

use modules\db\Database;
use PDO;

class DoacaoRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getDoacoesByCampanha($idCampanha) {
        $stmt = $this->pdo->prepare("SELECT * FROM Doacao WHERE idCampanha = ?");
        $stmt->execute([$idCampanha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArrecadacaoPorDia($idCampanha) {
        $stmt = $this->pdo->prepare("SELECT DATE(dataCriacao) as data, SUM(valor) as valor FROM Doacao WHERE idCampanha = ? GROUP BY DATE(dataCriacao) ORDER BY data");
        $stmt->execute([$idCampanha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApoiadoresPorDia($idCampanha) {
        $stmt = $this->pdo->prepare("SELECT DATE(dataCriacao) as data, COUNT(DISTINCT idUsuario) as quantidade FROM Doacao WHERE idCampanha = ? GROUP BY DATE(dataCriacao) ORDER BY data");
        $stmt->execute([$idCampanha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistribuicaoRecompensas($idCampanha)
    {
        $sql = "
            WITH UserDonationTotals AS (
                SELECT 
                    idUsuario,
                    SUM(valor) as totalDoado
                FROM Doacao
                WHERE idCampanha = :idCampanha AND status = 'completed'
                GROUP BY idUsuario
            )
            SELECT 
                r.nomeNivel as nomeRecompensa,
                COUNT(udt.idUsuario) as quantidade
            FROM Recompensa r
            JOIN UserDonationTotals udt ON udt.totalDoado >= r.valorDoacao
            LEFT JOIN Recompensa r2 ON udt.totalDoado >= r2.valorDoacao AND r2.valorDoacao > r.valorDoacao AND r.idCampanha = r2.idCampanha
            WHERE r.idCampanha = :idCampanha2 AND r2.id IS NULL
            GROUP BY r.nomeNivel;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha, ':idCampanha2' => $idCampanha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getListaApoiadores($idCampanha)
    {
        $sql = "
            WITH UserDonationTotals AS (
                SELECT 
                    idUsuario,
                    SUM(valor) as totalDoado,
                    MAX(dataCriacao) as ultimaDoacao
                FROM Doacao
                WHERE idCampanha = :idCampanha AND status = 'completed'
                GROUP BY idUsuario
            )
            SELECT 
                u.nomeUsuario as nome,
                udt.totalDoado as valor,
                udt.ultimaDoacao as data,
                (
                    SELECT r.nomeNivel 
                    FROM Recompensa r 
                    WHERE r.idCampanha = :idCampanha2 AND r.valorDoacao <= udt.totalDoado
                    ORDER BY r.valorDoacao DESC
                    LIMIT 1
                ) as recompensa
            FROM UserDonationTotals udt
            JOIN Usuario u ON u.idUsuario = udt.idUsuario
            ORDER BY udt.ultimaDoacao DESC;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha, ':idCampanha2' => $idCampanha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
