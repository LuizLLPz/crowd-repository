<?php

namespace api\controllers;

use models\campanha\InscricaoCampanha;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\Http;

class InscricaoCampanhaController extends ControllerBase
{
    #[HttpPost('/inscricaoCampanha')]
    public function inscrever(InscricaoCampanha $inscricaoCampanha): void
    {
        $inscricaoCampanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;

        $sucesso = InscricaoCampanha::inscreverUsuario($inscricaoCampanha);

        if ($sucesso) {
            Http::HttpResponse(200, "Usuário inscrito na campanha com sucesso!");
        } else {
            Http::HttpResponse(500, "Erro ao inscrever usuário na campanha.");
        }
    }

    #[HttpPost('/desinscreverCampanha')]
    public function desinscrever(InscricaoCampanha $inscricaoCampanha): void
    {
        $inscricaoCampanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;

        $sucesso = InscricaoCampanha::desinscreverUsuario($inscricaoCampanha);

        if ($sucesso) {
            Http::HttpResponse(200, "Usuário desinscrito da campanha com sucesso!");
        } else {
            Http::HttpResponse(500, "Erro ao desinscrever usuário da campanha.");
        }
    }
}
