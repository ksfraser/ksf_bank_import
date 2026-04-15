<?php

namespace Ksfraser\FaBankImport\Entity;

/**
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
