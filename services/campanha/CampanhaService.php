<?php
namespace services\campanha;

use models\Campanha;
use models\Campanha\HistoricoCampanha;
use models\Notificacao;
use modules\core\utils\File;
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

            if (isset($_FILES['imagem'])) {
                $imagemCampanha = $_FILES['imagem'];
                $nomeArquivo = "campanha-{$campanha->idCampanha}.".pathinfo($imagemCampanha['name'], PATHINFO_EXTENSION);
                $resultadoUpload = File::salvarImagem($imagemCampanha, $nomeArquivo);
                if ($resultadoUpload['success']) {
                    $campanha->caminhoImagem = $resultadoUpload['filePath'];
                    Campanha::alterar_caminhoImagem($campanha->idCampanha, $campanha->caminhoImagem);
                } else {
                    throw new \Exception("Falha no upload da imagem: {$resultadoUpload["message"]}");
                }
            }

            $historico = new HistoricoCampanha();
            $historico->idCampanha = $campanha->idCampanha;
            $historico->statusAntigo = 0;
            $historico->statusNovo = 0;
            $historico->idCriador = $campanha->idUsuario;
            $historico->descricao = "Campanha cadastrada pelo usuário";

            HistoricoCampanha::salvarHistorico($historico);

            $pdo->commit();

            return $campanha->caminhoImagem ?? "";

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function editar_campanha(Campanha $campanha): string
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $campanhaAntiga = Campanha::obter_campanha($campanha->idCampanha);

            Campanha::editar_campanha($campanha);

            if (isset($_FILES['imagem'])) {
                $imagemCampanha = $_FILES['imagem'];
                $nomeArquivo = "campanha-{$campanha->idCampanha}.".pathinfo($imagemCampanha['name'], PATHINFO_EXTENSION);
                $resultadoUpload = File::salvarImagem($imagemCampanha, $nomeArquivo);
                if ($resultadoUpload['success']) {
                    $campanha->caminhoImagem = $resultadoUpload['filePath'];
                    Campanha::alterar_caminhoImagem($campanha->idCampanha, $campanha->caminhoImagem);
                } else {
                    throw new \Exception("Falha no upload da imagem: {$resultadoUpload["message"]}");
                }
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
}

