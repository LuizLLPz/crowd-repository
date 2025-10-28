<?php

namespace models\core;

use modules\core\tipos\Entidade;
use modules\db\Database;


class ConfiguracaoNotificacaoUsuario extends Entidade
{
    public string $nomeTabela = "ConfiguracaoNotificacaoUsuario";
    public int $id;
    public int $idUsuario;
    public int $idEvento;
    public bool $enviaEmail;
    public bool $enviaPopup;

    public function __set(string $name, mixed $value): void
    {
        if ($name === 'enviaEmail' || $name === 'enviaPopup') {
            $this->{$name} = (bool)$value;
            return;
        }
        $this->{$name} = $value;
    }

    public static function buscarPorUsuario(int $idUsuario): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT
                e.id AS idEvento,
                e.codigo AS codigoEvento,
                e.titulo AS tituloEvento,
                e.descricao AS descricaoEvento,
                COALESCE(cnu.enviaEmail, 1) AS enviaEmail,
                COALESCE(cnu.enviaPopup, 1) AS enviaPopup
            FROM
                Evento e
            LEFT JOIN
                ConfiguracaoNotificacaoUsuario cnu ON e.id = cnu.idEvento AND cnu.idUsuario = :idUsuario
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as &
$result) {
            $result['enviaEmail'] = (bool)$result['enviaEmail'];
            $result['enviaPopup'] = (bool)$result['enviaPopup'];
        }

        return $results;
    }

    public static function salvarConfiguracoes(int $idUsuario, array $configs): bool
    {
        $pdo = Database::getConnection();
        $sql = "
            INSERT INTO ConfiguracaoNotificacaoUsuario (idUsuario, idEvento, enviaEmail, enviaPopup)
            VALUES (:idUsuario, :idEvento, :enviaEmail, :enviaPopup)
            ON DUPLICATE KEY UPDATE
                enviaEmail = VALUES(enviaEmail),
                enviaPopup = VALUES(enviaPopup)
        ";
        $stmt = $pdo->prepare($sql);

        try {
            $pdo->beginTransaction();
            foreach ($configs as $config) {
                $stmt->execute([
                    ':idUsuario' => $idUsuario,
                    ':idEvento' => $config->idEvento,
                    ':enviaEmail' => (int)$config->enviaEmail,
                    ':enviaPopup' => (int)$config->enviaPopup,
                ]);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Erro ao salvar configurações de notificação: " . $e->getMessage());
            return false;
        }
    }

    public static function buscarPorUsuarioEEvento(int $idUsuario, int $idEvento): ?object
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT enviaEmail, enviaPopup FROM ConfiguracaoNotificacaoUsuario WHERE idUsuario = :idUsuario AND idEvento = :idEvento");
        $stmt->execute([':idUsuario' => $idUsuario, ':idEvento' => $idEvento]);
        $config = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($config) {
            $config->enviaEmail = (bool)$config->enviaEmail;
            $config->enviaPopup = (bool)$config->enviaPopup;
            return $config;
        }

        return (object)['enviaEmail' => true, 'enviaPopup' => true];
    }
}