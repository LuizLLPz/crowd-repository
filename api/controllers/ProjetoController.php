<?php

namespace api\controllers;

use models\Projeto;
use modules\core\atributos\HttpGet;
use modules\core\atributos\HttpPost;
use modules\core\tipos\ControllerBase;

class ProjetoController extends ControllerBase
{
    #[HttpGet('/projetos')]
    public function listar(): void
    {
        $resp = Projeto::buscarProjetos(ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
    #[HttpPost('/projeto')]
    public function salvar(Projeto $projeto): void
    {
        $projeto->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $resp = Projeto::salvarProjeto($projeto);
        echo json_encode(['message' => $resp], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}