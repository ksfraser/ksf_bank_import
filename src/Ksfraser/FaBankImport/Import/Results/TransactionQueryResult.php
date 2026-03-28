<?php

namespace Ksfraser\FaBankImport\Import\Results;

/**
 * Result of transaction query/fetch operations.
 * 
 * Wraps transaction data with metadata and validation status.
 */
class TransactionQueryResult extends OperationResult
{
    /**
     * @var array Transaction data returned from query
     */
    private array $transactionData = [];

    /**
     * @var int Transaction ID queried
     */
    private int $transactionId;

    /**
     * @var array Full row data from database
     */
    private array $rowData = [];

    /**
     * Create a successful transaction query result.
     *
     * @param int $transactionId
     * @param array $transactionData
     * @param array $rowData Full database row
     * @return self
     */
    public static function found(int $transactionId, array $transactionData, array $rowData = []): self
    {
        $result = new self();
        $result->success = true;
        $result->transactionId = $transactionId;
        $result->transactionData = $transactionData;
        $result->rowData = $rowData;
        return $result;
    }

    /**
     * Create a not-found transaction query result.
     *
     * @param int $transactionId
     * @return self
     */
    public static function notFound(int $transactionId): self
    {
        $result = new self();
        $result->success = false;
        $result->transactionId = $transactionId;
        $result->errors[] = "Transaction {$transactionId} not found";
        return $result;
    }

    /**
     * Get transaction data.
     *
     * @param string|null $key If provided, get specific key from transaction data
     * @return mixed
     */
    public function getTransactionData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->transactionData;
        }
        return $this->transactionData[$key] ?? null;
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
     * Get full database row data.
     *
     * @return array
     */
    public function getRowData(): array
    {
        return $this->rowData;
    }

    /**
     * Check if transaction exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->success && count($this->transactionData) > 0;
    }
}
