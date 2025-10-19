<?php

namespace services\core;

use models\core\Excecao;

class ExcecaoService
{
    public static function reportarErro(array $payload, int $idUsuario): void
    {
        $excecao = new Excecao();
        $excecao->mensagem = $payload['mensagem'];
        $excecao->passos = $payload['passos'];
        $excecao->idUsuario = $idUsuario;
        Excecao::reportar($excecao);
    }

    public static function buscarExcecoes(): array
    {
        return Excecao::buscarExcecoes();
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        Excecao::atualizarStatus($id, $status);
    }
}
