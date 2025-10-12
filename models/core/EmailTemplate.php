<?php

namespace models\core;

use modules\core\tipos\Entidade;
use modules\db\Database;

class EmailTemplate extends Entidade
{
    public string $nomeTabela = "EmailTemplate";
    public int $id;
    public int $idEvento;
    public string $nome;
    public string $assunto;
    public string $corpo;

    public static function buscar(?string $pesquisa = null): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT t.*, e.titulo as nomeEvento FROM EmailTemplate t JOIN Evento e ON t.idEvento = e.id";
        if ($pesquisa) {
            $sql .= " WHERE t.nome LIKE :pesquisa OR t.assunto LIKE :pesquisa OR e.titulo LIKE :pesquisa";
        }
        $stmt = $pdo->prepare($sql);
        if ($pesquisa) {
            $stmt->bindValue(':pesquisa', '%' . $pesquisa . '%');
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function buscarPorId(int $id): ?EmailTemplate
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM EmailTemplate WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $template = $stmt->fetch();
        return $template ?: null;
    }

    public static function criar(EmailTemplate $template): int
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO EmailTemplate (idEvento, nome, assunto, corpo) VALUES (:idEvento, :nome, :assunto, :corpo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idEvento' => $template->idEvento,
            ':nome' => $template->nome,
            ':assunto' => $template->assunto,
            ':corpo' => $template->corpo,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function atualizar(EmailTemplate $template): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE EmailTemplate SET idEvento = :idEvento, nome = :nome, assunto = :assunto, corpo = :corpo WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $template->id,
            ':idEvento' => $template->idEvento,
            ':nome' => $template->nome,
            ':assunto' => $template->assunto,
            ':corpo' => $template->corpo,
        ]);
    }

    public static function deletar(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM EmailTemplate WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function buscarPorEvento(int $idEvento): ?EmailTemplate
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM EmailTemplate WHERE idEvento = :idEvento");
        $stmt->execute([':idEvento' => $idEvento]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, self::class);
        $template = $stmt->fetch();
        return $template ?: null;
    }
}
