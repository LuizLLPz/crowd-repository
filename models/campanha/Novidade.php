<?php

namespace models\campanha;

use models\campanha\enums\TipoAlvo;
use models\social\Curtida;
use models\social\Usuario;
use modules\core\tipos\Entidade;
use modules\core\utils\File;
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
    public array $midias = [];
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
            if (!empty($novidade['descricao'])) {
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $novidade['descricao']);
                $images = $dom->getElementsByTagName('img');

                foreach ($images as $img) {
                    if ($img->hasAttribute('data-path')) {
                        $path = $img->getAttribute('data-path');
                        $newSignedUrl = GoogleCloudStorageService::getSignedUrl($path);
                        $img->setAttribute('src', $newSignedUrl);
                    }
                }
                $body = $dom->getElementsByTagName('body')->item(0);
                $innerHtml = '';
                foreach ($body->childNodes as $child) {
                    $innerHtml .= $dom->saveHTML($child);
                }
                $novidade['descricao'] = $innerHtml;
            }

            $novidade['midias'] = self::buscar_midias_novidade($idNovidade);
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
            if (!empty($novidade['descricao'])) {
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $novidade['descricao']);
                $images = $dom->getElementsByTagName('img');

                foreach ($images as $img) {
                    if ($img->hasAttribute('data-path')) {
                        $path = $img->getAttribute('data-path');
                        $newSignedUrl = GoogleCloudStorageService::getSignedUrl($path);
                        $img->setAttribute('src', $newSignedUrl);
                    }
                }
                $body = $dom->getElementsByTagName('body')->item(0);
                $innerHtml = '';
                foreach ($body->childNodes as $child) {
                    $innerHtml .= $dom->saveHTML($child);
                }
                $novidade['descricao'] = $innerHtml;
            }

            $novidade['midias'] = self::buscar_midias_novidade($novidade['id']);
            if (!empty($novidade['caminhoFotoAutor'])) {
                $novidade['caminhoFotoAutor'] = GoogleCloudStorageService::getSignedUrl($novidade['caminhoFotoAutor']);
            }
            $novidade['curtidaUsuario'] = Curtida::buscar_curtido_usuario($idUsuario, $novidade['id'], TipoAlvo::NOVIDADE->value);
        }
        return $novidades;
    }

    public static function criar_noticia(Novidade &$novidade, int $idUsuario): Novidade {
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

        return $novidade;
    }

    public static function editar_novidade(Novidade $novidade): void
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Novidade SET titulo = :titulo, descricao = :descricao, dataModificacao = now() WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':titulo' => $novidade->titulo,
            ':descricao' => $novidade->descricao,
            ':id' => $novidade->id,
        ]);
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

    public static function buscar_midias_novidade(int $idNovidade): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT idMidia, caminhoArquivo, tipo, isCapa FROM Midia WHERE idEntidade = :idEntidade AND tipoEntidade = 'Novidade' ORDER BY isCapa DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idEntidade' => $idNovidade]);
        $midias = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($midias as &$midia) {
            if (!empty($midia['caminhoArquivo'])) {
                $midia['caminhoArquivo'] = GoogleCloudStorageService::getSignedUrl($midia['caminhoArquivo']);
            }
        }
        return $midias;
    }

}