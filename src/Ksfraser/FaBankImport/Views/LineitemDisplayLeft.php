<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_TABLE;

/**
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
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->table->toHtml();
    }
    
    /**
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->table->getHtml();
    }
}