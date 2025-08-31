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
}
