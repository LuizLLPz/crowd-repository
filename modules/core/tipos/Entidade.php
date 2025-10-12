<?php
namespace modules\core\tipos;

use DateTime;

abstract class Entidade
{
    public abstract string $nomeTabela {
        get;
        set;
    }

    public ?string $dataCriacao = "";
    public ?string $dataModificacao = "";

    public int $qtd;

}