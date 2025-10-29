<?php

namespace api\controllers;

use models\campanha\Novidade;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpDelete;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\tipos\http\atributos\HttpPut;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;
use modules\core\utils\Http;
use services\campanha\NovidadeService;
use services\core\MidiaService;

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
        $novidade->idRecompensa = $_POST['idRecompensa'] ?? null;
        NovidadeService::criar_novidade($novidade, ControllerBase::$usuarioAutenticado->idUsuario);

        try {
            MidiaService::processarMidias($_FILES, [], $novidade->id, 'Novidade');
        } catch (\Exception $e) {
            Http::HttpResponse(400, $e->getMessage());
        }

        $url = $_ENV['CORS_ORIGIN'] . '/novidade/' . $novidade->id;
        $link = new Link(LinkRel::SELF, $url, "Novidade criada");
        $links = array($link);
        echo json_encode(['message' => "Novidade criada com sucesso", '_links' => $links], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPut('/novidade')]
    public function editar(Novidade $novidade): void
    {
        $novidade->idRecompensa = $_POST['idRecompensa'] ?? null;
        NovidadeService::editar_novidade($novidade, ControllerBase::$usuarioAutenticado->idUsuario);

        $mediaData = json_decode($_POST['mediaData'] ?? '[]', true);

        try {
            $filesToProcess = [];
            if (isset($_FILES['media'])) {
                $filesToProcess['newMediaFiles'] = $_FILES['media'];
            }
            MidiaService::processarMidias($filesToProcess, [], $novidade->id, 'Novidade');
        } catch (\Exception $e) {
            Http::HttpResponse(400, $e->getMessage());
        }

        echo json_encode(['message' => "Novidade atualizada com sucesso"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
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