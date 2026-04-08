<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Strategies\IDGeneration;

use InvalidArgumentException;

/**
 * Strategy for generating bank account identifier keys
 *
 * Creates composite keys from bank ID, account ID, and Intuit BID.
 * Format: "BANKID|ACCTID|intu:INTU_BID" with fallback to "unknown"
 *
 * Wraps BankAccountMappingFactory::generateIdentifierKey() for polymorphic usage.
 */
final class BankAccountIdentifierStrategy implements IDGenerationStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getStrategyName(): string
    {
        return 'bank_account_identifier';
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $parameters Expected keys:
     *   - bankid (string|null): OFX bank ID
     *   - acctid (string|null): OFX account ID
     *   - intu_bid (string|null): Intuit business ID
     *
     * @return string Composite identifier like "021000021|123456789|intu:my_biz_123"
     *
     * @throws InvalidArgumentException If neither bankid nor acctid nor intu_bid provided
     */
    public function generate(array $parameters): string
    {
        if (!$this->validateParameters($parameters)) {
            throw new InvalidArgumentException(
                'BankAccountIdentifierStrategy requires at least one of: bankid, acctid, intu_bid'
            );
        }

        $bankid = $parameters['bankid'] ?? null;
        $acctid = $parameters['acctid'] ?? null;
        $intu_bid = $parameters['intu_bid'] ?? null;

        $parts = [];

        if (!empty($bankid)) {
            $parts[] = (string)$bankid;
        }
        if (!empty($acctid)) {
            $parts[] = (string)$acctid;
        }
        if (!empty($intu_bid)) {
            $parts[] = 'intu:' . (string)$intu_bid;
        }

        return implode('|', $parts) ?: 'unknown';
    }

    /**
     * {@inheritDoc}
     *
     * At least one identifier must be provided
     */
    public function validateParameters(array $parameters): bool
    {
        $bankid = $parameters['bankid'] ?? null;
        $acctid = $parameters['acctid'] ?? null;
        $intu_bid = $parameters['intu_bid'] ?? null;

        return !empty($bankid) || !empty($acctid) || !empty($intu_bid);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredParameters(): array
    {
        return [
            'bankid' => 'OFX bank routing number (optional)',
            'acctid' => 'OFX account number (optional)',
            'intu_bid' => 'Intuit business ID (optional)',
        ];
    }
}
