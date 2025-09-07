<?php
namespace services\campanha;

use models\Campanha;
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
}