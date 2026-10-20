<?php
// @BABOK RTM: FR-008 / DR-001 / ARCH-010 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Entity;

/**
 * @BABOK RTM: FR-008 / DR-001 / ARCH-010; file: Entity/PartnerType.php; coverage: docs/TestCases/ (file rollup)
 * Partner Type Enumeration
 * 
 * Defines the types of partners (suppliers, customers, bank accounts, quick entries)
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
enum PartnerType: string
{
    case SUPPLIER = 'supplier';
    case CUSTOMER = 'customer';
    case BANK_TRANSFER = 'bank_transfer';
    case QUICK_ENTRY = 'quick_entry';
}
