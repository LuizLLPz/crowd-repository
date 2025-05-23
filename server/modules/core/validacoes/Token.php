<?php

namespace modules\core\validacoes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
    public static function validarToken(): bool {
        if (!isset($_COOKIE['token'])) {
            return false;
        }

        $token = $_COOKIE['token'];

        try {
            JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
