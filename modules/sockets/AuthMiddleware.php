<?php

namespace modules\sockets;

use Exception;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware implements HttpServerInterface
{
    private HttpServerInterface $app;
    private string $jwtKey;

    public function __construct(HttpServerInterface $app)
    {
        $this->app = $app;
        if (empty($_ENV['JWT_KEY'])) {
            die("Erro crítico: JWT_KEY não definida no .env\n");
        }
        $this->jwtKey = $_ENV['JWT_KEY'];
    }

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        if ($request === null) {
            $conn->close();
            return;
        }

        $cookies = $this->parseCookies($request->getHeaderLine('Cookie'));
        $jwtToken = $cookies['token'] ?? null;

        if ($jwtToken === null) {
            echo "AuthMiddleware: Conexão recusada - Cookie 'token' não encontrado.\n";
            $conn->close();
            return;
        }

        try {
            $payload = JWT::decode($jwtToken, new Key($this->jwtKey, 'HS256'));

            $conn->auth = new \stdClass();
            $conn->auth->idUsuario = $payload->idUsuario;

            return $this->app->onOpen($conn, $request);

        } catch (Exception $e) {
            echo "AuthMiddleware: Conexão recusada - Token inválido. Erro: {$e->getMessage()}\n";
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        return $this->app->onMessage($from, $msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        return $this->app->onClose($conn);
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        return $this->app->onError($conn, $e);
    }

    private function parseCookies(string $cookieHeader): array
    {
        $cookies = [];
        if (!empty($cookieHeader)) {
            $pairs = explode(';', $cookieHeader);
            foreach ($pairs as $pair) {
                $parts = explode('=', $pair, 2);
                $name = trim($parts[0]);
                $value = $parts[1] ?? '';
                $cookies[$name] = trim($value);
            }
        }
        return $cookies;
    }
}