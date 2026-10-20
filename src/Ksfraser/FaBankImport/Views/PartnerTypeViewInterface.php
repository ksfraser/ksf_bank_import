<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: PartnerTypeViewInterface — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/PartnerTypeViewInterface.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getLineItemId
     * Get the line item ID this view is associated with
     * 
     * @return int
     */
    public function getLineItemId(): int;
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getSelectedPartnerId
     * Get the selected partner ID after matching/selection
     * 
     * @return int|null
     */
    public function getSelectedPartnerId(): ?int;
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getSelectedPartnerDetailId
     * Get the selected partner detail ID (e.g., branch code)
     * 
     * @return int|null
     */
    public function getSelectedPartnerDetailId(): ?int;
}
