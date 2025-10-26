<?php

namespace models\campanha;

use modules\core\tipos\Entidade;

class CampanhaIntegracaoGit extends Entidade
{
    public string $nomeTabela = "CampanhaIntegracaoGit";
    public int $id = 0;
    public int $idCampanha = 0;
    public string $plataforma = '';
    public string $urlRepositorio = '';
    public string $tokenAcesso = '';
    public string $dataCriacao;
    public string $dataAtualizacao;
}
