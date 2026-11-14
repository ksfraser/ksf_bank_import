<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / UAT-001 / BR-009 / FR-001*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006

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
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
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
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
 */
class MatchingGLFactory implements HtmlElementInterface
{
    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Create matching GL factory
     * 
     * @param object $bi_lineitem The bank import line item
     */
    public function __construct($bi_lineitem)
    {
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get HTML as string
     * 
     * @return string Empty string for now
     */
    public function getHtml(): string
    {
        return '';
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
