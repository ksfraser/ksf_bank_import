<?php

/**
 * Transaction Handler Interface
 *
 * Defines the contract for all transaction type handlers in the bank import system.
 * Each partner type (SP, CU, QE, BT, MA, ZZ) will have a dedicated handler implementing this interface.
 *
 * @package    Ksfraser\FaBankImport\Handlers
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Transaction Handler Interface
 *
 * Strategy pattern interface for processing different transaction types.
 * Enables SRP (Single Responsibility Principle) by isolating each transaction type's logic.
 *
 * Design Notes:
 * - $transactionPostData contains ONLY transaction-specific POST fields (extracted by TransactionProcessor)
 * - Fields include: partnerId, invoice, comment, partnerDetailId, etc.
 * - This decouples handlers from the full POST array structure
 * - Returns TransactionResult for type safety and display integration
 */
interface TransactionHandlerInterface
{
    /**
     * Process a transaction for the specific partner type
     *
     * @param array $transaction Transaction data from bank statement
     * @param array $transactionPostData Transaction-specific POST data (filtered, not entire $_POST)
     *                                   Expected keys: partnerId, invoice, comment, partnerDetailId
     * @param int $transactionId Database transaction ID
     * @param string $collectionIds Collection IDs
     * @param array $ourAccount Our bank account information
     * @return TransactionResult Processing result (success/error/warning with display integration)
     */
    public function process(
        array $transaction,
        array $transactionPostData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): TransactionResult;

    /**
     * Get the partner type short code this handler processes
     *
     * @return string Partner type code (SP, CU, QE, BT, MA, ZZ)
     */
    public function getPartnerType(): string;

    /**
     * Validate if this handler can process the given transaction
     *
     * Checks if the specified partner type matches this handler's type.
     *
     * @param string $partnerType The partner type code for this transaction
     * @return bool True if can process, false otherwise
     */
    public function canProcess(string $partnerType): bool;
}
