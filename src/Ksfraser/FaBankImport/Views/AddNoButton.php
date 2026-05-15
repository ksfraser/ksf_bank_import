<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

class AddNoButton implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $submit;

    public function __construct(int $index)
    {
        /**
         * Composition chosen intentionally: this class configures and wraps
         * a `HtmlSubmit` element rather than extending it. See project
         * docs for rationale.
         */
        $this->submit = new HtmlSubmit(new HtmlString(_("There is nothing to add")));
        $this->submit->setName("AddNo[$index]")->setClass('default');
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