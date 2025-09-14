<?php
namespace services\campanha;

use models\Campanha;
use models\Campanha\HistoricoCampanha;
use models\Notificacao;
use modules\db\Database;
use services\integrations\SocketService;

class CampanhaService
{
    public static function criar_campanha(Campanha $campanha): string
    {
        $pdo = Database::getConnection();
        try {

            $pdo->beginTransaction();

            Campanha::criar_campanha($campanha);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $campanha->idCampanha;
            $historico->statusAntigo = 0;
            $historico->statusNovo = 0;
            $historico->idCriador = $campanha->idUsuario;
            $historico->descricao = "Campanha cadastrada pelo usuário";

            HistoricoCampanha::salvarHistorico($historico);

            $pdo->commit();

            return "{$_ENV["CORS_ORIGIN"]}/campanha/{$campanha->idCampanha}";

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

            $campanha = Campanha::obterCampanha($idCampanha);
            $notificacao = new Notificacao();
            $notificacao->idUsuario = $campanha['idUsuario'];
            $notificacao->titulo = "Campanha Aprovada!";
            $notificacao->descricao = "Sua campanha '{$campanha['titulo']}' foi aprovada e já está visível para todos!";
            $notificacao->tipo = "campanha_aprovada";
            $notificacao->idItem = $idCampanha;

            Notificacao::criar($notificacao);
            HistoricoCampanha::salvarHistorico($historico);
            $pdo->commit();

            SocketService::notificar([$notificacao]);

            return "Campanha aprovada";
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


    public static function desativarCampanha(int $idCampanha, string $statusAntigo, int $idAtendente): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            Campanha::alterar_status($idCampanha, 6);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 0;
            $historico->idCriador = $idAtendente;
            $historico->descricao = "Campanha desativada pela moderação";

            HistoricoCampanha::salvarHistorico($historico);

            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

