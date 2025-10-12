<?php

namespace services\core;

use models\core\EmailTemplate;

class EmailTemplateService
{
    public static function listar(?string $pesquisa = null): array
    {
        return EmailTemplate::buscar($pesquisa);
    }

    public static function obterPorId(int $id): ?EmailTemplate
    {
        return EmailTemplate::buscarPorId($id);
    }

    public static function criar(EmailTemplate $template): int
    {
        return EmailTemplate::criar($template);
    }

    public static function atualizar(EmailTemplate $template): bool
    {
        return EmailTemplate::atualizar($template);
    }

    public static function deletar(int $id): bool
    {
        return EmailTemplate::deletar($id);
    }
}
