<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: FR-009 / IR-001 / ARCH-008*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-012 | BR-PROC-005 | UAT-PROC-010

 * ProcessTransactionButtonRow - wraps a ProcessTransaction submit into a HtmlLabelRow
 * 
 * @package Views
 * @since 20251019
 */
class ProcessTransactionButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-012 | BR-PROC-005 | UAT-PROC-010
     * Create process transaction button row for given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new SubmitButton("ProcessTransaction[$index]", _("Process"));
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
