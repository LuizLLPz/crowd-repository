<?php
namespace modules\core\tipos;

use DateTime;

abstract class Entidade
{
    public string $select {
        get {
            return "SELECT * FROM {$this->nomeTabela} ";
        }
    }
    public abstract string $nomeTabela {
        get;
        set;
    }

    public ?string $dataCriacao = "";
    public ?string $dataModificacao = "";

    public int $qtd;

}