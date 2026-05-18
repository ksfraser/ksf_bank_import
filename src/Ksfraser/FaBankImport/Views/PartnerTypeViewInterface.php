<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Partner Type View Interface
 * 
 * Contract for partner type view classes.
 * Each implementation renders the UI for selecting a specific partner type
 * (supplier, customer, bank transfer, quick entry).
 * 
 * @package Ksfraser\FaBankImport\Views
 * @since 20251019
 */
interface PartnerTypeViewInterface extends HtmlElementInterface
{
    /**
     * Get the line item ID this view is associated with
     * 
     * @return int
     */
    public function getLineItemId(): int;
    
    /**
     * Get the selected partner ID after matching/selection
     * 
     * @return int|null
     */
    public function getSelectedPartnerId(): ?int;
    
    /**
     * Get the selected partner detail ID (e.g., branch code)
     * 
     * @return int|null
     */
    public function getSelectedPartnerDetailId(): ?int;
}