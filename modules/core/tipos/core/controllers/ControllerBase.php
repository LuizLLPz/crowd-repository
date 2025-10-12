<?php
namespace modules\core\tipos\core\controllers;

use modules\core\tipos\core\controllers\UsuarioAutenticado;

class ControllerBase
{
    public static ?\modules\sockets\ConnectionManager $connectionManager = null;

    public static function setConnectionManager(\modules\sockets\ConnectionManager $manager): void
    {
        self::$connectionManager = $manager;
    }

    public static ?UsuarioAutenticado $usuarioAutenticado = null;

    public function __construct()
    {
    }

    public static function setDadosUsuarioAutenticado(UsuarioAutenticado $payload): void
    {
        ControllerBase::$usuarioAutenticado = $payload;
    }

    public static function estaAutenticado(): bool
    {
        return ControllerBase::$usuarioAutenticado !== null;
    }

}