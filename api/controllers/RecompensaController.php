<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Recompensa;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpDelete;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\atributos\HttpPut;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
use modules\core\utils\Http;

class RecompensaController extends ControllerBase
{

    #[HttpGet('/buscarrecompensaporidcampanha')]
    public function buscarEnvolvidoPorIdCampanha(): void
    {
        $idCampanha = $_GET['idCampanha'];
        $resp = Recompensa::buscarRecompensaPorIdCampanha($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/recompensa')]
    public function adicionarRecompensa(Recompensa $recompensa): void
    {
        Recompensa::adicionarRecompensa($recompensa, self::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(201, "Recompensa adicionada com sucesso!");
    }

    #[HttpPut('/recompensa')]
    public function atualizarRecompensa(Recompensa $recompensa): void
    {
        Recompensa::atualizarRecompensa($recompensa, self::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Recompensa atualizada com sucesso!");
    }

    #[HttpDelete('/recompensa')]
    public function removerRecompensa(): void
    {
        $idRecompensa = $_GET['idRecompensa'];
        Recompensa::removerRecompensa($idRecompensa, self::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Recompensa removida com sucesso!");
    }
}