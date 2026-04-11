<?php

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * SubmitButtonRow - composes a SubmitButton into a HtmlLabelRow.
 */
class SubmitButtonRow implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $row;

    public function __construct(string $name, string $label)
    {
        $button = new SubmitButton($name, $label);
        $labelElem = new HtmlString('');
        $this->row = new HtmlLabelRow($labelElem, $button);
    }

    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
