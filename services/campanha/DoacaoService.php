<?php

namespace services\campanha;

use models\campanha\Doacao;

class DoacaoService
{
    public static function criarDoacao(Doacao $doacao): int
    {
        return Doacao::criar($doacao);
    }
}
