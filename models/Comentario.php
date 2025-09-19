<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\core\utils\Utils;
use modules\db\Database;

class Comentario extends Entidade
{
    public string $nomeTabela = "Comentario";
    public ?int $idComentario = null;
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


    public static function listar(int $idNovidade): array {
        $pdo = Database::getConnection();

        $sqlString = "
            SELECT
                c.idComentario,
                c.comentario,
                c.caminhoImagem,
                c.dataCriacao,
                c.editado,
                c.indicePilha,
                c.idComentarioReferenciado,
                c.qtdCurtidas,
                COUNT(cr.idComentario) AS qtdComentarios,
                u.idUsuario AS idAutor,
                u.nomeUsuario AS nomeAutor,
                u.caminhoImagem AS caminhoFotoAutor,
                ca.titulo AS descricaoCargoAutor
            FROM
                Comentario c
            JOIN
                Usuario u ON c.idUsuario = u.idUsuario
            JOIN
                Cargo ca ON ca.id = u.idCargo
            LEFT JOIN
                Comentario cr ON c.idComentario = cr.idComentarioReferenciado
            WHERE
                c.idNovidade = :idNovidade
            GROUP BY
                c.dataCriacao, c.idComentario, u.idUsuario, u.nomeUsuario, u.caminhoImagem, ca.titulo
            ORDER BY
                c.dataCriacao;
        ";

        $stmt = $pdo->prepare($sqlString);
        $stmt->execute([':idNovidade' => $idNovidade]);
        $comentarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($comentarios as &$comentario) {
            if (!empty($comentario['caminhoImagem'])) {
                $comentario['caminhoImagem'] = Utils::getServerUrl() . '/' . $comentario['caminhoImagem'];
            }
            if (!empty($comentario['caminhoFotoAutor'])) {
                $comentario['caminhoFotoAutor'] = Utils::getServerUrl() . '/' . $comentario['caminhoFotoAutor'];
            }
        }
        return $comentarios;
    }

    public static function criar_comentario(Comentario $comentario, int $idUsuario): string {
        $pdo = Database::getConnection();
        $indicePilha = 0;
        if ($comentario->idComentarioReferenciado !== null) {
            $stmtParent = $pdo->prepare("SELECT indicePilha FROM Comentario WHERE idComentario = :idComentarioReferenciado");
            $stmtParent->execute([':idComentarioReferenciado' => $comentario->idComentarioReferenciado]);
            $parent = $stmtParent->fetch(\PDO::FETCH_ASSOC);
            if ($parent) {
                $indicePilha = $parent['indicePilha'] + 1;
            }
        }
        $comentario->indicePilha = $indicePilha;


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

        $comentario->idComentario = $pdo->lastInsertId();

        return json_encode(['message' => 'Comentário criado com sucesso!', 'idComentario' => $comentario->idComentario]);
    }

    public static function atualizar_curtidas(int $idComentario, bool $removerCurtida): void {
        $pdo = Database::getConnection();
        $sql = "";
        if ($removerCurtida) {
            $sql = "UPDATE Novidade SET qtdCurtidas = qtdCurtidas - 1 WHERE id = :idComentario";
        } else {
            $sql = "UPDATE Novidade SET qtdCurtidas = qtdCurtidas + 1 WHERE id = :idComentario";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idComentario' => $idComentario]);
    }

}