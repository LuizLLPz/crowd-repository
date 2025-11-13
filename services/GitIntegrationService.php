<?php

namespace services;

use models\campanha\CampanhaIntegracaoGit;

class GitIntegrationService
{
    public static function getCommits(int $idCampanha): array
    {
        $integracao = CampanhaIntegracaoGit::buscarPorCampanha($idCampanha);
        if (!$integracao) {
            return [];
        }

        $repoUrl = $integracao->urlRepositorio;
        $repoName = basename($repoUrl, '.git');
        $localPath = sys_get_temp_dir() . '/' . $repoName;

        if (!is_dir($localPath)) {
            shell_exec("git clone " . escapeshellarg($repoUrl) . " " . escapeshellarg($localPath));
        } else {
            shell_exec("cd " . escapeshellarg($localPath) . " && git pull");
        }

        $logCommand = "cd " . escapeshellarg($localPath) . " && git log --pretty=format:'{%n  \"sha\": \"%H\",%n  \"author\": \"%an\",%n  \"date\": \"%ad\",%n  \"message\": \"%s\"%n},'";
        $output = shell_exec($logCommand);

        $jsonOutput = "[" . rtrim(trim($output), ",") . "]";
        $commits = json_decode($jsonOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $commits;
    }
}