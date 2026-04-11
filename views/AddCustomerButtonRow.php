<?php

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;

class AddCustomerButtonRow implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $row;

    public function __construct(int $index)
    {
        $button = new AddCustomerButton($index);
        $labelText = new HtmlString("Add Customer");
        $this->row = new HtmlLabelRow($labelText, $button);
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
