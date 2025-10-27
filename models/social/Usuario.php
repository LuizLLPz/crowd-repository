<?php

namespace models\social;

use DateTime;
use modules\core\tipos\Entidade;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\db\Database;
use services\integrations\google\GoogleCloudStorageService;

class Usuario extends Entidade
{
    public int $idUsuario;
    public string $nomeTabela = "Usuario";
    public string $nomeUsuario;
    public string $senha;
    public string $email;
    public string $codigoVerificacao;
    public string $expiracaoCodigo;
    public bool $verificado;
    public ?string $funcao = null;
    public ?string $caminhoImagem = '';
    public ?string $stripe_account_id = null;
    public ?string $stripe_details_submitted = null;
    public ?string $stripe_charges_enabled = null;
    public ?string $stripe_payouts_enabled = null;
    public ?string $stripe_requirements_due = null;
    public ?string $telefone = '';
    public ?string $linkedin = '';
    public ?string $github = '';
    public ?string $instagram = '';
    public ?string $descricao = '';
    public ?int $idCargo = null;
    public ?string $descricaoCargo = null;
    public bool $tutorial_concluido = false;

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

    public static function buscar_usuario(int $idUsuario): Usuario
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT U.*, U.funcao, U.tutorial_concluido, C.titulo AS descricaoCargo FROM Usuario U LEFT JOIN Cargo C ON C.id = U.idCargo WHERE idUsuario = :idUsuario");
        $stmt->execute([':idUsuario' => $idUsuario]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        $usuario = $stmt->fetch();

        if ($usuario && !empty($usuario->descricao)) {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $usuario->descricao);
            $images = $dom->getElementsByTagName('img');

            foreach ($images as $img) {
                if ($img->hasAttribute('data-path')) {
                    $path = $img->getAttribute('data-path');
                    $newSignedUrl = GoogleCloudStorageService::getSignedUrl($path);
                    $img->setAttribute('src', $newSignedUrl);
                }
            }
            $body = $dom->getElementsByTagName('body')->item(0);
            $innerHtml = '';
            foreach ($body->childNodes as $child) {
                $innerHtml .= $dom->saveHTML($child);
            }
            $usuario->descricao = $innerHtml;
        }

        if ($usuario && !empty($usuario->caminhoImagem)) {
            $usuario->caminhoImagem = GoogleCloudStorageService::getSignedUrl($usuario->caminhoImagem);
        }

        if ($usuario && is_string($usuario->funcao)) {
            $usuario->funcao = FuncaoUsuario::tryFrom($usuario->funcao);
        }

