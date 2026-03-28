<?php

namespace Ksfraser\FaBankImport\Import\Results;

/**
 * Result of statement import operation.
 * 
 * Tracks successful/failed transactions, file metadata, and audit info.
 */
class StatementImportResult extends OperationResult
{
    /**
     * @var int Number of successfully imported transactions
     */
    private int $importedTransactionCount = 0;

    /**
     * @var int Number of skipped transactions (validation failures)
     */
    private int $skippedTransactionCount = 0;

    /**
     * @var int Number of failed transactions (processing errors)
     */
    private int $failedTransactionCount = 0;

    /**
     * @var int Statement ID if successfully imported
     */
    private ?int $statementId = null;

    /**
     * @var array Successfully imported transaction IDs
     */
    private array $importedTransactionIds = [];

    /**
     * @var array Failed transaction data with reasons
     */
    private array $failedTransactions = [];

    /**
     * Create a successful statement import result.
     *
     * @param int $statementId
     * @param int $importedCount
     * @param int $skippedCount
     * @param int $failedCount
     * @return self
     */
    public static function successfulImport(
        int $statementId,
        int $importedCount = 0,
        int $skippedCount = 0,
        int $failedCount = 0
    ): self {
        $result = new self();
        $result->success = true;
        $result->statementId = $statementId;
        $result->importedTransactionCount = $importedCount;
        $result->skippedTransactionCount = $skippedCount;
        $result->failedTransactionCount = $failedCount;
        return $result;
    }

    /**
     * Create a failed statement import result.
     *
     * @param string $error
     * @param int $skippedCount
     * @param int $failedCount
     * @return self
     */
    public static function importFailed(
        string $error,
        int $skippedCount = 0,
        int $failedCount = 0
    ): self {
        $result = new self();
        $result->success = false;
        $result->errors[] = $error;
        $result->skippedTransactionCount = $skippedCount;
        $result->failedTransactionCount = $failedCount;
        return $result;
    }

    /**
     * Record a successfully imported transaction.
     *
     * @param int $transactionId
     * @return $this
     */
    public function recordImportedTransaction(int $transactionId): self
    {
        $this->importedTransactionIds[] = $transactionId;
        $this->importedTransactionCount++;
        return $this;
    }

    /**
     * Record a skipped transaction.
     *
     * @param int $transactionId
     * @param string $reason
     * @return $this
     */
    public function recordSkippedTransaction(int $transactionId, string $reason): self
    {
        $this->failedTransactions[$transactionId] = [
            'status' => 'skipped',
            'reason' => $reason
        ];
        $this->skippedTransactionCount++;
        return $this;
    }

    /**
     * Record a failed transaction.
     *
     * @param int $transactionId
     * @param string $reason
     * @return $this
     */
    public function recordFailedTransaction(int $transactionId, string $reason): self
    {
        $this->failedTransactions[$transactionId] = [
            'status' => 'failed',
            'reason' => $reason
        ];
        $this->failedTransactionCount++;
        return $this;
    }

    /**
     * Get statement ID.
     *
     * @return int|null
     */
    public function getStatementId(): ?int
    {
        return $this->statementId;
    }

    /**
     * Get imported transaction count.
     *
     * @return int
     */
    public function getImportedTransactionCount(): int
    {
        return $this->importedTransactionCount;
    }

    /**
     * Get skipped transaction count.
     *
     * @return int
     */
    public function getSkippedTransactionCount(): int
    {
        return $this->skippedTransactionCount;
    }

    /**
     * Get failed transaction count.
     *
     * @return int
     */
    public function getFailedTransactionCount(): int
    {
        return $this->failedTransactionCount;
    }

    /**
     * Get all successfully imported transaction IDs.
     *
     * @return array
     */
    public function getImportedTransactionIds(): array
    {
        return $this->importedTransactionIds;
    }

    /**
     * Get failed transactions with reasons.
     *
     * @return array
     */
    public function getFailedTransactions(): array
    {
        return $this->failedTransactions;
    }
}
