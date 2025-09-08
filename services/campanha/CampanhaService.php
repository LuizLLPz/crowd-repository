<?php
namespace services\campanha;

use models\Campanha;
use models\Campanha\HistoricoCampanha;
use models\Notificacao;
use modules\db\Database;

class CampanhaService
{
    public static function desativarCampanha(int $idCampanha, string $statusAntigo, int $idAtendente): void
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("UPDATE Campanha SET status = 6 WHERE idCampanha = :idCampanha");
            $stmt->execute([
                ':idCampanha' => $idCampanha
            ]);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 0;
            $historico->idCriador = $idAtendente;
            $historico->descricao = "Campanha desativada pela moderação";

            HistoricoCampanha::salvarHistorico($historico);

        } catch (\Exception $e) {
            throw $e;
        }
    }
}

