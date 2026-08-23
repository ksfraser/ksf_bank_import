<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * Contract for transaction persistence.
 *
 * Reflects the actual TransactionRepository contract (refactor-psr):
 * filter-based reads and explicit, typed update operations.
 *
 * @since 20260822
 */
interface TransactionRepositoryInterface
{
    /**
     * Fetch all transactions.
     *
     * @return array
     */
    public function findAll(): array;

    /**
     * Fetch one transaction by id.
     *
     * @param int $id Transaction id.
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * Fetch transactions matching filters (e.g. ['status' => 'pending']).
     *
     * @param array $filters Column => value pairs.
     * @return array
     */
    public function findByFilters(array $filters): array;

    /**
     * Update status/FA linkage for a set of transactions.
     *
     * @param int[]  $transactionIds Transaction ids.
     * @param int    $status         New status.
     * @param int    $faTransNo      FA transaction number.
     * @param int    $faTransType    FA transaction type.
     * @param bool   $matched        Matched flag.
     * @param bool   $created        Created flag.
     * @param string|null $partnerType Partner type code.
     * @param string $partnerOption  Partner option/classification.
     * @return int Number of rows affected.
     */
    public function update(
        array $transactionIds,
        int $status,
        int $faTransNo,
        int $faTransType,
        bool $matched = false,
        bool $created = false,
        ?string $partnerType = null,
        string $partnerOption = ''
    ): int;

    /**
     * Reset transactions back to unmatched state after a void.
     *
     * @param int[] $transactionIds Transaction ids.
     * @param int   $faTransNo      FA transaction number that was voided.
     * @param int   $faTransType    FA transaction type that was voided.
     * @return int Rows affected.
     */
    public function reset(
        array $transactionIds,
        int $faTransNo,
        int $faTransType
    ): int;

    /**
     * Mark transactions as pre-voided before FA void completes.
     *
     * @param int $faTransNo   FA transaction number.
     * @param int $faTransType FA transaction type.
     * @return int Rows affected.
     */
    public function prevoid(int $faTransNo, int $faTransType): int;

    /**
     * Find normally-pairable transactions for an account.
     *
     * @param string|null $account Account code filter.
     * @return array
     */
    public function findNormalPairing(?string $account = null): array;
}
