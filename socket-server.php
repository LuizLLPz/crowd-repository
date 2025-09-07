<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\WebSocket\WsServer;
use modules\sockets\SocketRouter;
use modules\sockets\AuthMiddleware;
use modules\sockets\ConnectionManager;
use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

error_reporting(E_ALL & ~E_DEPRECATED);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$ws_port = $_ENV['WEBSOCKET_PORT'] ?? 8080;
$internal_api_port = $_ENV['SOCKET_INTERNAL_API_PORT'] ?? 8081;

$server = IoServer::factory(
    new RatchetHttpServer(
        new AuthMiddleware(
            new WsServer(
                new SocketRouter()
            )
        )
    ),
    $ws_port,
    '0.0.0.0'
);

echo "Servidor de WebSocket para clientes rodando na porta {$ws_port}\n";

$internalApiServer = new ReactHttpServer(
    $server->loop,
    function (ServerRequestInterface $request) {
        error_log("API INTERNA RECEBEU PAYLOAD: " . (string) $request->getBody());

        if ($request->getMethod() !== 'POST' || $request->getUri()->getPath() !== '/notify') {
            return new Response(404, ['Content-Type' => 'text/plain'], 'Not Found');
        }

        $notificacoes = json_decode((string) $request->getBody(), true);

        if (empty($notificacoes) || !is_array($notificacoes)) {
            return new Response(400, ['Content-Type' => 'text/plain'], 'Bad Request');
        }

        $connectionManager = ConnectionManager::getInstancia();
        $totalNotificadosOnline = 0;

        foreach ($notificacoes as $notificacao) {
            $usuarioId = $notificacao['idUsuario'] ?? null;
            if ($usuarioId) {
                $conexao = $connectionManager->getConexaoPorUsuarioId($usuarioId);
                if ($conexao) {
                    $payloadParaFrontend = [
                        'type'    => 'nova_notificacao',
                        'payload' => $notificacao
                    ];
                    $conexao->send(json_encode($payloadParaFrontend));
                    $totalNotificadosOnline++;
                }
            }
        }

        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'notificados_online' => $totalNotificadosOnline]));
    }
);

$socket = new \React\Socket\SocketServer("0.0.0.0:{$internal_api_port}", [], $server->loop);
$internalApiServer->listen($socket);

echo "API interna de notificações rodando na porta {$internal_api_port}\n";

$server->run();