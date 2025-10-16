<?php

namespace api\controllers;

use models\core\EmailTemplate;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpDelete;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\atributos\HttpPut;
use modules\core\utils\Http;
use services\core\EmailTemplateService;

class EmailTemplateController extends ControllerBase
{
    #[HttpGet('/email-templates')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $resp = EmailTemplateService::listar($pesquisa);
        Http::HttpResponse(200, 'Templates listados com sucesso', $resp);
    }

    #[HttpGet('/email-template')]
    public function obterPorId(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            Http::HttpResponse(400, 'ID do template não fornecido.');
            return;
        }
        $template = EmailTemplateService::obterPorId((int)$id);
        if ($template) {
            Http::HttpResponse(200, 'Template obtido com sucesso', $template);
        } else {
            Http::HttpResponse(404, 'Template de email não encontrado');
        }
    }

    #[HttpPost('/email-templates')]
    public function criar(EmailTemplate $template): void
    {
        $novoId = EmailTemplateService::criar($template);
        $novoTemplate = EmailTemplateService::obterPorId($novoId);
        Http::HttpResponse(201, 'Template criado com sucesso', $novoTemplate);
    }

    #[HttpPut('/email-template')]
    public function atualizar(EmailTemplate $template): void
    {
        $sucesso = EmailTemplateService::atualizar($template);
        if ($sucesso) {
            $templateAtualizado = EmailTemplateService::obterPorId($template->id);
            Http::HttpResponse(200, 'Template atualizado com sucesso', $templateAtualizado);
        } else {
            Http::HttpResponse(500, 'Falha ao atualizar o template de email');
        }
    }

    #[HttpDelete('/email-template')]
    public function deletar(EmailTemplate $template): void
    {
        $sucesso = EmailTemplateService::deletar($template->id);
        if ($sucesso) {
            Http::HttpResponse(204, 'Template deletado com sucesso');
        } else {
            Http::HttpResponse(500, 'Falha ao deletar o template de email');
        }
    }
}
