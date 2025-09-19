<?php

namespace api\controllers;

use models\Campanha;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
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
        $campanhasUsuario = $_GET['campanhasUsuario'];
        $administrador = ControllerBase::$usuarioAutenticado->funcao == FuncaoUsuario::ADMIN;
        $campanhasUsuarioBool = filter_var($campanhasUsuario, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $idUsuario = $campanhasUsuarioBool ? ControllerBase::$usuarioAutenticado->idUsuario : null;
        $resp = Campanha::buscar_campanhas($administrador, $pesquisa, $categoria, $idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/campanha')]
    public function salvar(Campanha $campanha): void
    {
        $campanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $url = CampanhaService::criar_campanha($campanha);
        $link = new Link(LinkRel::SELF, $url, "campanha criado");
        $links = array($link);
        echo json_encode(['message' => "campanha criado com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPut('/campanha')]
    public function editar_put(Campanha $campanha): void
    {
        $campanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $msg = CampanhaService::editar_campanha($campanha);
        echo json_encode(['data' => ['message' => $msg]], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/campanha/reprovar')]
    public function reprovar_campanha(Campanha $campanha): void
    {
        CampanhaService::reprovar_campanha($campanha->idCampanha, $campanha->status, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "C   ampanha reprovada"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/campanha/aprovar')]
    public function aprovar_campanha(Campanha $campanha): void
    {
        CampanhaService::aprovar_campanha($campanha->idCampanha, $campanha->status, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Campanha aprovada"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/campanha/desativar')]
    public function desativar_campanha(Campanha $campanha): void
    {
        CampanhaService::desativar_campanha($campanha->idCampanha, $campanha->status, idAtendente: ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Campanha desativada"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}