<?php
namespace services\integrations;

use GuzzleHttp\Client;

class SocketService
{
    public static function notificar(array $notificacoes): void
    {
        if (empty($notificacoes)) {
            return;
        }

        $internalApiUrl = $_ENV['SOCKET_INTERNAL_API_URL'] ?? 'http://localhost:8081/notify';

        try {
            $client = new Client(['timeout' => 2.0]);

            $payload = array_map(function($noti) { return (array)$noti; }, $notificacoes);

            $client->post($internalApiUrl, [ 'json' => $payload ]);

        } catch (\Throwable $e) {
            error_log('#### ERRO CRÍTICO NO SOCKETSERVICE ####');
            error_log('Mensagem: ' . $e->getMessage());
            error_log('Arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine());
        }
    }
}