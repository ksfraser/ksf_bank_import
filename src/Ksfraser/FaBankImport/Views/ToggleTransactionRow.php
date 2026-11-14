<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001

 * ToggleTransactionRow - composes a ToggleTransactionTypeButton into
 * a HtmlLabelRow for use in legacy views.
 * 
 * @package Views
 * @since 20251019
 */
class ToggleTransactionRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
     * Create toggle transaction row for given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new ToggleTransactionTypeButton($index);
        $label = new HtmlString("Toggle Transaction Type Debit/Credit");
        $this->row = new HtmlLabelRow($label, $button);
    }

    /**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
