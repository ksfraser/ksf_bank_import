<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FaDbAdapter;
use Ksfraser\FaBankImport\DTO\BiTransactionDto;
use Ksfraser\FaBankImport\Schema\BiTransactionsSchema;

abstract class AbstractThirdPartyTransactionRepository implements ThirdPartyTransactionRepositoryInterface
{
    /** @var string */
    protected $tableName;

    /** @var DbAdapterInterface */
    protected $db;

    public function __construct(?string $tableName = null, ?DbAdapterInterface $db = null)
    {
        $this->tableName = $tableName ?? BiTransactionsSchema::tableName();
        $this->db = $db ?? new FaDbAdapter();
    }

    /**
     * @return array<int, BiTransactionDto>
     */
    public function getAllTransactions(): array
    {
        $result = $this->db->query("SELECT * FROM {$this->tableName}");
        $rows = $this->coerceRows($result);
        return array_map(static function (array $row): BiTransactionDto {
            return BiTransactionDto::fromArray($row);
        }, $rows);
    }

    public function unsetTransaction($transactionId): bool
    {
        $transactionId = (int) $transactionId;

        // Status codes (see docs/MANTIS_2713_VALIDATION.md): -1 is flagged for review.
        $result = $this->db->query("UPDATE {$this->tableName} SET status = -1 WHERE id = {$transactionId}");
        return $result ? true : false;
    }

    public function findById($transactionId): ?BiTransactionDto
    {
        $transactionId = (int) $transactionId;
        $result = $this->db->query("SELECT * FROM {$this->tableName} WHERE id = {$transactionId}");
        $rows = $this->coerceRows($result);
        if (!isset($rows[0])) {
            return null;
        }
        return BiTransactionDto::fromArray($rows[0]);
    }

    public function toggleDebitCredit($transactionId): bool
    {
        $transactionDto = $this->findById($transactionId);
        if (!$transactionDto) {
            return false;
        }

        $transaction = $transactionDto->toArray();
        $current = (string) ($transaction['transactionDC'] ?? 'D');
        $newType = strtoupper($current) === 'D' ? 'C' : 'D';
        $transactionId = (int) $transactionId;

        $result = $this->db->query(
            "UPDATE {$this->tableName} SET transactionDC = '{$newType}' WHERE id = {$transactionId}"
        );

        return $result ? true : false;
    }

    /**
     * @param mixed $result
     * @return array<int, array<string, mixed>>
     */
    protected function coerceRows($result): array
    {
        if (!$result) {
            return [];
        }

        if (is_array($result)) {
            return $result;
        }

        // FrontAccounting typically returns a resource; our tests return TestDbResult.
        if (is_object($result) || is_resource($result)) {
            $rows = [];
            while ($row = $this->db->fetch($result)) {
                $rows[] = $row;
            }
            return $rows;
        }

        return [];
    }
}
