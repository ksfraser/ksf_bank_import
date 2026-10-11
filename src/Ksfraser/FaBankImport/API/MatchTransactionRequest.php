<?php

/**
 * Match Transaction Request DTO
 *
 * Incoming request to match a transaction to a partner.
 * Validates that all required fields are present and valid.
 *
 * @author Kevin Fraser
 * @since 2.4.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

/**
 * MatchTransactionRequest
 *
 * Request object for matching a transaction to a partner.
 * Contains validation logic for required fields.
 */
class MatchTransactionRequest
{
    /**
     * Constructor
     *
     * @param string $transactionId Transaction identifier
     * @param float $amount Transaction amount
     * @param string $description Transaction description
     * @param string $transactionType Type of transaction
     * @param string|null $referenceNumber Optional reference number
     * @throws \InvalidArgumentException If validation fails
     */
    public function __construct(
        private readonly string $transactionId,
        private readonly float $amount,
        private readonly string $description,
        private readonly string $transactionType,
        private readonly ?string $referenceNumber = null
    ) {
        $this->validate();
    }

    /**
     * Validate request data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->transactionId)) {
            throw new \InvalidArgumentException('Transaction ID cannot be empty');
        }

        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        if (empty($this->description)) {
            throw new \InvalidArgumentException('Description cannot be empty');
        }
    }

    /**
     * Create request from array (typically JSON decoded)
     *
     * @param array $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['transaction_id'] ?? '',
            (float)($data['amount'] ?? 0),
            $data['description'] ?? '',
            $data['transaction_type'] ?? '',
            $data['reference_number'] ?? null
        );
    }

    /**
     * Get transaction ID
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * Get transaction amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get transaction description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get transaction type
     */
    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    /**
     * Get reference number
     */
    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_type' => $this->transactionType,
        ];
    }
}
