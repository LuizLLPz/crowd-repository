<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Projeto extends Entidade
{
    public string $nomeTabela = "Projeto";

    public string $titulo;
    public int $metaArrecadacao;
    public int $valorArrecadado;
    public int $quantidadeApoiadores;

    public static function buscarProjetos(): array {

        $pdo = Database::getConnection();
        $stmt = $pdo->query(new Projeto()->select);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function salvarProjeto(Projeto $projeto): string {

        $pdo = Database::getConnection();

        $sql = "INSERT INTO Projeto (titulo, metaArrecadacao) 
            VALUES (:titulo, :metaArrecadacao)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':titulo' => $projeto->titulo,
            ':metaArrecadacao' => $projeto->metaArrecadacao,
        ]);

        return "Projeto cadastrado com sucesso!";
    }
}