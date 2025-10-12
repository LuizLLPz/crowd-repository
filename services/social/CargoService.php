<?php

namespace services\social;

use models\social\Cargo;

class CargoService
{
    public static function listar(?string $pesquisa = null): array
    {
        return Cargo::buscarCargos($pesquisa);
    }

    public static function obterPorId(int $id): ?Cargo
    {
        return Cargo::buscarPorId($id);
    }

    public static function criar(Cargo $cargo): int
    {
        return Cargo::criar($cargo);
    }

    public static function atualizar(Cargo $cargo): bool
    {
        return Cargo::atualizar($cargo);
    }

    public static function deletar(int $id): bool
    {
        return Cargo::deletar($id);
    }
}
