<?php

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * SubmitButton - generic adapter that builds a HtmlSubmit element.
 */
class SubmitButton implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $submit;

    public function __construct(string $name, string $label)
    {
        $this->submit = new HtmlSubmit(new HtmlString($label));
        $this->submit->setName($name)->setClass('default');
    }

    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
