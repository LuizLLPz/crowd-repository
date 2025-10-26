<?php

namespace dto\historico;

class HistoricoInteracaoResponse
{
    public int $id;
    public int $idUsuario;
    public int $idCampanha;
    public int $idCategoria;
    public string $dataCriacao;
    public CampanhaResponse $campanha;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->idUsuario = (int) $data['idUsuario'];
        $this->idCampanha = (int) $data['idCampanha'];
        $this->idCategoria = (int) $data['idCategoria'];
        $this->dataCriacao = $data['dataCriacao'];
        
        $this->campanha = new CampanhaResponse([
            'titulo' => $data['tituloCampanha'] ?? '',
            'categoria' => $data['tituloCategoria'] ?? '',
            'nomeAutor' => $data['nomeAutor'] ?? '',
            'caminhoImagemAutor' => $data['caminhoImagemAutor'] ?? ''
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idUsuario' => $this->idUsuario,
            'idCampanha' => $this->idCampanha,
            'idCategoria' => $this->idCategoria,
            'dataCriacao' => $this->dataCriacao,
            'campanha' => $this->campanha->toArray()
        ];
    }
}

class CampanhaResponse
{
    public string $titulo;
    public string $categoria;
    public string $nomeAutor;
    public string $caminhoImagemAutor;

    public function __construct(array $data)
    {
        $this->titulo = $data['titulo'] ?? '';
        $this->categoria = $data['categoria'] ?? '';
        $this->nomeAutor = $data['nomeAutor'] ?? '';
        $this->caminhoImagemAutor = $data['caminhoImagemAutor'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'titulo' => $this->titulo,
            'categoria' => $this->categoria,
            'nomeAutor' => $this->nomeAutor,
            'caminhoImagemAutor' => $this->caminhoImagemAutor
        ];
    }
}