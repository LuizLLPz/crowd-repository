<?php

namespace modules\core\tipos\http\tipos;

use modules\core\tipos\LinkRel;

class Link
{
    public LinkRel | string $rel;
    public string $href;
    public ?string $title;

    public function __construct(LinkRel | string $rel, string $href, ?string $title = null) {
        $this->rel = $rel;
        if (!filter_var($href, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Formato de url inválido para href " . $href);
        }
        $this->href = $href;
        $this->title = $title;
    }
}