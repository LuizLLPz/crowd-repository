<?php
namespace models\campanha;

use modules\core\tipos\Entidade;
use modules\db\Database;

class HistoricoCampanha extends Entidade
{
    public string $nomeTabela = "HistoricoCampanha";
    public int $idHistoricoCampanha = 0;
    public int $idCampanha;
    public string $statusAntigo;
    public string $statusNovo;
    public int $idCriador;
    public string $descricao;

    public static function salvarHistorico(HistoricoCampanha $historico): void
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO HistoricoCampanha (idCampanha, statusAntigo, statusNovo, idCriador, descricao, dataCriacao) 
                VALUES (:idCampanha, :statusAntigo, :statusNovo, :idCriador, :descricao, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $historico->idCampanha,
            ':statusAntigo' => $historico->statusAntigo,
            ':statusNovo' => $historico->statusNovo,
            ':idCriador' => $historico->idCriador,
            ':descricao' => $historico->descricao
        ]);
    }

    public static function listarPorCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT h.*, u.nomeUsuario as nomeCriador 
                FROM HistoricoCampanha h
                JOIN Usuario u ON h.idCriador = u.idUsuario
                WHERE h.idCampanha = :idCampanha 
                ORDER BY h.dataCriacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}