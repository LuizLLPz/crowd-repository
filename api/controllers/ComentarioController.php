<?php

namespace api\controllers;

use models\social\Comentario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use services\social\ComentarioService;

class ComentarioController extends ControllerBase
{
    #[HttpGet('/novidade/comentarios')]
    public function listar(): void
    {
        $idCampanha = $_GET['idNovidade'];
        $resp = Comentario::listar($idCampanha, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade/comentario')]
    public function salvar(Comentario $comentario): void
    {
        $url = ComentarioService::criar_comentario($comentario, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Comentario feito com sucesso", '_links' => ""], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade/comentario/curtir')]
    public function curtir(Comentario $comentario): void
    {
        ComentarioService::curtir_comentario($comentario->id, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Comentario curtido com sucesso"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}