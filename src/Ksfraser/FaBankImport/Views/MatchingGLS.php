<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK CLASS ROLLUP: MatchingGLS — 6 methods; FR-009 (6); IR-001 (6)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/MatchingGLS.php; coverage: docs/TestCases/ (file rollup)
 * MatchingGLS - Display matching general ledger transactions
 * 
 * @package Views
 * @since 20251019
 */
class MatchingGLS implements HtmlElementInterface
{
    /**
     * @var HTML_ROW_LABEL
     */
    protected $row;

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Create matching GLs display
     * 
     * @param object $bi_lineitem The bank import line item
     */
    public function __construct($bi_lineitem)
    {
        $data = new MatchingGLFactory($bi_lineitem);
        $label = "Matching GLs:";
        $this->row = new HTML_ROW_LABEL($data, $label, null, null);
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

/**
 * MatchingGLFactory - Factory for matching GL display data
 * 
 * @package Views
 * @since 20251019
 */
class MatchingGLFactory implements HtmlElementInterface
{
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Create matching GL factory
     * 
     * @param object $bi_lineitem The bank import line item
     */
    public function __construct($bi_lineitem)
    {
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
     * Get HTML as string
     * 
     * @return string Empty string for now
     */
    public function getHtml(): string
    {
        return '';
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
