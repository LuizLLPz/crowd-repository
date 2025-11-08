<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

use models\campanha\Campanha;

class Envolvido extends Entidade
{
    public int $idEnvolvido;
    public int $idCampanha;
    public int $idUsuario;
    public string $nomeTabela = "Envolvidos";
    public string $papel;

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Envolvido[]
     */
    public static function buscarEnvolvidoPorIdCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                e.idEnvolvido as id,
                e.idCampanha,
                e.idUsuario,
                e.papel,
                u.nomeUsuario,
                u.telefone,
                u.linkedin,
                u.github,
                u.instagram,
                u.caminhoImagem,
                u.descricao,
                c.titulo AS cargo
            FROM Envolvidos e
            INNER JOIN Usuario u 
                ON e.idUsuario = u.idUsuario
            LEFT JOIN Cargo c
                ON u.idCargo = c.id
            WHERE e.idCampanha = :idCampanha;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function adicionarEnvolvido(Envolvido $envolvido, int $idUsuarioLogado): void
    {
        if (!Campanha::isOwner($envolvido->idCampanha, $idUsuarioLogado)) {
            throw new \Exception("Você não tem permissão para adicionar participantes a esta campanha.");
        }

        $pdo = Database::getConnection();
        $sql = "INSERT INTO Envolvidos (idCampanha, idUsuario, papel) VALUES (:idCampanha, :idUsuario, :papel)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $envolvido->idCampanha,
            ':idUsuario' => $envolvido->idUsuario,
            ':papel' => $envolvido->papel,
        ]);
    }

    public static function removerEnvolvido(int $idEnvolvido, int $idUsuarioLogado): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT idCampanha FROM Envolvidos WHERE idEnvolvido = :idEnvolvido");
        $stmt->execute([':idEnvolvido' => $idEnvolvido]);
        $idCampanha = $stmt->fetchColumn();

        if (!$idCampanha || !Campanha::isOwner($idCampanha, $idUsuarioLogado)) {
            throw new \Exception("Você não tem permissão para remover este participante.");
        }

        $sql = "DELETE FROM Envolvidos WHERE idEnvolvido = :idEnvolvido";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idEnvolvido' => $idEnvolvido]);
    }

    public static function salvar(Envolvido $envolvido): void
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Envolvidos (idCampanha, idUsuario, papel) VALUES (:idCampanha, :idUsuario, :papel)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $envolvido->idCampanha,
            ':idUsuario' => $envolvido->idUsuario,
            ':papel' => $envolvido->papel,
        ]);
    }



}