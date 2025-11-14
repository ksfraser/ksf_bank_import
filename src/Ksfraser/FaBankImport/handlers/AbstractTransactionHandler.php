<?php

/**
 * Abstract Transaction Handler Base Class
 *
 * Provides common implementation for transaction handlers to eliminate duplication
 * across handler classes. Uses static PartnerType lookup for simplicity.
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

use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;

/**
 * Abstract Transaction Handler
 *
 * Template Method pattern implementation that provides common functionality:
 * - Partner type management (using static PartnerTypeConstants)
 * - Standard canProcess() implementation
 * - Shared utility methods
 *
 * Design:
 * - Uses constructor to eagerly initialize and validate partner type
 * - Calls static PartnerTypeConstants::getCodeByConstant() for centralized mapping
 * - Fails fast if subclass doesn't provide valid partner type
 *
 * Subclasses only need to:
 * 1. Define their PartnerType constant in getPartnerTypeConstant()
 * 2. Implement the actual process() logic
 *
 * Example:
 * ```php
 * class SupplierTransactionHandler extends AbstractTransactionHandler
 * {
 *     protected function getPartnerTypeConstant(): string
 *     {
 *         return 'SUPPLIER';  // References PartnerTypeConstants::SUPPLIER
 *     }
 *     
 *     public function process(...): array
 *     {
 *         // Implement supplier-specific logic
 *     }
 * }
 * ```
 */
abstract class AbstractTransactionHandler implements TransactionHandlerInterface
{
    /**
     * Partner type value object
     *
     * @var PartnerTypeInterface
     */
    private PartnerTypeInterface $partnerType;

    /**
     * Reference number service
     * 
     * @var ReferenceNumberService
     */
    protected ReferenceNumberService $referenceService;

    /**
     * Constructor - initializes and validates partner type
     *
     * Uses fail-fast pattern - if subclass doesn't provide valid type,
     * exception is thrown immediately on instantiation.
     *
     * @param ReferenceNumberService|null $referenceService Optional service for testing
     * @throws \InvalidArgumentException If partner type is invalid
     */
    public function __construct(?ReferenceNumberService $referenceService = null)
    {
        // Allow injection for testing, create default otherwise
        $this->referenceService = $referenceService ?? new ReferenceNumberService();
        
        $this->partnerType = $this->getPartnerTypeInstance();
        
        if (!$this->partnerType instanceof PartnerTypeInterface) {
            throw new \InvalidArgumentException(
                'Handler must provide valid PartnerType instance: ' . static::class
            );
        }

        // Validate short code format (2 uppercase letters)
        $shortCode = $this->partnerType->getShortCode();
        if (!preg_match('/^[A-Z]{2}$/', $shortCode)) {
            throw new \InvalidArgumentException(
                "Invalid partner type short code '{$shortCode}'. " .
                "Must be exactly 2 uppercase letters. Handler: " . static::class
            );
        }
    }

    /**
     * @inheritDoc
     */
    final public function getPartnerType(): string
    {
        return $this->partnerType->getShortCode();
    }

    /**
     * Get the PartnerType value object for this handler
     *
     * Returns the complete PartnerType object which provides:
     * - getShortCode(): Two-letter code ('SP', 'CU', etc.)
     * - getLabel(): Human-readable name ('Supplier', 'Customer', etc.)
     * - getDescription(): Optional description
     * - getPriority(): Sort priority for display
     *
     * @return PartnerTypeInterface The partner type value object
     */
    final public function getPartnerTypeObject(): PartnerTypeInterface
    {
        return $this->partnerType;
    }

    /**
     * Get the PartnerType instance for this handler
     *
     * Each concrete handler returns its specific PartnerType value object.
     * This provides access to short code, label, description, priority, etc.
     *
     * @return PartnerTypeInterface The partner type value object
     */
    abstract protected function getPartnerTypeInstance(): PartnerTypeInterface;

    /**
     * @inheritDoc
     * 
     * Standard implementation that checks if the partner type matches this handler.
     */
    final public function canProcess(string $partnerType): bool
    {
        return $partnerType === $this->partnerType->getShortCode();
    }

    /**
     * Validate transaction has required fields
     *
     * Common validation logic shared across handlers.
     *
     * @param array $transaction Transaction data
     * @param array $requiredFields Array of required field names
     * @throws \Exception if validation fails
     */
    protected function validateTransaction(array $transaction, array $requiredFields = []): void
    {
        // Default required fields if none specified
        if (empty($requiredFields)) {
            $requiredFields = ['transactionDC', 'transactionAmount', 'valueTimestamp', 'transactionTitle'];
        }
        
        foreach ($requiredFields as $field) {
            if (!isset($transaction[$field])) {
                throw new \Exception("Required field '{$field}' not set in transaction");
            }
        }
    }

    /**
     * Extract partner ID from transaction POST data
     *
     * Common utility for extracting partner ID from filtered POST data.
     * Expects 'partnerId' key in the transaction-specific data array.
     *
     * @param array $transactionPostData Transaction-specific POST data
     * @return int Partner ID
     * @throws \Exception if partner ID not found or invalid
     */
    protected function extractPartnerId(array $transactionPostData): int
    {
        if (!isset($transactionPostData['partnerId'])) {
            throw new \Exception("Partner ID not found in transaction data");
        }
        
        $partnerId = (int) $transactionPostData['partnerId'];
        
        if ($partnerId <= 0) {
            throw new \Exception("Invalid partner ID: must be positive integer");
        }
        
        return $partnerId;
    }

    /**
     * Calculate bank charges for transaction
     *
     * Common utility for charge calculation using global function.
     *
     * @param int $transactionId Transaction ID
     * @return float Charge amount
     */
    protected function calculateCharge(int $transactionId): float
    {
        // Use global function from bank_import_controller
        if (function_exists('sumCharges')) {
            return (float) sumCharges($transactionId);
        }
        
        return 0.0;
    }

    /**
     * Create error result
     *
     * Standard error format for consistency across handlers.
     * Returns TransactionResult for type safety and display integration.
     *
     * @param string $message Error message
     * @param array $data Additional error data
     * @return TransactionResult Error result
     */
    protected function createErrorResult(string $message, array $data = []): TransactionResult
    {
        return TransactionResult::error($message, $data);
    }

    /**
     * Create success result
     *
     * Standard success format for consistency across handlers.
     * Returns TransactionResult for type safety and display integration.
     *
     * @param int $transNo Transaction number
     * @param int $transType Transaction type constant
     * @param string $message Success message
     * @param array $data Additional result data (charge, reference, view links, etc.)
     * @return TransactionResult Success result
     */
    protected function createSuccessResult(
        int $transNo, 
        int $transType, 
        string $message, 
        array $data = []
    ): TransactionResult {
        return TransactionResult::success($transNo, $transType, $message, $data);
    }

    /**
     * Create warning result
     *
     * Standard warning format for consistency across handlers.
     * Returns TransactionResult for type safety and display integration.
     *
     * @param string $message Warning message
     * @param int $transNo Transaction number (if applicable)
     * @param int $transType Transaction type (if applicable)
     * @param array $data Additional warning data
     * @return TransactionResult Warning result
     */
    protected function createWarningResult(
        string $message,
        int $transNo = 0,
        int $transType = 0,
        array $data = []
    ): TransactionResult {
        return TransactionResult::warning($message, $transNo, $transType, $data);
    }
}
