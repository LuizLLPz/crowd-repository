<?php

require_once __DIR__ . '/../vendor/autoload.php';

use modules\db\Database;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function encerrarCampanhasExpiradas() {
    try {
        $pdo = Database::getConnection();
        
        $hoje = date('Y-m-d');
        $sql = "SELECT idCampanha FROM Campanha WHERE status = 1 AND dataFinal IS NOT NULL AND dataFinal < ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hoje]);
        
        $campanhasParaEncerrar = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($campanhasParaEncerrar)) {
            echo "Nenhuma campanha para encerrar hoje.\n";
            return;
        }

        $updateSql = "UPDATE Campanha SET status = 5 WHERE idCampanha = ?";
        $updateStmt = $pdo->prepare($updateSql);
        
        $count = 0;
        foreach ($campanhasParaEncerrar as $idCampanha) {
            $updateStmt->execute([$idCampanha]);
            $count++;
        }
        
        echo "Processo finalizado. {$count} campanha(s) encerrada(s).\n";
        
    } catch (\Exception $e) {
        error_log("Erro ao encerrar campanhas: " . $e->getMessage());
        echo "Ocorreu um erro durante o processo.\n";
    }
}

echo "Iniciando verificação de campanhas expiradas...\n";
encerrarCampanhasExpiradas();


