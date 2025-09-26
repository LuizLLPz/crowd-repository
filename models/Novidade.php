<?php

namespace models;

use models\campanha\enums\TipoAlvo;
use modules\core\tipos\Entidade;
use modules\core\utils\File;
use modules\core\utils\Utils;
use modules\db\Database;
use services\integrations\google\GoogleCloudStorageService;
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
        $stmt = $pdo->prepare("SELECT N.*, U.idUsuario as idAutor, U.nomeUsuario as nomeAutor, U.caminhoImagem AS caminhoFotoAutor, C.titulo AS descCargoAutor,\n                                     (SELECT COUNT(*) FROM Comentario C WHERE C.idNovidade = N.id) AS qtdComentarios\n                                     FROM Novidade N \n                                     JOIN Usuario U ON U.idUsuario = N.idUsuario JOIN Cargo C ON C.id = U.idCargo\n                                     WHERE N.id = :idNovidade");

        $stmt->execute([':idNovidade' => $idNovidade]);
        $novidade = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($novidade) {
            if (!empty($novidade['imagem'])) {
                $novidade['imagem'] = GoogleCloudStorageService::getSignedUrl($novidade['imagem']);
            }
            if (!empty($novidade['caminhoFotoAutor'])) {
                $novidade['caminhoFotoAutor'] = GoogleCloudStorageService::getSignedUrl($novidade['caminhoFotoAutor']);
            }
            $novidade['curtidaUsuario'] = Curtida::buscar_curtido_usuario($idUsuario, $novidade['id'], TipoAlvo::NOVIDADE->value);
        }

        return $novidade;
    }

    public static function listar(int $idCampanha, int $idUsuario): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT N.*, U.idUsuario as idAutor, U.nomeUsuario as nomeAutor, U.caminhoImagem AS caminhoFotoAutor, C.titulo AS descCargoAutor, \n                                     (SELECT COUNT(*) FROM Comentario C WHERE C.idNovidade = N.id) AS qtdComentarios\n                                     FROM Novidade N JOIN Usuario U ON U.idUsuario = N.idUsuario JOIN Cargo C ON C.id = U.idCargo\n                                     WHERE N.idCampanha = :idCampanha AND N.status = 'Ativa' ORDER BY N.dataCriacao DESC");
        $stmt->execute([':idCampanha' => $idCampanha]);
        $novidades = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($novidades as &$novidade) {
            if (!empty($novidade['imagem'])) {
                $novidade['imagem'] = GoogleCloudStorageService::getSignedUrl($novidade['imagem']);
            }
            if (!empty($novidade['caminhoFotoAutor'])) {
                $novidade['caminhoFotoAutor'] = GoogleCloudStorageService::getSignedUrl($novidade['caminhoFotoAutor']);
            }
            $novidade['curtidaUsuario'] = Curtida::buscar_curtido_usuario($idUsuario, $novidade['id'], TipoAlvo::NOVIDADE->value);
        }
        return $novidades;
    }

    public static function criar_noticia(Novidade $novidade, int $idUsuario, ?array $imagemFile = null): Novidade {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Novidade (idCampanha, idUsuario, titulo, descricao, dataCriacao, status) 
        VALUES (:idCampanha, :idUsuario, :titulo, :descricao, now(), :status)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':idCampanha' => $novidade->idCampanha,
            ':idUsuario' => $idUsuario,
            ':titulo' => $novidade->titulo,
            ':descricao' => $novidade->descricao,
            ':status' => 'Ativa',
        ]);
        $novidade->id = $pdo->lastInsertId();

        if ($imagemFile && $imagemFile['error'] === UPLOAD_ERR_OK) {
            $nomeArquivo = "campanha-{$novidade->idCampanha}-novidade-{$novidade->id}";
            $resultadoUpload = File::salvarImagem($imagemFile, $nomeArquivo);
            if ($resultadoUpload['success']) {
                $novidade->imagem = $resultadoUpload['filePath'];

                $stmtImg = $pdo->prepare("UPDATE Novidade SET imagem = :imagem WHERE id = :id");
                $stmtImg->execute([
                    ':imagem' => $novidade->imagem,
                    ':id' => $novidade->id
                ]);
            } else {
                // Optionally, you might want to roll back the transaction or handle the error
                throw new \Exception("Falha no upload da imagem: " . $resultadoUpload['message']);
            }
        }
        return $novidade;
    }

    public static function deletar(int $idNovidade, int $idUsuario): void
    {
        $nomeUsuario = Usuario::obter_nomeUsuario($idUsuario);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Novidade SET status = 'Cancelada', dataModificacao = now(), historico = CONCAT('Novidade cancelada pelo usuário ', :nomeUsuario)  WHERE id = :idNovidade");
        $stmt->execute([':idNovidade' => $idNovidade, ':nomeUsuario' => $nomeUsuario]);
    }

    public static function obter_idUsuario(int $idNovidade) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT idUsuario FROM Novidade WHERE id = :idNovidade");
        $stmt->execute([':idNovidade' => $idNovidade]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['idUsuario'] : null;
    }


}