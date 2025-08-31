<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\db\Database;

class Usuario extends Entidade
{
    public int $idUsuario;
    public string $nomeTabela = "Usuario";
    public string $nomeUsuario;
    public string $senha;
    public string $email;
    public int $codigoVerificacao;
    public string $expiracaoCodigo;
    public bool $verificado;
    public ?FuncaoUsuario $funcao = null;
    public ?string $caminhoImagem = '';
    public ?string $telefone = '';
    public ?string $linkedin = '';
    public ?string $github = '';
    public ?string $instagram = '';
    public ?string $descricao = '';
    public ?int $idCargo = null;

    public function __construct()
    {
        unset($this->funcao);
    }

    public function __set(string $name, mixed $value): void
    {
        if ($name === 'funcao' && is_string($value)) {
            $this->$name = FuncaoUsuario::tryFrom($value);
            return;
        }

        $this->$name = $value;
    }

    /**
     * @return Usuario[]
     */
    public static function buscarUsuarios(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(new Usuario()->select);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function criar_usuario(Usuario $usuario): string
    {

        $pdo = Database::getConnection();

        $senha = password_hash($usuario->senha, PASSWORD_DEFAULT);

        $usuario->codigoVerificacao = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $expiracaoCodigo = new DateTime('+15 minutes')->format('Y-m-d H:i:s');

        $sql = "INSERT INTO Usuario (nomeUsuario, email, senha, codigoVerificacao, expiracaoCodigo) 
            VALUES (:nomeUsuario, :email, :senha, :codigoVerificacao, :expiracaoCodigo)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nomeUsuario' => $usuario->nomeUsuario,
            ':email' => $usuario->email,
            ':senha' => $senha,
            ':codigoVerificacao' => $usuario->codigoVerificacao,
            ':expiracaoCodigo' => $expiracaoCodigo,
        ]);

        $usuario->idUsuario = $pdo->lastInsertId();

        return "Usuário cadastrado com sucesso!";
    }

    public static function buscarUsuarioPorEmail(string $nome): Usuario | false
    {
        $pdo = Database::getConnection();
        $sql = new Usuario()->select." WHERE email = :nomeUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nomeUsuario' => $nome]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        $usuario = $stmt->fetch();

        if ($usuario && is_string($usuario->funcao)) {
            $usuario->funcao = FuncaoUsuario::tryFrom($usuario->funcao);
        }
        return $usuario;
    }

    public static function buscarUsuarioPorId(int $idUsuario): Usuario | false
    {
        $pdo = Database::getConnection();
        $sql = new Usuario()->select." WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        $usuario = $stmt->fetch();
        if ($usuario && is_string($usuario->funcao)) {
            $usuario->funcao = FuncaoUsuario::tryFrom($usuario->funcao);
        }
        return $usuario;

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