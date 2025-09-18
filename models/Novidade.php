<?php

namespace models;

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
    public int $quantidadeLikes;
    public int $idUsuario;
    public ?string $nomeAutor = "";
    public ?string $caminhoFotoAutor = "";

    public static function obter(int $idNovidade): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT N.*, U.idUsuario as idAutor, U.nomeUsuario as nomeAutor, U.caminhoImagem AS caminhoFotoAutor FROM Novidade N JOIN Usuario U ON U.idUsuario = N.idUsuario WHERE N.id = :idNovidade");

        $stmt->execute([':idNovidade' => $idNovidade]);
        $novidade = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($novidade && !empty($novidade['imagem'])) {
            $novidade['imagem'] = Utils::getServerUrl() . '/' . $novidade['imagem'];
        }
        return $novidade;
    }

    public static function listar(int $idCampanha): array {
        $pdo = Database::getConnection();

        $sqlString = (new Novidade()->select) . " WHERE idCampanha = :idCampanha ORDER BY dataCriacao DESC";
        $stmt = $pdo->prepare($sqlString);
        $stmt->execute([':idCampanha' => $idCampanha]);
        $novidades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($novidades as &$novidade) {
            if (!empty($novidade['imagem'])) {
                $novidade['imagem'] = Utils::getServerUrl() . '/' . $novidade['imagem'];
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