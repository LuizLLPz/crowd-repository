<?php

namespace database;

use modules\db\Database;
use PDO;

class HistoricoInteracaoMigration
{
    public static function criarTabela(): bool
    {
        $pdo = Database::getConnection();
        
        try {
            // Verificar se a tabela já existe
            $stmt = $pdo->query("SHOW TABLES LIKE 'HistoricoInteracao'");
            if ($stmt->rowCount() > 0) {
                return true; // Tabela já existe
            }
            
            // Criar a tabela
            $sql = "
                CREATE TABLE HistoricoInteracao (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    idUsuario BIGINT NOT NULL,
                    idCampanha BIGINT NOT NULL, 
                    idCategoria BIGINT NOT NULL,
                    dataCriacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_usuario_categoria (idUsuario, idCategoria),
                    INDEX idx_usuario_data (idUsuario, dataCriacao),
                    INDEX idx_campanha_data (idCampanha, dataCriacao),
                    INDEX idx_categoria_data (idCategoria, dataCriacao)
                )
            ";
            
            $pdo->exec($sql);
            
            // Adicionar as foreign keys separadamente (mais compatível)
            $fks = [
                "ALTER TABLE HistoricoInteracao ADD FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario) ON DELETE CASCADE",
                "ALTER TABLE HistoricoInteracao ADD FOREIGN KEY (idCampanha) REFERENCES Campanha(idCampanha) ON DELETE CASCADE", 
                "ALTER TABLE HistoricoInteracao ADD FOREIGN KEY (idCategoria) REFERENCES Categoria(id) ON DELETE CASCADE"
            ];
            
            foreach ($fks as $fk) {
                try {
                    $pdo->exec($fk);
                } catch (\Exception $e) {
                    // Ignorar se foreign key já existe ou tabela referenciada não existe
                    error_log("FK Warning: " . $e->getMessage());
                }
            }
            
            $pdo->exec($sql);
            
            // Criar a view opcional
            $viewSql = "
                CREATE OR REPLACE VIEW categoria_interesse AS
                SELECT 
                    h.idUsuario,
                    h.idCategoria,
                    c.titulo as nomeCategoria,
                    COUNT(*) as pontos,
                    MAX(h.dataCriacao) as ultimaInteracao
                FROM HistoricoInteracao h
                JOIN Categoria c ON h.idCategoria = c.id
                GROUP BY h.idUsuario, h.idCategoria, c.titulo
            ";
            
            $pdo->exec($viewSql);
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Erro ao criar tabela HistoricoInteracao: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function verificarECriar(): bool
    {
        return self::criarTabela();
    }
}