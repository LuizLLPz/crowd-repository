<?php

namespace api\controllers;

use models\Usuario;
use modules\core\atributos\HttpGet;
use modules\core\atributos\HttpPost;
use modules\core\tipos\ControllerBase;
use services\EmailService;
use modules\core\utils\Utils;

class UsuarioController extends ControllerBase {
    #[HttpGet('/usuarios', auth: true)]
    public function listar(): void
    {
       $resp = Usuario::buscarUsuarios();
       echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/usuario', auth: false)]
    public function salvar(Usuario $usuario): void
    {
        $resp = Usuario::salvarUsuario($usuario);
        $emailService = new EmailService();
        $emailService->enviar($usuario->email, $usuario->nomeUsuario, "Verificar conta crowd repository",
            "O seu código de verificação é <b>{$usuario->codigoVerificacao}</b>");
        echo json_encode(['message'=> $resp], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/verificarConta', auth: true)]
    public function verificarConta(Usuario $usuario): void
    {
        $resultado = Usuario::buscarUsuarioPorId($usuario->idUsuario);
        $agora = new \DateTime();

        if ($resultado->codigoVerificacao !== $usuario->codigoVerificacao) {
            http_response_code(403);
            echo json_encode(["error" => "Código de verificação inválido"]);
            return;
        }
        $expiracao = new \DateTime($resultado->expiracaoCodigo);

        if ($agora > $expiracao) {
            http_response_code(400);
            echo json_encode(["error" => "Código expirado"]);
            return;
        }

        Usuario::verificarUsuario($usuario->idUsuario);

        $emailService = new EmailService();
        $emailService->enviar($resultado->email, $resultado->nomeUsuario, "Verificar conta crowd repository",
            "Conta verificada com sucesso! Aproveite o crowd repository!");

        http_response_code(200);
        echo json_encode(["message" => "Conta verificada com sucesso!"]);
    }

    #[HttpPost('/reenviarCodigo', auth: true)]
    public function reenviarCodigo(Usuario $usuario): void
    {
        $result = Usuario::buscarUsuarioPorId($usuario->idUsuario);
        Usuario::gerarNovoCodigo($result);

        $emailService = new EmailService();
        $emailService->enviar(
            $result->email,
            $result->nomeUsuario,
            "Verificar conta crowd repository",
            "O seu código de verificação é <b>{$result->codigoVerificacao}</b>"
        );

        http_response_code(200);
        echo json_encode([
            "message" => "Um código de verificação foi enviado para " . Utils::mascararEmail($result->email)
        ]);
    }

}