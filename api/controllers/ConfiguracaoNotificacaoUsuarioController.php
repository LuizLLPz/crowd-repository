<?php

namespace api\controllers;

use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPut;
use modules\core\utils\Http;
use services\core\ConfiguracaoNotificacaoUsuarioService;

class ConfiguracaoNotificacaoUsuarioController extends ControllerBase
{
    #[HttpGet('/usuarios/configuracoes-notificacao')]
    public function obterPorUsuario(): void
    {
        $idUsuario = $_GET['idUsuario'] ?? null;
        if (!$idUsuario) {
            Http::HttpResponse(400, 'ID do usuário não fornecido.');
            return;
        }
        $configuracoes = ConfiguracaoNotificacaoUsuarioService::obterPorUsuario((int)$idUsuario);
        Http::HttpResponse(200, 'Configurações de notificação obtidas com sucesso', $configuracoes);
    }

    #[HttpPut('/usuarios/configuracoes-notificacao')]
    public function salvar(): void
    {
        $idUsuario = $_GET['idUsuario'] ?? null;
        if (!$idUsuario) {
            Http::HttpResponse(400, 'ID do usuário não fornecido.');
            return;
        }
        $configs = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Http::HttpResponse(400, 'JSON inválido');
        }

        ConfiguracaoNotificacaoUsuarioService::salvar($idUsuario, $configs);

    }
}
