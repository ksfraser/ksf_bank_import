<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Supplier Partner Type View
 * 
 * Single Responsibility: Display supplier selection UI for a bank transaction line item.
 * 
 * Displays:
 * - "Payment To:" label
 * - Supplier dropdown list
 * 
 * @author Kevin Fraser
 * @since 20250422
 */
class SupplierPartnerTypeView implements HtmlElementInterface
{
    private $lineItemId;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     */
    public function __construct(int $lineItemId)
    {
        $this->lineItemId = $lineItemId;
    }
    
    /**
     * Get the HTML for this view
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        ob_start();
        
        // Display supplier selection
        label_row(
            _("Payment To:"),
            supplier_list("partnerId_{$this->lineItemId}")
        );
        
        return ob_get_clean();
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}