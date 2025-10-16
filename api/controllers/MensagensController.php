<?php

namespace api\controllers;

use models\social\Mensagens;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use services\social\MensagemService;

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
        foreach ($resp as &$mensagem) {
            if (isset($mensagem['reacoes']) && is_string($mensagem['reacoes'])) {
                $mensagem['reacoes'] = json_decode($mensagem['reacoes'], true);
            }
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/enviarmensagem')]
    public function enviarMensagem(Mensagens $mensagem): void
    {
        $novaMensagem = MensagemService::enviar_mensagem($mensagem->chatId, ControllerBase::$usuarioAutenticado->idUsuario, $mensagem->mensagem);
        echo json_encode($novaMensagem, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

}