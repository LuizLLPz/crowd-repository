<?php

namespace api\controllers;

use models\social\MensagemReacoes;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\tipos\Http\atributos\HttpGet;

class MensagemReacoesController extends ControllerBase
{
    #[HttpPost('/mensagemreacoes/adicionar')]
    public function adicionarReacao(MensagemReacoes $reacao): void
    {
        if (MensagemReacoes::adicionarReacao($reacao->mensagemId, ControllerBase::$usuarioAutenticado->idUsuario, $reacao->emoji)) {
            $usuarioNome = ControllerBase::$usuarioAutenticado->nomeUsuario; // Assuming nomeUsuario is available
            // Broadcast the reaction update via the internal WebSocket API
            $client = new \GuzzleHttp\Client();
            $internalApiPort = $_ENV['SOCKET_INTERNAL_API_PORT'] ?? 8081;
            $client->post("http://localhost:{$internalApiPort}/broadcast-reaction-update", [
                'json' => [
                    'mensagemId' => $reacao->mensagemId,
                    'usuarioId' => ControllerBase::$usuarioAutenticado->idUsuario,
                    'usuarioNome' => $usuarioNome,
                    'emoji' => $reacao->emoji,
                    'action' => 'added'
                ]
            ]);
            echo json_encode(['message' => 'Reação adicionada com sucesso'], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Erro ao adicionar reação'], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
        }
    }

    #[HttpDelete('/mensagemreacoes/remover')]
    public function removerReacao(MensagemReacoes $reacao): void
    {
        if (MensagemReacoes::removerReacao($reacao->mensagemId, ControllerBase::$usuarioAutenticado->idUsuario, $reacao->emoji)) {
            $usuarioNome = ControllerBase::$usuarioAutenticado->nomeUsuario; // Assuming nomeUsuario is available
            // Broadcast the reaction update via the internal WebSocket API
            $client = new \GuzzleHttp\Client();
            $internalApiPort = $_ENV['SOCKET_INTERNAL_API_PORT'] ?? 8081;
            $client->post("http://localhost:{$internalApiPort}/broadcast-reaction-update", [
                'json' => [
                    'mensagemId' => $reacao->mensagemId,
                    'usuarioId' => ControllerBase::$usuarioAutenticado->idUsuario,
                    'usuarioNome' => $usuarioNome,
                    'emoji' => $reacao->emoji,
                    'action' => 'removed'
                ]
            ]);
            echo json_encode(['message' => 'Reação removida com sucesso'], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Erro ao remover reação'], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
        }
    }

    #[HttpGet('/mensagemreacoes/buscar')]
    public function buscarReacoes(): void
    {
        $mensagemId = $_GET['mensagemId'] ?? null;
        if (!$mensagemId) {
            http_response_code(400);
            echo json_encode(['message' => 'ID da mensagem é obrigatório'], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
            return;
        }
        $reacoes = MensagemReacoes::buscarReacoesPorMensagem($mensagemId);
        echo json_encode($reacoes, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }
}
