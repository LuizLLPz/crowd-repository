<?php

namespace api\controllers;

use models\Comentario;
use models\Novidade;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class ComentarioController extends ControllerBase
{
    #[HttpGet('/comentarios')]
    public function listar(): void
    {
        $idCampanha = $_GET['idNovidade'];
        $resp = Novidade::listar($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade')]
    public function salvar(Comentario $comentario): void
    {
        $url = Comentario::criar_comentario($comentario, ControllerBase::$usuarioAutenticado->idUsuario);
        $link = new Link(LinkRel::SELF, $url, "Comentário criado");
        $links = array($link);
        echo json_encode(['message' => "Comentario feito com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}