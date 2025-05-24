<?php
namespace modules\core\atributos;
use Attribute;
#[Attribute(Attribute::TARGET_METHOD)]
class HttpPost
{
    public function __construct(public string $path, public bool $auth = true)
    {
    }
}
