<?php
namespace modules\core\tipos\http\atributos;
use Attribute;
use modules\core\tipos\http\tipos\FuncaoUsuario;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPost
{
    public function __construct(public string $path, public bool $auth = true, public ?FuncaoUsuario $funcaoUsuario = FuncaoUsuario::USER)
    {
    }
}
