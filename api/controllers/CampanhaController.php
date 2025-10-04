<?php

namespace api\controllers;

use models\campanha\Campanha;
use models\campanha\HistoricoCampanha;
use models\campanha\InscricaoCampanha;
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
        error_log('API Response: ' . print_r($resp, true));
        echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    #[HttpGet(path: '/campanha/historico')]
    public function obterHistorico(): void
    {
        $idCampanha = $_GET['idCampanha'] ?? null;
        if (!$idCampanha) {
            Http::HttpResponse(400, 'ID da campanha é obrigatório.');
            return;
        }

        $historico = HistoricoCampanha::listarPorCampanha((int)$idCampanha);
        Http::HttpResponse(200, 'Histórico da campanha buscado com sucesso.', $historico);
    }

    #[HttpGet(path: '/campanhas')]
    public function obterCampanhas(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $categoriaRaw = $_GET['idCategoria'] ?? null;
        $idUsuario = $_GET['idUsuario'] ?? null;
        $categoria = ($categoriaRaw === '' ? null : (int) $categoriaRaw);
        $campanhasUsuario = $_GET['campanhasUsuario'] ?? null;
        $campanhasApoiadas = $_GET['campanhasApoiadas'] ?? null;
        $filtroAdministrador = $_GET['filtroAdministrador'] ?? null;

        $administrador = ControllerBase::$usuarioAutenticado->funcao == FuncaoUsuario::ADMIN;

        if ($filtroAdministrador != null && !$administrador) {
            Http::HttpResponse(403, "Você não tem permissão para acessar este filtro.");
        }

        $campanhasUsuarioBool = filter_var($campanhasUsuario, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($idUsuario == null && $campanhasUsuarioBool) $idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;

        $idUsuarioApoiador = null;
        $campanhasApoiadasBool = filter_var($campanhasApoiadas, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($campanhasApoiadasBool) {
            $idUsuarioApoiador = ControllerBase::$usuarioAutenticado->idUsuario;
        }

        $resp = Campanha::buscar_campanhas($administrador, $pesquisa, $categoria, $idUsuario, $filtroAdministrador, $idUsuarioApoiador);
        Http::HttpResponse(200, "Campanhas encontradas", $resp);
    }

    #[HttpPost('/campanha')]
    public function salvar(Campanha $campanha): void
    {
        $campanha->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $path = CampanhaService::criar_campanha($campanha);
        $url = $_ENV['CORS_ORIGIN'] . $path;
        $link = new Link(LinkRel::SELF, $url, "campanha criado");
        $links = array($link);
        Http::HttpResponse(200, "Campanha criada com sucesso!", [
            'idCampanha' => $campanha->idCampanha,
            'titulo' => $campanha->titulo,
            'idUsuario' => $campanha->idUsuario
        ], $links);
    }

    #[HttpPut('/campanha')]
    public function editar(Campanha $campanha): void
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

    #[HttpGet('/campanha/apoiadores', auth: false)]
    public function obterApoiadores(): void
    {
        $idCampanha = $_GET["idCampanha"] ?? null;
        $doadores = Campanha::obter_apoiadores($idCampanha);
        Http::HttpResponse(200, "Doadores encontrados", $doadores);
    }

}