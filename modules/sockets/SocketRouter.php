<?php

namespace modules\sockets;

use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use modules\sockets\Handlers\CampanhaHandler;

class SocketRouter implements MessageComponentInterface
{
    private ConnectionManager $connectionManager;
    private CampanhaHandler $campanhaHandler;

    public function __construct()
    {
        $this->connectionManager = ConnectionManager::getInstancia();
        $this->campanhaHandler = new CampanhaHandler();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $idUsuario = $conn->auth->idUsuario ?? null;

        if ($idUsuario === null) {
            $conn->close();
            return;
        }

        $this->connectionManager->adicionarConexao($conn);
        $this->connectionManager->associarUsuario($conn, $idUsuario);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if (!isset($from->auth->idUsuario)) {
            return;
        }

        $from->usuarioId = $from->auth->idUsuario;

        $dados = json_decode($msg);
        if (!$dados || !isset($dados->type)) {
            echo "Mensagem mal formatada recebida. Ignorando.\n";
            return;
        }

        switch ($dados->type) {
            case 'campanha_inscrever':
                $this->campanhaHandler->onSubscribe($from, $dados->payload);
                break;

            default:
                $hash = spl_object_hash($from);
                echo "Tipo de mensagem desconhecido ('{$dados->type}') recebido da conexão {$hash}\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connectionManager->removerConexao($conn);
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $hash = spl_object_hash($conn);
        echo "Ocorreu um erro na conexão {$hash}: {$e->getMessage()}\n";
        $this->connectionManager->removerConexao($conn);
        $conn->close();
    }
}