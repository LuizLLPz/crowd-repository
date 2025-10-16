<?php

namespace api\controllers;

use models\campanha\Categoria;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpDelete;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\atributos\HttpPut;
use modules\core\utils\Http;
use services\campanha\CategoriaService;

class CategoriaController extends ControllerBase
{
    #[HttpGet('/categorias')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $resp = CategoriaService::listar($pesquisa);
        Http::HttpResponse(200, 'Categorias listadas com sucesso', $resp);
    }

    #[HttpGet('/categoria')]
    public function obterPorId(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            Http::HttpResponse(400, 'ID da categoria não fornecido.');
            return;
        }
        $categoria = CategoriaService::obterPorId((int)$id);
        if ($categoria) {
            Http::HttpResponse(200, 'Categoria obtida com sucesso', $categoria);
        } else {
            Http::HttpResponse(404, 'Categoria não encontrada');
        }
    }

    #[HttpPost('/categorias')]
    public function criar(Categoria $categoria): void
    {
        $novoId = CategoriaService::criar($categoria);
        $novaCategoria = CategoriaService::obterPorId($novoId);
        Http::HttpResponse(201, 'Categoria criada com sucesso', $novaCategoria);
    }

    #[HttpPut('/categoria')]
    public function atualizar(Categoria $categoria): void
    {
        $sucesso = CategoriaService::atualizar($categoria);
        if ($sucesso) {
            $categoriaAtualizada = CategoriaService::obterPorId($categoria->id);
            Http::HttpResponse(200, 'Categoria atualizada com sucesso', $categoriaAtualizada);
        } else {
            Http::HttpResponse(500, 'Falha ao atualizar a categoria');
        }
    }

    #[HttpDelete('/categoria')]
    public function deletar(Categoria $categoria): void
    {
        $sucesso = CategoriaService::deletar($categoria->id);
        if ($sucesso) {
            Http::HttpResponse(204, 'Categoria deletada com sucesso');
        } else {
            Http::HttpResponse(500, 'Falha ao deletar a categoria');
        }
    }
}