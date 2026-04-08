<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\VBScript\HtmlVBScriptString;

/**
 * HtmlScriptVBScript - Strict VBScript <script> tag
 * Accepts only HtmlVBScriptString content, sets type="text/vbscript".
 */
class HtmlScriptVBScript extends HtmlScript
{
    public function __construct(HtmlVBScriptString $content)
    {
        parent::__construct('text/vbscript', $content);
    }
}
