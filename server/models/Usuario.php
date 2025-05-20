<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

class Usuario extends Entidade
{
    public int $idUsuario;
    public string $nomeTabela = "Usuario";
    public string $nome;
    public string $sobrenome;
    public string $nomeUsuario;
    public string $senha;
    public string $nascimento;


    /**
     * @return Usuario[]
     */
    public static function buscarUsuarios(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(new Usuario()->select);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function salvarUsuario(Usuario $usuario): string
    {
        $pdo = Database::getConnection();

        $senha = password_hash($usuario->senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO Usuario (nome, sobrenome, nomeUsuario, senha, nascimento) 
            VALUES (:nome, :sobrenome, :nomeUsuario, :senha, :nascimento)";
        $stmt = $pdo->prepare($sql);

        $nascimento = new DateTime($usuario->nascimento)->format('Y-m-d');
        $stmt->execute([
            ':nome' => $usuario->nome,
            ':sobrenome' => $usuario->sobrenome,
            ':nomeUsuario' => $usuario->nomeUsuario,
            ':senha' => $senha,
            ':nascimento' => $nascimento,
        ]);
        return "Usuário cadastrado com sucesso!";
    }

    public static function buscarUsuarioPorNome(string $nome): Usuario | false
    {
        $pdo = Database::getConnection();
        $sql = new Usuario()->select." WHERE nomeUsuario = :nomeUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nomeUsuario' => $nome]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        return $stmt->fetch();
    }
}