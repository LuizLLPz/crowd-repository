<?php

namespace api\controllers;

use models\Campanha;
use models\campanha\enums\FiltroCampanha;
use models\campanha\enums\StatusCampanha;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
use modules\core\utils\Http;
use services\campanha\CampanhaService;

class CampanhaController extends ControllerBase
{
    #[HttpGet('/campanha')]
    public function obter(): void
    {
        $idCampanha = $_GET["idCampanha"];
        $resp = Campanha::obter_campanha($idCampanha, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/campanhas')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $categoriaRaw = $_GET['idCategoria'] ?? null;
        $categoria = ($categoriaRaw === '' ? null : (int) $categoriaRaw);
        $campanhasUsuario = $_GET['campanhasUsuario'] ?? null;
        $filtroAdministrador = $_GET['filtroAdministrador'] ?? null;

        $administrador = ControllerBase::$usuarioAutenticado->funcao == FuncaoUsuario::ADMIN;

        if ($filtroAdministrador != null && !$administrador) {
            Http::HttpResponse(403, "Você não tem permissão para acessar este filtro.");
        }

        $campanhasUsuarioBool = filter_var($campanhasUsuario, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $idUsuario = $campanhasUsuarioBool ? ControllerBase::$usuarioAutenticado->idUsuario : null;

        $resp = Campanha::buscar_campanhas($administrador, $pesquisa, $categoria, $idUsuario, $filtroAdministrador);
        Http::HttpResponse(200, "Campanhas encontradas", $resp);
    }

    #[HttpPost('/campanha')]
    public function salvar(Campanha $campanha): void
    {
        $campanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $url = CampanhaService::criar_campanha($campanha);
        $link = new Link(LinkRel::SELF, $url, "campanha criado");
        $links = array($link);
        Http::HttpResponse(200, "Campanha criada com sucesso!", [
            'idCampanha' => $campanha->idCampanha,
            'titulo' => $campanha->titulo,
            'idUsuario' => $campanha->idUsuario
        ], $links);
    }

    #[HttpPut('/campanha')]
    public function editar_put(Campanha $campanha): void
    {
        $campanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $msg = CampanhaService::editar_campanha($campanha);
        Http::HttpResponse(200, $msg);
    }

    #[HttpPost('/campanha/reprovar')]
    public function reprovar_campanha(Campanha $campanha): void
    {
        CampanhaService::reprovar_campanha($campanha->idCampanha, $campanha->status, ControllerBase::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Campanha reprovada");
    }

    #[HttpPost('/campanha/aprovar')]
    public function aprovar_campanha(Campanha $campanha): void
    {
        CampanhaService::aprovar_campanha($campanha->idCampanha, $campanha->status, ControllerBase::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Campanha aprovada");
    }

    #[HttpPost('/campanha/desativar')]
    public function desativar_campanha(Campanha $campanha): void
    {
        CampanhaService::desativar_campanha($campanha->idCampanha, $campanha->status, idAtendente: ControllerBase::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Campanha desativada");
    }

}