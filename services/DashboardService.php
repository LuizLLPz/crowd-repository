<?php

namespace services;

use repositories\CampanhaRepository;
use repositories\DoacaoRepository;

class DashboardService {
    private CampanhaRepository $campanhaRepository;
    private DoacaoRepository $doacaoRepository;

    public function __construct() {
        $this->campanhaRepository = new CampanhaRepository();
        $this->doacaoRepository = new DoacaoRepository();
    }

    public function getDashboardData($idCampanha) {
        $kpis = $this->getKpis($idCampanha);
        $arrecadacaoPorDia = $this->doacaoRepository->getArrecadacaoPorDia($idCampanha);
        $apoiadoresPorDia = $this->doacaoRepository->getApoiadoresPorDia($idCampanha);
        $distribuicaoRecompensas = $this->doacaoRepository->getDistribuicaoRecompensas($idCampanha);
        $listaApoiadores = $this->doacaoRepository->getListaApoiadores($idCampanha);

        return [
            'kpis' => $kpis,
            'arrecadacaoPorDia' => $arrecadacaoPorDia,
            'apoiadoresPorDia' => $apoiadoresPorDia,
            'distribuicaoRecompensas' => $distribuicaoRecompensas,
            'listaApoiadores' => $listaApoiadores,
        ];
    }

    private function getKpis($idCampanha) {
        $campanha = $this->campanhaRepository->obter_campanha($idCampanha);
        $doacoes = $this->doacaoRepository->getDoacoesByCampanha($idCampanha);

        $totalArrecadado = array_sum(array_column($doacoes, 'valor'));
        $totalApoiadores = count($doacoes);
        $doacaoMedia = $totalApoiadores > 0 ? $totalArrecadado / $totalApoiadores : 0;

        $diasAtivos = 0;
        if ($campanha && $campanha->dataCriacao) {
            $dataCriacao = new \DateTime($campanha->dataCriacao);
            $hoje = new \DateTime();
            $diasAtivos = $dataCriacao->diff($hoje)->days;
        }

        $totalDias = 30;
        if ($campanha && $campanha->dataCriacao && $campanha->dataFinal) {
            $dataCriacao = new \DateTime($campanha->dataCriacao);
            $dataFinal = new \DateTime($campanha->dataFinal);
            $totalDias = $dataCriacao->diff($dataFinal)->days;
        }

        return [
            'totalArrecadado' => $totalArrecadado,
            'meta' => $campanha ? $campanha->metaArrecadacao : 0,
            'totalApoiadores' => $totalApoiadores,
            'doacaoMedia' => $doacaoMedia,
            'diasAtivos' => $diasAtivos,
            'totalDias' => $totalDias,
        ];
    }
}
