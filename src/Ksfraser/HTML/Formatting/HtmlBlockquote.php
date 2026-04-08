<?php

namespace Ksfraser\HTML\Formatting;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlBlockquote - semantic wrapper for <blockquote>
 */
class HtmlBlockquote extends HtmlElement {
    public function __construct(HtmlElementInterface $content) {
        parent::__construct($content);
        $this->setTag('blockquote');
    }
}
