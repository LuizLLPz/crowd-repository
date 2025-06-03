<?php
namespace modules\core\tipos\core\controllers;

class ControllerBase
{
    protected static ?UsuarioAutenticado $usuarioAutenticado = null;

    public static function setDadosUsuarioAutenticado(UsuarioAutenticado $payload): void
    {
        ControllerBase::$usuarioAutenticado = $payload;
    }

    public static function estaAutenticado(): bool
    {
        return ControllerBase::$usuarioAutenticado !== null;
    }

}