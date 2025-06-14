<?php

namespace modules\core\tipos\core\controllers;

use modules\core\tipos\http\tipos\FuncaoUsuario;

class UsuarioAutenticado
{
    public int $idUsuario;
    public string $nomeUsuario;
    public bool $verificado;
    public int $exp;
    public ?FuncaoUsuario $funcao;

    public function __set(string $name, mixed $value): void
    {
        if ($name === 'funcao' && is_string($value)) {
            $this->$name = FuncaoUsuario::tryFrom($value);
            return;
        }

        $this->$name = $value;
    }

    function __construct($idUsuario, $nomeUsuario, $verificado, $exp, $funcao)
    {
        $this->idUsuario = $idUsuario;
        $this->nomeUsuario = $nomeUsuario;
        $this->verificado = $verificado;
        $this->exp = $exp;
        $this->funcao = FuncaoUsuario::tryFrom($funcao);
    }
}