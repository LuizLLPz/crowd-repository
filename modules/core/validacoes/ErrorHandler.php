<?php

namespace modules\core\validacoes;

class ErrorHandler
{
    public static function registrar(): void
    {
        set_exception_handler([self::class, 'tratarExcecao']);
        set_error_handler([self::class, 'tratarErro']);
        register_shutdown_function([self::class, 'tratarErroFatal']);
    }

    public static function tratarExcecao(\Throwable $e): void
    {
        self::responderJson(500, [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }

    public static function tratarErro(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        self::responderJson(500, [
            'error' => $errstr,
            'file'  => $errfile,
            'line'  => $errline,
        ]);

        return true;
    }

    public static function tratarErroFatal(): void
    {
        $erro = error_get_last();
        if ($erro && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::responderJson(500, [
                'error' => $erro['message'],
                'file'  => $erro['file'],
                'line'  => $erro['line'],
            ]);
        }
    }

    private static function responderJson(int $codigo, array $dados): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode($dados);
        exit;
    }
}
