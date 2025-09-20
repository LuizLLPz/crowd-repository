<?php

namespace models;

use models\campanha\enums\TipoAlvo;
use modules\core\tipos\Entidade;
use modules\core\utils\File;
use modules\core\utils\Utils;
use modules\db\Database;
use function modules\core\utils\getServerUrl;

class Novidade extends Entidade
{
    public string $nomeTabela = "Novidade";

    public int $id;
    public int $idCampanha;
    public string $titulo;
    public string $descricao;
    public string $imagem;
    public int $qtdLikes;
    public int $qtdComentarios;
    public int $idUsuario;
    public ?string $nomeAutor = "";
    public ?string $caminhoFotoAutor = "";
    public string $descCargoAutor = "";
    public bool $curtidaUsuario = false;

    public static function obter(int $idNovidade, int $idUsuario): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT N.*, U.idUsuario as idAutor, U.nomeUsuario as nomeAutor, U.caminhoImagem AS caminhoFotoAutor, C.titulo AS descCargoAutor,
                                     (SELECT COUNT(*) FROM Comentario C WHERE C.idNovidade = N.id) AS qtdComentarios
                                     FROM Novidade N 
                                     JOIN Usuario U ON U.idUsuario = N.idUsuario JOIN Cargo C ON C.id = U.idCargo
                                     WHERE N.id = :idNovidade");

        $stmt->execute([':idNovidade' => $idNovidade]);
        $novidade = $stmt->fetch(\PDO::FETCH_ASSOC);

        $novidade['curtidaUsuario'] = Curtida::buscar_curtido_usuario($idUsuario, $novidade['id'], TipoAlvo::NOVIDADE->value);
        if ($novidade && !empty($novidade['imagem'])) {
            $novidade['imagem'] = Utils::getServerUrl() . '/' . $novidade['imagem'];
        }
        if (!empty($novidade['caminhoFotoAutor'])) {
            $novidade['caminhoFotoAutor'] = Utils::getServerUrl() . '/' . $novidade['caminhoFotoAutor'];
        }
        return $novidade;
    }

    public static function listar(int $idCampanha, int $idUsuario): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT N.*, U.idUsuario as idAutor, U.nomeUsuario as nomeAutor, U.caminhoImagem AS caminhoFotoAutor, C.titulo AS descCargoAutor, 
                                     (SELECT COUNT(*) FROM Comentario C WHERE C.idNovidade = N.id) AS qtdComentarios
                                     FROM Novidade N JOIN Usuario U ON U.idUsuario = N.idUsuario JOIN Cargo C ON C.id = U.idCargo
                                     WHERE N.idCampanha = :idCampanha ORDER BY N.dataCriacao DESC");
        $stmt->execute([':idCampanha' => $idCampanha]);
        $novidades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($novidades as &$novidade) {
            $novidade['curtidaUsuario'] = Curtida::buscar_curtido_usuario($idUsuario, $novidade['id'], TipoAlvo::NOVIDADE->value);
            if (!empty($novidade['imagem'])) {
                $novidade['imagem'] = Utils::getServerUrl() . '/' . $novidade['imagem'];
            }
            if (!empty($novidade['caminhoFotoAutor'])) {
                $novidade['caminhoFotoAutor'] = Utils::getServerUrl() . '/' . $novidade['caminhoFotoAutor'];
            }
        }
        return $novidades;
    }

    public static function criar_noticia(Novidade $novidade, int $idUsuario): Novidade {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Novidade (idCampanha, idUsuario, titulo, descricao, dataCriacao) 
        VALUES (:idCampanha, :idUsuario, :titulo, :descricao, now())";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':idCampanha' => $novidade->idCampanha,
            ':idUsuario' => $idUsuario,
            ':titulo' => $novidade->titulo,
            ':descricao' => $novidade->descricao,
        ]);
        $novidade->id = $pdo->lastInsertId();

        if (isset($_FILES['imagem'])) {
            $imagemCampanha = $_FILES['imagem'];
            $nomeArquivo = "campanha-{$novidade->idCampanha}-novidade-{$novidade->id}.".pathinfo($imagemCampanha['name'], PATHINFO_EXTENSION);;
            $resultadoUpload = File::salvarImagem($imagemCampanha, $nomeArquivo);
            if ($resultadoUpload['success']) {
                $novidade->imagem = $resultadoUpload['relativePath'];

                $stmtImg = $pdo->prepare("UPDATE Novidade SET imagem = :imagem WHERE id = :id");
                $stmtImg->execute([
                    ':imagem' => $novidade->imagem,
                    ':id' => $novidade->id
                ]);
            } else {
                throw new \Exception("Falha no upload da imagem");
            }
        }
        return $novidade;
    }

}