<?php

namespace api\controllers;

use models\social\Cargo;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\utils\Http;
use services\social\CargoService;

class CargoController extends ControllerBase
{
    #[HttpGet('/cargos')]
    public function listar(): void
    {
        $pesquisa = $_GET['pesquisa'] ?? null;
        $resp = CargoService::listar($pesquisa);
        Http::HttpResponse(200, 'Cargos listados com sucesso', $resp);
    }

    #[HttpGet('/cargo')]
    public function obterPorId(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            Http::HttpResponse(400, 'ID do cargo não fornecido.');
            return;
        }
        $cargo = CargoService::obterPorId((int)$id);
        if ($cargo) {
            Http::HttpResponse(200, 'Cargo obtido com sucesso', $cargo);
        } else {
            Http::HttpResponse(404, 'Cargo não encontrado');
        }
    }

    #[HttpPost('/cargos')]
    public function criar(Cargo $cargo): void
    {
        $novoId = CargoService::criar($cargo);
        $novoCargo = CargoService::obterPorId($novoId);
        Http::HttpResponse(201, 'Cargo criado com sucesso', $novoCargo);
    }

    #[HttpPut('/cargo')]
    public function atualizar(Cargo $cargo): void
    {
        $sucesso = CargoService::atualizar($cargo);
        if ($sucesso) {
            $cargoAtualizado = CargoService::obterPorId($cargo->id);
            Http::HttpResponse(200, 'Cargo atualizado com sucesso', $cargoAtualizado);
        } else {
            Http::HttpResponse(500, 'Falha ao atualizar o cargo');
        }
    }

    #[HttpDelete('/cargo')]
    public function deletar(Cargo $cargo): void
    {
        $sucesso = CargoService::deletar($cargo->id);
        if ($sucesso) {
            Http::HttpResponse(204, 'Cargo deletado com sucesso');
        } else {
            Http::HttpResponse(500, 'Falha ao deletar o cargo');
        }
    }
}