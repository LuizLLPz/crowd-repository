<?php

namespace api\controllers;

use dto\historico\CreateHistoricoRequest;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\utils\Http;
use services\campanha\HistoricoInteracaoService;

class HistoricoInteracaoController extends ControllerBase
{
    private HistoricoInteracaoService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new HistoricoInteracaoService();
    }

    /**
     * Endpoint 1: Registrar interação do usuário com uma campanha
     * POST /api/historico-interacao
     */
    #[HttpPost('/historico-interacao')]
    public function registrarInteracao(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Http::HttpResponse(400, 'Dados JSON inválidos');
                return;
            }

            $request = new CreateHistoricoRequest($input);
            $idUsuario = self::$usuarioAutenticado->idUsuario;

            $resultado = $this->service->registrarInteracao($idUsuario, $request);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message, $resultado->data);
            } else {
                Http::HttpResponse(400, $resultado->message, $resultado->data);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::registrarInteracao - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    /**
     * Endpoint 2: Obter histórico do usuário
     * GET /api/historico-interacao?idUsuario={id}
     */
    #[HttpGet('/historico-interacao')]
    public function obterHistorico(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idUsuario = $_GET['idUsuario'] ?? null;
            
            // Se não foi fornecido idUsuario, usar o do usuário autenticado
            if (!$idUsuario) {
                $idUsuario = self::$usuarioAutenticado->idUsuario;
            } else {
                $idUsuario = (int) $idUsuario;
                
                // Verificar se o usuário está tentando acessar histórico de outro usuário
                // (por enquanto, só permitir acesso ao próprio histórico)
                if ($idUsuario !== self::$usuarioAutenticado->idUsuario) {
                    Http::HttpResponse(403, 'Acesso negado: você só pode acessar seu próprio histórico');
                    return;
                }
            }

            $resultado = $this->service->buscarHistoricoUsuario($idUsuario);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message, $resultado->data);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::obterHistorico - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    /**
     * Endpoint 3: Obter categorias de interesse do usuário
     * GET /api/historico-interacao/categorias-interesse?idUsuario={id}
     */
    #[HttpGet('/historico-interacao/categorias-interesse')]
    public function obterCategoriasInteresse(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idUsuario = $_GET['idUsuario'] ?? null;
            
            // Se não foi fornecido idUsuario, usar o do usuário autenticado
            if (!$idUsuario) {
                $idUsuario = self::$usuarioAutenticado->idUsuario;
            } else {
                $idUsuario = (int) $idUsuario;
                
                // Verificar se o usuário está tentando acessar dados de outro usuário
                if ($idUsuario !== self::$usuarioAutenticado->idUsuario) {
                    Http::HttpResponse(403, 'Acesso negado: você só pode acessar suas próprias categorias de interesse');
                    return;
                }
            }

            $resultado = $this->service->buscarCategoriasInteresse($idUsuario);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message, $resultado->data);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::obterCategoriasInteresse - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    /**
     * Endpoint 4: Obter campanhas recomendadas
     * GET /api/historico-interacao/campanhas-recomendadas?idUsuario={id}
     */
    #[HttpGet('/historico-interacao/campanhas-recomendadas')]
    public function obterCampanhasRecomendadas(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idUsuario = $_GET['idUsuario'] ?? null;
            $limite = (int) ($_GET['limite'] ?? 20);
            
            // Se não foi fornecido idUsuario, usar o do usuário autenticado
            if (!$idUsuario) {
                $idUsuario = self::$usuarioAutenticado->idUsuario;
            } else {
                $idUsuario = (int) $idUsuario;
                
                // Verificar se o usuário está tentando acessar recomendações de outro usuário
                if ($idUsuario !== self::$usuarioAutenticado->idUsuario) {
                    Http::HttpResponse(403, 'Acesso negado: você só pode acessar suas próprias recomendações');
                    return;
                }
            }

            // Validar limite
            if ($limite < 1 || $limite > 100) {
                Http::HttpResponse(400, 'Limite deve estar entre 1 e 100');
                return;
            }

            $resultado = $this->service->buscarCampanhasRecomendadas($idUsuario, $limite);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message, $resultado->data);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::obterCampanhasRecomendadas - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    /**
     * Endpoint 5: Remover interação específica
     * DELETE /historico-interacao?id={id}
     */
    #[HttpDelete('/historico-interacao')]
    public function removerInteracao(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idInteracao = $_GET['id'] ?? null;
            
            if (!$idInteracao || !is_numeric($idInteracao)) {
                Http::HttpResponse(400, 'ID da interação é obrigatório e deve ser numérico');
                return;
            }

            $idInteracao = (int) $idInteracao;
            $idUsuario = self::$usuarioAutenticado->idUsuario;

            $resultado = $this->service->removerInteracao($idInteracao, $idUsuario);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::removerInteracao - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    #[HttpDelete('/historico-interacao/limpar')]
    public function limparHistorico(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idUsuario = $_GET['idUsuario'] ?? null;
            
            if (!$idUsuario) {
                $idUsuario = self::$usuarioAutenticado->idUsuario;
            } else {
                $idUsuario = (int) $idUsuario;
                
                if ($idUsuario !== self::$usuarioAutenticado->idUsuario) {
                    Http::HttpResponse(403, 'Acesso negado: você só pode limpar seu próprio histórico');
                    return;
                }
            }

            $resultado = $this->service->limparHistoricoUsuario($idUsuario);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::limparHistorico - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    /**
     * Endpoint adicional: Obter estatísticas do usuário
     * GET /api/historico-interacao/estatisticas
     */
    #[HttpGet('/historico-interacao/estatisticas')]
    public function obterEstatisticas(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idUsuario = self::$usuarioAutenticado->idUsuario;

            $resultado = $this->service->obterEstatisticasUsuario($idUsuario);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message, $resultado->data);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::obterEstatisticas - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }

    /**
     * Endpoint adicional: Obter recomendações avançadas
     * GET /api/historico-interacao/recomendacoes-avancadas
     */
    #[HttpGet('/historico-interacao/recomendacoes-avancadas')]
    public function obterRecomendacoesAvancadas(): void
    {
        if (!$this->estaAutenticado()) {
            Http::HttpResponse(401, 'Usuário não autenticado');
            return;
        }

        try {
            $idUsuario = self::$usuarioAutenticado->idUsuario;
            
            $filtros = [
                'limite' => (int) ($_GET['limite'] ?? 20),
                'incluirPopulares' => filter_var($_GET['incluirPopulares'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
                'percentualPopulares' => (int) ($_GET['percentualPopulares'] ?? 20)
            ];

            // Validar filtros
            if ($filtros['limite'] < 1 || $filtros['limite'] > 100) {
                Http::HttpResponse(400, 'Limite deve estar entre 1 e 100');
                return;
            }

            if ($filtros['percentualPopulares'] < 0 || $filtros['percentualPopulares'] > 100) {
                Http::HttpResponse(400, 'Percentual de populares deve estar entre 0 e 100');
                return;
            }

            $resultado = $this->service->obterRecomendacoesAvancadas($idUsuario, $filtros);
            
            if ($resultado->success) {
                Http::HttpResponse(200, $resultado->message, $resultado->data);
            } else {
                Http::HttpResponse(400, $resultado->message);
            }

        } catch (\Exception $e) {
            error_log('Erro no HistoricoInteracaoController::obterRecomendacoesAvancadas - ' . $e->getMessage());
            Http::HttpResponse(500, 'Erro interno do servidor');
        }
    }
}