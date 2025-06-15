<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\core\utils\File;
use modules\core\utils\Utils;
use modules\db\Database;
use function modules\core\utils\getServerUrl;

class Projeto extends Entidade
{
    public string $nomeTabela = "Projeto";
    public int $idProjeto = 0;
    public int $idUsuario = 0;
    public string $titulo;
    public int $status = 0;
    public int $idCategoria;
    public string $categoria = "";
    public string $roadmap;
    public string $caminhoImagem = '';
    public int $metaArrecadacao;
    public int $valorArrecadado = 0;
    public string $telefone = '';
    public string $linkedin = '';
    public string $email = '';
    public string $github = '';
    public string $instagram = '';

    public static function buscarProjetos(?string $pesquisa = null, ?int $idCategoria = null, ?int $idUsuario = null): array {
        $pdo = Database::getConnection();

        $sql = "SELECT P.*, C.titulo AS categoria 
            FROM Projeto P 
            LEFT JOIN Categoria C ON C.id = P.idCategoria ";

        $where = [];
        $params = [];

        if ($pesquisa) {
            $where[] = "P.titulo LIKE :pesquisa";
            $params[':pesquisa'] = '%' . $pesquisa . '%';
        }

        if ($idCategoria) {
            $where[] = "P.idCategoria = :idCategoria";
            $params[':idCategoria'] = $idCategoria;
        }

        if ($idUsuario) {
            $where[] = "P.idUsuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY dataCriacao desc ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $projetos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($projetos as &$projeto) {
            if (!empty($projeto['caminhoImagem'])) {
                $projeto['caminhoImagem'] = Utils::getServerUrl() . '/' . ltrim($projeto['caminhoImagem'], '/');
            }
        }

        return $projetos;
    }


    public static function obterProjeto(int $idProjeto): array {
        $pdo = Database::getConnection();
        $sql = "SELECT P.*, C.titulo AS categoria FROM Projeto P 
         LEFT JOIN Categoria C ON C.id = P.idCategoria
         WHERE idProjeto = :idProjeto ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":idProjeto" => $idProjeto]);
        $projeto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!empty($projeto['caminhoImagem'])) {
            $projeto['caminhoImagem'] = Utils::getServerUrl() . '/' . $projeto['caminhoImagem'];
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
                    idCategoria, 
                    metaArrecadacao, 
                    valorArrecadado, 
                    telefone, 
                    linkedin, 
                    email, 
                    github, 
                    instagram,
                    caminhoImagem,
                    status,
                    dataCriacao
                ) VALUES (
                    :idUsuario,
                    :titulo, 
                    :roadmap, 
                    :idCategoria, 
                    :metaArrecadacao, 
                    :valorArrecadado, 
                    :telefone, 
                    :linkedin, 
                    :email, 
                    :github, 
                    :instagram,
                    :caminhoImagem,
                    3,     
                    now()
        )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':idUsuario'        => $projeto->idUsuario,
                ':titulo'           => $projeto->titulo,
                ':roadmap'          => $projeto->roadmap,
                ':idCategoria'      => $projeto->idCategoria,
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
                    throw new \Exception("Falha no upload da imagem: {$resultadoUpload["message"]}");
                }
            }

            $pdo->commit();

            return "{$_ENV["CORS_ORIGIN"]}/projeto/{$projeto->idProjeto}";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function aprovarProjeto(int $idProjeto, int $statusAntigo, int $idAprovador): string
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE Projeto SET status = 1 WHERE idProjeto = :idProjeto");
            $stmt->execute([
                ':idProjeto' => $idProjeto
            ]);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idProjeto;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 1;
            $historico->idCriador = $idAprovador;
            $historico->descricao = "Projeto aprovado";

            HistoricoCampanha::salvarHistorico($historico);

            $pdo->commit();

            return "Projeto aprovado";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}