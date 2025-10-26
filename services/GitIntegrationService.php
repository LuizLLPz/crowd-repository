<?php

namespace services;

use models\campanha\CampanhaIntegracaoGit;
use modules\db\Database;
use PDO;

class GitIntegrationService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function encrypt($data): string
    {
        $key = $_ENV['ENCRYPTION_KEY'];
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    private function decrypt($data): string
    {
        $key = $_ENV['ENCRYPTION_KEY'];
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    public function salvarIntegracao(int $idCampanha, string $plataforma, string $urlRepositorio, string $tokenAcesso): array
    {
        $encryptedToken = $this->encrypt($tokenAcesso);

        $sql = "
            INSERT INTO CampanhaIntegracaoGit (idCampanha, plataforma, urlRepositorio, tokenAcesso)
            VALUES (:idCampanha, :plataforma, :urlRepositorio, :tokenAcesso)
            ON DUPLICATE KEY UPDATE
            plataforma = VALUES(plataforma),
            urlRepositorio = VALUES(urlRepositorio),
            tokenAcesso = VALUES(tokenAcesso);
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $idCampanha,
            ':plataforma' => $plataforma,
            ':urlRepositorio' => $urlRepositorio,
            ':tokenAcesso' => $encryptedToken
        ]);

        return ['success' => true, 'message' => 'Integração salva com sucesso.'];
    }

    public function obterIntegracao(int $idCampanha): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, idCampanha, plataforma, urlRepositorio FROM CampanhaIntegracaoGit WHERE idCampanha = ?");
        $stmt->execute([$idCampanha]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function removerIntegracao(int $idCampanha): array
    {
        $stmt = $this->pdo->prepare("DELETE FROM CampanhaIntegracaoGit WHERE idCampanha = ?");
        $stmt->execute([$idCampanha]);
        return ['success' => true, 'message' => 'Integração removida com sucesso.'];
    }

    public function obterCommits(int $idCampanha): array
    {
        $integration = $this->obterIntegracao($idCampanha);
        if (!$integration) {
            return ['error' => 'Integração não configurada.'];
        }

        return [
            [
                'sha' => 'a1b2c3d4',
                'message' => 'feat: Implement initial dashboard structure',
                'author' => 'Lipinho',
                'date' => '2025-10-27T10:00:00Z',
                'url' => 'https://github.com/user/repo/commit/a1b2c3d4'
            ],
            [
                'sha' => 'e5f6g7h8',
                'message' => "fix: Correct password validation regex\n\n- The previous regex was too restrictive.\n- Added more comprehensive tests.",
                'author' => 'Gemini',
                'date' => '2025-10-27T09:30:00Z',
                'url' => 'https://github.com/user/repo/commit/e5f6g7h8'
            ]
        ];
    }
}
