<?php

namespace services\campanha;

use models\campanha\Categoria;

class CategoriaService
{
    public static function listar(?string $pesquisa = null): array
    {
        return Categoria::buscarCategorias($pesquisa);
    }

    public static function obterPorId(int $id): ?Categoria
    {
        return Categoria::buscarPorId($id);
    }

    public static function criar(Categoria $categoria): int
    {
        return Categoria::criar($categoria);
    }

    public static function atualizar(Categoria $categoria): bool
    {
        return Categoria::atualizar($categoria);
    }

    public static function deletar(int $id): bool
    {
        return Categoria::deletar($id);
    }
}
