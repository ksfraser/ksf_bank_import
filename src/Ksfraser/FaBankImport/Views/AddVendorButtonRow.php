<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: FR-009 / IR-001 / ARCH-008*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013

 * AddVendorButtonRow - composes an AddVendorButton into a HtmlLabelRow.
 * 
 * @package Views
 * @since 20251019
 */
class AddVendorButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
     * Create add vendor button row for given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new AddVendorButton($index);
        $labelText = new HtmlString("Add Vendor");
        $this->row = new HtmlLabelRow($labelText, $button);
    }

    /**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
