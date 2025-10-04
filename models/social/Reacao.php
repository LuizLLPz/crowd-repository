<?php

namespace models\social;

use modules\db\Database;
use services\integrations\google\GoogleCloudStorageService;
use modules\core\tipos\Entidade;

class Reacao extends Entidade
{
    public string $nomeTabela = "Reacao";

    public int $id_alvo;
    public string $tipo_alvo;
    public int $id_usuario;
    public string $emoji;

    public static function salvar(Reacao $reacao): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Reacao (id_alvo, tipo_alvo, id_usuario, emoji) VALUES (:id_alvo, :tipo_alvo, :id_usuario, :emoji)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id_alvo' => $reacao->id_alvo,
            ':tipo_alvo' => $reacao->tipo_alvo,
            ':id_usuario' => $reacao->id_usuario,
            ':emoji' => $reacao->emoji,
        ]);
    }

    public static function remover(int $id_alvo, string $tipo_alvo, int $id_usuario, string $emoji): bool
    {
        $pdo = Database::getConnection();
        $sql = "DELETE FROM Reacao WHERE id_alvo = :id_alvo AND tipo_alvo = :tipo_alvo AND id_usuario = :id_usuario AND emoji = :emoji";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id_alvo' => $id_alvo,
            ':tipo_alvo' => $tipo_alvo,
            ':id_usuario' => $id_usuario,
            ':emoji' => $emoji
        ]);
    }

    public static function buscarPorAlvo(int $id_alvo, string $tipo_alvo, int $id_usuario_logado): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT 
                    r.emoji,
                    u.idUsuario,
                    u.nomeUsuario,
                    u.caminhoImagem
                FROM Reacao r
                JOIN Usuario u ON r.id_usuario = u.idUsuario
                WHERE r.id_alvo = :id_alvo AND r.tipo_alvo = :tipo_alvo";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_alvo' => $id_alvo,
            ':tipo_alvo' => $tipo_alvo,
        ]);
        $reacoesRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $reacoesAgrupadas = [];
        foreach ($reacoesRaw as $reacao) {
            $emoji = $reacao['emoji'];
            if (!isset($reacoesAgrupadas[$emoji])) {
                $reacoesAgrupadas[$emoji] = [
                    'emoji' => $emoji,
                    'total' => 0,
                    'usuarios' => [],
                    'usuarioReagiu' => false,
                ];
            }

            $caminhoImagem = null;
            if (!empty($reacao['caminhoImagem'])) {
                $caminhoImagem = GoogleCloudStorageService::getSignedUrl($reacao['caminhoImagem']);
            }

            $reacoesAgrupadas[$emoji]['usuarios'][] = [
                'idUsuario' => $reacao['idUsuario'],
                'nomeUsuario' => $reacao['nomeUsuario'],
                'caminhoFoto' => $caminhoImagem,
            ];
            $reacoesAgrupadas[$emoji]['total']++;

            if ($reacao['idUsuario'] == $id_usuario_logado) {
                $reacoesAgrupadas[$emoji]['usuarioReagiu'] = true;
            }
        }

        return array_values($reacoesAgrupadas);
    }
}
