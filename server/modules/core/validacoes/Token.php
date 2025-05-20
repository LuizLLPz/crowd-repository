<?php

namespace modules\core\validacoes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
    public static function validarToken(): bool {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return false;
        }

        $token = substr($token, 7);

        try {
            JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


}