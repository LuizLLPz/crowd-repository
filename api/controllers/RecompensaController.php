<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Recompensa;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class RecompensaController extends ControllerBase
{

    #[HttpGet('/buscarrecompensaporidcampanha')]
    public function buscarEnvolvidoPorIdCampanha(): void
    {
        $idCampanha = $_GET['idCampanha'];
        $resp = Recompensa::buscarRecompensaPorIdCampanha($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}