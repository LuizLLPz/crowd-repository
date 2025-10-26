<?php

namespace dto\historico;

class CategoriaInteresseResponse
{
    public int $idCategoria;
    public string $nomeCategoria;
    public int $pontos;
    public string $ultimaInteracao;

    public function __construct(array $data)
    {
        $this->idCategoria = (int) $data['idCategoria'];
        $this->nomeCategoria = $data['nomeCategoria'] ?? '';
        $this->pontos = (int) $data['pontos'];
        $this->ultimaInteracao = $data['ultimaInteracao'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'idCategoria' => $this->idCategoria,
            'nomeCategoria' => $this->nomeCategoria,
            'pontos' => $this->pontos,
            'ultimaInteracao' => $this->ultimaInteracao
        ];
    }
}