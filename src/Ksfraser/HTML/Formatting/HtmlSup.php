<?php

namespace Ksfraser\HTML\Formatting;

class HtmlSup extends HtmlFormatting
{
    function __construct( $data )
    {
        parent::__construct( $data );
        $this->tag = "sup";
    }
}
