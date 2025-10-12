<?php

namespace services\core;

use modules\db\Database;
use services\integrations\google\GoogleCloudStorageService;
use modules\core\utils\File;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;

class MidiaService
{
    const MAX_IMAGES_NOVITY = 4;
    const MAX_VIDEOS_NOVITY = 1;
    const MAX_IMAGES_CAMPAIGN = 4;
    const MAX_VIDEOS_CAMPAIGN = 1;

    public static function salvarImagemMarkdown(array $file, int $idUsuario): array
    {
        $subfolder = 'markdown_images/' . $idUsuario . '/';
        $resultadoUpload = File::salvarImagem($file, null, 5 * 1024 * 1024, $subfolder);

        if ($resultadoUpload['success']) {
            return ['path' => $resultadoUpload['filePath']];
        } else {
            throw new \Exception("Falha no upload da imagem para o markdown: " . $resultadoUpload['message']);
        }
    }

    public static function processarMidias(
        array $newFilesRaw,
        array $existingMediaData,
        int $idEntidade,
        string $tipoEntidade
    ): void {
        $pdo = Database::getConnection();

        if (empty($idEntidade) || empty($tipoEntidade)) {
            throw new \InvalidArgumentException("É necessário fornecer um idEntidade e tipoEntidade.");
        }

        $newFiles = [];
        if (isset($newFilesRaw['newMediaFiles'])) {
            $files = $newFilesRaw['newMediaFiles'];
            if (is_array($files['name'])) {
                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === UPLOAD_ERR_OK) {
                        $newFiles[] = [
                            'name' => $name,
                            'type' => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error' => $files['error'][$key],
                            'size' => $files['size'][$key],
                            'isCover' => isset($_POST["newMediaFiles_" . $key . "_isCover"]) ? (bool)$_POST["newMediaFiles_" . $key . "_isCover"] : false,
                        ];
                    }
                }
            } else if ($files['error'] === UPLOAD_ERR_OK) {
                $newFiles[] = [
                    'name' => $files['name'],
                    'type' => $files['type'],
                    'tmp_name' => $files['tmp_name'],
                    'error' => $files['error'],
                    'size' => $files['size'],
                    'isCover' => isset($_POST["newMediaFiles_0_isCover"]) ? (bool)$_POST["newMediaFiles_0_isCover"] : false,
                ];
            }
        }

        $currentImageCount = 0;
        $currentVideoCount = 0;

        foreach ($existingMediaData as $media) {
            if (!($media['isDeleted'] ?? false) && isset($media['idMidia'])) {
                $stmt = $pdo->prepare("SELECT tipo FROM Midia WHERE idMidia = :idMidia");
                $stmt->execute([':idMidia' => $media['idMidia']]);
                $dbMedia = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($dbMedia) {
                    if ($dbMedia['tipo'] === 'imagem') $currentImageCount++;
                    if ($dbMedia['tipo'] === 'video') $currentVideoCount++;
                }
            }
        }

        $newImageCount = 0;
        $newVideoCount = 0;
        foreach ($newFiles as $fileData) {
            $mimeType = mime_content_type($fileData['tmp_name']);
            if (str_starts_with($mimeType, 'image/')) $newImageCount++;
            if (str_starts_with($mimeType, 'video/')) $newVideoCount++;
        }

        $maxImages = $tipoEntidade === 'Campanha' ? self::MAX_IMAGES_CAMPAIGN : self::MAX_IMAGES_NOVITY;
        $maxVideos = $tipoEntidade === 'Campanha' ? self::MAX_VIDEOS_CAMPAIGN : self::MAX_VIDEOS_NOVITY;

        if (($currentImageCount + $newImageCount) > $maxImages) {
            throw new \Exception("Limite de " . $maxImages . " imagens para " . $tipoEntidade . " excedido.");
        }
        if (($currentVideoCount + $newVideoCount) > $maxVideos) {
            throw new \Exception("Limite de " . $maxVideos . " vídeos para " . $tipoEntidade . " excedido.");
        }

        try {
            foreach ($existingMediaData as $media) {
                if (($media['isDeleted'] ?? false) && isset($media['idMidia'])) {
                    $stmt = $pdo->prepare("SELECT caminhoArquivo FROM Midia WHERE idMidia = :idMidia");
                    $stmt->execute([':idMidia' => $media['idMidia']]);
                    $dbMedia = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($dbMedia) {
                        GoogleCloudStorageService::deleteFile($dbMedia['caminhoArquivo']);
                        $stmt = $pdo->prepare("DELETE FROM Midia WHERE idMidia = :idMidia");
                        $stmt->execute([':idMidia' => $media['idMidia']]);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Erro ao deletar mídia existente: " . $e->getMessage());
        }

        // Reset all covers for the entity first, before inserting new media that might be covers
        $updateSql = "UPDATE Midia SET isCapa = 0 WHERE idEntidade = :idEntidade AND tipoEntidade = :tipoEntidade";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([':idEntidade' => $idEntidade, ':tipoEntidade' => $tipoEntidade]);

        $newlyUploadedMidiaIds = [];
        foreach ($newFiles as $fileData) {
            $mimeType = mime_content_type($fileData['tmp_name']);
            $tipoMidia = str_starts_with($mimeType, 'image/') ? 'imagem' : (str_starts_with($mimeType, 'video/') ? 'video' : null);

            if (!$tipoMidia) {
                throw new \Exception("Tipo de mídia não suportado: " . $mimeType);
            }

            $caminhoArquivo = null;
            if ($tipoMidia === 'imagem') {
                $resultadoUpload = File::salvarImagem($fileData);
                if ($resultadoUpload['success']) {
                    $caminhoArquivo = $resultadoUpload['filePath'];
                } else {
                    throw new \Exception("Falha no upload da imagem: " . $resultadoUpload['message']);
                }
            } elseif ($tipoMidia === 'video') {
                $tempVideoPath = $fileData['tmp_name'];
                $outputFileName = uniqid('compressed_video_', true) . '.mp4';
                $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $outputFileName;

                try {
                    $ffmpeg = FFMpeg::create([
                        'ffmpeg.binaries'  => $_ENV['FFMPEG_PATH'],
                        'ffprobe.binaries' => $_ENV['FFPROBE_PATH']
                    ]);
                    $video = $ffmpeg->open($tempVideoPath);
                    $format = new X264();
                    $format->setKiloBitrate(1000);
                    $video->save($format, $outputPath);

                    $objectName = GoogleCloudStorageService::uploadFile($outputPath, $outputFileName);
                    $caminhoArquivo = $objectName;

                    unlink($outputPath);
                } catch (\Exception $e) {
                    throw new \Exception("Falha na compressão ou upload do vídeo: " . $e->getMessage());
                }
            }

            if ($caminhoArquivo) {
                $stmt = $pdo->prepare("INSERT INTO Midia (idEntidade, tipoEntidade, caminhoArquivo, tipo, isCapa) VALUES (:idEntidade, :tipoEntidade, :caminhoArquivo, :tipo, :isCapa)");
                $stmt->execute([
                    ':idEntidade' => $idEntidade,
                    ':tipoEntidade' => $tipoEntidade,
                    ':caminhoArquivo' => $caminhoArquivo,
                    ':tipo' => $tipoMidia,
                    ':isCapa' => (isset($fileData['isCover']) && ($fileData['isCover'] === true || $fileData['isCover'] === 'true' || $fileData['isCover'] === 1 || $fileData['isCover'] === '1')) ? 1 : 0,
                ]);
                $newlyUploadedMidiaIds[] = $pdo->lastInsertId();
            }
        }

        try {
            $coverMidiaId = null;
            foreach ($existingMediaData as $media) {
                if (($media['isCover'] ?? false) && !($media['isDeleted'] ?? false) && isset($media['idMidia'])) {
                    $coverMidiaId = $media['idMidia'];
                    break;
                }
            }

            if ($coverMidiaId === null) {
                foreach ($newFiles as $index => $fileData) {
                    if (($fileData['isCover'] ?? false) && isset($newlyUploadedMidiaIds[$index])) {
                        $coverMidiaId = $newlyUploadedMidiaIds[$index];
                        break;
                    }
                }
            }

            if ($coverMidiaId !== null) {
                $stmt = $pdo->prepare("UPDATE Midia SET isCapa = 1 WHERE idMidia = :idMidia");
                $stmt->execute([':idMidia' => $coverMidiaId]);
            }
        } catch (\Exception $e) {
            throw new \Exception("Erro ao definir mídia de capa: " . $e->getMessage());
        }
    }
}