<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001

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
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get the line item ID this view is associated with
     * 
     * @return int
     */
    public function getLineItemId(): int;
    
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get the selected partner ID after matching/selection
     * 
     * @return int|null
     */
    public function getSelectedPartnerId(): ?int;
    
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get the selected partner detail ID (e.g., branch code)
     * 
     * @return int|null
     */
    public function getSelectedPartnerDetailId(): ?int;
}
