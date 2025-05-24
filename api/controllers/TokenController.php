<?php

namespace api\controllers;

use models\Usuario;
use modules\core\atributos\HttpPost;
use modules\core\tipos\ControllerBase;
use Firebase\JWT\JWT;

class TokenController extends ControllerBase
{
    #[HttpPost('/token', auth: false)]
    public function Autenticar(Usuario $usuario): void {
        $resultado = Usuario::buscarUsuarioPorNome($usuario->nomeUsuario);
        if (!$resultado) {
            http_response_code(401);
            echo json_encode(["erro" => "Credenciais inválidas!"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (password_verify($usuario->senha, $resultado->senha)) {
            $payload = [
                "id" => $resultado->idUsuario,
                "nomeUsuario" => $resultado->nomeUsuario,
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
            echo json_encode(["message" => "Logado com sucesso!"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        http_response_code(401);
        echo json_encode(["erro" => "Credenciais inválidas"], JSON_UNESCAPED_UNICODE);
    }
}