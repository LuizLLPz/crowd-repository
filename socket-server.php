<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\WebSocket\WsServer;
use modules\sockets\SocketRouter;
use modules\sockets\AuthMiddleware;
use modules\sockets\ConnectionManager;
use modules\sockets\handlers\ChatHandler;
use React\Http\HttpServer as ReactHttpServer;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

error_reporting(E_ALL & ~E_DEPRECATED);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$ws_port = $_ENV['WEBSOCKET_PORT'] ?? 8080;
$internal_api_port = $_ENV['SOCKET_INTERNAL_API_PORT'] ?? 8081;

$connectionManager = ConnectionManager::getInstancia();
$chatHandler = new ChatHandler($connectionManager);

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
    function (ServerRequestInterface $request) use ($connectionManager, $chatHandler) {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        if ($method === 'POST' && $path === '/notify') {
            $notificacoes = json_decode((string) $request->getBody(), true);

            if (empty($notificacoes) || !is_array($notificacoes)) {
                return new Response(400, ['Content-Type' => 'text/plain'], 'Bad Request');
            }

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
                    }
                }
            }

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok']));
        } elseif ($method === 'POST' && $path === '/broadcast-chat-message') {
            $data = json_decode((string) $request->getBody(), true);
            $messageData = $data['payload'];
            $senderId = $data['remetenteId'];

            if (empty($messageData) || !is_array($messageData) || !$senderId) {
                return new Response(400, ['Content-Type' => 'text/plain'], 'Bad Request');
            }

            $chatHandler->broadcastNewMessage($messageData, $senderId);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok']));
        } elseif ($method === 'POST' && $path === '/broadcast-reaction-update') {
            $reactionData = json_decode((string) $request->getBody(), true);

            if (empty($reactionData) || !is_array($reactionData)) {
                return new Response(400, ['Content-Type' => 'text/plain'], 'Bad Request');
            }

            $chatHandler->broadcastReactionUpdate($reactionData);

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok']));
        }

        return new Response(404, ['Content-Type' => 'text/plain'], 'Not Found');
    }
);

$socket = new \React\Socket\SocketServer("0.0.0.0:{$internal_api_port}", [], $server->loop);
$internalApiServer->listen($socket);

echo "API interna de notificações rodando na porta {$internal_api_port}\n";

$server->run();
