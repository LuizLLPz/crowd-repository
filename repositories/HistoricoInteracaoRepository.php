<?php

namespace repositories;

use modules\db\Database;
use PDO;
use services\integrations\google\GoogleCloudStorageService;
use database\HistoricoInteracaoMigration;

class HistoricoInteracaoRepository
{
    private PDO $pdo;
    private static bool $tabelaVerificada = false;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
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
     * Registra uma nova interação, evitando duplicatas no mesmo dia
     */
    public function registrarInteracao(int $idUsuario, int $idCampanha, int $idCategoria): bool
    {
        // Verificar se já existe interação hoje
        if ($this->jaInteragiuHoje($idUsuario, $idCampanha)) {
            return false;
        }
        
        $sql = "INSERT INTO HistoricoInteracao (idUsuario, idCampanha, idCategoria, dataCriacao) 
                VALUES (:idUsuario, :idCampanha, :idCategoria, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idCampanha' => $idCampanha,
            ':idCategoria' => $idCategoria
        ]);
    }

    /**
     * Verifica se o usuário já interagiu com uma campanha hoje
     */
    public function jaInteragiuHoje(int $idUsuario, int $idCampanha): bool
    {
        $hoje = date('Y-m-d');
        $sql = "SELECT COUNT(*) FROM HistoricoInteracao 
                WHERE idUsuario = :idUsuario 
                AND idCampanha = :idCampanha 
                AND DATE(dataCriacao) = :hoje";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idCampanha' => $idCampanha,
            ':hoje' => $hoje
        ]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Busca o histórico de interações de um usuário
     */
    public function buscarHistoricoUsuario(int $idUsuario, int $limite = 50): array
    {
        $sql = "SELECT h.*, 
                       c.titulo AS tituloCampanha,
                       cat.titulo AS tituloCategoria,
                       u.nomeUsuario AS nomeAutor,
                       u.caminhoImagem AS caminhoImagemAutor
                FROM HistoricoInteracao h
                LEFT JOIN Campanha c ON c.idCampanha = h.idCampanha
                LEFT JOIN Categoria cat ON cat.id = h.idCategoria
                LEFT JOIN Usuario u ON u.idUsuario = c.idUsuario
                WHERE h.idUsuario = :idUsuario
                ORDER BY h.dataCriacao DESC
                LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar URLs das imagens
        foreach ($resultados as &$item) {
            if (!empty($item['caminhoImagemAutor'])) {
                $item['caminhoImagemAutor'] = GoogleCloudStorageService::getSignedUrl($item['caminhoImagemAutor']);
            }
        }
        
        return $resultados;
    }

    /**
     * Busca categorias de interesse ordenadas por pontuação
     */
    public function buscarCategoriasInteresse(int $idUsuario): array
    {
        $sql = "SELECT 
                    h.idCategoria,
                    c.titulo AS nomeCategoria,
                    COUNT(*) AS pontos,
                    MAX(h.dataCriacao) AS ultimaInteracao
                FROM HistoricoInteracao h
                JOIN Categoria c ON h.idCategoria = c.id
                WHERE h.idUsuario = :idUsuario
                GROUP BY h.idCategoria, c.titulo
                ORDER BY pontos DESC, ultimaInteracao DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca campanhas já visualizadas pelo usuário
     */
    public function buscarCampanhasVisualizadas(int $idUsuario): array
    {
        $sql = "SELECT DISTINCT idCampanha FROM HistoricoInteracao WHERE idUsuario = :idUsuario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'idCampanha');
    }

    /**
     * Busca campanhas recomendadas baseadas no histórico do usuário
     */
    public function buscarCampanhasRecomendadas(int $idUsuario, int $limite = 20): array
    {
        // Buscar categorias de interesse
        $categoriasInteresse = $this->buscarCategoriasInteresse($idUsuario);
        
        if (empty($categoriasInteresse)) {
            // Se não há histórico, retornar campanhas mais populares
            return $this->buscarCampanhasPopulares($limite);
        }
        
        // Extrair IDs das categorias
        $categoriasIds = array_column($categoriasInteresse, 'idCategoria');
        $categoriasStr = implode(',', array_map('intval', $categoriasIds));
        
        $sql = "SELECT 
                    c.*,
                    cat.titulo AS categoria,
                    u.nomeUsuario AS nomeAutor,
                    u.caminhoImagem AS caminhoImagemAutor,
                    IFNULL((SELECT SUM(d.valor) FROM Doacao d WHERE d.idCampanha = c.idCampanha AND d.status = 'completed'), 0) AS valorArrecadado,
                    (SELECT COUNT(*) FROM HistoricoInteracao h WHERE h.idCategoria = c.idCategoria AND h.idUsuario = :idUsuario) AS pontuacaoCategoria,
                    (SELECT caminhoArquivo FROM Midia m WHERE m.idEntidade = c.idCampanha AND m.tipoEntidade = 'Campanha' AND m.tipo = 'imagem' ORDER BY m.isCapa DESC LIMIT 1) AS imagemCapa,
                    (SELECT MAX(h2.dataCriacao) FROM HistoricoInteracao h2 WHERE h2.idCampanha = c.idCampanha AND h2.idUsuario = :idUsuario2) AS ultimaVisualizacao,
                    CASE 
                        WHEN (SELECT COUNT(*) FROM HistoricoInteracao h3 WHERE h3.idCampanha = c.idCampanha AND h3.idUsuario = :idUsuario3) > 0 
                        THEN 1 ELSE 0 
                    END AS jaVisualizada
                FROM Campanha c
                LEFT JOIN Categoria cat ON cat.id = c.idCategoria
                LEFT JOIN Usuario u ON u.idUsuario = c.idUsuario
                WHERE c.status = 1 
                AND c.idCategoria IN ($categoriasStr)
                AND c.idUsuario != :idUsuario4
                ORDER BY 
                    jaVisualizada ASC,
                    pontuacaoCategoria DESC, 
                    c.dataCriacao DESC
                LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario2', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario3', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario4', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar URLs das imagens
        foreach ($resultados as &$campanha) {
            if (!empty($campanha['imagemCapa'])) {
                $campanha['imagemCapa'] = GoogleCloudStorageService::getSignedUrl($campanha['imagemCapa']);
            }
            if (!empty($campanha['caminhoImagemAutor'])) {
                $campanha['caminhoImagemAutor'] = GoogleCloudStorageService::getSignedUrl($campanha['caminhoImagemAutor']);
            }
        }
        
        return $resultados;
    }

    /**
     * Busca campanhas populares quando não há histórico
     */
    public function buscarCampanhasPopulares(int $limite = 20): array
    {
        $sql = "SELECT 
                    c.*,
                    cat.titulo AS categoria,
                    u.nomeUsuario AS nomeAutor,
                    u.caminhoImagem AS caminhoImagemAutor,
                    IFNULL((SELECT SUM(d.valor) FROM Doacao d WHERE d.idCampanha = c.idCampanha AND d.status = 'completed'), 0) AS valorArrecadado,
                    0 AS pontuacaoCategoria,
                    (SELECT caminhoArquivo FROM Midia m WHERE m.idEntidade = c.idCampanha AND m.tipoEntidade = 'Campanha' AND m.tipo = 'imagem' ORDER BY m.isCapa DESC LIMIT 1) AS imagemCapa
                FROM Campanha c
                LEFT JOIN Categoria cat ON cat.id = c.idCategoria
                LEFT JOIN Usuario u ON u.idUsuario = c.idUsuario
                WHERE c.status = 1
                ORDER BY valorArrecadado DESC, c.dataCriacao DESC
                LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar URLs das imagens
        foreach ($resultados as &$campanha) {
            if (!empty($campanha['imagemCapa'])) {
                $campanha['imagemCapa'] = GoogleCloudStorageService::getSignedUrl($campanha['imagemCapa']);
            }
            if (!empty($campanha['caminhoImagemAutor'])) {
                $campanha['caminhoImagemAutor'] = GoogleCloudStorageService::getSignedUrl($campanha['caminhoImagemAutor']);
            }
        }
        
        return $resultados;
    }

    /**
     * Remove uma interação específica
     */
    public function removerInteracao(int $id, int $idUsuario): bool
    {
        $sql = "DELETE FROM HistoricoInteracao WHERE id = :id AND idUsuario = :idUsuario";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':idUsuario' => $idUsuario
        ]);
    }

    /**
     * Limpa todo o histórico de um usuário
     */
    public function limparHistoricoUsuario(int $idUsuario): bool
    {
        $sql = "DELETE FROM HistoricoInteracao WHERE idUsuario = :idUsuario";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':idUsuario' => $idUsuario]);
    }

    /**
     * Conta o total de interações de um usuário
     */
    public function contarInteracoesUsuario(int $idUsuario): int
    {
        $sql = "SELECT COUNT(*) FROM HistoricoInteracao WHERE idUsuario = :idUsuario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Busca estatísticas de interações por período
     */
    public function buscarEstatisticasPorPeriodo(int $idUsuario, string $dataInicio, string $dataFim): array
    {
        $sql = "SELECT 
                    DATE(dataCriacao) as data,
                    COUNT(*) as total_interacoes,
                    COUNT(DISTINCT idCategoria) as categorias_distintas,
                    COUNT(DISTINCT idCampanha) as campanhas_distintas
                FROM HistoricoInteracao 
                WHERE idUsuario = :idUsuario 
                AND DATE(dataCriacao) BETWEEN :dataInicio AND :dataFim
                GROUP BY DATE(dataCriacao)
                ORDER BY data DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':dataInicio' => $dataInicio,
            ':dataFim' => $dataFim
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}