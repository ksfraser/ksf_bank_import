<?php

namespace Ksfraser\HTML\Formatting;

class HtmlDeleted extends HtmlFormatting
{
    function __construct( $data )
    {
        parent::__construct( $data );
        $this->tag = "del";
    }
}
