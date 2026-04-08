<?php

namespace Ksfraser\HTML\Formatting;

class HtmlItalic extends HtmlFormatting
{
    function __construct( $data )
    {
        parent::__construct( $data );
        $this->tag = "i";
    }
}
