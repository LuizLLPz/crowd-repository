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
    #[HttpGet('/projetos')]
    public function listar(): void
    {
        $resp = Projeto::buscarProjetos();
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