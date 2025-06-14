<?php

namespace modules\core\validacoes;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use modules\core\tipos\core\controllers\UsuarioAutenticado;

class Token
{
    public static function validarToken(): UsuarioAutenticado|false
    {
        if (!isset($_COOKIE['token'])) {
            return false;
        }

        $token = $_COOKIE['token'];

        if (empty($_ENV['JWT_KEY'])) {
            return false;
        }

        try {
            $decodedPayload = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
             return new UsuarioAutenticado($decodedPayload->idUsuario,
                 $decodedPayload->nomeUsuario,
                 $decodedPayload->verificado,
                 $decodedPayload->exp,
                 $decodedPayload->funcaoUsuario
             );
        }
        catch (Exception $e) {
            return false;
        }
    }
}