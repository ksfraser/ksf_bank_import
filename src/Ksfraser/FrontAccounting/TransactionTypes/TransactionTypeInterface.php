<?php

/**
 * Transaction Type Interface
 * 
 * Defines contract for FrontAccounting transaction type plugins.
 * Each transaction type has a numeric code, human-readable label,
 * and metadata flags describing what the transaction affects.
 * 
 * @package    Ksfraser\FrontAccounting
 * @subpackage TransactionTypes
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      1.0.0
 */

namespace Ksfraser\FrontAccounting\TransactionTypes;

/**
 * Interface for transaction type definitions
 * 
 * Transaction types represent different kinds of GL entries in FrontAccounting:
 * - Banking transactions (payments, deposits, transfers)
 * - Customer transactions (invoices, credits, payments)
 * - Supplier transactions (invoices, credits, payments)
 * - Inventory transactions (adjustments, transfers, receiving)
 * - Journal entries
 * 
 * Each type has metadata flags:
 * - moneyMoved: true if transaction involves bank accounts
 * - goodsMoved: true if transaction involves inventory
 * - affectsAR: true if transaction affects accounts receivable
 * - affectsAP: true if transaction affects accounts payable
 * 
 * Example implementation:
 * <code>
 * class BankPaymentTransactionType implements TransactionTypeInterface
 * {
 *     public function getCode(): int { return ST_BANKPAYMENT; }
 *     public function getLabel(): string { return _("Bank Payment"); }
 *     public function hasMoneyMoved(): bool { return true; }
 *     public function hasGoodsMoved(): bool { return false; }
 *     public function affectsAR(): bool { return false; }
 *     public function affectsAP(): bool { return false; }
 * }
 * </code>
 * 
 * @since 1.0.0
 */
interface TransactionTypeInterface
{
    /**
     * Get the ST_ constant numeric code
     * 
     * @return int Transaction type code (e.g., 0 for ST_JOURNAL, 1 for ST_BANKPAYMENT)
     * 
     * @since 1.0.0
     */
    public function getCode(): int;
    
    /**
     * Get human-readable label
     * 
     * Should use _() for translation support.
     * 
     * @return string Human-readable label (e.g., "Bank Payment")
     * 
     * @since 1.0.0
     */
    public function getLabel(): string;
    
    /**
     * Does this transaction involve money movement?
     * 
     * True for banking transactions (payments, deposits, transfers)
     * that affect bank account balances.
     * 
     * @return bool True if money moves
     * 
     * @since 1.0.0
     */
    public function hasMoneyMoved(): bool;
    
    /**
     * Does this transaction involve goods/inventory movement?
     * 
     * True for inventory transactions (adjustments, transfers, receiving)
     * that affect stock levels.
     * 
     * @return bool True if goods move
     * 
     * @since 1.0.0
     */
    public function hasGoodsMoved(): bool;
    
    /**
     * Does this transaction affect Accounts Receivable?
     * 
     * True for customer-related transactions (invoices, credits, payments).
     * 
     * @return bool True if affects AR
     * 
     * @since 1.0.0
     */
    public function affectsAR(): bool;
    
    /**
     * Does this transaction affect Accounts Payable?
     * 
     * True for supplier-related transactions (invoices, credits, payments).
     * 
     * @return bool True if affects AP
     * 
     * @since 1.0.0
     */
    public function affectsAP(): bool;
}
