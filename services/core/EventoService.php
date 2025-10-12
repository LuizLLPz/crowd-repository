<?php

namespace services\core;

use models\core\Evento;

class EventoService
{
    public static function listar(?string $pesquisa = null): array
    {
        return Evento::buscar($pesquisa);
    }

    public static function obterPorId(int $id): ?Evento
    {
        return Evento::buscarPorId($id);
    }

    public static function criar(Evento $evento): int
    {
        return Evento::criar($evento);
    }

    public static function atualizar(Evento $evento): bool
    {
        return Evento::atualizar($evento);
    }

    public static function deletar(int $id): bool
    {
        return Evento::deletar($id);
    }
}
