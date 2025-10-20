<?php

namespace services\core;

use models\core\ConfiguracaoNotificacaoUsuario;
use models\core\Evento;


class ConfiguracaoNotificacaoUsuarioService
{
    public static function obterPorUsuario(int $idUsuario): array
    {
        return ConfiguracaoNotificacaoUsuario::buscarPorUsuario($idUsuario);
    }

    public static function salvar(int $idUsuario, array $configs): bool
    {
        return ConfiguracaoNotificacaoUsuario::salvarConfiguracoes($idUsuario, $configs);
    }

    public static function gerarPermissoesNotificacaoParaNovoUsuario(int $idUsuario): void
    {
        $configs = [];
        $eventos = Evento::buscar();
        foreach ($eventos as $evento) {
            $config = new ConfiguracaoNotificacaoUsuario();
            $config->idUsuario = $idUsuario;
            $config->idEvento = $evento->id;
            $config->enviaEmail = true;
            $config->enviaPopup = true;
            $configs[] = $config;
        }
        ConfiguracaoNotificacaoUsuarioService::salvar($idUsuario, $configs);
    }
}
