<?php

namespace api\controllers;

use models\Novidade;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
use services\campanha\NovidadeService;

class NovidadeController extends ControllerBase
{
    #[HttpGet('/novidade')]
    public function obter(): void
    {
        $idNovidade = $_GET["idNovidade"];
        $resp = Novidade::obter($idNovidade, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/novidades')]
    public function listar(): void
    {
        $idCampanha = $_GET['idCampanha'];
        $resp = Novidade::listar($idCampanha, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade')]
    public function salvar(Novidade $novidade): void
    {
        $imagemFile = isset($_FILES['imagem']) ? $_FILES['imagem'] : null;
        $url = NovidadeService::criar_novidade($novidade, ControllerBase::$usuarioAutenticado->idUsuario, $imagemFile);
        $link = new Link(LinkRel::SELF, $url, "Novidade criada");
        $links = array($link);
        echo json_encode(['message' => "Novidade criada com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade/curtir')]
    public function curtir(Novidade $novidade): void
    {
        NovidadeService::curtir_novidade($novidade->id, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Comentario curtido com sucesso"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpDelete('/novidade')]
    public function deletar(): void
    {
        $id = $_GET['idNovidade'];
        NovidadeService::deletar_novidade($id, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Novidade deletada com sucesso"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}