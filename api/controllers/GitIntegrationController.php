<?php

namespace api\controllers;

use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\Http;
use models\campanha\CampanhaIntegracaoGit;
use services\GitIntegrationService;

class GitIntegrationController extends ControllerBase
{
    #[HttpPost('/campanha/integracao/git')]
    public function salvar(CampanhaIntegracaoGit $integracao): void
    {
        CampanhaIntegracaoGit::salvar($integracao);
        Http::HttpResponse(200, "Integração com Git salva com sucesso!");
    }

    #[HttpGet('/campanha/integracao/git/commits')]
    public function obterCommits(): void
    {
        $idCampanha = $_GET['idCampanha'] ?? null;
        if (!$idCampanha) {
            Http::HttpResponse(400, 'ID da campanha é obrigatório.');
            return;
        }

        $commits = GitIntegrationService::getCommits((int)$idCampanha);
        Http::HttpResponse(200, "Commits da campanha buscados com sucesso.", $commits);
    }
}
