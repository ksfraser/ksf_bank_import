<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Strategies\IDGeneration;

use InvalidArgumentException;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;

/**
 * Strategy for generating transaction reference numbers
 *
 * Generates guaranteed unique FrontAccounting transaction references.
 * Wraps ReferenceNumberService for polymorphic usage in strategy pattern.
 *
 * Example: Generates references like "1001", "1002", etc. for FA transactions
 */
final class TransactionReferenceStrategy implements IDGenerationStrategy
{
    /**
     * Reference number service
     *
     * @var ReferenceNumberService
     */
    private ReferenceNumberService $referenceService;

    /**
     * Constructor
     *
     * @param ?ReferenceNumberService $referenceService Optional service (auto-created if null)
     */
    public function __construct(?ReferenceNumberService $referenceService = null)
    {
        $this->referenceService = $referenceService ?? new ReferenceNumberService();
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategyName(): string
    {
        return 'transaction_reference';
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $parameters Expected keys:
     *   - trans_type (int): FrontAccounting transaction type constant (ST_CUSTPAYMENT, etc.)
     *
     * @return string Unique reference number
     *
     * @throws InvalidArgumentException If trans_type is missing or invalid
     */
    public function generate(array $parameters): string
    {
        if (!$this->validateParameters($parameters)) {
            throw new InvalidArgumentException(
                'TransactionReferenceStrategy requires: trans_type (integer)'
            );
        }

        $transType = (int)$parameters['trans_type'];

        return $this->referenceService->getUniqueReference($transType);
    }

    /**
     * {@inheritDoc}
     *
     * Requires: trans_type as integer
     */
    public function validateParameters(array $parameters): bool
    {
        return isset($parameters['trans_type'])
            && is_int($parameters['trans_type'])
            && $parameters['trans_type'] > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredParameters(): array
    {
        return [
            'trans_type' => 'FrontAccounting transaction type constant (int, e.g., ST_CUSTPAYMENT)',
        ];
    }
}
