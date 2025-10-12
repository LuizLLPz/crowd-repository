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
use services\integrations\email\EmailService;
use services\integrations\SocketService;

class DenunciaService
{

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

            if ($denuncia->status == "Aprovada") {
                switch ($denuncia->tipoAlvo) {
                    case 'Campanha':
                        CampanhaService::desativar_campanha($denuncia->idAlvo, 1, $denuncia->idAtendente, hasTransaction: true);
                        break;
                }
            }

            $notificacoes = [];

            if ($denuncia->idUsuario !== $denuncia->idAtendente) {
                $notificacao = new Notificacao();
                $notificacao->idUsuario = $denuncia->idUsuario;
                $notificacao->titulo = "Denúncia Atendida";
                $notificacao->descricao = "Sua denúncia com relação a/o " . strtolower($denuncia->tipoAlvo);
                $notificacao->descricao .= $denuncia->status == "Aprovada" ? " foi atendida!" : " foi reprovada! Para mais informações entre em contato conosco!";
                $notificacao->tipo = "denuncia_atendida";
                $notificacao->idItem = $denuncia->idAlvo;
                Notificacao::criar($notificacao);
                $notificacoes[] = $notificacao;

                try {
                    $usuarioDenunciante = \models\social\Usuario::buscar_usuario($denuncia->idUsuario);
                    if ($usuarioDenunciante) {
                        $emailService = new EmailService();
                        $template = file_get_contents(__DIR__ . '/../integrations/email/templates/resposta_denuncia.html');

                        $statusClass = $denuncia->status == 'Aprovada' ? 'aprovada' : 'reprovada';

                        $conteudoEmail = str_replace(
                            ['{nomeUsuario}', '{protocoloDenuncia}', '{statusDenuncia}', '{statusClass}', '{respostaAdmin}'],
                            [$usuarioDenunciante->nomeUsuario, $denuncia->id, $denuncia->status, $statusClass, $denuncia->justificativa],
                            $template
                        );

                        $emailService->enviar(
                            $usuarioDenunciante->email,
                            $usuarioDenunciante->nomeUsuario,
                            "Atualização sobre sua denúncia [Protocolo #{$denuncia->id}]",
                            $conteudoEmail
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Falha ao enviar e-mail de resposta da denúncia: " . $e->getMessage());
                }
            }

            if ($denuncia->status == "Aprovada") {
                $idDonoAlvo = 0;
                switch ($denuncia->tipoAlvo) {
                    case 'Novidade':
                        $idDonoAlvo = Novidade::obter_idUsuario($denuncia->idAlvo);
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

                if ($idDonoAlvo && $idDonoAlvo !== $denuncia->idAtendente) {
                    $notificacaoDono = new Notificacao();
                    $notificacaoDono->idUsuario = $idDonoAlvo;
                    $notificacaoDono->titulo = $denuncia->tipoAlvo . " que você cadastrou foi removido/a pela moderação";
                    $notificacaoDono->descricao = "Um item seu não está de acordo com os nossos termos e foi excluído. Para mais informações entre em contato conosco!";
                    $notificacaoDono->tipo = "item_excluido";
                    $notificacaoDono->idItem = $denuncia->idAlvo;
                    Notificacao::criar($notificacaoDono);
                    $notificacoes[] = $notificacaoDono;
                }
            }

            $pdo->commit();

            if (!empty($notificacoes)) {
                SocketService::notificar($notificacoes);
            }

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

}

