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

        $uploadsBaseDir = __DIR__ . '/../../../uploads';
        $destinationDirectory = $uploadsBaseDir . '/images';
        $fileTmpName = $fileData['tmp_name'];

        if (!file_exists($fileTmpName)) {
            return ['success' => false, 'message' => 'Arquivo temporário não encontrado.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];

        $fileExtension = array_search($detectedMimeType, $allowedMimeTypes, true);
        if ($fileExtension === false) {
            return ['success' => false, 'message' => "Tipo de arquivo (MIME) inválido. Permitidos: " . implode(', ', array_keys($allowedMimeTypes))];
        }

        if (!is_dir($destinationDirectory)) {
            if (!mkdir($destinationDirectory, 0775, true)) {
                return ['success' => false, 'message' => "Falha ao criar o diretório de destino."];
            }
        }

        $baseName = $fileName ? pathinfo($fileName, PATHINFO_FILENAME) : uniqid('', true);
        $sanitizedBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $newFileName = $sanitizedBaseName . '.' . $fileExtension;
        $filePath = rtrim($destinationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFileName;

        $moveSuccess = false;
        if (is_uploaded_file($fileTmpName)) {
            $moveSuccess = move_uploaded_file($fileTmpName, $filePath);
        } else {
            $moveSuccess = rename($fileTmpName, $filePath);
        }

        if ($moveSuccess) {
            $relativePath = 'uploads/images/' . $newFileName;
            return [
                'success' => true,
                'filePath' => $filePath,
                'relativePath' => $relativePath,
                'message' => 'Arquivo salvo com sucesso.'
            ];
        } else {
            if (file_exists($fileTmpName)) {
                unlink($fileTmpName);
            }
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