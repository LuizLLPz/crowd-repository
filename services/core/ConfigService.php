<?php

namespace services\core;

use models\core\Configuracao;

class ConfigService
{
    private static array $cache = [];

    public static function get(string $provedor, string $chave): ?string
    {
        $cacheKey = "{$provedor}.{$chave}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $valor = Configuracao::getChave($provedor, $chave);
        self::$cache[$cacheKey] = $valor;

        return $valor;
    }

    public static function getProviderConfig(string $provedor): array
    {
        if (isset(self::$cache[$provedor])) {
            return self::$cache[$provedor];
        }

        $config = Configuracao::getConfig($provedor);
        self::$cache[$provedor] = $config;

        return $config;
    }

    public static function set(string $provedor, string $chave, ?string $valor, bool $encrypt = false): void
    {
        Configuracao::setChave($provedor, $chave, $valor, $encrypt);
        unset(self::$cache["{$provedor}.{$chave}"]);
        unset(self::$cache[$provedor]);
    }
    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
