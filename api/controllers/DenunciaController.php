<?php

namespace api\controllers;

use models\campanha\Denuncia;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\core\utils\Http;
use services\core\DenunciaService;

class DenunciaController extends ControllerBase
{
    #[HttpGet('/denuncias', funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function listar(): void
    {
        $idAlvo = $_GET['idAlvo'] ?? null;
        $tipoAlvo = $_GET['tipoAlvo'] ?? null;
        $resp = Denuncia::buscarDenuncias($idAlvo, $tipoAlvo);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/denuncia') ]
    public function salvar(Denuncia $denuncia): void
    {
        $denuncia->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $res = DenunciaService::denunciar($denuncia);
        Http::HttpResponse(200, $res);
    }

   #[HttpPost('/denuncia/atender', funcaoUsuario: FuncaoUsuario::ADMIN)]
    public function atender_denuncia(Denuncia $denuncia): void
    {
        $denuncia->idAtendente = ControllerBase::$usuarioAutenticado->idUsuario;
        DenunciaService::atender_denuncia($denuncia);
        Http::HttpResponse(200, "Denúncia atendida com sucesso!");
    }
}