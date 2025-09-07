<?php
namespace modules\sockets;

use Ratchet\ConnectionInterface;
use SplObjectStorage;

class ConnectionManager
{
    private static ?self $instancia = null;

    public static function getInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct()
    {
        $this->conexoes = new SplObjectStorage();
    }

    private SplObjectStorage $conexoes;
    private array $usuarioParaConexao = [];

    public function adicionarConexao(ConnectionInterface $conn): void
    {
        $this->conexoes->attach($conn);
        echo "Nova conexão estabelecida! ({$conn->resourceId})\n";
    }

    public function associarUsuario(ConnectionInterface $conn, int $idUsuario): void
    {
        $conn->idUsuario = $idUsuario;
        $this->usuarioParaConexao[$idUsuario] = $conn;
        echo "Conexão {$conn->resourceId} autenticada com sucesso para o usuário {$idUsuario}\n";
    }

    public function removerConexao(ConnectionInterface $conn): void
    {
        if (isset($conn->usuarioId) && isset($this->usuarioParaConexao[$conn->idUsuario])) {
            unset($this->usuarioParaConexao[$conn->idUsuario]);
            echo "Usuário {$conn->usuarioId} desconectado.\n";
        }

        $this->conexoes->detach($conn);
        echo "Conexão {$conn->resourceId} fechada.\n";
    }

    public function getConexaoPorUsuarioId(int $idUsuario): ?ConnectionInterface
    {
        return $this->usuarioParaConexao[$idUsuario] ?? null;
    }
}