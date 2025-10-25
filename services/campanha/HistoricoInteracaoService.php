<?php

namespace services\campanha;

use dto\historico\CreateHistoricoRequest;
use dto\historico\HistoricoInteracaoResponse;
use dto\historico\CategoriaInteresseResponse;
use dto\historico\CampanhaRecomendadaResponse;
use dto\historico\ApiResponse;
use models\campanha\HistoricoInteracao;
use models\campanha\Campanha;
use models\campanha\Categoria;
use models\social\Usuario;
use repositories\HistoricoInteracaoRepository;
use database\HistoricoInteracaoMigration;
use Exception;

class HistoricoInteracaoService
{
    private HistoricoInteracaoRepository $repository;
    private static bool $tabelaVerificada = false;

    public function __construct()
    {
        $this->repository = new HistoricoInteracaoRepository();
        $this->garantirTabelaExiste();
    }

    /**
     * Garante que a tabela HistoricoInteracao existe no banco
     */
    private function garantirTabelaExiste(): void
    {
        if (!self::$tabelaVerificada) {
            HistoricoInteracaoMigration::verificarECriar();
            self::$tabelaVerificada = true;
        }
    }

    /**
     * Registra uma nova interação do usuário com uma campanha
     */
    public function registrarInteracao(int $idUsuario, CreateHistoricoRequest $request): ApiResponse
    {
        try {
            // Validar dados de entrada
            $erros = $request->validar();
            if (!empty($erros)) {
                return ApiResponse::erro('Dados inválidos: ' . implode(', ', $erros));
            }

            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            // Verificar se a campanha existe e está ativa
            $campanha = Campanha::obter_campanha($request->idCampanha);
            if (!$campanha) {
                return ApiResponse::erro('Campanha não encontrada');
            }

            if ($campanha->status !== 1) {
                return ApiResponse::erro('Campanha não está ativa');
            }

            // Verificar se a categoria existe e corresponde à campanha
            if ($campanha->idCategoria !== $request->idCategoria) {
                return ApiResponse::erro('Categoria não corresponde à campanha');
            }

            // Verificar se já interagiu hoje
            if ($this->repository->jaInteragiuHoje($idUsuario, $request->idCampanha)) {
                return ApiResponse::erro('Interação já registrada hoje para esta campanha');
            }

            // Registrar a interação
            $sucesso = $this->repository->registrarInteracao(
                $idUsuario, 
                $request->idCampanha, 
                $request->idCategoria
            );

            if ($sucesso) {
                return ApiResponse::sucesso('Interação registrada com sucesso');
            } else {
                return ApiResponse::erro('Erro ao registrar interação');
            }

        } catch (Exception $e) {
            error_log('Erro ao registrar interação: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Busca o histórico de interações de um usuário
     */
    public function buscarHistoricoUsuario(int $idUsuario): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $historico = $this->repository->buscarHistoricoUsuario($idUsuario);
            
            $historicoFormatado = array_map(function($item) {
                return (new HistoricoInteracaoResponse($item))->toArray();
            }, $historico);

            return ApiResponse::sucesso('Histórico recuperado com sucesso', $historicoFormatado);

        } catch (Exception $e) {
            error_log('Erro ao buscar histórico: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Busca categorias de interesse do usuário
     */
    public function buscarCategoriasInteresse(int $idUsuario): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $categorias = $this->repository->buscarCategoriasInteresse($idUsuario);
            
            $categoriasFormatadas = array_map(function($item) {
                return (new CategoriaInteresseResponse($item))->toArray();
            }, $categorias);

            return ApiResponse::sucesso('Categorias de interesse recuperadas com sucesso', $categoriasFormatadas);

        } catch (Exception $e) {
            error_log('Erro ao buscar categorias de interesse: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Busca campanhas recomendadas para o usuário
     */
    public function buscarCampanhasRecomendadas(int $idUsuario, int $limite = 20): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $campanhas = $this->repository->buscarCampanhasRecomendadas($idUsuario, $limite);
            
            $campanhasFormatadas = array_map(function($item) {
                return (new CampanhaRecomendadaResponse($item))->toArray();
            }, $campanhas);

            return ApiResponse::sucesso('Campanhas recomendadas recuperadas com sucesso', $campanhasFormatadas);

        } catch (Exception $e) {
            error_log('Erro ao buscar campanhas recomendadas: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Remove uma interação específica
     */
    public function removerInteracao(int $idInteracao, int $idUsuario): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $sucesso = $this->repository->removerInteracao($idInteracao, $idUsuario);

            if ($sucesso) {
                return ApiResponse::sucesso('Interação removida com sucesso');
            } else {
                return ApiResponse::erro('Interação não encontrada ou não pertence ao usuário');
            }

        } catch (Exception $e) {
            error_log('Erro ao remover interação: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Limpa todo o histórico de um usuário
     */
    public function limparHistoricoUsuario(int $idUsuario): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $totalInteracoes = $this->repository->contarInteracoesUsuario($idUsuario);
            
            if ($totalInteracoes === 0) {
                return ApiResponse::sucesso('Histórico já está vazio');
            }

            $sucesso = $this->repository->limparHistoricoUsuario($idUsuario);

            if ($sucesso) {
                return ApiResponse::sucesso("Histórico limpo com sucesso. {$totalInteracoes} interações removidas.");
            } else {
                return ApiResponse::erro('Erro ao limpar histórico');
            }

        } catch (Exception $e) {
            error_log('Erro ao limpar histórico: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Obtém estatísticas do usuário
     */
    public function obterEstatisticasUsuario(int $idUsuario): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $totalInteracoes = $this->repository->contarInteracoesUsuario($idUsuario);
            $categoriasInteresse = $this->repository->buscarCategoriasInteresse($idUsuario);
            $campanhasVisualizadas = count($this->repository->buscarCampanhasVisualizadas($idUsuario));

            $estatisticas = [
                'totalInteracoes' => $totalInteracoes,
                'totalCategoriasInteresse' => count($categoriasInteresse),
                'totalCampanhasVisualizadas' => $campanhasVisualizadas,
                'categoriaFavorita' => !empty($categoriasInteresse) ? $categoriasInteresse[0] : null,
                'pontuacaoTotal' => array_sum(array_column($categoriasInteresse, 'pontos'))
            ];

            return ApiResponse::sucesso('Estatísticas recuperadas com sucesso', $estatisticas);

        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }

    /**
     * Algoritmo de recomendação avançado
     */
    public function obterRecomendacoesAvancadas(int $idUsuario, array $filtros = []): ApiResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = Usuario::buscarUsuarioPorId($idUsuario);
            if (!$usuario) {
                return ApiResponse::erro('Usuário não encontrado');
            }

            $limite = $filtros['limite'] ?? 20;
            $incluirPopulares = $filtros['incluirPopulares'] ?? true;
            $percentualPopulares = $filtros['percentualPopulares'] ?? 20;

            // Buscar campanhas recomendadas baseadas no histórico
            $campanhasPersonalizadas = $this->repository->buscarCampanhasRecomendadas($idUsuario, $limite);
            
            $resultado = [];

            // Se incluir populares e temos campanhas personalizadas
            if ($incluirPopulares && !empty($campanhasPersonalizadas)) {
                $qtdPopulares = ceil($limite * $percentualPopulares / 100);
                $qtdPersonalizadas = $limite - $qtdPopulares;

                // Pegar apenas as personalizadas necessárias
                $campanhasPersonalizadas = array_slice($campanhasPersonalizadas, 0, $qtdPersonalizadas);
                
                // Buscar campanhas populares para complementar
                $campanhasPopulares = $this->repository->buscarCampanhasPopulares($qtdPopulares);
                
                // Filtrar populares que não estejam nas personalizadas
                $idsPersonalizadas = array_column($campanhasPersonalizadas, 'idCampanha');
                $campanhasPopulares = array_filter($campanhasPopulares, function($campanha) use ($idsPersonalizadas) {
                    return !in_array($campanha['idCampanha'], $idsPersonalizadas);
                });

                // Combinar resultados
                $resultado = array_merge($campanhasPersonalizadas, array_slice($campanhasPopulares, 0, $qtdPopulares));
            } else {
                $resultado = $campanhasPersonalizadas;
            }

            // Formatar resultado
            $campanhasFormatadas = array_map(function($item) {
                return (new CampanhaRecomendadaResponse($item))->toArray();
            }, $resultado);

            // Adicionar metadados
            $metadata = [
                'algoritmo' => 'híbrido',
                'baseadoEm' => !empty($campanhasPersonalizadas) ? 'histórico_usuario' : 'popularidade',
                'totalRecomendacoes' => count($campanhasFormatadas)
            ];

            return ApiResponse::sucesso('Recomendações avançadas recuperadas com sucesso', [
                'campanhas' => $campanhasFormatadas,
                'metadata' => $metadata
            ]);

        } catch (Exception $e) {
            error_log('Erro ao obter recomendações avançadas: ' . $e->getMessage());
            return ApiResponse::erro('Erro interno do servidor');
        }
    }
}