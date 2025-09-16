<?php

namespace models;

use DateTime;
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
        $stmt = $pdo->query(new Mensagens()->select);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function buscarMensagensDeUmChat(int $idChat): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT * FROM Mensagens WHERE chatId = :idChat;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idChat' => $idChat]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function criarMensagem(int $idChat, int $idUsuario, string $mensagem): string
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        $sql = "INSERT INTO Mensagens (chatId, usuarioId, mensagem) 
        VALUES (:chatId, :usuarioId, :mensagem)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':chatId' => $idChat,
            ':usuarioId' => $idUsuario,
            ':mensagem' => $mensagem,
        ]);

        $pdo->commit();

        return "Mensagem enviada com sucesso!";
    }

}