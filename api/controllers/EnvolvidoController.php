<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Envolvido;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpDelete;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
use modules\core\utils\Http;

class EnvolvidoController extends ControllerBase
{

    #[HttpGet('/buscarenvolvidoporidcampanha')]
    public function buscarEnvolvidoPorIdCampanha(): void
    {
        $idCampanha = $_GET['idCampanha'];
        $resp = Envolvido::buscarEnvolvidoPorIdCampanha($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/envolvido')]
    public function adicionarEnvolvido(Envolvido $envolvido): void
    {
        Envolvido::adicionarEnvolvido($envolvido, self::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(201, "Participante adicionado com sucesso!");
    }

    #[HttpDelete('/envolvido')]
    public function removerEnvolvido(): void
    {
        $idEnvolvido = $_GET['idEnvolvido'];
        Envolvido::removerEnvolvido($idEnvolvido, self::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Participante removido com sucesso!");
    }
}