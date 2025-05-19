<?php
namespace modules\core\atributos;
use Attribute;
#[Attribute(Attribute::TARGET_METHOD)]
class HttpGet
{
    public function __construct(public string $path)
    {
    }
}
