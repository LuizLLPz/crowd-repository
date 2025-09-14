<?php

namespace api\controllers;

use models\Novidade;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
use services\campanha\NovidadeService;

class NovidadeController extends ControllerBase
{
    #[HttpGet('/novidades')]
    public function listar(): void
    {
        $idCampanha = $_GET['idCampanha'];
        $resp = Novidade::listar($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade')]
    public function salvar(Novidade $novidade): void
    {
        $url = NovidadeService::criar_novidade($novidade, ControllerBase::$usuarioAutenticado->idUsuario);
        $link = new Link(LinkRel::SELF, $url, "Novidade criada");
        $links = array($link);
        echo json_encode(['message' => "Novidade criada com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}