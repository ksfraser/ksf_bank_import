<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_TABLE;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001

 * LineitemDisplayLeft - Left side display for line items
 * 
 * Composes a table with transaction details: date, type, accounts, amount, title
 * 
 * @package Views
 * @since 20251019 - Added PHPDoc, use statements, return type hints
 */
class LineitemDisplayLeft implements HtmlElementInterface
{
    /**
     * @var HTML_TABLE
     */
    protected $table;
    
    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     * Create left display panel
     * 
     * @param object $bi_lineitem The bank import line item data
     */
    public function __construct($bi_lineitem)
    {
        $this->table = $table = new HTML_TABLE(null, 100);
        $table->appendRow(new \Ksfraser\FaBankImport\Views\TransDate($bi_lineitem));
        $table->appendRow(new \Ksfraser\FaBankImport\Views\TransType($bi_lineitem));
        $table->appendRow(new \Ksfraser\FaBankImport\Views\OurBankAccount($bi_lineitem));
        $table->appendRow(new \Ksfraser\FaBankImport\Views\OtherBankAccount($bi_lineitem));
        $table->appendRow(new \Ksfraser\FaBankImport\Views\AmountCharges($bi_lineitem));
        $table->appendRow(new \Ksfraser\FaBankImport\Views\TransTitle($bi_lineitem));
    }
    
    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->table->toHtml();
    }
    
    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->table->getHtml();
    }
}
