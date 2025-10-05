<?php
namespace services\campanha;

use models\campanha\Campanha;
use models\campanha\enums\TipoAlvo;
use models\campanha\InscricaoCampanha;
use models\campanha\Novidade;
use models\core\Notificacao;
use models\social\Curtida;
use modules\core\utils\File;
use modules\db\Database;
use services\integrations\SocketService;
use services\core\MidiaService;

class NovidadeService
{
    private static function criarNotificacaoSeDiferente(Notificacao $notificacao, int $idCriador): void
    {
        if ($notificacao->idUsuario !== $idCriador) {
            Notificacao::criar($notificacao);
        }
    }

    public static function criar_novidade(Novidade $novidade, int $idUsuario): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $novidadeCriada = Novidade::criar_noticia($novidade, $idUsuario);

            if (!empty($_FILES)) {
                $uploadedMidias = MidiaService::salvarMidia($_FILES, null, $novidadeCriada->id);
                // Assuming the frontend sends a flag for which media is cover
                // This part might need adjustment based on actual frontend implementation
                foreach ($uploadedMidias as $midia) {
                    // Example: if frontend sends 'isCover' flag for each file
                    // if ($midia['isCover']) {
                    //     MidiaService::definirCapa($midia['idMidia'], null, $novidadeCriada->id);
                    //     break;
                    // }
                }
            }

            $inscricoes = InscricaoCampanha::obterInscricoesCampanha($novidadeCriada->idCampanha);

            $notificacoesParaSocket = [];

            if (!empty($inscricoes)) {
                $tituloCampanha = Campanha::obter_Titulo($novidadeCriada->idCampanha);

                foreach ($inscricoes as $inscricao) {
                    $novaNotificacao = new Notificacao();
                    $novaNotificacao->idUsuario = $inscricao['idUsuario'];
                    $novaNotificacao->titulo = "Nova atualização em " . $tituloCampanha;
                    $novaNotificacao->descricao = $novidadeCriada->titulo;
                    $novaNotificacao->tipo = Notificacao::TIPO_NOVA_NOVIDADE;
                    $novaNotificacao->idItem = $novidade->idCampanha;

                    self::criarNotificacaoSeDiferente($novaNotificacao, $idUsuario);
                    $notificacoesParaSocket[] = $novaNotificacao;
                }
            }

            $pdo->commit();

            if (!empty($notificacoesParaSocket)) {
                SocketService::notificar($notificacoesParaSocket);
            }

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function editar_novidade(Novidade $novidade, int $idUsuario): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $novidadeAntiga = Novidade::obter($novidade->id, $idUsuario);
            if (!$novidadeAntiga) {
                throw new \Exception("Novidade não encontrada.");
            }
            if ($novidadeAntiga['idAutor'] !== $idUsuario) {
                throw new \Exception("Você não tem permissão para editar esta novidade.");
            }

            Novidade::editar_novidade($novidade);
            $pdo->commit();
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