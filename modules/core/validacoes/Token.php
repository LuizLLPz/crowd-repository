<?php

namespace modules\core\validacoes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use modules\core\tipos\UsuarioAutenticado;
use stdClass;
use Exception;

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
             );
        }
        catch (Exception $e) {
            return false;
        }
    }
}