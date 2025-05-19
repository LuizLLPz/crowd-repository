<?php

namespace models;

use modules\core\tipos\Entidade;

class Projeto extends Entidade
{
    public string $nomeTabela = "Projeto";

    public static function get(): Projeto {

        return new Projeto();
    }
}