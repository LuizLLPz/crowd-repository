<?php

namespace api\controllers;

use models\social\Comentario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\File;
use modules\core\utils\Http;
use services\social\ComentarioService;

class ComentarioController extends ControllerBase
{
    #[HttpGet('/novidade/comentarios')]
    public function listar(): void
    {
        $idCampanha = $_GET['idNovidade'];
        $resp = Comentario::listar($idCampanha, ControllerBase::$usuarioAutenticado->idUsuario);
        Http::HttpResponse(200, "Registros encontrados", $resp);
    }

    #[HttpPost('/novidade/comentario')]
    public function salvar(): void
    {
        $comentario = new Comentario();
        $comentario->idNovidade = $_POST['idNovidade'] ?? null;
        $comentarioTexto = $_POST['comentario'] ?? '';
        $comentario->comentario = ($comentarioTexto === 'undefined' || $comentarioTexto === 'null') ? '' : $comentarioTexto;
        $comentario->idComentarioReferenciado = $_POST['idComentarioReferenciado'] !== 'null' && $_POST['idComentarioReferenciado'] ? $_POST['idComentarioReferenciado'] : null;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
            $uploadResult = File::salvarImagem($_FILES['imagem']);
            if ($uploadResult['success']) {
                $comentario->caminhoImagem = $uploadResult['filePath'];
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Erro ao salvar imagem: ' . $uploadResult['message']]);
                return;
            }
        }

        $result = ComentarioService::criar_comentario($comentario, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Comentario feito com sucesso", 'data' => $result], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/novidade/comentario/curtir')]
    public function curtir(Comentario $comentario): void
    {
        ComentarioService::curtir_comentario($comentario->id, ControllerBase::$usuarioAutenticado->idUsuario);
        echo json_encode(['message' => "Comentario curtido com sucesso"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}