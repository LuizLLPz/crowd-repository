<?php

namespace models\core;

use modules\core\tipos\Entidade;
use modules\db\Database;
use services\core\EncryptionService;

class Configuracao extends Entidade
{
    public string $nomeTabela = "Configuracoes";
    public int $id;
    public string $provedor;
    public string $chave;
    public ?string $valor;
    public bool $is_encrypted;

    private static ?EncryptionService $encryptionService = null;

    private static function getEncryptionService(): EncryptionService
    {
        if (self::$encryptionService === null) {
            self::$encryptionService = new EncryptionService();
        }
        return self::$encryptionService;
    }

    public static function getChave(string $provedor, string $chave): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT valor, is_encrypted FROM Configuracoes WHERE provedor = :provedor AND chave = :chave");
        $stmt->execute([':provedor' => $provedor, ':chave' => $chave]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result || $result['valor'] === null) {
            return null;
        }

        if ($result['is_encrypted']) {
            return self::getEncryptionService()->decrypt($result['valor']);
        }

        return $result['valor'];
    }
    
    public static function getConfig(string $provedor): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT chave, valor, is_encrypted FROM Configuracoes WHERE provedor = :provedor");
        $stmt->execute([':provedor' => $provedor]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $config = [];
        foreach ($results as $row) {
            if ($row['is_encrypted'] && $row['valor'] !== null) {
                $config[$row['chave']] = self::getEncryptionService()->decrypt($row['valor']);
            } else {
                $config[$row['chave']] = $row['valor'];
            }
        }
        return $config;
    }

    public static function setChave(string $provedor, string $chave, ?string $valor, bool $encrypt = false): void
    {
        $finalValue = $valor;
        if ($encrypt && $valor !== null) {
            $finalValue = self::getEncryptionService()->encrypt($valor);
        }

        $pdo = Database::getConnection();
        $sql = "INSERT INTO Configuracoes (provedor, chave, valor, is_encrypted)
                VALUES (:provedor, :chave, :valor, :is_encrypted)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), is_encrypted = VALUES(is_encrypted)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':provedor' => $provedor,
            ':chave' => $chave,
            ':valor' => $finalValue,
            ':is_encrypted' => (int)$encrypt
        ]);
    }
}
