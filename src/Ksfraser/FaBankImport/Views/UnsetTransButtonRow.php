<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: UnsetTransButtonRow — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/UnsetTransButtonRow.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
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
