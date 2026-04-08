<?php
namespace Ksfraser\HTML\Button;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\JS\HtmlJsEventTrait;

/**
 * HtmlButton - HTML <button> element abstraction
 * @since 20250517
 */
class HtmlButton extends HtmlElement {
    use \Ksfraser\HTML\JS\HtmlJsEventTrait;

    /**
     * Constructor
     * @param HtmlElementInterface|null $data Optional content
     */
    public function __construct(HtmlElementInterface $data = null) {
        parent::__construct($data);
        $this->setTag('button');
    }
}
