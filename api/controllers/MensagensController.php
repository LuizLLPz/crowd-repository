<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Mensagens;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\http\tipos\Link;
use modules\core\tipos\LinkRel;

class MensagensController extends ControllerBase
{
    #[HttpGet('/mensagens')]
    public function listar(): void
    {
        $resp = Mensagens::buscarMensagens();
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/listarmensagensdeumchat')]
    public function listarMensagensDeUmChat(): void
    {
        $idChat = $_GET['idChat'];
        $resp = Mensagens::buscarMensagensDeUmChat($idChat);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/enviarmensagem')]
    public function enviarMensagem(Mensagens $mensagem): void
    {
        $url = Mensagens::criarMensagem($mensagem->chatId, ControllerBase::$usuarioAutenticado->idUsuario, $mensagem->mensagem);
        echo json_encode(['message' => "Mensagem feita com sucesso"], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}