<?php

namespace models\campanha;

use modules\core\tipos\Entidade;
use modules\db\Database;
use PDO;
use DateTime;

class HistoricoInteracao extends Entidade
{
    public string $nomeTabela = "HistoricoInteracao";
    
    public int $id = 0;
    public int $idUsuario = 0;
    public int $idCampanha = 0;
    public int $idCategoria = 0;
    public ?string $dataCriacao = null;
    
    // Propriedades para joins
    public ?string $tituloCampanha = null;
    public ?string $tituloCategoria = null;
    public ?string $nomeAutor = null;
    public ?string $caminhoImagemAutor = null;

    public function __construct()
    {
        if (!$this->dataCriacao) {
            $this->dataCriacao = (new DateTime())->format('Y-m-d H:i:s');
        }
    }

    /**
     * Registra uma nova interação, evitando duplicatas no mesmo dia
     */
    public static function registrarInteracao(int $idUsuario, int $idCampanha, int $idCategoria): bool
    {
        $pdo = Database::getConnection();
        
        // Verificar se já existe interação hoje
        $hoje = date('Y-m-d');
        $sqlVerifica = "SELECT COUNT(*) FROM HistoricoInteracao 
                       WHERE idUsuario = :idUsuario 
                       AND idCampanha = :idCampanha 
                       AND DATE(dataCriacao) = :hoje";
        
        $stmt = $pdo->prepare($sqlVerifica);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idCampanha' => $idCampanha,
            ':hoje' => $hoje
        ]);
        
        $jaInteragiu = $stmt->fetchColumn() > 0;
        
        if ($jaInteragiu) {
            return false; // Já interagiu hoje
        }
        
        // Inserir nova interação
        $sql = "INSERT INTO HistoricoInteracao (idUsuario, idCampanha, idCategoria, dataCriacao) 
                VALUES (:idUsuario, :idCampanha, :idCategoria, NOW())";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idCampanha' => $idCampanha,
            ':idCategoria' => $idCategoria
        ]);
    }

    /**
     * Busca o histórico de interações de um usuário
     */
    public static function buscarHistoricoUsuario(int $idUsuario, int $limite = 50): array
    {
        $pdo = Database::getConnection();
        
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca categorias de interesse ordenadas por pontuação
     */
    public static function buscarCategoriasInteresse(int $idUsuario): array
    {
        $pdo = Database::getConnection();
        
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca campanhas já visualizadas pelo usuário
     */
    public static function buscarCampanhasVisualizadas(int $idUsuario): array
    {
        $pdo = Database::getConnection();
        
        $sql = "SELECT DISTINCT idCampanha FROM HistoricoInteracao WHERE idUsuario = :idUsuario";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idUsuario' => $idUsuario]);
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'idCampanha');
    }

    /**
     * Busca campanhas recomendadas baseadas no histórico do usuário
     */
    public static function buscarCampanhasRecomendadas(int $idUsuario, int $limite = 20): array
    {
        $pdo = Database::getConnection();
        
        // Buscar categorias de interesse
        $categoriasInteresse = self::buscarCategoriasInteresse($idUsuario);
        
        if (empty($categoriasInteresse)) {
            // Se não há histórico, retornar campanhas mais populares
            return self::buscarCampanhasPopulares($limite);
        }
        
        // Buscar campanhas já visualizadas
        $campanhasVisualizadas = self::buscarCampanhasVisualizadas($idUsuario);
        $campanhasVisualizadasStr = empty($campanhasVisualizadas) ? '0' : implode(',', $campanhasVisualizadas);
        
        // Extrair IDs das categorias
        $categoriasIds = array_column($categoriasInteresse, 'idCategoria');
        $categoriasIdsStr = implode(',', $categoriasIds);
        
        $sql = "SELECT 
                    c.*,
                    cat.titulo AS categoria,
                    u.nomeUsuario AS nomeAutor,
                    u.caminhoImagem AS caminhoImagemAutor,
                    IFNULL((SELECT SUM(d.valor) FROM Doacao d WHERE d.idCampanha = c.idCampanha AND d.status = 'completed'), 0) AS valorArrecadado,
                    (SELECT COUNT(*) FROM HistoricoInteracao h WHERE h.idCategoria = c.idCategoria AND h.idUsuario = :idUsuario) AS pontuacaoCategoria,
                    (SELECT caminhoArquivo FROM Midia m WHERE m.idEntidade = c.idCampanha AND m.tipoEntidade = 'Campanha' AND m.tipo = 'imagem' ORDER BY m.isCapa DESC LIMIT 1) AS imagemCapa
                FROM Campanha c
                LEFT JOIN Categoria cat ON cat.id = c.idCategoria
                LEFT JOIN Usuario u ON u.idUsuario = c.idUsuario
                WHERE c.status = 1 
                AND c.idCategoria IN ($categoriasIdsStr)
                AND c.idCampanha NOT IN ($campanhasVisualizadasStr)
                ORDER BY pontuacaoCategoria DESC, c.dataCriacao DESC
                LIMIT :limite";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca campanhas populares quando não há histórico
     */
    private static function buscarCampanhasPopulares(int $limite = 20): array
    {
        $pdo = Database::getConnection();
        
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove uma interação específica
     */
    public static function removerInteracao(int $id, int $idUsuario): bool
    {
        $pdo = Database::getConnection();
        
        $sql = "DELETE FROM HistoricoInteracao WHERE id = :id AND idUsuario = :idUsuario";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':idUsuario' => $idUsuario
        ]);
    }

    /**
     * Limpa todo o histórico de um usuário
     */
    public static function limparHistoricoUsuario(int $idUsuario): bool
    {
        $pdo = Database::getConnection();
        
        $sql = "DELETE FROM HistoricoInteracao WHERE idUsuario = :idUsuario";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':idUsuario' => $idUsuario]);
    }

    /**
     * Verifica se o usuário já interagiu com uma campanha hoje
     */
    public static function jaInteragiuHoje(int $idUsuario, int $idCampanha): bool
    {
        $pdo = Database::getConnection();
        
        $hoje = date('Y-m-d');
        $sql = "SELECT COUNT(*) FROM HistoricoInteracao 
                WHERE idUsuario = :idUsuario 
                AND idCampanha = :idCampanha 
                AND DATE(dataCriacao) = :hoje";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':idCampanha' => $idCampanha,
            ':hoje' => $hoje
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
}