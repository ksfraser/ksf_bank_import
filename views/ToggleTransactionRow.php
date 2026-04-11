<?php

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * ToggleTransactionRow - composes a ToggleTransactionTypeButton into
 * a HtmlLabelRow for use in legacy views.
 */
class ToggleTransactionRow implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $row;

    public function __construct(int $index)
    {
        $button = new ToggleTransactionTypeButton($index);
        $label = new HtmlString("Toggle Transaction Type Debit/Credit");
        $this->row = new HtmlLabelRow($label, $button);
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
