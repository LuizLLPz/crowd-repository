<?php

namespace api\controllers;

use models\Campanha;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class ProjetoController extends ControllerBase
{
    #[HttpGet('/projeto')]
    public function obter(): void
    {
        $idProjeto = $_GET["idProjeto"];
        $resp = Campanha::obterProjeto($idProjeto);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/projetos')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $categoriaRaw = $_GET['idCategoria'] ?? null;
        $categoria = ($categoriaRaw === '' ? null : (int) $categoriaRaw);
        $projetosUsuario = $_GET['projetosUsuario'];
        $projetosUsuarioBool = filter_var($projetosUsuario, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $idUsuario = $projetosUsuarioBool ? ControllerBase::$usuarioAutenticado->idUsuario : null;
        $resp = Campanha::buscarProjetos($pesquisa, $categoria, $idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/projeto')]
    public function salvar(Campanha $projeto): void
    {
        $projeto->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $url = Campanha::criar_campanha($projeto);
        $link = new Link(LinkRel::SELF, $url, "Projeto criado");
        $links = array($link);
        echo json_encode(['message' => "Projeto criado com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/projeto/aprovar')]
    public function aprovarProjeto(Campanha $projeto): void
    {
        Campanha::aprovarProjeto($projeto->idProjeto, $projeto->status, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Projeto aprovado"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}