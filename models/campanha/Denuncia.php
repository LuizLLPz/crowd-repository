<?php

namespace models\campanha;

use models\campanha\enums\MotivoDenuncia;
use models\campanha\enums\TipoAlvo;
use modules\core\tipos\Entidade;
use modules\db\Database;

class Denuncia extends Entidade
{

    public string $nomeTabela = "Denuncia";

    public int $idAlvo;
    public ?int $idUsuario = 0;
    public ?string $motivoDenuncia = "";
    public ?string $comentario = "";
    public ?string $caminhoImagem = "";
    public string $tipoAlvo;
    public ?string $status = "";
    public ?int $idAtendente = 0;
    public ?string $dataAtendimento = "";
    public ?string $atendimento = "";


    public static function buscar_denuncia_objeto_usuario(Denuncia $denuncia) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT D.*, U.nomeUsuario, U.email AS emailUsuario
                                     FROM Denuncia D
                                     JOIN Usuario U ON D.idUsuario = U.idUsuario
                                     WHERE D.idAlvo = :idAlvo AND D.tipoAlvo = :tipoAlvo AND D.idUsuario = :idUsuario");
        $stmt->execute([
            ':idAlvo' => $denuncia->idAlvo,
            ':tipoAlvo' => $denuncia->tipoAlvo,
            ':idUsuario' => $denuncia->idUsuario
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function buscar_denuncias(?int $idAlvo = null, ?string $tipoAlvo = null): array {
        $pdo = Database::getConnection();

        $sql = "SELECT D.*, CONCAT(D.tipoAlvo, '-', D.idAlvo, '-', D.idUsuario) AS id, U.nomeUsuario, U.email AS emailUsuario
                FROM Denuncia D
                JOIN Usuario U ON D.idUsuario = U.idUsuario";

        $params = [];
        $conditions = [];

        if ($idAlvo !== null) {
            $conditions[] = "D.idAlvo = :idAlvo";
            $params[':idAlvo'] = $idAlvo;
        }

        if ($tipoAlvo !== null) {
            $conditions[] = "D.tipoAlvo = :tipoAlvo";
            $params[':tipoAlvo'] = $tipoAlvo;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $denuncias = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($denuncias as &$denuncia) {
            $denuncia['motivoDenunciaLabel'] = MotivoDenuncia::from($denuncia['motivoDenuncia'])->getLabel();
        }

        return $denuncias;
    }

    public static function denunciarObjeto(Denuncia $denuncia): string {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO Denuncia (idAlvo, idUsuario, motivoDenuncia, comentario, tipoAlvo, status, dataCriacao) VALUES (:idDenuncia, :idUsuario, :motivoDenuncia, :comentario, :tipoAlvo, 'Ativa', NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idDenuncia' => $denuncia->idAlvo,
            ':idUsuario' => $denuncia->idUsuario,
            ':motivoDenuncia' => $denuncia->motivoDenuncia,
            ':comentario' => $denuncia->comentario,
            ':tipoAlvo' => $denuncia->tipoAlvo,
        ]);
        return "Denúncia registrada com sucesso!";
    }

    public static function updateCaminhoImagem(int $idUsuario, int $idAlvo, string $caminhoImagem) {
        $pdo = Database::getConnection();
        $sql = "UPDATE Denuncia SET caminhoImagem = :caminhoImagem WHERE idUsuario = :idUsuario AND idAlvo = :idAlvo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':caminhoImagem' => $caminhoImagem,
            ':idUsuario' => $idUsuario,
            ':idAlvo' => $idAlvo
        ]);
    }

    public static function buscarDenunciaUsuario(int $idUsuario, int $idAlvo, TipoAlvo $tipoAlvo) : bool {
        $pdo = Database::getConnection();

        $sql = "SELECT COUNT(*) FROM Denuncia WHERE idUsuario = :idUsuario AND idAlvo = :idAlvo AND tipoAlvo = :tipoAlvo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idAlvo' => $idAlvo,
            ':tipoAlvo' => $tipoAlvo->value
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public static function atenderDenuncia(Denuncia $denuncia) {
        $pdo = Database::getConnection();
        $sql = "UPDATE Denuncia SET idAtendente = :idAtendente, status = :status, atendimento = :atendimento, dataAtendimento = NOW() WHERE idUsuario = :idUsuario AND idAlvo = :idAlvo AND tipoAlvo = :tipoAlvo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idAtendente' => $denuncia->idAtendente,
            ':status' => $denuncia->status,
            ':atendimento' => $denuncia->atendimento,
            ':idUsuario' => $denuncia->idUsuario,
            ':idAlvo' => $denuncia->idAlvo,
            ':tipoAlvo' => $denuncia->tipoAlvo
        ]);
    }
}
