<?php

namespace services\integrations\google;

use Google\Cloud\Storage\StorageClient;

class GoogleCloudStorageService
{
    public static function uploadFile(string $localFilePath, string $destinationObjectName): string
    {
        try {
            $storage = new StorageClient();
            $bucketName = $_ENV['GCS_BUCKET_NAME'];
            $bucket = $storage->bucket($bucketName);

            $bucket->upload(fopen($localFilePath, 'r'), [
                'name' => $destinationObjectName
            ]);

            return $destinationObjectName;

        } catch (\Exception $e) {
            throw new \Exception('Falha ao fazer upload para o Google Cloud Storage: ' . $e->getMessage());
        }
    }

    public static function getSignedUrl(string $objectName, int $expirationMinutes = 15): string
    {
        try {
            error_log('GoogleCloudStorageService::getSignedUrl - Object Name: ' . $objectName);
            $objectName = trim($objectName);
            $storage = new StorageClient();
            $bucketName = $_ENV['GCS_BUCKET_NAME'];
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->object($objectName);
            $url = $object->signedUrl(
                new \DateTime("+$expirationMinutes minutes"),
                [
                    'version' => 'v4',
                ]
            );
            error_log('GoogleCloudStorageService::getSignedUrl - Generated URL: ' . $url);

            return $url;
        } catch (\Exception $e) {
            error_log('GoogleCloudStorageService::getSignedUrl - Error: ' . $e->getMessage());
            return '';
        }
    }

    public static function deleteFile(string $objectName): void
    {
        try {
            $storage = new StorageClient();
            $bucketName = $_ENV['GCS_BUCKET_NAME'];
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->object($objectName);

            $object->delete();
        } catch (\Exception $e) {
            error_log('GoogleCloudStorageService::deleteFile - Error: ' . $e->getMessage());
        }
    }
}
