<?php

namespace modules\core\tipos\core\controllers;

class UsuarioAutenticado
{
    public int $idUsuario;
    public string $nomeUsuario;
    public bool $verificado;
    public int $exp;

    function __construct($idUsuario, $nomeUsuario, $verificado, $exp)
    {
        $this->idUsuario = $idUsuario;
        $this->nomeUsuario = $nomeUsuario;
        $this->verificado = $verificado;
        $this->exp = $exp;
    }
}