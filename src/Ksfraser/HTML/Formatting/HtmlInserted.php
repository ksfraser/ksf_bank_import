<?php

namespace Ksfraser\HTML\Formatting;

class HtmlInserted extends HtmlFormatting
{
    function __construct( $data )
    {
        parent::__construct( $data );
        $this->tag = "ins";
    }
}
