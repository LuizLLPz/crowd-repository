<?php

namespace modules\core\utils;

class Utils {
    public static function mascararEmail(string $email): string
    {
        [$nome, $dominio] = explode('@', $email);
        $primeiros = substr($nome, 0, 2);
        $mascara = str_repeat('*', max(strlen($nome) - 2, 0));
        return $primeiros . $mascara . '@' . $dominio;
    }

    public static function getServerUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
