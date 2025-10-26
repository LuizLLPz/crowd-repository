<?php

namespace api\controllers;

use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\utils\Http;
use services\DashboardService;
use function modules\core\utils\Http;

class DashboardController extends ControllerBase {

    #[HttpGet('/dashboard')]
    public function getDashboardData() {
        if (!isset($_GET['idCampanha']) || !is_numeric($_GET['idCampanha'])) {
            Http::HttpResponse(400, 'idCampanha is required and must be a number.');
        }
        $idCampanha = (int)$_GET['idCampanha'];
        $dashboardService = new DashboardService();
        $data = $dashboardService->getDashboardData($idCampanha);
        Http::HttpResponse(200, "Dados encontrados", $data);
    }
}
