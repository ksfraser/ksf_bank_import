<?php

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * ToggleTransactionTypeButton - small adapter that builds a HtmlSubmit
 * element for toggling transaction debit/credit.
 */
class ToggleTransactionTypeButton implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $submit;

    public function __construct(int $index)
    {
        $this->submit = new HtmlSubmit(new HtmlString(_("ToggleTransaction")));
        $this->submit->setName("ToggleTransaction[$index]")->setClass('default');
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
