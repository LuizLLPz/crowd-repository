<?php

namespace models\campanha\enums;

enum TipoAlvo: string
{
    case CAMPANHA = 'Campanha';
    case USUARIO = 'Usuario';
    case NOVIDADE = 'Novidade';
    case COMENTARIO = 'Comentario';
    case MENSAGEM = 'Mensagem';
}