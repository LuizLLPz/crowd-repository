<?php

namespace services\usuario;

use Firebase\JWT\JWT;
use models\Usuario;
use modules\core\utils\File;
use modules\db\Database;
use services\integrations\email\EmailService;

class UsuarioService {
    public static function salvar_usuario(Usuario $usuario): string {
        $resp = "";
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            $resp = Usuario::criar_usuario($usuario);
            $emailService = new EmailService();
            $emailService->enviar($usuario->email, $usuario->nomeUsuario, "Verificar conta crowd repository",
                "O seu código de verificação é <b>{$usuario->codigoVerificacao}</b>");

            $payload = [
                "idUsuario" => $usuario->idUsuario,
                "nomeUsuario" => $usuario->nomeUsuario,
                "verificado" => false,
                "funcaoUsuario" => "user",
                "exp" => time() + (60 * 60 * 24),
            ];
            $jwt = JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');

            if (isset($_FILES['imagem'])) {
                $imagemPerfil = $_FILES['imagem'];
                $nomeArquivo = "perfil-{$usuario->idUsuario}.".pathinfo($imagemPerfil['name'], PATHINFO_EXTENSION);;
                $resultadoUpload = File::salvarImagem($imagemPerfil, $nomeArquivo);
                if ($resultadoUpload['success']) {
                    Usuario::alterar_caminhoImagem($usuario->idUsuario, caminhoImagem: $resultadoUpload['relativePath']);
                } else {
                    throw new \Exception("Falha no upload da imagem: {$resultadoUpload["message"]}");
                }
            }

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
            $pdo->commit();
        }
        catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $resp;
    }
}