<?php

namespace Ksfraser\HTML\Formatting;

class HtmlStrong extends HtmlFormatting
{
    function __construct( $data )
    {
        parent::__construct( $data );
        $this->tag = "strong";
    }
}
