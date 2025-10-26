<?php

namespace models\core;

use modules\core\tipos\Entidade;
use modules\core\validacoes\Token;
use modules\db\Database;
use PDO;
use Throwable;

class Excecao extends Entidade
{
    public string $nomeTabela = "Excecoes";

    public ?int $id = null;
    public ?string $mensagem = null;
    public ?string $arquivo = null;
    public ?int $linha = null;
    public ?string $stack_trace = null;
    public ?string $contexto = null;
    public ?int $id_usuario_logado = null;
    public ?string $rota_requisitada = null;
    public ?string $metodo_http = null;
    public ?string $data_ocorrencia = null;
    public ?string $status = 'NOVA';
    public ?string $tipo = 'AUTOMATICA';
    public ?string $passos = null;

    public static function salvar(Throwable $e): void
    {
        try {
            $excecao = new self();
            $excecao->mensagem = $e->getMessage();
            $excecao->arquivo = $e->getFile();
            $excecao->linha = $e->getLine();
            $excecao->stack_trace = $e->getTraceAsString();
            $excecao->contexto = json_encode([
                'GET' => $_GET,
                'POST' => $_POST,
                'SERVER' => $_SERVER,
                'BODY' => file_get_contents('php://input')
            ]);

            $resultadoToken = Token::validarToken();
            if ($resultadoToken) {
                $excecao->id_usuario_logado = $resultadoToken->idUsuario;
            }

            $excecao->rota_requisitada = $_SERVER['REQUEST_URI'] ?? null;
            $excecao->metodo_http = $_SERVER['REQUEST_METHOD'] ?? null;

            $pdo = Database::getConnection();
            $sql = "INSERT INTO Excecoes (mensagem, arquivo, linha, stack_trace, contexto, id_usuario_logado, rota_requisitada, metodo_http, tipo, status) 
                    VALUES (:mensagem, :arquivo, :linha, :stack_trace, :contexto, :id_usuario_logado, :rota_requisitada, :metodo_http, :tipo, :status)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':mensagem', $excecao->mensagem);
            $stmt->bindValue(':arquivo', $excecao->arquivo);
            $stmt->bindValue(':linha', $excecao->linha, PDO::PARAM_INT);
            $stmt->bindValue(':stack_trace', $excecao->stack_trace);
            $stmt->bindValue(':contexto', $excecao->contexto);
            $stmt->bindValue(':id_usuario_logado', $excecao->id_usuario_logado);
            $stmt->bindValue(':rota_requisitada', $excecao->rota_requisitada);
            $stmt->bindValue(':metodo_http', $excecao->metodo_http);
            $stmt->bindValue(':tipo', 'AUTOMATICA');
            $stmt->bindValue(':status', 'NOVA');
            $stmt->execute();

        } catch (Throwable $dbError) {
            error_log("Falha ao salvar exceção no banco: " . $dbError->getMessage());
        }
    }

    public static function buscar_excecoes(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT e.id, e.mensagem, e.stack_trace as stackTrace, e.data_ocorrencia as dataOcorrencia, u.nomeUsuario, e.id_usuario_logado as idUsuario, e.status, e.tipo, e.passos, e.justificativa, ur.nomeUsuario as nomeUsuarioResolveu, e.contexto FROM Excecoes e LEFT JOIN Usuario u ON e.id_usuario_logado = u.idUsuario LEFT JOIN Usuario ur ON e.id_usuario_resolveu = ur.idUsuario ORDER BY e.data_ocorrencia DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function salvarManual(): void
    {
        try {
            $this->tipo = $this->tipo ?: 'MANUAL';
            $this->status = $this->status ?: 'NOVA';
            $this->data_ocorrencia = $this->data_ocorrencia ?: date('Y-m-d H:i:s');

            $pdo = Database::getConnection();
            $sql = "INSERT INTO Excecoes (mensagem, passos, id_usuario_logado, tipo, status, data_ocorrencia, contexto)
                    VALUES (:mensagem, :passos, :id_usuario_logado, :tipo, :status, :data_ocorrencia, :contexto)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':mensagem', $this->mensagem);
            $stmt->bindValue(':passos', $this->passos);
            $stmt->bindValue(':id_usuario_logado', $this->id_usuario_logado);
            $stmt->bindValue(':tipo', $this->tipo);
            $stmt->bindValue(':status', $this->status);
            $stmt->bindValue(':data_ocorrencia', $this->data_ocorrencia);
            $stmt->bindValue(':contexto', $this->contexto);
            $stmt->execute();
            $this->id = $pdo->lastInsertId();

        } catch (Throwable $dbError) {
            error_log("Falha ao salvar exceção manual no banco: " . $dbError->getMessage());
            throw $dbError;
        }
    }

    public static function updateStatus(int $id, string $status, ?string $justificativa = null): void
    {
        $pdo = Database::getConnection();
        $idUsuario = Token::validarToken()->idUsuario;

        $sql = "UPDATE Excecoes SET status = :status";
        $params = [':status' => $status, ':id' => $id];

        if ($status === 'Resolvida') {
            $sql .= ", justificativa = :justificativa, id_usuario_resolveu = :id_usuario_resolveu";
            $params[':justificativa'] = $justificativa;
            $params[':id_usuario_resolveu'] = $idUsuario;
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public static function agrupar(array $ids): void
    {
        if (empty($ids)) {
            throw new \InvalidArgumentException("Nenhum ID de exceção fornecido.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT id, mensagem, stack_trace, passos, data_ocorrencia, tipo FROM Excecoes WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $exceptionsToGroup = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $latestDate = '0000-00-00 00:00:00';
            foreach ($exceptionsToGroup as $ex) {
                if ($ex['data_ocorrencia'] > $latestDate) {
                    $latestDate = $ex['data_ocorrencia'];
                }
            }

            $firstMessage = $exceptionsToGroup[0]['mensagem'];
            $allSameMessage = true;
            foreach ($exceptionsToGroup as $ex) {
                if ($ex['mensagem'] !== $firstMessage) {
                    $allSameMessage = false;
                    break;
                }
            }

            $newMessage = $allSameMessage ? "Grupo de " . count($ids) . " ocorrências de: " . $firstMessage 
                                        : "Grupo de " . count($ids) . " exceções com mensagens diversas.";

            $newException = new self();
            $newException->mensagem = $newMessage;
            $newException->passos = "Este é um registro de exceções agrupadas. Os detalhes estão no contexto.";
            $newException->tipo = 'AGRUPADA';
            $newException->status = 'Nova';
            $newException->id_usuario_logado = Token::validarToken()->idUsuario;
            $newException->contexto = json_encode($exceptionsToGroup);
            $newException->data_ocorrencia = $latestDate;
            $newException->salvarManual();

            $deleteSql = "DELETE FROM Excecoes WHERE id IN ($placeholders)";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute($ids);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("Falha ao agrupar exceções: " . $e->getMessage());
            throw $e;
        }
    }
}
