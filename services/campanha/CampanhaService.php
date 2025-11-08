<?php
namespace services\campanha;

use models\campanha\Campanha;
use models\campanha\HistoricoCampanha;
use services\core\NotificacaoService;
use modules\db\Database;

use models\Envolvido;
use models\Recompensa;

class CampanhaService
{
    public static function criar_campanha(Campanha $campanha): void
    {
        $dataFinal = new \DateTime($campanha->dataFinal);
        $dataMinima = (new \DateTime())->add(new \DateInterval('P1M'));

        if ($dataFinal < $dataMinima) {
            throw new \Exception("A data de encerramento deve ser de no mínimo 1 mês a partir de hoje.");
        }

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            Campanha::criar_campanha($campanha);

            foreach ($campanha->envolvidos as $envolvidoData) {
                $envolvido = new Envolvido();
                $envolvido->idCampanha = $campanha->idCampanha;
                $envolvido->idUsuario = $envolvidoData['idUsuario'];
                $envolvido->papel = $envolvidoData['funcao'];
                Envolvido::salvar($envolvido);
            }

            foreach ($campanha->recompensas as $recompensaData) {
                $recompensa = new Recompensa();
                $recompensa->idCampanha = $campanha->idCampanha;
                $recompensa->nivel = $recompensaData['nivel'];
                $recompensa->nomeNivel = $recompensaData['nomeNivel'];
                $recompensa->valorDoacao = (int) preg_replace('/[^0-9]/', '', $recompensaData['valorDoacao']);
                $recompensa->vantagens = $recompensaData['vantagens'];
                $recompensa->corRecompensa = $recompensaData['corRecompensa'];
                Recompensa::salvar($recompensa);
            }

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $campanha->idCampanha;
            $historico->statusAntigo = 0;
            $historico->statusNovo = 0;
            $historico->idCriador = $campanha->idUsuario;
            $historico->descricao = "Campanha cadastrada pelo usuário";

            HistoricoCampanha::salvarHistorico($historico);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function editar_campanha(Campanha $campanha): string
    {
        $dataFinal = new \DateTime($campanha->dataFinal);
        $dataMinima = (new \DateTime())->add(new \DateInterval('P1M'));

        if ($dataFinal < $dataMinima) {
            throw new \Exception("A data de encerramento deve ser de no mínimo 1 mês a partir de hoje.");
        }

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $campanhaAntiga = Campanha::obter_campanha($campanha->idCampanha);

            if ($campanhaAntiga->hasDoacoes && !empty($campanha->recompensas)) {
                throw new \Exception("Não é possível editar recompensas de uma campanha que já recebeu doações.");
            }

            Campanha::editar_campanha($campanha);

            // Clear existing envolvidos and recompensas
            $stmt = $pdo->prepare("DELETE FROM Envolvido WHERE idCampanha = :idCampanha");
            $stmt->execute([':idCampanha' => $campanha->idCampanha]);
            $stmt = $pdo->prepare("DELETE FROM Recompensa WHERE idCampanha = :idCampanha");
            $stmt->execute([':idCampanha' => $campanha->idCampanha]);

            foreach ($campanha->envolvidos as $envolvidoData) {
                $envolvido = new Envolvido();
                $envolvido->idCampanha = $campanha->idCampanha;
                $envolvido->idUsuario = $envolvidoData['idUsuario'];
                $envolvido->papel = $envolvidoData['funcao'];
                Envolvido::salvar($envolvido);
            }

            foreach ($campanha->recompensas as $recompensaData) {
                $recompensa = new Recompensa();
                $recompensa->idCampanha = $campanha->idCampanha;
                $recompensa->nivel = $recompensaData['nivel'];
                $recompensa->nomeNivel = $recompensaData['nomeNivel'];
                $recompensa->valorDoacao = (int) preg_replace('/[^0-9]/', '', $recompensaData['valorDoacao']);
                $recompensa->vantagens = $recompensaData['vantagens'];
                $recompensa->corRecompensa = $recompensaData['corRecompensa'];
                Recompensa::salvar($recompensa);
            }

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

