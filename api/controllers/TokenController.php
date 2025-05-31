<?php

namespace api\controllers;

use models\Usuario;
use modules\core\atributos\HttpPost;
use modules\core\tipos\ControllerBase;
use Firebase\JWT\JWT;
use services\EmailService;

class TokenController extends ControllerBase
{
    #[HttpPost('/token', auth: false)]
    public function Autenticar(Usuario $usuario): void {
        $resultado = Usuario::buscarUsuarioPorNome($usuario->nomeUsuario);
        if (!$resultado) {
            http_response_code(401);
            echo json_encode(["error" => "Credenciais inválidas!"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (password_verify($usuario->senha, $resultado->senha)) {
            $payload = [
                "idUsuario" => $resultado->idUsuario,
                "nomeUsuario" => $resultado->nomeUsuario,
                "verificado" => $resultado->verificado,
                "exp" => time() + (60 * 60 * 24)
            ];
            $jwt = JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');

            setcookie(
                "token",
                $jwt,
                [
                    "expires" => time() + (60 * 60 * 24),
                    "path" => "/",
                    "domain" => $_ENV['COOKIE_DOMAIN'] ?? "",
                    "secure" => true,
                    "httponly" => true,
                    "samesite" => "Strict",
                ]
            );
            if (!$resultado->verificado) {
                Usuario::gerarNovoCodigo($resultado);
                $emailService = new EmailService();
                $emailService->enviar($resultado->email, $resultado->nomeUsuario, "Verificar conta crowd repository",
                    "O seu código de verificação é <b>{$resultado->codigoVerificacao}</b>");
            }
            echo json_encode(array_merge(["message" => "Logado com sucesso!"], $payload), JSON_UNESCAPED_UNICODE);
            exit;
        }
        http_response_code(401);
        echo json_encode(["error" => "Credenciais inválidas"], JSON_UNESCAPED_UNICODE);
    }
}