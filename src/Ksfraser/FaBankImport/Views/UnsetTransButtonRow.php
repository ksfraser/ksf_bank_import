<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010

 * UnsetTransButtonRow - wraps an UnsetTrans submit into a HtmlLabelRow
 * 
 * @package Views
 * @since 20251019
 */
class UnsetTransButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Create unset transaction button row for given index and transaction number
     * 
     * @param int $index The transaction index
     * @param int $fa_trans_no The FA transaction number
     */
    public function __construct(int $index, int $fa_trans_no)
    {
        $labelLeft = new HtmlString("Unset Transaction Association");
        $button = new SubmitButton("UnsetTrans[$index]", _("Unset Transaction $fa_trans_no"));
        $this->row = new HtmlLabelRow($labelLeft, $button);
    }

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
