<?php

namespace api\controllers;

use models\social\Curtida;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;

class CurtidaController extends ControllerBase
{
    #[HttpGet('/curtidas')]
    public function listarPorAlvo(): void
    {
        $tipoAlvo = $_GET['tipoAlvo'] ?? null;
        $idAlvo = $_GET['idAlvo'] ?? null;

        if (!$tipoAlvo || !$idAlvo) {
            http_response_code(400);
            echo json_encode(['message' => 'Os parâmetros tipoAlvo e idAlvo são obrigatórios.']);
            return;
        }

        $usuarios = Curtida::buscarPorAlvo($tipoAlvo, (int)$idAlvo);
        echo json_encode($usuarios, JSON_UNESCAPED_UNICODE);
    }
}
