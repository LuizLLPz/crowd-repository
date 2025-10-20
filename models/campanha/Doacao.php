<?php

namespace models\campanha;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Doacao extends Entidade
{
    public string $nomeTabela = "doacao";
    public int $id = 0;
    public int $idCampanha = 0;
    public int $idUsuario = 0;
    public int $valor = 0;
    public string $stripeTransactionId = '';
    public string $status = '';

    public static function criar(Doacao $doacao): int
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Doacao (idCampanha, idUsuario, valor, stripeTransactionId, status, dataCriacao) VALUES (:idCampanha, :idUsuario, :valor, :stripeTransactionId, :status, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $doacao->idCampanha,
            ':idUsuario' => $doacao->idUsuario,
            ':valor' => $doacao->valor,
            ':stripeTransactionId' => $doacao->stripeTransactionId,
            ':status' => $doacao->status
        ]);
        return $pdo->lastInsertId();
    }

    public static function obter_valor_doado_usuario_campanha(int $idCampanha, int $idUsuario): int
    {
        $pdo = Database::getConnection();
        $sql = "SELECT SUM(valor) FROM Doacao WHERE idCampanha = :idCampanha AND idUsuario = :idUsuario AND status = 'completed'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha, ':idUsuario' => $idUsuario]);
        return (int) $stmt->fetchColumn();
    }
}
