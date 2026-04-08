<?php

namespace Ksfraser\HTML\Formatting;

class HtmlSub extends HtmlFormatting
{
    function __construct( $data )
    {
        parent::__construct( $data );
        $this->tag = "sub";
    }
}
