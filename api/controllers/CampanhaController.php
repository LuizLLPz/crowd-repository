<?php

namespace api\controllers;

use models\Campanha;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class CampanhaController extends ControllerBase
{
    #[HttpGet('/campanha')]
    public function obter(): void
    {
        $idCampanha = $_GET["idCampanha"];
        $resp = Campanha::obterCampanha($idCampanha);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/campanhas')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $categoriaRaw = $_GET['idCategoria'] ?? null;
        $categoria = ($categoriaRaw === '' ? null : (int) $categoriaRaw);
        $campanhasUsuario = $_GET['campanhasUsuario'];
        $campanhasUsuarioBool = filter_var($campanhasUsuario, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $idUsuario = $campanhasUsuarioBool ? ControllerBase::$usuarioAutenticado->idUsuario : null;
        $resp = Campanha::buscarCampanhas($pesquisa, $categoria, $idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/campanha')]
    public function salvar(Campanha $campanha): void
    {
        $campanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $url = Campanha::criar_campanha($campanha);
        $link = new Link(LinkRel::SELF, $url, "campanha criado");
        $links = array($link);
        echo json_encode(['message' => "campanha criado com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/campanha/aprovar')]
    public function aprovarCampanha(Campanha $campanha): void
    {
        Campanha::aprovarCampanha($campanha->idCampanha, $campanha->status, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "campanha aprovado"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}