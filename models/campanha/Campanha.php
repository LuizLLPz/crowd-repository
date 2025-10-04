<?php

namespace models\campanha;

use models\campanha\enums\FiltroCampanha;
use models\campanha\enums\StatusCampanha;
use models\campanha\enums\TipoAlvo;
use models\core\Denuncia;
use modules\core\tipos\Entidade;
use modules\db\Database;
use services\integrations\google\GoogleCloudStorageService;

class Campanha extends Entidade
{
    public string $nomeTabela = "Campanha";
    public int $idCampanha = 0;
    public int $idUsuario = 0;
    public string $titulo = '';
    public int $status = 0;
    public int $idCategoria = 0;
    public string $categoria = "";
    public string $roadmap = '';
    public string $caminhoImagem = '';
    public int $metaArrecadacao = 0;
    public int $valorArrecadado = 0;
    public string $telefone = '';
    public string $linkedin = '';
    public string $email = '';
    public string $github = '';
    public string $instagram = '';
    public bool $denunciadoUsuario;
    public bool $inscritoUsuario;
    public string $nomeAutor;
    public string $caminhoFotoAutor;
    public int $qtdDenuncias;
    public static function buscar_campanhas(?bool $administrador = false, ?string $pesquisa = null, ?int $idCategoria = null, ?int $idUsuario = null, ?int $filtroAdministrador = null, ?int $idUsuarioApoiador = null): array {
        $pdo = Database::getConnection();

        $sql = "SELECT C.*, CA.titulo AS categoria, U.nomeUsuario AS nomeAutor, U.caminhoImagem AS caminhoImagemAutor,
            (SELECT COUNT(*) FROM Denuncia D WHERE D.idAlvo = C.idCampanha and tipoAlvo = 'Campanha') AS qtdDenuncias,
            IFNULL((SELECT SUM(D.valor) FROM Doacao D WHERE D.idCampanha = C.idCampanha AND D.status = 'completed'), 0) AS valorArrecadado,
            IFNULL((SELECT COUNT(DISTINCT D.idUsuario) FROM Doacao D WHERE D.idCampanha = C.idCampanha AND D.status = 'completed'), 0) AS qtdApoiadores
            FROM Campanha C 
            LEFT JOIN Categoria CA ON CA.id = C.idCategoria 
            LEFT JOIN Usuario U ON U.idUsuario = C.idUsuario
            ";

        $where = [];
        $params = [];

        if ($idUsuarioApoiador) {
            $sql .= " LEFT JOIN Doacao DOA ON DOA.idCampanha = C.idCampanha";
            $where[] = "DOA.idUsuario = :idUsuarioApoiador";
            $params[':idUsuarioApoiador'] = $idUsuarioApoiador;
        }

        if ($pesquisa) {
            $where[] = "C.titulo LIKE :pesquisa";
            $params[':pesquisa'] = '%' . $pesquisa . '%';
        }

        if ($idCategoria) {
            $where[] = "C.idCategoria = :idCategoria";
            $params[':idCategoria'] = $idCategoria;
        }

        if ($idUsuario) {
            $where[] = "C.idUsuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }

        if ($idUsuario == null && !$administrador) {
           $where[] = "C.status = ".StatusCampanha::ATIVA->value;
        }

        if ($administrador && $filtroAdministrador == null) {
            $where[] = "C.status not in (".StatusCampanha::ENCERRADA->value.",".StatusCampanha::DESATIVADA->value.")";
        }

        if ($filtroAdministrador != null) {
           $where[] = "C.status = :filtroAdministrador";
           $params[':filtroAdministrador'] = $filtroAdministrador;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY C.idCampanha ORDER BY dataCriacao desc ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $campanhas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($campanhas as &$campanha) {
            if (!empty($campanha['caminhoImagem'])) {
                $campanha['caminhoImagem'] = GoogleCloudStorageService::getSignedUrl($campanha['caminhoImagem']);
            }
            if (!empty($campanha['caminhoImagemAutor'])) {
                error_log('Campanha::buscar_campanhas - caminhoImagemAutor before signing: ' . $campanha['caminhoImagemAutor']);
                $campanha['caminhoImagemAutor'] = GoogleCloudStorageService::getSignedUrl($campanha['caminhoImagemAutor']);
            }
        }

        return $campanhas;
    }


    public static function obter_campanha(int $idCampanha, ?int $idUsuario = null): ?Campanha {
        $pdo = Database::getConnection();
        $sql = "SELECT P.*, C.titulo AS categoria, U.nomeUsuario AS nomeAutor, U.caminhoImagem AS caminhoImagemAutor,
                  IFNULL((SELECT SUM(D.valor) FROM Doacao D WHERE D.idCampanha = P.idCampanha AND D.status = 'completed'), 0) AS valorArrecadado,
                  IFNULL((SELECT COUNT(DISTINCT D.idUsuario) FROM Doacao D WHERE D.idCampanha = P.idCampanha AND D.status = 'completed'), 0) AS qtdApoiadores
                  FROM Campanha P
               LEFT JOIN Categoria C ON C.id = P.idCategoria
               LEFT JOIN Usuario U ON U.idUsuario = P.idUsuario
               WHERE idCampanha = :idCampanha ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":idCampanha" => $idCampanha]);
        $campanhaData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$campanhaData) {
            return null;
        }

