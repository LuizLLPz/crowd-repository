<?php

namespace api\controllers;

use models\core\Excecao;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
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

    #[HttpPut('/excecao/{id}/status', auth: true, funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function atualizarStatus(int $id, Excecao $body): void
    {
        Excecao::updateStatus($id, $body->status);
        Http::HttpResponse(200, "Status da exceção atualizado com sucesso!");
    }
}