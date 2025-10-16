<?php

namespace modules\sockets;

use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
class SocketRouter implements MessageComponentInterface
{
    private ConnectionManager $connectionManager;

    public function __construct()
    {
        $this->connectionManager = ConnectionManager::getInstancia();
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