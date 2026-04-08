<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlJSString
 *
 * Semantic wrapper for JavaScript code as an HTML element.
 * Extends HtmlString for clarity: use this when representing inline JS content.
 *
 * NOTE: HtmlString and HtmlJSString are functionally identical, but HtmlJSString is used for semantic clarity when representing JavaScript content in HTML elements (e.g., <script> tags).
 */
class HtmlJSString extends HtmlString implements HtmlElementInterface
{
    // No additional implementation; semantic distinction only.
}
