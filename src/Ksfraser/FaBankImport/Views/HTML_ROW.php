<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/HTML_ROW.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Create table row with given data
     * 
     * @param string $data The cell content
     */
    public function __construct(string $data)
    {
        $this->row = new HtmlTableRow(new HtmlString($data));
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
}
