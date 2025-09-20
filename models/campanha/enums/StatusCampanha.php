<?php
namespace models\campanha\enums;

enum StatusCampanha: int
{
    case ATIVA = 1;
    case CANCELADA = 2;
    case EM_APROVACAO = 3;
    case REPROVADA = 4;
    case ENCERRADA = 5;
    case DESATIVADA = 6;
}