        $campanha = new Campanha();
        foreach ($campanhaData as $key => $value) {
            if (property_exists($campanha, $key)) {
                $campanha->$key = $value;
            }
        }

        if (!empty($campanha->caminhoImagem)) {
            $campanha->caminhoImagem = GoogleCloudStorageService::getSignedUrl($campanha->caminhoImagem);
        }

        if ($idUsuario != null) {
            $campanha->denunciadoUsuario = Denuncia::buscarDenunciaUsuario($idUsuario, $idCampanha, TipoAlvo::CAMPANHA);
            $inscricao = InscricaoCampanha::obterInscritoCampanhaUsuario($idCampanha, $idUsuario);
            $campanha->inscritoUsuario = $inscricao && $inscricao['status'] === 'ativa';
        }

        return $campanha;
    }

    public static function criar_campanha(Campanha $campanha)
    {
        $pdo = Database::getConnection();

        try {

            $sql = "INSERT INTO Campanha (
                    idUsuario,
                    titulo, 
                    roadmap, 
                    idCategoria, 
                    metaArrecadacao, 
                    valorArrecadado, 
                    telefone, 
                    linkedin, 
                    email, 
                    github, 
                    instagram,
                    caminhoImagem,
                    status,
                    dataCriacao
                ) VALUES (
                    :idUsuario,
                    :titulo, 
                    :roadmap, 
                    :idCategoria, 
                    :metaArrecadacao, 
                    :valorArrecadado, 
                    :telefone, 
                    :linkedin, 
                    :email, 
                    :github, 
                    :instagram,
                    :caminhoImagem,
                    3,     
                    now()
        )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo'           => $campanha->titulo,
                ':roadmap'          => $campanha->roadmap,
                ':idCategoria'      => $campanha->idCategoria,
                ':metaArrecadacao'  => $campanha->metaArrecadacao,
                ':valorArrecadado'  => $campanha->valorArrecadado,
                ':telefone'         => $campanha->telefone,
                ':linkedin'         => $campanha->linkedin,
                ':email'            => $campanha->email,
                ':github'           => $campanha->github,
                ':instagram'        => $campanha->instagram,
                ':idUsuario'        => $campanha->idUsuario,
                ':caminhoImagem'    => $campanha->caminhoImagem
            ]);
            $campanha->idCampanha = $pdo->lastInsertId();

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function editar_campanha(Campanha $campanha): string
    {
        $pdo = Database::getConnection();
        try {
            $sql = "UPDATE Campanha SET 
                        titulo = :titulo,
                        roadmap = :roadmap,
                        idCategoria = :idCategoria,
                        metaArrecadacao = :metaArrecadacao,
                        telefone = :telefone,
                        email = :email,
                        linkedin = :linkedin,
                        github = :github,
                        instagram = :instagram
                    WHERE idCampanha = :idCampanha";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $campanha->titulo,
                ':roadmap' => $campanha->roadmap,
                ':idCategoria' => $campanha->idCategoria,
                ':metaArrecadacao' => $campanha->metaArrecadacao,
                ':telefone' => $campanha->telefone,
                ':email' => $campanha->email,
                ':linkedin' => $campanha->linkedin,
                ':github' => $campanha->github,
                ':instagram' => $campanha->instagram,
                ':idCampanha' => $campanha->idCampanha
            ]);
            return "Campanha atualizada com sucesso!";
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function alterar_status(int $idCampanha, int $status): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Campanha SET status = :status WHERE idCampanha = :idCampanha");
        $stmt->execute([
            ':status' => $status,
            ':idCampanha' => $idCampanha
        ]);
    }

    public static function alterar_caminhoImagem(int $idCampanha, string $caminhoImagem): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE Campanha SET caminhoImagem = :caminhoImagem WHERE idCampanha = :idCampanha");
        $stmt->execute([
            ':caminhoImagem' => $caminhoImagem,
            ':idCampanha' => $idCampanha
        ]);
    }

    public static function obter_Titulo(int $idCampanha): string {
        $pdo = Database::getConnection();
        $sql = "SELECT titulo FROM Campanha WHERE idCampanha = :idCampanha";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['titulo'] : '';
    }

    public static function obter_idUsuario(int $idCampanha) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT idUsuario FROM Campanha WHERE idCampanha = :idCampanha");
        $stmt->execute([':idCampanha' => $idCampanha]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['idUsuario'] : null;
    }

    public static function obter_apoiadores(int $idCampanha): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT
                    U.idUsuario,
                    U.nomeUsuario,
                    U.caminhoImagem,
                    D.valor,
                    D.dataCriacao
                FROM Doacao D
                JOIN Usuario U ON D.idUsuario = U.idUsuario
                WHERE D.idCampanha = :idCampanha AND D.status = 'completed'
                ORDER BY D.dataCriacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idCampanha' => $idCampanha]);
        $doadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($doadores as &$doador) {
            if (!empty($doador['caminhoImagem'])) {
                $doador['caminhoImagem'] = GoogleCloudStorageService::getSignedUrl($doador['caminhoImagem']);
            }
        }

        return $doadores;
    }

}