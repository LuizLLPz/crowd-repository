<?php

namespace services\core;

use models\campanha\Campanha;
use models\core\ConfiguracaoNotificacaoUsuario;
use models\core\EmailTemplate;
use models\core\Evento;
use models\core\Excecao;
use models\core\Notificacao;
use models\social\Usuario;
use services\integrations\email\EmailService;
use services\integrations\SocketService;

class NotificacaoService
{

    public static function disparar(string $eventoCodigo, int $idUsuarioDestinatario, array $dadosContexto = [], bool $validaPermissao = true): void
    {
        $evento = Evento::buscarPorCodigo($eventoCodigo);

        if (!$evento) {
            error_log("NotificacaoService::disparar - Evento '{$eventoCodigo}' não encontrado.");
            return;
        }

        $configUsuario = ConfiguracaoNotificacaoUsuario::buscarPorUsuarioEEvento($idUsuarioDestinatario, $evento->id);

        if ($configUsuario->enviaPopup) {
            $notificacao = new Notificacao();
            $notificacao->idUsuario = $idUsuarioDestinatario;
            $notificacao->titulo = $evento->titulo;
            $notificacao->descricao = self::renderizarTemplate($evento->descricao, $dadosContexto);
            $notificacao->tipo = $eventoCodigo;
            $notificacao->idItem = $dadosContexto['idItem'] ?? null;

            switch ($eventoCodigo) {
                case 'nova-mensagem':
                    $notificacao->link = '/mensagens?chatId=' . $notificacao->idItem;
                    break;
            }

            $novaNotificacao = Notificacao::criar($notificacao);

            try {
                SocketService::notificar([$novaNotificacao]);
            } catch (\Exception $e) {
                Excecao::salvar($e);
                error_log('Erro ao enviar notificação via socket: ' . $e->getMessage());
            }
        }

        if ($configUsuario->enviaEmail || !$validaPermissao) {
            $template = EmailTemplate::buscarPorEvento($evento->id);

            if (!$template) {
                error_log("NotificacaoService::disparar - Template de email para o evento '{$eventoCodigo}' não encontrado.");
                return;
            }

            $usuario = Usuario::buscarUsuarioPorId($idUsuarioDestinatario);
            if (!$usuario) return;

            $dadosTemplate = array_merge($dadosContexto, ['nomeUsuario' => $usuario->nomeUsuario]);

            $assunto = self::renderizarTemplate($template->assunto, $dadosTemplate);
            $corpo = self::renderizarTemplate($template->corpo, $dadosTemplate);

            try {
                $emailService = new EmailService();
                $emailService->enviar($usuario->email, $usuario->nomeUsuario, $assunto, $corpo);
            } catch (\Exception $e) {
                Excecao::salvar($e);
                error_log("Erro ao enviar e-mail de notificação ({$eventoCodigo}): " . $e->getMessage());
            }
        }
    }

    private static function renderizarTemplate(string $template, array $dados): string
    {
        foreach ($dados as $chave => $valor) {
            if (is_scalar($valor)) {
                $template = str_replace('{' . $chave . '}', $valor, $template);
            }
        }
        return $template;
    }


}