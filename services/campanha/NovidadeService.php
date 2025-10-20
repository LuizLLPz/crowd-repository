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
                    $stmt = $pdo->prepare("INSERT INTO Midia (idEntidade, tipoEntidade, caminhoArquivo, tipo, isCapa) VALUES (:idEntidade, :tipoEntidade, :caminhoArquivo, 'imagem', 0)");
                    foreach ($imagePaths as $path) {
                        $stmt->execute([
                            ':idEntidade' => $novidadeCriada->id,
                            ':tipoEntidade' => 'Novidade',
                            ':caminhoArquivo' => $path,
                        ]);
                    }
                }
            }

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
        // Se a novidade não tem recompensa associada, é pública
        if (empty($novidade['idRecompensa'])) {
            return true;
        }

        // Se o usuário é o dono da campanha, ele sempre tem acesso
        if (Campanha::isOwner($novidade['idCampanha'], $idUsuario)) {
            return true;
        }

        // Obter a recompensa associada à novidade
        $recompensa = Recompensa::buscarPorId($novidade['idRecompensa']);
        if (!$recompensa) {
            // Se a recompensa não existe, mas a novidade está vinculada, negar acesso por segurança
            return false;
        }

        // Obter o valor total doado pelo usuário para esta campanha
        $valorDoado = Doacao::obter_valor_doado_usuario_campanha($novidade['idCampanha'], $idUsuario);

        // Verificar se o valor doado é suficiente para a recompensa
        return $valorDoado >= $recompensa['valorDoacao'];
    }

}