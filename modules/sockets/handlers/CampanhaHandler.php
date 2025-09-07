<?php

namespace modules\sockets\Handlers;

use Ratchet\ConnectionInterface;
use models\campanha\InscricaoCampanha;

class CampanhaHandler
{

    public function __construct()
    {
    }

    public function onSubscribe(ConnectionInterface $from, object $payload)
    {
        if (!isset($from->usuarioId) || !isset($payload->campanhaId)) {
            echo "Tentativa de inscrição com dados incompletos. Ignorando.\n";
            return;
        }

        $usuarioId = $from->usuarioId;
        $campanhaId = (int)$payload->campanhaId;

        $sucesso = InscricaoCampanha::inscreverUsuario($usuarioId, $campanhaId);

        $resposta = [
            'type' => 'confirmacao_inscricao',
            'payload' => [
                'campanhaId' => $campanhaId,
                'sucesso' => $sucesso
            ]
        ];
        $from->send(json_encode($resposta));

        if ($sucesso) {
            echo "Handler: Usuário {$usuarioId} se inscreveu com sucesso na campanha {$campanhaId}\n";
        } else {
            echo "Handler: Falha ao inscrever usuário {$usuarioId} na campanha {$campanhaId}\n";
        }
    }
}