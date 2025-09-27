<?php
namespace models\social;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Curtida extends Entidade
{
    public string $nomeTabela = "Curtida";
    public int $idUsuario;
    public int $idAlvo;
    public string $tipoAlvo;

    public static function buscar_curtido_usuario(int $idUsuario, string $idAlvo, string $tipoAlvo): bool {

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Curtida WHERE idAlvo = :idAlvo AND idUsuario = :idUsuario AND tipoAlvo = :tipoAlvo");
        $stmt->execute([':idAlvo' => $idAlvo, ':idUsuario' => $idUsuario, ':tipoAlvo' => $tipoAlvo]);
        return $stmt->fetchColumn();
    }

    public static function salvar_curtida(Curtida $curtida): bool
    {
        $pdo = Database::getConnection();
        $removerCurtida = self::buscar_curtido_usuario($curtida->idUsuario, $curtida->idAlvo, $curtida->tipoAlvo);

        if ($removerCurtida) {
            $stmt = $pdo->prepare("DELETE FROM Curtida WHERE idAlvo = :idAlvo AND idUsuario = :idUsuario AND tipoAlvo = :tipoAlvo");
            $stmt->execute([':idAlvo' => $curtida->idAlvo, ':idUsuario' => $curtida->idUsuario, ':tipoAlvo' => $curtida->tipoAlvo]);
            $stmt = $pdo->prepare("UPDATE {$curtida->tipoAlvo} SET qtdCurtidas = qtdCurtidas - 1 WHERE id = :idAlvo");
            $stmt->execute([':idAlvo' => $curtida->idAlvo]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO Curtida (idAlvo, idUsuario, tipoAlvo) VALUES (:idAlvo, :idUsuario, :tipoAlvo)");
            $stmt->execute([':idAlvo' => $curtida->idAlvo, ':idUsuario' => $curtida->idUsuario, ':tipoAlvo' => $curtida->tipoAlvo]);
            $stmt = $pdo->prepare("UPDATE {$curtida->tipoAlvo} SET qtdCurtidas = qtdCurtidas + 1 WHERE id = :idAlvo");
            $stmt->execute([':idAlvo' => $curtida->idAlvo]);
        }
        return $removerCurtida;
    }
}
