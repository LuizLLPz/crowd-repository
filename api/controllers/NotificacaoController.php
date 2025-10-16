<?php
namespace api\controllers;

use models\core\Notificacao;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\http\atributos\HttpGet;
use modules\core\tipos\http\atributos\HttpPost;
use modules\core\utils\Http;


class NotificacaoController extends ControllerBase {
    #[HttpGet('/notificacoes')]
    public function listar(): void
    {
        $idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $lidas = $_GET['lidas'] ?? null;
        $limite = $_GET['limite'] ?? 20;

        $lidasBool = null;
        if ($lidas !== null) {
            $lidasBool = filter_var($lidas, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $resp = Notificacao::buscarPorUsuario($idUsuario, $lidasBool, (int)$limite);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/notificacao/marcarLida')]
    public function marcarComoLida(Notificacao $notificacao): void
    {
        $idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        Notificacao::marcarComoLida($notificacao->idNotificacao, $idUsuario);

        Http::HttpResponse(200, "Notificação marcada como lida.");

    }

    #[HttpPost('/notificacao/marcarTodasLidas')]
    public function marcarTodasComoLidas(): void
    {
        $idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
         Notificacao::marcarTodasComoLidas($idUsuario);

        Http::HttpResponse(200, "Todas as notificações marcadas como lidas.");
    }

}