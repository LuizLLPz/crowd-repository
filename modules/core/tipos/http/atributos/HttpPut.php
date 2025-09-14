<?php
namespace modules\core\tipos\Http\atributos;

use Attribute;
use modules\core\tipos\http\tipos\FuncaoUsuario;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPut
{
    public function __construct(public string $path, public bool $auth = true, public ?FuncaoUsuario $funcaoUsuario = FuncaoUsuario::USER)
    {
    }
}
