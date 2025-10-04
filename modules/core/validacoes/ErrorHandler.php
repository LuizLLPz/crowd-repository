<?php

namespace modules\core\validacoes;

use models\core\Excecao;
use Throwable;

class ErrorHandler
{
    public static function registrar(): void
    {
        set_exception_handler([self::class, 'tratarExcecao']);
        set_error_handler([self::class, 'tratarErro']);
        register_shutdown_function([self::class, 'tratarErroFatal']);
    }

    public static function tratarExcecao(Throwable $e): void
    {
        self::salvarExcecao($e);
        self::responderJson(500, self::getMensagemErro($e));
    }

    public static function tratarErro(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        $excecao = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        self::salvarExcecao($excecao);
        self::responderJson(500, self::getMensagemErro($excecao));
        return true;
    }

    public static function tratarErroFatal(): void
    {
        $erro = error_get_last();
        if ($erro && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $excecao = new \ErrorException($erro['message'], 0, $erro['type'], $erro['file'], $erro['line']);
            self::salvarExcecao($excecao);
            self::responderJson(500, self::getMensagemErro($excecao));
        }
    }

    private static function salvarExcecao(Throwable $e): void
    {
        Excecao::salvar($e);
    }

    private static function getMensagemErro(Throwable $e): array
    {
        if (getenv('APP_ENV') === 'production') {
            return ['error' => 'Ocorreu um erro inesperado. Nossa equipe já foi notificada.'];
        }

        return [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ];
    }

    private static function responderJson(int $codigo, array $dados): void
    {
        if (headers_sent()) {
            return;
        }
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode($dados);
        exit;
    }
}