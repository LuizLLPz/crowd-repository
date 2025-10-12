<?php

namespace models\campanha;

use modules\core\tipos\Entidade;
use modules\db\Database;

class InscricaoCampanha extends Entidade
{
    public string $nomeTabela = "InscricaoCampanha";
    public int $idCampanha;
    public int $idUsuario;
    public string $status;
    public bool $enviaEmail;

    public static function inscreverUsuario(InscricaoCampanha $inscricaoCampanha): bool
    {
        $pdo = Database::getConnection();
        $sql = "";

        $inscricaoExistente = InscricaoCampanha::obterInscritoCampanhaUsuario($inscricaoCampanha->idCampanha, $inscricaoCampanha->idUsuario);

        if ($inscricaoExistente) {
            $sql = "UPDATE InscricaoCampanha SET status = 'ativa' WHERE idCampanha = :idCampanha AND idUsuario = :idUsuario";
        } else {
            $sql = "INSERT INTO InscricaoCampanha (idCampanha, idUsuario, status, dataCriacao) VALUES (:idCampanha, :idUsuario, 'ativa', NOW())";
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':idCampanha' => $inscricaoCampanha->idCampanha,
            ':idUsuario' => $inscricaoCampanha->idUsuario
        ]);
    }

    public static function desinscreverUsuario(InscricaoCampanha $inscricaoCampanha): bool
    {
        $pdo = Database::getConnection();

        $sql = "UPDATE InscricaoCampanha SET status = 'cancelada' WHERE idCampanha = :idCampanha AND idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':idCampanha' => $inscricaoCampanha->idCampanha,
            ':idUsuario' => $inscricaoCampanha->idUsuario
        ]);
    }

    public static function obterInscricoesCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM InscricaoCampanha WHERE idCampanha = :idCampanha AND status = 'ativa' ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function obterInscritoCampanhaUsuario(int $idCampanha, int $idUsuario): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM InscricaoCampanha WHERE idCampanha = :idCampanha AND idUsuario = :idUsuario ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $idCampanha,
            ':idUsuario' => $idUsuario
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : [];
    }

    public static function buscarInscritosPorCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT u.idUsuario, u.nomeUsuario, u.email
                FROM InscricaoCampanha ic
                JOIN Usuario u ON ic.idUsuario = u.idUsuario
                WHERE ic.idCampanha = :idCampanha
                  AND ic.status = 'ativa'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}