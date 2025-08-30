<?php


namespace api\controllers;

use models\Comentario;
use models\Denuncia;
use models\Novidade;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class DenunciaController extends ControllerBase
{
    #[HttpPost('/denuncia')]
    public function salvar(Denuncia $denuncia): void
    {
        $res = Denuncia::denunciarCampanha($denuncia);


    }
}