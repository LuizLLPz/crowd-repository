<?php

namespace models\campanha;

use models\campanha\enums\MotivoDenuncia;
use modules\core\tipos\Entidade;
use modules\db\Database;

class Denuncia extends Entidade
{

    public string $nomeTabela = "Denuncia";

    public int $idCampanha;
    public int $idUsuario = 0;
    public string $motivoDenuncia;
    public string $descDenuncia;
    public string $comentario;

    public static function denunciarCampanha(Denuncia $denuncia): string {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO Denuncia (idCampanha, idUsuario, motivoDenuncia, comentario) VALUES (:idDenuncia, :idUsuario, :motivoDenuncia, :comentario)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idDenuncia' => $denuncia->idCampanha,
            ':idUsuario' => $denuncia->idUsuario,
            ':motivoDenuncia' => $denuncia->motivoDenuncia,
            ':comentario' => $denuncia->comentario
        ]);

        return "Denúncia registrada com sucesso!";
    }

    public static function buscarDenunciaUsuarioCampanha(int $idUsuario, int $idCampanha): bool {
        $pdo = Database::getConnection();

        $sql = "SELECT COUNT(*) FROM Denuncia WHERE idUsuario = :idUsuario AND idCampanha = :idCampanha";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idCampanha' => $idCampanha
        ]);
        return $stmt->fetchColumn() > 0;

    }
}
