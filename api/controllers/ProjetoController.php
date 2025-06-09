<?php

namespace api\controllers;

use models\Projeto;
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
        $resp = Projeto::obterProjeto($idProjeto);
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
        $resp = Projeto::buscarProjetos($pesquisa, $categoria, $idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/projeto')]
    public function salvar(Projeto $projeto): void
    {
        $projeto->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $url = Projeto::salvarProjeto($projeto);
        $link = new Link(LinkRel::SELF, $url, "Projeto criado");
        $links = array($link);
        echo json_encode(['message' => "Projeto criado com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}