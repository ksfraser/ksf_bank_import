<?php

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * ProcessTransactionButtonRow - wraps a ProcessTransaction submit into a HtmlLabelRow
 */
class ProcessTransactionButtonRow implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $row;

    public function __construct(int $index)
    {
        $button = new SubmitButton("ProcessTransaction[$index]", _("Process"));
        $label = new HtmlString('');
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
