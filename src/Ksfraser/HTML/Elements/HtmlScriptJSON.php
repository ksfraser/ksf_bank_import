<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Json\HtmlJsonString;

/**
 * HtmlScriptJSON - Strict JSON <script> tag
 * Accepts only HtmlJsonString content, sets type="application/json".
 */
class HtmlScriptJSON extends HtmlScript
{
    public function __construct(HtmlJsonString $content)
    {
        parent::__construct('application/json', $content);
    }
}
