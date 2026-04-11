<?php

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * UnsetTransButtonRow - wraps an UnsetTrans submit into a HtmlLabelRow
 */
class UnsetTransButtonRow implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $row;

    public function __construct(int $index, int $fa_trans_no)
    {
        $labelLeft = new HtmlString("Unset Transaction Association");
        $button = new SubmitButton("UnsetTrans[$index]", _("Unset Transaction $fa_trans_no"));
        $this->row = new HtmlLabelRow($labelLeft, $button);
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
