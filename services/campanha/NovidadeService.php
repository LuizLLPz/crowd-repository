<?php
namespace services\campanha;

use models\Campanha;
use models\campanha\enums\TipoAlvo;
use models\Curtida;
use models\Novidade;
use models\campanha\InscricaoCampanha;
use models\Notificacao;
use services\integrations\SocketService;
use modules\db\Database;

class NovidadeService
{
    public static function criar_novidade(Novidade $novidade, int $idUsuario): string
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $novidadeCriada = Novidade::criar_noticia($novidade, $idUsuario);
            $inscricoes = InscricaoCampanha::obterInscricoesCampanha($novidadeCriada->idCampanha);

            $notificacoesParaSocket = [];

            if (!empty($inscricoes)) {
                $tituloCampanha = Campanha::obterTitulo($novidadeCriada->idCampanha);

                foreach ($inscricoes as $inscricao) {
                    $novaNotificacao = new Notificacao();
                    $novaNotificacao->idUsuario = $inscricao['idUsuario'];
                    $novaNotificacao->titulo = "Nova atualização em " . $tituloCampanha;
                    $novaNotificacao->descricao = $novidadeCriada->titulo;
                    $novaNotificacao->tipo = Notificacao::TIPO_NOVA_NOVIDADE;
                    $novaNotificacao->idItem = $novidade->idCampanha;

                    $notificacaoSalva = Notificacao::criar($novaNotificacao);
                    $notificacoesParaSocket[] = $notificacaoSalva;
                }
            }

            $pdo->commit();

            if (!empty($notificacoesParaSocket)) {
                SocketService::notificar($notificacoesParaSocket);
            }

            return "{$_ENV["CORS_ORIGIN"]}/novidade/{$novidade->id}/";

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function curtir_novidade(int $idNovidade, string $idUsuario) {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $curtida = new Curtida();
            $curtida->idAlvo = $idNovidade;
            $curtida->idUsuario = $idUsuario;
            $curtida->tipoAlvo = TipoAlvo::NOVIDADE->value;
            Curtida::salvar_curtida($curtida);
            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function deletar_novidade(int $idNovidade, int $idUsuario): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $novidade = Novidade::obter($idNovidade, $idUsuario);
            if (!$novidade) {
                throw new \Exception("Novidade não encontrada.");
            }
            if ($novidade['idUsuario'] !== $idUsuario) {
                throw new \Exception("Você não tem permissão para deletar esta novidade.");
            }

            Novidade::deletar($idNovidade, $idUsuario);
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


}