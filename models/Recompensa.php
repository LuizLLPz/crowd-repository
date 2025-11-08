<?php

namespace models;

use DateTime;
use modules\core\tipos\Entidade;
use modules\db\Database;

use models\campanha\Campanha;

class Recompensa extends Entidade
{
    public int $id;
    public int $idCampanha;
    public int $nivel;
    public string $nomeTabela = "Recompensa";
    public string $nomeNivel;
    public int $valorDoacao;
    public string $vantagens;
    public string $corRecompensa;

    public function __construct()
    {
        unset($this->funcao);
    }


    /**
     * @return Recompensa[]
     */
    public static function buscarRecompensaPorIdCampanha(int $idCampanha): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                id,
                idCampanha,
                nivel,
                nomeNivel,
                valorDoacao,
                vantagens,
                corRecompensa
            FROM 
                Recompensa
            WHERE 
                idCampanha = :idCampanha;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function adicionarRecompensa(Recompensa $recompensa, int $idUsuarioLogado): void
    {
        if (!Campanha::isOwner($recompensa->idCampanha, $idUsuarioLogado)) {
            throw new \Exception("Você não tem permissão para adicionar recompensas a esta campanha.");
        }

        $pdo = Database::getConnection();
        $sql = "INSERT INTO Recompensa (idCampanha, nivel, nomeNivel, valorDoacao, vantagens, corRecompensa) VALUES (:idCampanha, :nivel, :nomeNivel, :valorDoacao, :vantagens, :corRecompensa)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $recompensa->idCampanha,
            ':nivel' => $recompensa->nivel,
            ':nomeNivel' => $recompensa->nomeNivel,
            ':valorDoacao' => $recompensa->valorDoacao,
            ':vantagens' => $recompensa->vantagens,
            ':corRecompensa' => $recompensa->corRecompensa,
        ]);
    }

    public static function atualizarRecompensa(Recompensa $recompensa, int $idUsuarioLogado): void
    {
        if (!Campanha::isOwner($recompensa->idCampanha, $idUsuarioLogado)) {
            throw new \Exception("Você não tem permissão para atualizar recompensas desta campanha.");
        }

        $pdo = Database::getConnection();
        $sql = "UPDATE Recompensa SET nivel = :nivel, nomeNivel = :nomeNivel, valorDoacao = :valorDoacao, vantagens = :vantagens, corRecompensa = :corRecompensa WHERE id = :id AND idCampanha = :idCampanha";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nivel' => $recompensa->nivel,
            ':nomeNivel' => $recompensa->nomeNivel,
            ':valorDoacao' => $recompensa->valorDoacao,
            ':vantagens' => $recompensa->vantagens,
            ':corRecompensa' => $recompensa->corRecompensa,
            ':id' => $recompensa->id,
            ':idCampanha' => $recompensa->idCampanha,
        ]);
    }

    public static function removerRecompensa(int $idRecompensa, int $idUsuarioLogado): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT idCampanha FROM Recompensa WHERE id = :idRecompensa");
        $stmt->execute([':idRecompensa' => $idRecompensa]);
        $idCampanha = $stmt->fetchColumn();

        if (!$idCampanha || !Campanha::isOwner($idCampanha, $idUsuarioLogado)) {
            throw new \Exception("Você não tem permissão para remover esta recompensa.");
        }

        $sql = "DELETE FROM Recompensa WHERE id = :idRecompensa";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idRecompensa' => $idRecompensa]);
    }

    public static function salvar(Recompensa $recompensa): void
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO Recompensa (idCampanha, nivel, nomeNivel, valorDoacao, vantagens, corRecompensa) VALUES (:idCampanha, :nivel, :nomeNivel, :valorDoacao, :vantagens, :corRecompensa)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idCampanha' => $recompensa->idCampanha,
            ':nivel' => $recompensa->nivel,
            ':nomeNivel' => $recompensa->nomeNivel,
            ':valorDoacao' => $recompensa->valorDoacao,
            ':vantagens' => $recompensa->vantagens,
            ':corRecompensa' => $recompensa->corRecompensa,
        ]);
    }



}