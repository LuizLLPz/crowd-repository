<?php
namespace modules\core\tipos\Http\atributos;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpGet
{
    public function __construct(public string $path, public bool $auth = true)
    {
    }
}
