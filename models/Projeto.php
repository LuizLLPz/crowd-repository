<?php

namespace models;

use modules\core\tipos\Entidade;
use modules\db\Database;

class Projeto extends Entidade
{
    public string $nomeTabela = "Projeto";
    public int $idProjeto = 0;
    public int $idUsuario = 0;
    public string $titulo;
    public string $roadmap;
    public int $metaArrecadacao;
    public int $valorArrecadado = 0;
    public string $telefone = '';
    public string $linkedin = '';
    public string $email = '';
    public string $github = '';
    public string $instagram = '';

    public static function buscarProjetos(int $idUsuario): array {
        $pdo = Database::getConnection();
        $sqlString = (new Projeto()->select) . " WHERE idUsuario = :idUsuario";
        $stmt = $pdo->prepare($sqlString);
        $stmt->execute([":idUsuario" => $idUsuario]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function buscarProjetoID(int $idProjeto): array {
        $pdo = Database::getConnection();
        $sql = new Projeto()->select." WHERE idProjeto = :idProjeto";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":idProjeto" => $idProjeto]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function salvarProjeto(Projeto $projeto): string
    {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO Projeto (
                    idUsuario,
                    titulo, 
                    roadmap, 
                    metaArrecadacao, 
                    valorArrecadado, 
                    telefone, 
                    linkedin, 
                    email, 
                    github, 
                    instagram
                ) VALUES (
                    :idUsuario,
                    :titulo, 
                    :roadmap, 
                    :metaArrecadacao, 
                    :valorArrecadado, 
                    :telefone, 
                    :linkedin, 
                    :email, 
                    :github, 
                    :instagram
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario'        => $projeto->idUsuario,
            ':titulo'           => $projeto->titulo,
            ':roadmap'          => $projeto->roadmap,
            ':metaArrecadacao'  => $projeto->metaArrecadacao,
            ':valorArrecadado'  => $projeto->valorArrecadado, // Assumindo que este valor é passado no objeto Projeto
            ':telefone'         => $projeto->telefone,
            ':linkedin'         => $projeto->linkedin,
            ':email'            => $projeto->email,
            ':github'           => $projeto->github,
            ':instagram'        => $projeto->instagram,
        ]);
        $projeto->idProjeto = $pdo->lastInsertId();

        return "{$_ENV["CORS_ORIGIN"]}/projeto/{$projeto->idProjeto}";
    }
}