        return $usuario;
    }

    public static function buscarUsuarios(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM Usuario");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function buscar_usuarios(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM Usuario");
        $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($usuarios as &$usuario) {
            if (!empty($usuario['descricao'])) {
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $usuario['descricao']);
                $images = $dom->getElementsByTagName('img');

                foreach ($images as $img) {
                    if ($img->hasAttribute('data-path')) {
                        $path = $img->getAttribute('data-path');
                        $newSignedUrl = GoogleCloudStorageService::getSignedUrl($path);
                        $img->setAttribute('src', $newSignedUrl);
                    }
                }
                $body = $dom->getElementsByTagName('body')->item(0);
                $innerHtml = '';
                foreach ($body->childNodes as $child) {
                    $innerHtml .= $dom->saveHTML($child);
                }
                $usuario['descricao'] = $innerHtml;
            }

            if (!empty($usuario['caminhoImagem'])) {
                $usuario['caminhoImagem'] = GoogleCloudStorageService::getSignedUrl($usuario['caminhoImagem']);
            }
        }

        return $usuarios;
    }

    public static function buscar_usuarios_input(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT idUsuario, nomeUsuario, email, caminhoImagem, idCargo FROM Usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($usuarios as &$usuario) {
            if (!empty($usuario['caminhoImagem'])) {
                $usuario['caminhoImagem'] = GoogleCloudStorageService::getSignedUrl($usuario['caminhoImagem']);
            }
        }

        return $usuarios;
    }




    public static function buscar_usuario_por_stripe_account_id(string $stripeAccountId): Usuario|false
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Usuario WHERE stripe_account_id = :stripe_account_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':stripe_account_id' => $stripeAccountId]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        $usuario = $stmt->fetch();

        if ($usuario && !empty($usuario->caminhoImagem)) {
            $usuario->caminhoImagem = GoogleCloudStorageService::getSignedUrl($usuario->caminhoImagem);
        }

        if ($usuario && is_string($usuario->funcao)) {
            $usuario->funcao = FuncaoUsuario::tryFrom($usuario->funcao);
        }

        return $usuario;
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

    public static function editar_usuario(Usuario $usuario): void
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Usuario SET 
                    nomeUsuario = :nomeUsuario,
                    telefone = :telefone,
                    linkedin = :linkedin,
                    github = :github,
                    instagram = :instagram,
                    descricao = :descricao,
                    idCargo = :idCargo
                WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nomeUsuario' => $usuario->nomeUsuario,
            ':telefone' => $usuario->telefone,
            ':linkedin' => $usuario->linkedin,
            ':github' => $usuario->github,
            ':instagram' => $usuario->instagram,
            ':descricao' => $usuario->descricao,
            ':idCargo' => $usuario->idCargo,
            ':idUsuario' => $usuario->idUsuario
        ]);
    }

    public static function alterar_caminhoImagem(int $idUsuario, string $caminhoImagem): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Usuario SET caminhoImagem = :caminhoImagem WHERE idUsuario = :idUsuario");
        $stmt->execute([
            ':caminhoImagem' => $caminhoImagem,
            ':idUsuario' => $idUsuario
        ]);
    }

    public static function obter_nomeUsuario(int $idUsuario): string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT nomeUsuario FROM Usuario WHERE idUsuario = :idUsuario");
        $stmt->execute([':idUsuario' => $idUsuario]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['nomeUsuario'] : '';
    }

    public static function buscar_usuario_por_email(string $nome): Usuario|false
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Usuario WHERE email = :nomeUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nomeUsuario' => $nome]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        $usuario = $stmt->fetch();

        if ($usuario && !empty($usuario->caminhoImagem)) {
            $usuario->caminhoImagem = GoogleCloudStorageService::getSignedUrl($usuario->caminhoImagem);
        }

        if ($usuario && is_string($usuario->funcao)) {
            $usuario->funcao = FuncaoUsuario::tryFrom($usuario->funcao);
        }
        return $usuario;
    }

    public static function buscarUsuarioPorId(int $idUsuario): Usuario|false
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM Usuario WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Usuario::class);
        $usuario = $stmt->fetch();
        if ($usuario && !empty($usuario->caminhoImagem)) {
            $usuario->caminhoImagem = GoogleCloudStorageService::getSignedUrl($usuario->caminhoImagem);
        }
        if ($usuario && is_string($usuario->funcao)) {
            $usuario->funcao = FuncaoUsuario::tryFrom($usuario->funcao);
        }
        return $usuario;

    }

    public static function atualizarStripeAccountStatus(
        int $idUsuario,
        bool $detailsSubmitted,
        bool $chargesEnabled,
        bool $payoutsEnabled,
        string $requirementsDue
    ): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Usuario SET 
            stripe_details_submitted = :details_submitted,
            stripe_charges_enabled = :charges_enabled,
            stripe_payouts_enabled = :payouts_enabled,
            stripe_requirements_due = :requirements_due
            WHERE idUsuario = :idUsuario");
        $stmt->execute([
            ':details_submitted' => $detailsSubmitted ? 1 : 0,
            ':charges_enabled' => $chargesEnabled ? 1 : 0,
            ':payouts_enabled' => $payoutsEnabled ? 1 : 0,
            ':requirements_due' => $requirementsDue,
            ':idUsuario' => $idUsuario
        ]);
    }

    public static function gerar_novo_codigo(Usuario $usuario): bool
    {
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

    public static function verificar_usuario(int $idUsuario): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE Usuario SET verificado = 1 WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        return true;
    }

    public static function atualizarStripeAccountId(int $idUsuario, string $stripeAccountId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Usuario SET stripe_account_id = :stripe_account_id WHERE idUsuario = :idUsuario");
        $stmt->execute([
            ':stripe_account_id' => $stripeAccountId,
            ':idUsuario' => $idUsuario
        ]);
    }

    public static function atualizarTutorialConcluido(int $idUsuario, bool $concluido): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Usuario SET tutorial_concluido = :concluido WHERE idUsuario = :idUsuario");
        $stmt->execute([
            ':concluido' => $concluido ? 1 : 0,
            ':idUsuario' => $idUsuario
        ]);
    }
}