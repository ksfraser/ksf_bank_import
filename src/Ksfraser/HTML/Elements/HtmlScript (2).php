<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlScriptLanguage;

/**
 * Abstract HtmlScript - Template for script tag subclasses
 * 
 * Provides common logic for script tags. Subclasses should specify script type and content.
 * 
 * @package Ksfraser\HTML
 */
abstract class HtmlScript extends HtmlElement
{
    /**
     * Protected constructor for script tag subclasses
     */
    protected function __construct()
    {
        parent::__construct();
        $this->tag = 'script';
    }
}
