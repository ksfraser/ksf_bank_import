<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlBlock - semantic wrapper for <div>
 */
class HtmlBlock extends HtmlDiv {
    public function __construct(HtmlElementInterface $content) {
        parent::__construct($content);
    }
}
