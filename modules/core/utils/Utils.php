<?php

namespace modules\core\utils;

class Utils
{
    public static function mascararEmail(string $email): string
    {
        [$nome, $dominio] = explode('@', $email);
        $primeiros = substr($nome, 0, 2);
        $mascara = str_repeat('*', max(strlen($nome) - 2, 0));
        return $primeiros . $mascara . '@' . $dominio;
    }

    public static function getServerUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    public static function parse_multipart_form_data(?string $formData): array
    {
        $result = ['post' => [], 'files' => []];
        if (empty($formData)) {
            return $result['post'];
        }

        $boundary = substr($formData, 0, strpos($formData, "\r\n"));
        if (empty($boundary)) {
            return $result['post'];
        }

        $parts = array_slice(explode($boundary, $formData), 1);

        foreach ($parts as $part) {
            $part = ltrim($part, "\r\n");
            if (str_ends_with($part, "--\r\n")) {
                $part = substr($part, 0, -4);
            }

            if (trim($part) === '' || !str_contains($part, "\r\n\r\n")) {
                continue;
            }

            [$rawHeaders, $body] = explode("\r\n\r\n", $part, 2);
            $rawHeaders = trim($rawHeaders);

            $headers = [];
            foreach (explode("\r\n", $rawHeaders) as $header) {
                if (!str_contains($header, ':')) continue;
                [$name, $value] = explode(':', $header, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }

            if (empty($headers['content-disposition'])) {
                continue;
            }

            $matches = [];
            if (!preg_match('/form-data;\s*name="([^"]+)"(?:;\s*filename="([^"]+)")?/i', $headers['content-disposition'], $matches)) {
                continue;
            }
            $fieldName = $matches[1];
            $fileName = $matches[2] ?? null;

            if ($fileName !== null) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'php_upload_');
                file_put_contents($tmpPath, $body);

                $fileData = [
                    'name' => $fileName,
                    'type' => $headers['content-type'] ?? 'application/octet-stream',
                    'tmp_name' => $tmpPath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($body),
                ];

                if (str_ends_with($fieldName, '[]')) {
                    $fieldName = rtrim($fieldName, '[]');
                    $result['files'][$fieldName][] = $fileData;
                } else {
                    $result['files'][$fieldName] = $fileData;
                }
            } else {
                if (str_ends_with($fieldName, '[]')) {
                    $fieldName = rtrim($fieldName, '[]');
                    $result['post'][$fieldName][] = $body;
                } else {
                    $result['post'][$fieldName] = $body;
                }
            }
        }
        $_FILES = $result['files'];

        return $result['post'];
    }
}