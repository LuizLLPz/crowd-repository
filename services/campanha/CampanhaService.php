<?php
namespace services\campanha;

use models\campanha\Campanha;
use models\campanha\HistoricoCampanha;
use services\core\NotificacaoService;
use modules\db\Database;

class CampanhaService
{
    public static function criar_campanha(Campanha $campanha): void
    {
        $pdo = Database::getConnection();
        try {
            error_log('CRIAR_CAMPANHA_SERVICE: BEGIN');
            $pdo->beginTransaction();
            Campanha::criar_campanha($campanha);
            error_log('CRIAR_CAMPANHA_SERVICE: Campanha created in model, ID: ' . $campanha->idCampanha);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $campanha->idCampanha;
            $historico->statusAntigo = 0;
            $historico->statusNovo = 0;
            $historico->idCriador = $campanha->idUsuario;
            $historico->descricao = "Campanha cadastrada pelo usuário";

            HistoricoCampanha::salvarHistorico($historico);
            error_log('CRIAR_CAMPANHA_SERVICE: Historico saved');

            $pdo->commit();
            error_log('CRIAR_CAMPANHA_SERVICE: COMMIT SUCCEEDED');

        } catch (\Exception $e) {
            error_log('CRIAR_CAMPANHA_SERVICE: EXCEPTION CAUGHT - ' . $e->getMessage());
            $pdo->rollBack();
            error_log('CRIAR_CAMPANHA_SERVICE: ROLLBACK EXECUTED');
            throw $e;
        }
    }

    public static function editar_campanha(Campanha $campanha): string
    {
        $pdo = Database::getConnection();
        try {
            error_log('$_FILES in CampanhaService: ' . print_r($_FILES, true));
            $pdo->beginTransaction();
            $campanhaAntiga = Campanha::obter_campanha($campanha->idCampanha);

            Campanha::editar_campanha($campanha);

            $pdo->commit();
            return "Campanha atualizada com sucesso!";

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function reprovar_campanha(int $idCampanha, int $statusAntigo, int $idAprovador): string
    {
        $pdo = Database::getConnection();

        try {
            Campanha::alterar_status($idCampanha, 4);

            $pdo->beginTransaction();

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 4;
            $historico->idCriador = $idAprovador;
            $historico->descricao = "Campanha reprovada pelo administrador";

            $campanha = Campanha::obter_campanha($idCampanha);
            if ($campanha && $campanha->idUsuario != $idAprovador) {
                $appUrl = getenv('APP_URL') ?: 'http://localhost:3001';
                NotificacaoService::disparar(
                    'campanha-reprovada',
                    $campanha->idUsuario,
                    [
                        'idItem' => $idCampanha,
                        'nomeCampanha' => $campanha->titulo,
                        'linkCampanha' => "{$appUrl}/campanha/{$campanha->idCampanha}"
                    ]
                );
            }

            HistoricoCampanha::salvarHistorico($historico);
            $pdo->commit();

            return "Campanha reprovada";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


    public static function aprovar_campanha(int $idCampanha, int $statusAntigo, int $idAprovador): string
    {
        $pdo = Database::getConnection();

        try {
            Campanha::alterar_status($idCampanha, 1);

            $pdo->beginTransaction();

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 1;
            $historico->idCriador = $idAprovador;
            $historico->descricao = "Campanha aprovada pelo administrador";

            $campanha = Campanha::obter_campanha($idCampanha);
            if ($campanha && $campanha->idUsuario != $idAprovador) {
                $appUrl = getenv('APP_URL') ?: 'http://localhost:3000';
                NotificacaoService::disparar(
                    'campanha-aprovada',
                    $campanha->idUsuario,
                    [
                        'idItem' => $idCampanha,
                        'nomeCampanha' => $campanha->titulo,
                        'linkCampanha' => "{$appUrl}/campanha/{$campanha->idCampanha}"
                    ]
                );
            }

            HistoricoCampanha::salvarHistorico($historico);
            $pdo->commit();

            return "Campanha aprovada";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


    public static function desativar_campanha(int $idCampanha, string $statusAntigo, int $idAtendente, bool $hasTransaction = false): void
    {
        $pdo = Database::getConnection();
        try {
            if (!$hasTransaction) $pdo->beginTransaction();
            Campanha::alterar_status($idCampanha, 6);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 6;
            $historico->idCriador = $idAtendente;
            $historico->descricao = "Campanha desativada pela moderação";

            HistoricoCampanha::salvarHistorico($historico);

            $campanha = Campanha::obter_campanha($idCampanha);
            if ($campanha && $campanha->idUsuario != $idAtendente) {
                $appUrl = getenv('APP_URL') ?: 'http://localhost:3000';
                NotificacaoService::disparar(
                    'campanha-desativada',
                    $campanha->idUsuario,
                    [
                        'idItem' => $idCampanha,
                        'nomeCampanha' => $campanha->titulo,
                        'linkCampanha' => "{$appUrl}/campanha/{$campanha->idCampanha}"
                    ]
                );
            }

            if (!$hasTransaction) $pdo->commit();

        } catch (\Exception $e) {
            if (!$hasTransaction) $pdo->rollBack();
            throw $e;
        }
    }
}

