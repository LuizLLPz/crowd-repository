<?php
namespace modules\core\tipos;

use DateTime;

abstract class Entidade
{
    public string $select {
        get {
            // Monta a consulta SQL
            return "SELECT * FROM {$this->nomeTabela} ";
        }
    }
    public abstract string $nomeTabela {
        get;
        set;
    }

    public ?DateTime $dataCriacao;
    public ?DateTime $dataModificacao;

    public int $qtd;

}