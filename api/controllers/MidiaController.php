<?php

namespace api\controllers;

use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\Http;
use services\core\MidiaService;
use services\integrations\google\GoogleCloudStorageService;

class MidiaController extends ControllerBase
{
    #[HttpPost('/midia/upload-markdown-image')]
    public function uploadMarkdownImage(): void
    {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $idUsuario = self::$usuarioAutenticado->idUsuario;
                $file = $_FILES['image'];

                $resultado = MidiaService::salvarImagemMarkdown($file, $idUsuario);
                $path = $resultado['path'];
                $signedUrl = GoogleCloudStorageService::getSignedUrl($path);

                Http::HttpResponse(200, "Imagem enviada com sucesso", [
                    'url' => $signedUrl,
                    'path' => $path
                ]);

            } catch (\Exception $e) {
                Http::HttpResponse(500, "Erro ao salvar a imagem: " . $e->getMessage());
            }
        } else {
            Http::HttpResponse(400, "Nenhum arquivo de imagem válido foi enviado.");
        }
    }
}
