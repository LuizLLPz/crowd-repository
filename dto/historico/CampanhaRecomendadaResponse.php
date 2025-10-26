<?php

namespace dto\historico;

use services\integrations\google\GoogleCloudStorageService;

class CampanhaRecomendadaResponse
{
    public int $idCampanha;
    public string $titulo;
    public string $categoria;
    public int $metaArrecadacao;
    public int $valorArrecadado;
    public string $roadmap;
    public string $nomeAutor;
    public string $caminhoImagemAutor;
    public ?string $imagemCapa;
    public int $pontuacaoCategoria;
    public string $dataCriacao;

    public function __construct(array $data)
    {
        $this->idCampanha = (int) $data['idCampanha'];
        $this->titulo = $data['titulo'] ?? '';
        $this->categoria = $data['categoria'] ?? '';
        $this->metaArrecadacao = (int) ($data['metaArrecadacao'] ?? 0);
        $this->valorArrecadado = (int) ($data['valorArrecadado'] ?? 0);
        $this->roadmap = $data['roadmap'] ?? '';
        $this->nomeAutor = $data['nomeAutor'] ?? '';
        $this->caminhoImagemAutor = $data['caminhoImagemAutor'] ?? '';
        $this->pontuacaoCategoria = (int) ($data['pontuacaoCategoria'] ?? 0);
        $this->dataCriacao = $data['dataCriacao'] ?? '';
        
        // Processar imagem capa
        $this->imagemCapa = null;
        if (!empty($data['imagemCapa'])) {
            $this->imagemCapa = GoogleCloudStorageService::getSignedUrl($data['imagemCapa']);
        }
        
        // Processar imagem do autor
        if (!empty($this->caminhoImagemAutor)) {
            $this->caminhoImagemAutor = GoogleCloudStorageService::getSignedUrl($this->caminhoImagemAutor);
        }
    }

    public function toArray(): array
    {
        return [
            'idCampanha' => $this->idCampanha,
            'titulo' => $this->titulo,
            'categoria' => $this->categoria,
            'metaArrecadacao' => $this->metaArrecadacao,
            'valorArrecadado' => $this->valorArrecadado,
            'roadmap' => $this->roadmap,
            'nomeAutor' => $this->nomeAutor,
            'caminhoImagemAutor' => $this->caminhoImagemAutor,
            'imagemCapa' => $this->imagemCapa,
            'pontuacaoCategoria' => $this->pontuacaoCategoria,
            'dataCriacao' => $this->dataCriacao
        ];
    }
}