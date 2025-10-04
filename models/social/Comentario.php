<?php

namespace models\social;

use modules\core\tipos\Entidade;
use modules\core\utils\Utils;
use modules\db\Database;
use services\integrations\google\GoogleCloudStorageService;

class Comentario extends Entidade
{
    public string $nomeTabela = "Comentario";
    public ?int $id = null;
    public string $comentario;
    public ?string $caminhoImagem = null;
    public int $idNovidade;
    public int $idUsuario;
    public ?int $idComentarioReferenciado = null;
    public int $indicePilha = 0;
    public bool $editado = false;
    public int $qtdCurtidas = 0;

    public ?string $nomeAutor = null;
    public ?string $caminhoFotoAutor = null;
    public ?string $descricaoCargoAutor = null;


    public static function listar(int $idNovidade, int $idUsuario): array {
        $pdo = Database::getConnection();

        $sqlString = "
            SELECT
                c.id,
                c.comentario,
                c.caminhoImagem,
                c.dataCriacao,
                c.editado,
                c.indicePilha,
                c.idComentarioReferenciado,
                c.qtdCurtidas,
                COUNT(cr.id) AS qtdComentarios,
                u.idUsuario AS idAutor,
                u.nomeUsuario AS nomeAutor,
                u.caminhoImagem AS caminhoFotoAutor,
                ca.titulo AS descricaoCargoAutor,
                (IF(ct.idUsuario IS NOT NULL, TRUE, FALSE)) AS curtidaUsuario
            FROM
                Comentario c
            JOIN
                Usuario u ON c.idUsuario = u.idUsuario
            JOIN
                Cargo ca ON ca.id = u.idCargo
            LEFT JOIN
                Comentario cr ON c.id = cr.idComentarioReferenciado
            LEFT JOIN 
                Curtida ct ON ct.idAlvo = c.id AND ct.tipoAlvo = 'Comentario' AND ct.idUsuario = :idUsuario
            WHERE
                c.idNovidade = :idNovidade
            GROUP BY
                c.dataCriacao, c.id, u.idUsuario, u.nomeUsuario, u.caminhoImagem, ca.titulo, curtidaUsuario
            ORDER BY
                c.dataCriacao;
        ";

        $stmt = $pdo->prepare($sqlString);
        $stmt->execute([':idNovidade' => $idNovidade, ':idUsuario' => $idUsuario]);
        $comentarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($comentarios as &$comentario) {
            if (!empty($comentario['caminhoImagem'])) {
                $comentario['caminhoImagem'] = GoogleCloudStorageService::getSignedUrl($comentario['caminhoImagem']);
            }
            if (!empty($comentario['caminhoFotoAutor'])) {
                $comentario['caminhoFotoAutor'] = GoogleCloudStorageService::getSignedUrl($comentario['caminhoFotoAutor']);
            }
        }
        return $comentarios;
    }

    public static function criar_comentario(Comentario $comentario, int $idUsuario): string {
        $pdo = Database::getConnection();
        $indicePilha = 0;
        if ($comentario->idComentarioReferenciado !== null) {
            $stmtParent = $pdo->prepare("SELECT indicePilha FROM Comentario WHERE id = :idComentarioReferenciado");
            $stmtParent->execute([':idComentarioReferenciado' => $comentario->idComentarioReferenciado]);
            $parent = $stmtParent->fetch(\PDO::FETCH_ASSOC);
            if ($parent) {
                $indicePilha = $parent['indicePilha'] + 1;
            }
        }
        $comentario->indicePilha = $indicePilha;

        if (!isset($comentario->comentario) || $comentario->comentario === 'undefined') {
            $comentario->comentario = '';
        }



        $sql = "
            INSERT INTO Comentario 
            (idNovidade, idUsuario, comentario, caminhoImagem, idComentarioReferenciado, indicePilha, dataCriacao) 
            VALUES 
            (:idNovidade, :idUsuario, :comentario, :caminhoImagem, :idComentarioReferenciado, :indicePilha, now())
        ";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':idNovidade' => $comentario->idNovidade,
            ':idUsuario' => $idUsuario,
            ':comentario' => $comentario->comentario,
            ':caminhoImagem' => $comentario->caminhoImagem,
            ':idComentarioReferenciado' => $comentario->idComentarioReferenciado,
            ':indicePilha' => $comentario->indicePilha,
        ]);

        $comentario->id = $pdo->lastInsertId();

        return json_encode(['message' => 'Comentário criado com sucesso!', '$id' => $comentario->id]);
    }

    public static function obter_idUsuario(int $idComentario) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT idUsuario FROM Comentario WHERE id = :idComentario");
        $stmt->execute([':idComentario' => $idComentario]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['idUsuario'] : null;
    }

}