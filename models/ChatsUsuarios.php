<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

class ChatsUsuarios extends Entidade
{
    public int $chatUsuariosId;
    public int $usuarioId;
    public int $chatId;
    public string $nomeTabela = "ChatsUsuarios";
    public ?string $entrouEm = null;


    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return ChatsUsuarios[]
     */
    public static function buscarChatsUsuarios(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(new ChatsUsuarios()->select);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}