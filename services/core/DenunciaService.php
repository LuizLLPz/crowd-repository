<?php
namespace services\core;

use models\campanha\Campanha;
use models\campanha\Novidade;
use models\core\Denuncia;
use models\core\Notificacao;
use models\social\Comentario;
use modules\core\utils\File;
use modules\db\Database;
use services\campanha\CampanhaService;
use services\integrations\SocketService;

class DenunciaService
{
    private static function criarNotificacaoSeDiferente(Notificacao $notificacao, int $idCriador): void
    {
        if ($notificacao->idUsuario !== $idCriador) {
            Notificacao::criar($notificacao);
        }
    }

    public static function denunciar(Denuncia $denuncia): string
    {
        $res = Denuncia::denunciarObjeto($denuncia);

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $imagemDenuncia = $_FILES['imagem'];
            $nomeArquivo = "denuncia-{$denuncia->idAlvo}-{$denuncia->idUsuario}." . pathinfo($imagemDenuncia['name'], PATHINFO_EXTENSION);
            $resultadoUpload = File::salvarImagem($imagemDenuncia, $nomeArquivo);

            if ($resultadoUpload['success']) {
                $denuncia->caminhoImagem = $resultadoUpload['relativePath'];
                Denuncia::updateCaminhoImagem($denuncia->idUsuario, $denuncia->idAlvo, $denuncia->caminhoImagem);
            } else {
                error_log("Falha no upload da imagem da denúncia: {$resultadoUpload["message"]}");
            }
        }
        return $res;
    }

    public static function atender_denuncia(Denuncia $denuncia): void
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            Denuncia::atenderDenuncia($denuncia);
            switch ($denuncia->tipoAlvo) {
                case 'Campanha':
                    if ($denuncia->status == "Aprovada")
                        CampanhaService::desativar_campanha($denuncia->idAlvo, 1, $denuncia->idAtendente, hasTransaction: true);
                    break;
            }

            $notificacoes = [];
            $notificacao = new Notificacao();
            $notificacao->idUsuario = $denuncia->idUsuario;
            $notificacao->titulo = "Denúncia Atendida";
            $notificacao->descricao = "Sua denúncia com relação a/o " . strtolower($denuncia->tipoAlvo);
            $notificacao->descricao .= $denuncia->status == "Aprovada" ? " foi atendida!" : " foi reprovada! Para mais informações entre em contato conosco!";
            $notificacao->tipo = "denuncia_atendida";
            $notificacao->idItem = $denuncia->idAlvo;
            Notificacao::criar($notificacao);
            $notificacoes[] = $notificacao;

            $notificacaoDono = new Notificacao();
            $idDonoAlvo = 0;

            switch ($denuncia->tipoAlvo) {
                case 'Novidade':
                    $idDonoAlvo =  Novidade::obter_idUsuario($denuncia->idAlvo);
                    break;
                case 'Comentario':
                    $idDonoAlvo = Comentario::obter_idUsuario($denuncia->idAlvo);
                    break;
                case 'Campanha':
                    $idDonoAlvo = Campanha::obter_idUsuario($denuncia->idAlvo);
                    break;
                case 'Usuario':
                    $idDonoAlvo = $denuncia->idAlvo;
                    break;
            }

            if ($denuncia->status == "Aprovada") {
                $notificacaoDono->idUsuario = $idDonoAlvo;
                $notificacaoDono->titulo =$denuncia->tipoAlvo . " que você cadastrou foi removido/a pela moderação";
                $notificacaoDono->descricao = "Um item seu não está de acordo com os nossos termos e foi excluído. Para mais informações entre em contato conosco!";
                $notificacaoDono->tipo = "item_excluido";
                $notificacaoDono->idItem = $denuncia->idAlvo;
                self::criarNotificacaoSeDiferente($notificacaoDono, $denuncia->idAtendente);
                $notificacoes[] = $notificacaoDono;
            }

            $pdo->commit();

            SocketService::notificar($notificacoes);


        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

}

