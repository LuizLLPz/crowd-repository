<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\core\utils\File;
use modules\db\Database;

class Projeto extends Entidade
{
    public string $nomeTabela = "Projeto";
    public int $idProjeto = 0;
    public int $idUsuario = 0;
    public string $titulo;
    public string $roadmap;
    public string $caminhoImagem = '';
    public int $metaArrecadacao;
    public int $valorArrecadado = 0;
    public string $telefone = '';
    public string $linkedin = '';
    public string $email = '';
    public string $github = '';
    public string $instagram = '';

    public static function buscarProjetos(): array {
        $pdo = Database::getConnection();
        $sqlString = (new Projeto()->select) . " ORDER BY idProjeto DESC";
        $stmt = $pdo->prepare($sqlString);
        $stmt->execute();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;
        $projetos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($projetos as &$projeto) {
            if (!empty($projeto['caminhoImagem'])) {
                $projeto['caminhoImagem'] = $baseUrl . '/' . $projeto['caminhoImagem'];
            }
        }
        return $projetos;
    }

    public static function obterProjeto(int $idProjeto): array {
        $pdo = Database::getConnection();
        $sql = new Projeto()->select." WHERE idProjeto = :idProjeto";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":idProjeto" => $idProjeto]);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;
        $projeto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!empty($projeto['caminhoImagem'])) {
            $projeto['caminhoImagem'] = $baseUrl . '/' . $projeto['caminhoImagem'];
        }
        return $projeto;

    }

    public static function salvarProjeto(Projeto $projeto): string
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO Projeto (
                    idUsuario,
                    titulo, 
                    roadmap, 
                    metaArrecadacao, 
                    valorArrecadado, 
                    telefone, 
                    linkedin, 
                    email, 
                    github, 
                    instagram,
                    caminhoImagem
                ) VALUES (
                    :idUsuario,
                    :titulo, 
                    :roadmap, 
                    :metaArrecadacao, 
                    :valorArrecadado, 
                    :telefone, 
                    :linkedin, 
                    :email, 
                    :github, 
                    :instagram,
                    :caminhoImagem
        )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':idUsuario'        => $projeto->idUsuario,
                ':titulo'           => $projeto->titulo,
                ':roadmap'          => $projeto->roadmap,
                ':metaArrecadacao'  => $projeto->metaArrecadacao,
                ':valorArrecadado'  => $projeto->valorArrecadado,
                ':telefone'         => $projeto->telefone,
                ':linkedin'         => $projeto->linkedin,
                ':email'            => $projeto->email,
                ':github'           => $projeto->github,
                ':instagram'        => $projeto->instagram,
                ':caminhoImagem'    => $projeto->caminhoImagem
            ]);
            $projeto->idProjeto = $pdo->lastInsertId();

            if (isset($_FILES['imagem'])) {
                $imagemProjeto = $_FILES['imagem'];
                $nomeArquivo = "projeto-{$projeto->idProjeto}.".pathinfo($imagemProjeto['name'], PATHINFO_EXTENSION);;
                $resultadoUpload = File::salvarImagem($imagemProjeto, $nomeArquivo);
                if ($resultadoUpload['success']) {
                    $projeto->caminhoImagem = $resultadoUpload['relativePath'];

                    $stmtImg = $pdo->prepare("UPDATE Projeto SET caminhoImagem = :caminhoImagem WHERE idProjeto = :idProjeto");
                    $stmtImg->execute([
                        ':caminhoImagem' => $projeto->caminhoImagem,
                        ':idProjeto' => $projeto->idProjeto
                    ]);
                } else {
                    throw new \Exception("Falha no upload da imagem");
                }
            }

            $pdo->commit();

            return "{$_ENV["CORS_ORIGIN"]}/projeto/{$projeto->idProjeto}";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}