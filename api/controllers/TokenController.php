<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Usuario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\Http;
use services\EmailService;

class TokenController extends ControllerBase
{
    #[HttpPost('/token', auth: false)]
    public function Autenticar(Usuario $usuario): void {
        $resultado = Usuario::buscarUsuarioPorEmail($usuario->nomeUsuario);
        if (!$resultado) {
            Http::HttpResponse(401, "Credenciais inválidas!");
        }
        if (password_verify($usuario->senha, $resultado->senha)) {
            $payload = [
                "idUsuario" => $resultado->idUsuario,
                "nomeUsuario" => $resultado->nomeUsuario,
                "verificado" => $resultado->verificado,
                "funcaoUsuario" => $resultado->funcao->value,
                "exp" => time() + (60 * 60 * 24),
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
            Http::HttpResponse(200, "Logado com sucesso!", $payload);
        }
        Http::HttpResponse(401, "Credenciais Inválidas");
    }
}