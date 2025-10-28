<?php
namespace services\campanha;

use models\campanha\Campanha;
use models\campanha\Doacao;
use models\campanha\enums\TipoAlvo;
use models\campanha\InscricaoCampanha;
use models\campanha\Novidade;
use models\campanha\Recompensa;
use models\core\Notificacao;
use models\social\Curtida;
use modules\core\utils\File;
use modules\db\Database;
use services\core\NotificacaoService;
use services\integrations\SocketService;
use services\core\MidiaService;

class NovidadeService
{

    public static function criar_novidade(Novidade $novidade, int $idUsuario): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            if (!Campanha::isUsuarioParticipante($novidade->idCampanha, $idUsuario)) {
                throw new \Exception("Você não tem permissão para criar novidades nesta campanha.");
            }
            $novidadeCriada = Novidade::criar_noticia($novidade, $idUsuario);

            $descricao = $novidade->descricao;
            if (!empty($descricao)) {
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $descricao);
                $images = $dom->getElementsByTagName('img');

                $imagePaths = [];
                foreach ($images as $img) {
                    if ($img->hasAttribute('data-path')) {
                        $imagePaths[] = $img->getAttribute('data-path');
                    }
                }

                            if (!empty($imagePaths)) {
                                MidiaService::salvar_midias_markdown($novidadeCriada->id, 'Novidade', $imagePaths);
                            }
                
                            if (isset($_FILES['newMediaFiles'])) {
                                MidiaService::salvar_midias($novidadeCriada->id, 'Novidade', $_FILES['newMediaFiles']);
                            }            }

            $inscritos = InscricaoCampanha::buscarInscritosPorCampanha($novidadeCriada->idCampanha);

            if (!empty($inscritos)) {
                $tituloCampanha = Campanha::obter_Titulo($novidadeCriada->idCampanha);
                $appUrl = getenv('CORS_ORIGIN') ?: 'http://localhost:3000';
                $linkNovidade = "{$appUrl}/campanha/{$novidadeCriada->idCampanha}?tab=novidades";

                foreach ($inscritos as $inscrito) {
                    if ($inscrito['idUsuario'] != $idUsuario) {
                        NotificacaoService::disparar(
                            'nova-novidade-campanha',
                            $inscrito['idUsuario'],
                            [
                                'idItem' => $novidadeCriada->idCampanha,
                                'nomeCampanha' => $tituloCampanha,
                                'tituloNovidade' => $novidadeCriada->titulo,
                                'descricaoNovidade' => $novidadeCriada->descricao,
                                'linkNovidade' => $linkNovidade,
                            ]
                        );
                    }
                }
            }

            $pdo->commit();

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
            if (!Campanha::isUsuarioParticipante($novidadeAntiga['idCampanha'], $idUsuario)) {
                throw new \Exception("Você não tem permissão para editar novidades nesta campanha.");
            }

            Novidade::editar_novidade($novidade);

            if (isset($_FILES['newMediaFiles'])) {
                MidiaService::salvar_midias($novidade->id, 'Novidade', $_FILES['newMediaFiles']);
            }

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
            if (!Campanha::isUsuarioParticipante($novidade['idCampanha'], $idUsuario)) {
                throw new \Exception("Você não tem permissão para deletar novidades nesta campanha.");
            }

            Novidade::deletar($idNovidade, $idUsuario);
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function verificarAcessoNovidade(array $novidade, int $idUsuario): bool
    {
        if (empty($novidade['idRecompensa'])) {
            return true;
        }
        if (Campanha::isOwner($novidade['idCampanha'], $idUsuario)) {
            return true;
        }

        $recompensa = Recompensa::buscarPorId($novidade['idRecompensa']);
        if (!$recompensa) {
            return false;
        }

        $valorDoado = Doacao::obter_valor_doado_usuario_campanha($novidade['idCampanha'], $idUsuario);

        return $valorDoado >= $recompensa['valorDoacao'];
    }

}