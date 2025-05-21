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
    public string $email;
    public int $codigoVerificacao;
    public string $expiracaoCodigo;
    public bool $verificado;


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

        $nascimento = new DateTime($usuario->nascimento)->format('Y-m-d');

        $usuario->codigoVerificacao = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $expiracaoCodigo = new DateTime('+15 minutes')->format('Y-m-d H:i:s');

        $sql = "INSERT INTO Usuario (nome, sobrenome, nomeUsuario, email, senha, nascimento, codigoVerificacao, expiracaoCodigo) 
            VALUES (:nome, :sobrenome, :nomeUsuario, :email, :senha, :nascimento, :codigoVerificacao, :expiracaoCodigo)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nome' => $usuario->nome,
            ':sobrenome' => $usuario->sobrenome,
            ':nomeUsuario' => $usuario->nomeUsuario,
            ':email' => $usuario->email,
            ':senha' => $senha,
            ':nascimento' => $nascimento,
            ':codigoVerificacao' => $usuario->codigoVerificacao,
            ':expiracaoCodigo' => $expiracaoCodigo,
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

    public static function buscarUsuarioPorId(int $idUsuario): Usuario | false
    {
        $pdo = Database::getConnection();
        $sql = new Usuario()->select." WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        return $stmt->fetch();
    }

    public static function gerarNovoCodigo(Usuario $usuario): bool {
        $pdo = Database::getConnection();
        $usuario->codigoVerificacao = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $expiracaoCodigo = new DateTime('+15 minutes')->format('Y-m-d H:i:s');
        $sql = "UPDATE Usuario SET codigoVerificacao = :codigoVerificacao, 
                   expiracaoCodigo = :expiracaoCodigo WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigoVerificacao' => $usuario->codigoVerificacao,
            ':expiracaoCodigo' => $expiracaoCodigo,
            ':idUsuario' => $usuario->idUsuario
        ]);
        return true;
    }

    public static function verificarUsuario(int $idUsuario): bool {
        $pdo = Database::getConnection();
        $sql = "UPDATE Usuario SET verificado = 1 WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        return true;
    }
}