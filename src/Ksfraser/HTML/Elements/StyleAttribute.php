<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * StyleAttribute represents an inline style attribute for HTML elements.
 *
 * A Style is an attribute with KEY Style and value param:setting
 *
 *   https://www.w3schools.com/html/html_styles.asp
 *
 *   Examples:
 *     background-color
 *     color
 *     font-family
 *     font-size
 *     text-align
 *
 *   This is INLINE CSS. There is also Internal CSS (see HtmlStyle).
 */
class StyleAttribute extends HtmlAttribute
{
    public function __construct(string $value)
    {
        parent::__construct('style', $value);
    }
}
