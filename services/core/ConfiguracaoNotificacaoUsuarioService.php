<?php

namespace services\core;

use models\core\ConfiguracaoNotificacaoUsuario;
use models\core\Evento;
use modules\db\Database;


class ConfiguracaoNotificacaoUsuarioService
{
    public static function obterPorUsuario(int $idUsuario): array
    {
        $eventos = Evento::buscar();
        $configuracoesExistentes = ConfiguracaoNotificacaoUsuario::buscarPorUsuario($idUsuario);
        $configuracoesFormatadas = [];

        foreach ($eventos as $evento) {
            $configEncontrada = null;
            foreach ($configuracoesExistentes as $existente) {
                if ($existente['idEvento'] === $evento['id']) {
                    $configEncontrada = $existente;
                    break;
                }
            }

            $configuracoesFormatadas[] = [
                'idEvento' => $evento['id'],
                'codigoEvento' => $evento['codigo'],
                'tituloEvento' => $evento['titulo'],
                'descricaoEvento' => $evento['descricao'],
                'enviaEmail' => $configEncontrada ? $configEncontrada['enviaEmail'] : true,
                'enviaPopup' => $configEncontrada ? $configEncontrada['enviaPopup'] : true,
            ];
        }

        return $configuracoesFormatadas;
    }

    public static function salvar(int $idUsuario, array $configs): void
    {
        ConfiguracaoNotificacaoUsuario::salvarConfiguracoes($idUsuario, $configs);
    }

    public static function gerarPermissoesNotificacaoParaNovoUsuario(int $idUsuario): void
    {
        $configs = [];
        $eventos = Evento::buscar();
        foreach ($eventos as $evento) {
            $config = new ConfiguracaoNotificacaoUsuario();
            $config->idUsuario = $idUsuario;
            $config->idEvento = $evento['id'];
            $config->enviaEmail = true;
            $config->enviaPopup = true;
            $configs[] = $config;
        }
        ConfiguracaoNotificacaoUsuarioService::salvar($idUsuario, $configs);
    }
}
