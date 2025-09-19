<?php

namespace api\controllers;

use Firebase\JWT\JWT;
use models\Usuario;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\utils\Http;
use modules\core\utils\Utils;
use services\integrations\email\EmailService;
use services\usuario\UsuarioService;

class UsuarioController extends ControllerBase {

    #[HttpGet('/usuario')]
    public function buscar_usuario(): void
    {
        $idUsuario = $_GET['idUsuario'] ?? null;
        $resp = Usuario::buscar_usuario($idUsuario);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpGet('/usuarios')]
    public function listar(): void
    {
       $resp = Usuario::buscar_usuarios();
       echo json_encode($resp, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }

    #[HttpPost('/usuario', auth: false)]
    public function salvar(Usuario $usuario): void
    {
        $usuarioConsulta = Usuario::buscar_usuario_por_email($usuario->email);
        if ($usuarioConsulta) {
            Http::HttpResponse(409, "Email já cadastrado");
        }

        $resp = UsuarioService::salvar_usuario($usuario);

        Http::HttpResponse(200, $resp, [
            'idUsuario' => $usuario->idUsuario,
            'nomeUsuario' => $usuario->nomeUsuario,
            'email' => $usuario->email,
        ]);
    }

    #[HttpPut("/usuario/editarPerfil")]
    public function editar_perfil(Usuario $usuario): void
    {
        $usuario->idUsuario = ControllerBase::$usuarioAutenticado->idUsuario;
        $msg = UsuarioService::editar_usuario($usuario);
        echo json_encode(['data' => ['message' => $msg]], JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
    }


    #[HttpPost('/verificarConta')]
    public function verificar_conta(Usuario $usuario): void
    {
        $resultado = Usuario::buscarUsuarioPorId($usuario->idUsuario);
        $agora = new \DateTime();

        if ($resultado->codigoVerificacao !== $usuario->codigoVerificacao) {
            Http::HttpResponse(403, "Código de verificação inválido");
        }

        $expiracao = new \DateTime($resultado->expiracaoCodigo);

        if ($agora > $expiracao) {
            Http::HttpResponse(400, "Código expirado");
        }

        Usuario::verificarUsuario($usuario->idUsuario);

        $emailService = new EmailService();
        $emailService->enviar($resultado->email, $resultado->nomeUsuario, "Verificar conta crowd repository",
            "Conta verificada com sucesso! Aproveite o crowd repository!");

        Http::HttpResponse(200, "Conta verificada com sucesso!");
    }

    #[HttpPost('/reenviarCodigo')]
    public function reenviar_codigo(Usuario $usuario): void
    {
        $result = Usuario::buscarUsuarioPorId($usuario->idUsuario);
        Usuario::gerarNovoCodigo($result);

        $emailService = new EmailService();
        $emailService->enviar(
            $result->email,
            $result->nomeUsuario,
            "Verificar conta crowd repository",
            "O seu código de verificação é <b>{$result->codigoVerificacao}</b>"
        );

        Http::HttpResponse(201, "Um código de verificação foi enviado para " . Utils::mascararEmail($result->email));
    }

}