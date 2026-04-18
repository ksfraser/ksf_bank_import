<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

/**
 * Type Consistency Scoring Rule
 *
 * If transaction type aligns with partner type, increase confidence.
 * This ensures we're matching suppliers to supplier transactions, etc.
 *
 * Scoring:
 * - Supplier + (CHECK, BANK_TRANSFER, WIRE, ACH): +3.0
 * - Customer + (INVOICE, DEPOSIT, CHECK_DEPOSIT): +3.0
 * - Other combinations: 0.0
 *
 * Partner type constants (typically from database):
 * - 1: Supplier (PT_SUPPLIER)
 * - 2: Customer (PT_CUSTOMER)
 * - 3: Bank (PT_BANK)
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class TypeConsistencyRule implements ScoringRule
{
    private const PT_SUPPLIER = 1;
    private const PT_CUSTOMER = 2;
    private const PT_BANK = 3;

    private const SUPPLIER_TYPES = ['CHECK', 'BANK_TRANSFER', 'WIRE', 'ACH'];
    private const CUSTOMER_TYPES = ['INVOICE', 'DEPOSIT', 'CHECK_DEPOSIT'];

    public function calculateScore(array $transaction, KeywordMatch $match): float
    {
        if (!isset($transaction['type'])) {
            return 0.0;
        }

        $typeUpper = strtoupper((string)$transaction['type']);
        $partnerType = $match->getPartnerType();

        // Supplier-type transactions
        if ($partnerType === self::PT_SUPPLIER) {
            if (in_array($typeUpper, self::SUPPLIER_TYPES, true)) {
                return 3.0;
            }
        }

        // Customer-type transactions
        if ($partnerType === self::PT_CUSTOMER) {
            if (in_array($typeUpper, self::CUSTOMER_TYPES, true)) {
                return 3.0;
            }
        }

        return 0.0;
    }

    public function getName(): string
    {
        return 'TypeConsistencyRule';
    }

    public function getMaxBoost(): float
    {
        return 3.0;
    }

    public function getMinReduction(): float
    {
        return 0.0;
    }
}
