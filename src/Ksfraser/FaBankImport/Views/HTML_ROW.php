<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: FR-009 / IR-001 / ARCH-008*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001

 * HTML_ROW - Wraps data in an HTML table row
 * 
 * @package Views
 * @since 20251019
 */
class HTML_ROW implements HtmlElementInterface
{
    /**
     * @var HtmlTableRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Create table row with given data
     * 
     * @param string $data The cell content
     */
    public function __construct(string $data)
    {
        $this->row = new HtmlTableRow(new HtmlString($data));
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }
}
