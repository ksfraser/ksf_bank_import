<?php

namespace Ksfraser\FaBankImport\Import\Results;

/**
 * Result of a single transaction processing operation.
 * 
 * Tracks transaction state, GL entries created, and account reconciliation.
 */
class TransactionProcessResult extends OperationResult
{
    /**
     * @var int Transaction being processed
     */
    private int $transactionId;

    /**
     * @var array GL entries created
     */
    private array $glEntriesCreated = [];

    /**
     * @var int Contact ID linked (if any)
     */
    private ?int $contactId = null;

    /**
     * @var string Contact type (CU|DE|SU|VE|EM|BR)
     */
    private ?string $contactType = null;

    /**
     * @var float Amount posted to GL
     */
    private float $amountPosted = 0.0;

    /**
     * Create a successful transaction processing result.
     *
     * @param int $transactionId
     * @return self
     */
    public static function successful(int $transactionId): self
    {
        $result = new self();
        $result->success = true;
        $result->transactionId = $transactionId;
        return $result;
    }

    /**
     * Create a failed transaction processing result.
     *
     * @param int $transactionId
     * @param string $error
     * @return self
     */
    public static function failed(int $transactionId, string $error): self
    {
        $result = new self();
        $result->success = false;
        $result->transactionId = $transactionId;
        $result->errors[] = $error;
        return $result;
    }

    /**
     * Get transaction ID.
     *
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * Record a GL entry created.
     *
     * @param int $journalId
     * @param string $account
     * @param float $amount
     * @param string $memo
     * @return $this
     */
    public function recordGlEntry(int $journalId, string $account, float $amount, string $memo): self
    {
        $this->glEntriesCreated[] = [
            'journal_id' => $journalId,
            'account' => $account,
            'amount' => $amount,
            'memo' => $memo
        ];
        $this->amountPosted += abs($amount);
        return $this;
    }

    /**
     * Get all GL entries created.
     *
     * @return array
     */
    public function getGlEntries(): array
    {
        return $this->glEntriesCreated;
    }

    /**
     * Set contact linked to this transaction.
     *
     * @param int $contactId
     * @param string $contactType
     * @return $this
     */
    public function setContact(int $contactId, string $contactType): self
    {
        $this->contactId = $contactId;
        $this->contactType = $contactType;
        return $this;
    }

    /**
     * Get contact ID linked to this transaction.
     *
     * @return int|null
     */
    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    /**
     * Get contact type.
     *
     * @return string|null
     */
    public function getContactType(): ?string
    {
        return $this->contactType;
    }

    /**
     * Get total amount posted to GL.
     *
     * @return float
     */
    public function getAmountPosted(): float
    {
        return $this->amountPosted;
    }
}
