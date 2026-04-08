<?php

namespace Ksfraser\HTML\Formatting;

use Ksfraser\HTML\HtmlElement;

class HtmlFormatting extends HtmlElement
{
    public function __construct($content)
    {
        parent::__construct($content);
        $this->setTag('span');
    }
}
