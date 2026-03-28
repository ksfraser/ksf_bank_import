<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\ProcessorNotFoundException;

/**
 * Interface for transaction processors.
 * 
 * Defines contract for strategy classes that handle different partner types.
 */
interface TransactionProcessorInterface
{
    /**
     * Process a transaction for a specific partner type.
     *
     * @param array $transaction Transaction data
     * @param array $postData POST data from request
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Comma-separated collection IDs
     * @param array $bankAccount Bank account data
     * @return mixed Processing result
     */
    public function process(
        array $transaction,
        array $postData,
        int $transactionId,
        string $collectionIds,
        array $bankAccount
    ): mixed;
}

/**
 * Factory for creating and retrieving transaction processors.
 * 
 * Maps partner types (SP, CU, QE, BT, MA, ZZ, etc.) to their
 * corresponding processor implementations.
 */
class TransactionProcessorFactory
{
    /**
     * @var array Mapping of partner type to processor class
     */
    private array $processorMap = [
        'SP' => 'SupplierTransactionProcessor',
        'CU' => 'CustomerTransactionProcessor',
        'QE' => 'QuickEntryTransactionProcessor',
        'BT' => 'BankTransferTransactionProcessor',
        'MA' => 'ManualAdjustmentTransactionProcessor',
        'ZZ' => 'DefaultTransactionProcessor',
    ];

    /**
     * @var array Cached processor instances
     */
    private array $processorCache = [];

    /**
     * Create a processor for the given partner type.
     *
     * @param string $partnerType
     * @return TransactionProcessorInterface
     * @throws ProcessorNotFoundException
     */
    public function create(string $partnerType): TransactionProcessorInterface
    {
        if (empty($partnerType)) {
            throw ProcessorNotFoundException::unknownPartnerType(
                '',
                array_keys($this->processorMap)
            );
        }

        // Return cached instance if available
        if (isset($this->processorCache[$partnerType])) {
            return $this->processorCache[$partnerType];
        }

        if (!isset($this->processorMap[$partnerType])) {
            throw ProcessorNotFoundException::unknownPartnerType(
                $partnerType,
                array_keys($this->processorMap)
            );
        }

        $className = $this->processorMap[$partnerType];
        $processorClass = "Ksfraser\\FaBankImport\\Import\\Strategies\\{$className}";

        if (!class_exists($processorClass)) {
            throw ProcessorNotFoundException::classNotFound($processorClass);
        }

        try {
            $processor = new $processorClass();
            
            if (!($processor instanceof TransactionProcessorInterface)) {
                throw ProcessorNotFoundException::invalidInterface($processorClass);
            }

            // Cache for future use
            $this->processorCache[$partnerType] = $processor;

            return $processor;
        } catch (\Throwable $e) {
            if ($e instanceof ProcessorNotFoundException) {
                throw $e;
            }
            throw ProcessorNotFoundException::instantiationFailed($processorClass, $e->getMessage());
        }
    }

    /**
     * Register a custom processor for a partner type.
     *
     * @param string $partnerType
     * @param string $processorClass Fully qualified class name
     * @return $this
     */
    public function register(string $partnerType, string $processorClass): self
    {
        $this->processorMap[$partnerType] = $processorClass;
        // Clear cache for this type
        unset($this->processorCache[$partnerType]);
        return $this;
    }

    /**
     * Get all supported partner types.
     *
     * @return array
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->processorMap);
    }

    /**
     * Check if a partner type is supported.
     *
     * @param string $partnerType
     * @return bool
     */
    public function isSupported(string $partnerType): bool
    {
        return isset($this->processorMap[$partnerType]);
    }
}
