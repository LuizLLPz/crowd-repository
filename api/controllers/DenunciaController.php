<?php


namespace api\controllers;

use models\campanha\Denuncia;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\Http;

class DenunciaController extends ControllerBase
{
    #[HttpPost('/denuncia') ]
    public function salvar(Denuncia $denuncia): void
    {
        $denuncia->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $res = Denuncia::denunciarCampanha($denuncia);
        Http::HttpResponse(200, $res);
    }
}