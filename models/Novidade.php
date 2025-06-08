<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\core\utils\File;
use modules\db\Database;

class Novidade extends Entidade
{
    public string $nomeTabela = "Novidade";

    public int $id;
    public string $titulo;
    public string $descricao;
    public string $imagem;
    public int $quantidadeLikes;

    public static function listar(int $idProjeto) {
        $pdo = Database::getConnection();

        $sqlString = (new Novidade()->select) . " WHERE idProjeto = :idProjeto ORDER BY dataCriacao DESC";
        $stmt = $pdo->prepare($sqlString);
        $stmt->execute([':idProjeto' => $idProjeto]);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;
        $novidades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($novidades as &$novidade) {
            if (!empty($novidade['imagem'])) {
                $novidade['imagem'] = $baseUrl . '/' . $novidade['imagem'];
            }
        }
        return $novidades;
    }

    public static function criar_noticia(Novidade $novidade, int $idProjeto, int $idUsuario): string {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO Novidade (idProjeto, idUsuario, titulo, descricao, dataCriacao) 
            VALUES (:idProjeto, :idUsuario, :titulo, :descricao, now())";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':idProjeto' => $idProjeto,
                ':idUsuario' => $idUsuario,
                ':titulo' => $novidade->titulo,
                ':descricao' => $novidade->descricao,
            ]);
            $novidade->id = $pdo->lastInsertId();

            if (isset($_FILES['imagem'])) {
                $imagemProjeto = $_FILES['imagem'];
                $nomeArquivo = "projeto-{$idProjeto}-novidade-{$novidade->id}.".pathinfo($imagemProjeto['name'], PATHINFO_EXTENSION);;
                $resultadoUpload = File::salvarImagem($imagemProjeto, $nomeArquivo);
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

            $pdo->commit();
        }
        catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        return "{$_ENV["CORS_ORIGIN"]}/projeto/{$novidade->id}";
    }
}