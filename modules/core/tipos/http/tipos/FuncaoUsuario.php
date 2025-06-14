<?php

namespace modules\core\tipos\http\tipos;

enum FuncaoUsuario: string
{
    case USER = 'user';
    case ADMIN = 'admin';
}