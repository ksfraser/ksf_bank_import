<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Javascript\HtmlJSString;

/**
 * HtmlScriptJS - Strict JavaScript <script> tag
 * Accepts only HtmlJSString content, sets type="text/javascript".
 */
class HtmlScriptJS extends HtmlScript
{
    public function __construct(HtmlJSString $content)
    {
        parent::__construct('text/javascript', $content);
    }
}
