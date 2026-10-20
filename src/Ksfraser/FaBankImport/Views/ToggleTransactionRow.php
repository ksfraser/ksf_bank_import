<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: ToggleTransactionRow — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/ToggleTransactionRow.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
