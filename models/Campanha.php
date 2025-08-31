<?php

namespace models;

use models\campanha\Denuncia;
use modules\core\tipos\Entidade;
use modules\core\utils\File;
use modules\core\utils\Utils;
use modules\db\Database;

class Campanha extends Entidade
{
    public string $nomeTabela = "Campanha";
    public int $idCampanha = 0;
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
    public bool $denunciadoUsuario;
    public int $qtdDenuncias;

    public static function buscarCampanhas(?string $pesquisa = null, ?int $idCategoria = null, ?int $idUsuario = null): array {
        $pdo = Database::getConnection();

        $sql = "SELECT C.*, C.titulo AS categoria 
            FROM Campanha C 
            LEFT JOIN Categoria CA ON CA.id = C.idCategoria ";

        $where = [];
        $params = [];

        if ($pesquisa) {
            $where[] = "C.titulo LIKE :pesquisa";
            $params[':pesquisa'] = '%' . $pesquisa . '%';
        }

        if ($idCategoria) {
            $where[] = "C.idCategoria = :idCategoria";
            $params[':idCategoria'] = $idCategoria;
        }

        if ($idUsuario) {
            $where[] = "C.idUsuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }

        $sql .= ", (SELECT COUNT(*) FROM Denuncia D WHERE D.idCampanha = C.idCampanha) AS quantidadeDenuncias";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }



        $sql .= " ORDER BY dataCriacao desc ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $campanhas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($campanhas as &$campanha) {
            if (!empty($campanha['caminhoImagem'])) {
                $campanha['caminhoImagem'] = Utils::getServerUrl() . '/' . ltrim($campanha['caminhoImagem'], '/');
            }
        }

        return $campanhas;
    }


    public static function obterCampanha(int $idCampanha, int $idUsuario): array {
        $pdo = Database::getConnection();
        $sql = "SELECT P.*, C.titulo AS categoria FROM Campanha P 
         LEFT JOIN Categoria C ON C.id = P.idCategoria
         WHERE idCampanha = :idCampanha ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":idCampanha" => $idCampanha]);
        $campanha = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!empty($campanha['caminhoImagem'])) {
            $campanha['caminhoImagem'] = Utils::getServerUrl() . '/' . $campanha['caminhoImagem'];
        }

        $campanha['denunciadoUsuario'] = Denuncia::buscarDenunciaUsuarioCampanha($idUsuario, $idCampanha);

        return $campanha;

    }

    public static function criar_campanha(Campanha $campanha): string
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO Campanha (
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
                ':titulo'           => $campanha->titulo,
                ':roadmap'          => $campanha->roadmap,
                ':idCategoria'      => $campanha->idCategoria,
                ':metaArrecadacao'  => $campanha->metaArrecadacao,
                ':valorArrecadado'  => $campanha->valorArrecadado,
                ':telefone'         => $campanha->telefone,
                ':linkedin'         => $campanha->linkedin,
                ':email'            => $campanha->email,
                ':github'           => $campanha->github,
                ':instagram'        => $campanha->instagram,
                ':idUsuario'        => $campanha->idUsuario,
                ':caminhoImagem'    => $campanha->caminhoImagem
            ]);
            $campanha->idCampanha = $pdo->lastInsertId();

            if (isset($_FILES['imagem'])) {
                $imagemCampanha = $_FILES['imagem'];
                $nomeArquivo = "campanha-{$campanha->idCampanha}.".pathinfo($imagemCampanha['name'], PATHINFO_EXTENSION);;
                $resultadoUpload = File::salvarImagem($imagemCampanha, $nomeArquivo);
                if ($resultadoUpload['success']) {
                    $campanha->caminhoImagem = $resultadoUpload['relativePath'];

                    $stmtImg = $pdo->prepare("UPDATE Campanha SET caminhoImagem = :caminhoImagem WHERE idCampanha = :idCampanha");
                    $stmtImg->execute([
                        ':caminhoImagem' => $campanha->caminhoImagem,
                        ':idCampanha' => $campanha->idCampanha
                    ]);
                } else {
                    throw new \Exception("Falha no upload da imagem: {$resultadoUpload["message"]}");
                }
            }

            $pdo->commit();

            return "{$_ENV["CORS_ORIGIN"]}/campanha/{$campanha->idCampanha}";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function aprovarCampanha(int $idCampanha, int $statusAntigo, int $idAprovador): string
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE Campanha SET status = 1 WHERE idCampanha = :idCampanha");
            $stmt->execute([
                ':idCampanha' => $idCampanha
            ]);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 1;
            $historico->idCriador = $idAprovador;
            $historico->descricao = "Campanha aprovada";

            HistoricoCampanha::salvarHistorico($historico);

            $pdo->commit();

            return "Campanha aprovada";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}