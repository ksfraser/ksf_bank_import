<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-012 | BR-PROC-005 | UAT-PROC-010

 * ProcessBothSidesButtonRow - wraps a ProcessBothSides submit into a HtmlLabelRow
 *
 * Rendered on rows that have a detected paired bank transfer counterpart
 * (findPaired / isPaired). The POST action "ProcessBothSides" is handled in
 * process_statements.php by PairedTransferProcessor, which creates the FA
 * bank transfer for both sides at once - no separate Match step needed.
 *
 * @package Views
 * @since 20261114
 */
class ProcessBothSidesButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-012 | BR-PROC-005 | UAT-PROC-010
     * Create "Process Both Sides" button row for given transaction index
     *
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new \Ksfraser\HTML\Elements\HtmlSubmit(new HtmlString(_("Process Both Sides")));
        $button->setName("ProcessBothSides[$index]");
        $button->setClass("default");
        $label = new HtmlString('');
        $this->row = new HtmlLabelRow($label, $button);
    }

    /**
 * @BABOK FR-PROC-012 | BR-PROC-005 | UAT-PROC-010
     * Get HTML as string
     *
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-PROC-012 | BR-PROC-005 | UAT-PROC-010
     * Output HTML directly to screen
     *
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
