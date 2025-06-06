<?php

namespace modules\core\utils;

class File
{
    public static function salvarImagem(
        array $fileData,
        ?string $fileName = null,
        int $maxFileSize = 5 * 1024 * 1024,
    ): array {
        if (!isset($fileData['error']) || is_array($fileData['error'])) {
            return ['success' => false, 'message' => 'Parâmetros de arquivo inválidos.'];
        }

        switch ($fileData['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'message' => 'Nenhum arquivo foi enviado.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'message' => "O arquivo excede o tamanho máximo permitido."];
            default:
                return ['success' => false, 'message' => "Erro desconhecido no upload do arquivo."];
        }

        if ($fileData['size'] > $maxFileSize) {
            return ['success' => false, 'message' => "O arquivo excede o tamanho máximo permitido de " . ($maxFileSize / 1024 / 1024) . "MB."];
        }

        $destinationDirectory = __DIR__ . '/../../../uploads/images';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileTmpName = $fileData['tmp_name'];
        $fileType = $fileData['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'message' => "Extensão de arquivo inválida. Permitidas: " . implode(', ', $allowedExtensions)];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = finfo_file($finfo, $fileTmpName);
            finfo_close($finfo);
            if (!in_array($detectedMimeType, $allowedMimeTypes)) {
                return ['success' => false, 'message' => "Tipo de arquivo (MIME) inválido detectado no servidor. Permitidos: " . implode(', ', $allowedMimeTypes)];
            }
        } elseif (!in_array($fileType, $allowedMimeTypes)) {
            return ['success' => false, 'message' => "Tipo de arquivo (MIME) inválido enviado pelo navegador. Permitidos: " . implode(', ', $allowedMimeTypes)];
        }

        if (!is_dir($destinationDirectory)) {
            if (!mkdir($destinationDirectory, 0775, true)) {
                return ['success' => false, 'message' => "Falha ao criar o diretório de destino: {$destinationDirectory}. Verifique as permissões."];
            }
        } elseif (!is_writable($destinationDirectory)) {
            return ['success' => false, 'message' => "O diretório de destino não tem permissão de escrita: {$destinationDirectory}."];
        }

        $fileNameWithoutExtension = $fileName ? pathinfo($fileName, PATHINFO_FILENAME) : null;
        $newFileName = ($fileNameWithoutExtension ?? uniqid('', true)) . '.' . $fileExtension;
        $filePath = rtrim($destinationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFileName;

        if (move_uploaded_file($fileTmpName, $filePath)) {
            $relativePath = 'uploads/images/' . $newFileName;
            return [
                'success' => true,
                'filePath' => $filePath,
                'relativePath' => $relativePath,
                'message' => 'Arquivo salvo com sucesso.'
            ];
        } else {
            return ['success' => false, 'message' => 'Falha ao mover o arquivo para o diretório de destino.'];
        }
    }


    public static function delete(string $filePath): bool
    {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}