<?php

namespace models\social;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Mensagens extends Entidade
{
    public int $idMensagem;
    public int $chatId;
    public int $usuarioId;
    public string $nomeTabela = "Mensagens";
    public ?string $criadoEm = null;
    public ?string $lidoEm = null;
    public string $mensagem = "";

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Mensagens[]
     */
    public static function buscarMensagens(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM Mensagens");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function buscarMensagensDeUmChat(int $idChat): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                m.*, 
                u.caminhoImagem as urlFoto
            FROM Mensagens m 
            INNER JOIN Usuario u ON m.usuarioId = u.idUsuario
            WHERE m.chatId = :idChat
            ORDER BY m.criadoEm ASC;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idChat' => $idChat]);
        $mensagens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($mensagens as &$mensagem) {
            $reacoes = MensagemReacoes::buscarReacoesPorMensagem($mensagem['idMensagem']);
            $mensagem['reacoes'] = $reacoes;
        }

        return $mensagens;
    }


    public static function buscarMensagemPorId(int $idMensagem): ?array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Mensagens WHERE idMensagem = :idMensagem";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idMensagem' => $idMensagem]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function criarMensagem(int $idChat, int $idUsuario, string $mensagem): array
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Mensagens (chatId, usuarioId, mensagem) 
        VALUES (:chatId, :usuarioId, :mensagem)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':chatId' => $idChat,
            ':usuarioId' => $idUsuario,
            ':mensagem' => $mensagem,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Falha ao inserir a mensagem no banco de dados. Erro: " . implode(" ", $stmt->errorInfo()));
        }

        $idMensagem = $pdo->lastInsertId();

        error_log("Tentando recuperar mensagem recém-criada. ID: {$idMensagem}, UsuarioID: {$idUsuario}");

        $sql = "SELECT m.*, u.caminhoImagem as urlFoto FROM Mensagens m INNER JOIN Usuario u ON m.usuarioId = u.idUsuario WHERE m.idMensagem = :idMensagem";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idMensagem' => $idMensagem]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            throw new \Exception("Falha ao recuperar a mensagem recém-criada.");
        }

        return $result;
    }

}