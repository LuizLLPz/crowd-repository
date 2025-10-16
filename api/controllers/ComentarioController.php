<?php

namespace api\controllers;

use models\social\Comentario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpDelete;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\File;
use modules\core\utils\Http;

class ComentarioController extends ControllerBase
{

    #[HttpPost(path: '/novidade/comentario')]
    public function criarComentario(): void
    {
        $comentario = new Comentario();
        $comentario->idNovidade = $_POST['idNovidade'];
        $comentario->comentario = $_POST['comentario'];
        $comentario->idComentarioReferenciado = $_POST['idComentarioReferenciado'] !== 'null' ? $_POST['idComentarioReferenciado'] : null;

        if (isset($_FILES['imagem'])) {
            $file = new File();
            $file->name = $_FILES['imagem']['name'];
            $file->tmp_name = $_FILES['imagem']['tmp_name'];
            $file->type = $_FILES['imagem']['type'];
            $comentario->caminhoImagem = $file->salvar();
        }

        $idUsuario = parent::$usuarioAutenticado->idUsuario;
        $result = Comentario::criar_comentario($comentario, $idUsuario);
        Http::HttpResponse(201, 'Comentário criado com sucesso', json_decode($result));
    }

    #[HttpGet(path: '/novidade/comentarios')]
    public function obterComentarios(): void
    {
        $idNovidade = $_GET['idNovidade'] ?? null;
        if (!$idNovidade) {
            Http::HttpResponse(400, 'ID da novidade é obrigatório.');
            return;
        }
        $idUsuario = parent::$usuarioAutenticado->idUsuario ?? 0;

        $comentarios = Comentario::listar((int)$idNovidade, $idUsuario);
        Http::HttpResponse(200, 'Comentários buscados com sucesso', $comentarios);
    }

    #[HttpGet(path: '/comentario')]
    public function obterComentarioPorId(): void
    {
        $idComentario = $_GET['id'] ?? null;
        if (!$idComentario) {
            Http::HttpResponse(400, 'ID do comentário é obrigatório.');
            return;
        }
        $idUsuario = parent::$usuarioAutenticado->idUsuario ?? 0;

        $comentarioTree = Comentario::buscarTreePorId((int)$idComentario, $idUsuario);
        Http::HttpResponse(200, 'Comentário buscado com sucesso', $comentarioTree);
    }

    #[HttpPost(path: '/novidade/comentario/curtir')]
    public function curtirComentario(Comentario $comentario): void
    {
        $idUsuario = parent::$usuarioAutenticado->idUsuario;
        Comentario::curtir($comentario->id, $idUsuario);
        Http::HttpResponse(200, 'Comentário curtido com sucesso');
    }
}
