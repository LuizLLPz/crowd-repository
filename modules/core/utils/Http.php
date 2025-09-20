<?php

namespace modules\core\utils;

use JetBrains\PhpStorm\NoReturn;

class Http
{
    #[NoReturn] public static function HttpResponse(int $status, string $msg, mixed $additionalData = []): void
    {
        $response = ($status >= 400) ? 'error' : 'message';
        http_response_code($status);
        echo json_encode([$response => $msg, "payload" => $additionalData]);
        die();
    }
}