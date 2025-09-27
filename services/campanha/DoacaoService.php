<?php

namespace services\campanha;

use models\campanha\Doacao;

class DoacaoService
{
    public static function criarDoacao(Doacao $doacao): void
    {
        Doacao::criar($doacao);
    }
}
