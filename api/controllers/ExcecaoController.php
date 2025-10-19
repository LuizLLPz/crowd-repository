<?php

namespace api\controllers;

use models\core\Excecao;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\atributos\HttpPut;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\core\utils\Http;

class ExcecaoController extends ControllerBase
{
    #[HttpPost('/excecao/reportar')]
    public function reportar(Excecao $excecao): void
    {
        $excecao->id_usuario_logado = self::$usuarioAutenticado->idUsuario;
        $excecao->salvarManual();
        Http::HttpResponse(201, "Exceção reportada com sucesso!");
    }

    #[HttpGet('/excecoes', auth: true, funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function buscarTodos(): void
    {
        $excecoes = Excecao::buscar_excecoes();
        Http::HttpResponse(200, "Exceções encontradas", $excecoes);
    }

    #[HttpPost('/excecao/agrupar', auth: true, funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function agruparEFinalizar(): void
    {
        $ids = json_decode(file_get_contents('php://input'), true)['ids'];
        Excecao::agrupar($ids);
        Http::HttpResponse(200, "Exceções agrupadas com sucesso!");
    }

    #[HttpPut('/excecao/status', auth: true, funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function atualizarStatus(): void
    {
        $id = $_GET['idExcecao'];
        $status = $_GET['status'];
        $justificativa = $_GET['justificativa'] ?? null;
        Excecao::updateStatus($id, $status, $justificativa);
        Http::HttpResponse(200, "Status da exceção atualizado com sucesso!");
    }
}