<?php

namespace api\controllers;

use models\social\Reacao;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\utils\Http;

class ReacaoController extends ControllerBase
{
    #[HttpPost('/reacao')]
    public function adicionarReacao(): void
    {
        $input = file_get_contents('php://input');
        $dados = json_decode($input, true);

        if (!$dados || !isset($dados['id_alvo']) || !isset($dados['tipo_alvo']) || !isset($dados['emoji'])) {
            Http::HttpResponse(400, "Dados da reação inválidos.");
            return;
        }

        $reacao = new Reacao();
        $reacao->id_alvo = $dados['id_alvo'];
        $reacao->tipo_alvo = $dados['tipo_alvo'];
        $reacao->emoji = $dados['emoji'];
        $reacao->id_usuario = parent::$usuarioAutenticado->idUsuario;

        if (Reacao::salvar($reacao)) {
            Http::HttpResponse(201, "Reação salva com sucesso.");
        } else {
            Http::HttpResponse(409, "O usuário já reagiu com este emoji a este item.");
        }
    }

    #[HttpDelete('/reacao')]
    public function removerReacao(): void
    {
        $id_alvo = $_GET['id_alvo'] ?? null;
        $tipo_alvo = $_GET['tipo_alvo'] ?? null;
        $emoji = $_GET['emoji'] ?? null;
        $id_usuario = parent::$usuarioAutenticado->idUsuario;

        if (!$id_alvo || !$tipo_alvo || !$emoji || !$id_usuario) {
            Http::HttpResponse(400, "Parâmetros 'id_alvo', 'tipo_alvo', 'emoji' e 'id_usuario' são obrigatórios.");
            return;
        }

        if (Reacao::remover((int)$id_alvo, $tipo_alvo, $id_usuario, $emoji)) {
            Http::HttpResponse(200, "Reação removida com sucesso.");
        } else {
            Http::HttpResponse(404, "Reação não encontrada ou não pôde ser removida.");
        }
    }

    #[HttpGet('/reacoes', auth: false)]
    public function buscarReacoes(): void
    {
        $id_alvo = $_GET['id_alvo'] ?? null;
        $tipo_alvo = $_GET['tipo_alvo'] ?? null;
        $id_usuario_logado = parent::$usuarioAutenticado->idUsuario ?? 0;

        if (!$id_alvo || !$tipo_alvo) {
            Http::HttpResponse(400, "Parâmetros 'id_alvo' e 'tipo_alvo' são obrigatórios.");
            return;
        }

        $reacoes = Reacao::buscarPorAlvo((int)$id_alvo, $tipo_alvo, $id_usuario_logado);
        
        Http::HttpResponse(200, 'Reações buscadas com sucesso.', $reacoes);
    }
}
