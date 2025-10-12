<?php

namespace api\controllers;

use models\core\Evento;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\utils\Http;
use services\core\EventoService;

class EventoController extends ControllerBase
{
    #[HttpGet('/eventos')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $resp = EventoService::listar($pesquisa);
        Http::HttpResponse(200, 'Eventos listados com sucesso', $resp);
    }

    #[HttpGet('/evento')]
    public function obterPorId(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            Http::HttpResponse(400, 'ID do evento não fornecido.');
            return;
        }
        $evento = EventoService::obterPorId((int)$id);
        if ($evento) {
            Http::HttpResponse(200, 'Evento obtido com sucesso', $evento);
        } else {
            Http::HttpResponse(404, 'Evento não encontrado');
        }
    }

    #[HttpPost('/eventos')]
    public function criar(Evento $evento): void
    {
        $novoId = EventoService::criar($evento);
        $novoEvento = EventoService::obterPorId($novoId);
        Http::HttpResponse(201, 'Evento criado com sucesso', $novoEvento);
    }

    #[HttpPut('/evento')]
    public function atualizar(Evento $evento): void
    {
        $sucesso = EventoService::atualizar($evento);
        if ($sucesso) {
            $eventoAtualizado = EventoService::obterPorId($evento->id);
            Http::HttpResponse(200, 'Evento atualizado com sucesso', $eventoAtualizado);
        } else {
            Http::HttpResponse(500, 'Falha ao atualizar o evento');
        }
    }

    #[HttpDelete('/evento')]
    public function deletar(Evento $evento): void
    {
        $sucesso = EventoService::deletar($evento->id);
        if ($sucesso) {
            Http::HttpResponse(204, 'Evento deletado com sucesso');
        } else {
            Http::HttpResponse(500, 'Falha ao deletar o evento');
        }
    }
}
