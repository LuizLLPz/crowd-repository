<?php

namespace services\core;

use models\core\ConfiguracaoNotificacaoUsuario;


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
}
