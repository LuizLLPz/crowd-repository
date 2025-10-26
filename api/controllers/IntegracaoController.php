<?php

namespace api\controllers;

use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\atributos\HttpDelete;
use services\GitIntegrationService;

class IntegracaoController extends ControllerBase
{
    #[HttpPost('/campanha/{idCampanha}/integracao')]
    public function salvarIntegracao($idCampanha)
    {
        $service = new GitIntegrationService();
        $body = $this->getRawBody();
        $result = $service->salvarIntegracao(
            $idCampanha,
            $body['plataforma'],
            $body['urlRepositorio'],
            $body['tokenAcesso']
        );
        $this->ok($result);
    }

    #[HttpGet('/campanha/{idCampanha}/integracao/commits')]
    public function obterCommits($idCampanha)
    {
        $service = new GitIntegrationService();
        $commits = $service->obterCommits($idCampanha);
        $this->ok($commits);
    }

    #[HttpDelete('/campanha/{idCampanha}/integracao')]
    public function removerIntegracao($idCampanha)
    {
        $service = new GitIntegrationService();
        $result = $service->removerIntegracao($idCampanha);
        $this->ok($result);
    }
    
    #[HttpGet('/campanha/{idCampanha}/integracao')]
    public function obterIntegracao($idCampanha)
    {
        $service = new GitIntegrationService();
        $integration = $service->obterIntegracao($idCampanha);
        $this->ok($integration);
    }
}
