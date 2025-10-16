<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Envolvido;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class EnvolvidoController extends ControllerBase
{

    #[HttpGet('/buscarenvolvidoporidcampanha')]
    public function buscarEnvolvidoPorIdCampanha(): void
    {
        $idCampanha = $_GET['idCampanha'];
        $resp = Envolvido::buscarEnvolvidoPorIdCampanha($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}