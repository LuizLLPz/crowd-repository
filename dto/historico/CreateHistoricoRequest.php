<?php

namespace dto\historico;

class CreateHistoricoRequest
{
    public int $idCampanha;
    public int $idCategoria;

    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->idCampanha = (int) ($data['idCampanha'] ?? 0);
            $this->idCategoria = (int) ($data['idCategoria'] ?? 0);
        }
    }

    public function validar(): array
    {
        $erros = [];

        if (empty($this->idCampanha) || $this->idCampanha <= 0) {
            $erros[] = 'idCampanha é obrigatório e deve ser um número positivo';
        }

        if (empty($this->idCategoria) || $this->idCategoria <= 0) {
            $erros[] = 'idCategoria é obrigatório e deve ser um número positivo';
        }

        return $erros;
    }

    public function toArray(): array
    {
        return [
            'idCampanha' => $this->idCampanha,
            'idCategoria' => $this->idCategoria
        ];
    }
}