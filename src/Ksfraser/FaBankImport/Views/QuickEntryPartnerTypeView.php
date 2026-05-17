<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Quick Entry Partner Type View
 * 
 * Single Responsibility: Display quick entry selection UI for a bank transaction line item.
 * 
 * Displays:
 * - "Quick Entry:" label
 * - Quick entry dropdown list
 * 
 * @author Kevin Fraser
 * @since 20250422
 */
class QuickEntryPartnerTypeView implements HtmlElementInterface
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
        
        // Display quick entry selection
        label_row(
            _("Quick Entry:"),
            quick_entries_list("partnerId_{$this->lineItemId}")
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