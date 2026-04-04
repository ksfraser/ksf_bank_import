<?php

namespace Ksfraser\FaBankImport\Import\DTOs;

/**
 * Data transfer object for parsed statement data
 *
 * Bridges the gap between raw parser output and domain entities.
 * Contains all data needed to create BiStatement, BiTransaction, and BiLineItem entities.
 *
 * Immutable - use create() factory for construction.
 */
final class ParsedStatementDTO
{
    /**
     * Create parsed statement DTO
     *
     * @param string $statementDate Format: YYYY-MM-DD
     * @param string $accountReference Bank account identifier
     * @param string $currency 3-letter currency code (e.g., CAD, USD)
     * @param float $openingBalance
     * @param float $closingBalance
     * @param array<int, array<string, mixed>> $transactions Array of transaction records
     * @param string $parserType The parser that created this DTO (csv, xls, ofx, qfx, etc.)
     * @param array<string, mixed> $metadata Optional metadata from parser
     */
    private function __construct(
        public readonly string $statementDate,
        public readonly string $accountReference,
        public readonly string $currency,
        public readonly float $openingBalance,
        public readonly float $closingBalance,
        public readonly array $transactions,
        public readonly string $parserType,
        public readonly array $metadata = []
    ) {
    }

    /**
     * Create a new ParsedStatementDTO
     *
     * @param array<string, mixed> $data The parsed statement data
     * @return self
     *
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function create(array $data): self
    {
        $required = ['statementDate', 'accountReference', 'currency', 'openingBalance', 'closingBalance', 'transactions', 'parserType'];
        $missing = array_diff($required, array_keys($data));

        if ($missing) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }

        return new self(
            $data['statementDate'],
            $data['accountReference'],
            $data['currency'],
            (float)$data['openingBalance'],
            (float)$data['closingBalance'],
            (array)$data['transactions'],
            $data['parserType'],
            $data['metadata'] ?? []
        );
    }

    /**
     * Get transaction count
     *
     * @return int
     */
    public function getTransactionCount(): int
    {
        return count($this->transactions);
    }

    /**
     * Get total debit amount
     *
     * @return float
     */
    public function getTotalDebits(): float
    {
        return array_reduce(
            $this->transactions,
            function (float $sum, array $txn): float {
                if (($txn['dc'] ?? null) === 'D') {
                    $sum += (float)($txn['amount'] ?? 0);
                }
                return $sum;
            },
            0.0
        );
    }

    /**
     * Get total credit amount
     *
     * @return float
     */
    public function getTotalCredits(): float
    {
        return array_reduce(
            $this->transactions,
            function (float $sum, array $txn): float {
                if (($txn['dc'] ?? null) === 'C') {
                    $sum += (float)($txn['amount'] ?? 0);
                }
                return $sum;
            },
            0.0
        );
    }

    /**
     * Get calculated net change
     *
     * @return float
     */
    public function getNetChange(): float
    {
        return $this->getTotalCredits() - $this->getTotalDebits();
    }

    /**
     * Convert to array for storage/serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'statementDate' => $this->statementDate,
            'accountReference' => $this->accountReference,
            'currency' => $this->currency,
            'openingBalance' => $this->openingBalance,
            'closingBalance' => $this->closingBalance,
            'transactions' => $this->transactions,
            'parserType' => $this->parserType,
            'metadata' => $this->metadata,
        ];
    }
}
