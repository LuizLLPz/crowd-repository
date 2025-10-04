<?php
namespace services\campanha;

use models\campanha\Campanha;
use models\Campanha\HistoricoCampanha;
use models\core\Notificacao;
use modules\core\utils\File;
use modules\db\Database;
use services\integrations\SocketService;

class CampanhaService
{
    public static function criar_campanha(Campanha $campanha): string
    {
        $pdo = Database::getConnection();
        try {
            error_log('CRIAR_CAMPANHA_SERVICE: BEGIN');
            $pdo->beginTransaction();
            Campanha::criar_campanha($campanha);
            error_log('CRIAR_CAMPANHA_SERVICE: Campanha created in model, ID: ' . $campanha->idCampanha);

            if (isset($_FILES['imagem'])) {
                self::salvarImagemCampanha($campanha);
            }

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

            return "/campanha/{$campanha->idCampanha}";

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

            if (isset($_FILES['imagem'])) {
                self::salvarImagemCampanha($campanha);
            } else if (!empty($campanhaAntiga['caminhoImagem']) && empty($campanha->caminhoImagem)) {
                File::delete($campanhaAntiga['caminhoImagem']);
                $campanha->caminhoImagem = '';
                Campanha::alterar_caminhoImagem($campanha->idCampanha, $campanha->caminhoImagem);
            }
            $pdo->commit();
            return $campanha->caminhoImagem ?? "";

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
            $notificacao = new Notificacao();
            $notificacao->idUsuario = $campanha['idUsuario'];
            $notificacao->titulo = "Campanha Rerovada!";
            $notificacao->descricao = "Sua campanha '{$campanha['titulo']}' foi reprovada :(";
            $notificacao->tipo = "campanha_reprovada";
            $notificacao->idItem = $idCampanha;

            Notificacao::criar($notificacao);
            HistoricoCampanha::salvarHistorico($historico);
            $pdo->commit();

            SocketService::notificar([$notificacao]);

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


    public static function desativar_campanha(int $idCampanha, string $statusAntigo, int $idAtendente, bool $hasTransaction = false): void
    {
        $pdo = Database::getConnection();
        try {
            if (!$hasTransaction) $pdo->beginTransaction();
            Campanha::alterar_status($idCampanha, 6);

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $idCampanha;
            $historico->statusAntigo = $statusAntigo;
            $historico->statusNovo = 0;
            $historico->idCriador = $idAtendente;
            $historico->descricao = "Campanha desativada pela moderação";

            HistoricoCampanha::salvarHistorico($historico);

            if (!$hasTransaction) $pdo->commit();

        } catch (\Exception $e) {
            if (!$hasTransaction) $pdo->rollBack();
            throw $e;
        }
    }

    private static function salvarImagemCampanha(Campanha $campanha): void
    {
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $nomeArquivo = "campanha-{$campanha->idCampanha}";
            $resultadoUpload = File::salvarImagem($_FILES['imagem'], $nomeArquivo);
            if ($resultadoUpload['success']) {
                $campanha->caminhoImagem = $resultadoUpload['filePath'];
                Campanha::alterar_caminhoImagem($campanha->idCampanha, $campanha->caminhoImagem);
                error_log('CampanhaService::salvarImagemCampanha: Image saved successfully. Path: ' . $campanha->caminhoImagem);
            } else {
                error_log('CampanhaService::salvarImagemCampanha: Image upload failed: ' . $resultadoUpload['message']);
                throw new \Exception("Falha no upload da imagem: " . $resultadoUpload['message']);
            }
        } else {
            error_log('CampanhaService::salvarImagemCampanha: No image file or upload error.');
        }
    }

}

