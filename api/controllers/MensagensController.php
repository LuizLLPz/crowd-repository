<?php

namespace api\controllers;

use models\social\Mensagens;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;

class MensagensController extends ControllerBase
{
    #[HttpGet('/mensagens')]
    public function listar(): void
    {
        $resp = Mensagens::buscarMensagens();
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/listarmensagensdeumchat')]
    public function listarMensagensDeUmChat(): void
    {
        $idChat = $_GET['idChat'];
        $resp = Mensagens::buscarMensagensDeUmChat($idChat);
        foreach ($resp as &$mensagem) {
            if (isset($mensagem['reacoes']) && is_string($mensagem['reacoes'])) {
                $mensagem['reacoes'] = json_decode($mensagem['reacoes'], true);
            }
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/enviarmensagem')]
    public function enviarMensagem(Mensagens $mensagem): void
    {
        $novaMensagem = Mensagens::criarMensagem($mensagem->chatId, ControllerBase::$usuarioAutenticado->idUsuario, $mensagem->mensagem);

        // Broadcast the new message via the internal WebSocket API
        $client = new \GuzzleHttp\Client();
        $internalApiPort = $_ENV['SOCKET_INTERNAL_API_PORT'] ?? 8081;
        $client->post("http://localhost:{$internalApiPort}/broadcast-chat-message", [
            'json' => $novaMensagem
        ]);

        echo json_encode($novaMensagem, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}