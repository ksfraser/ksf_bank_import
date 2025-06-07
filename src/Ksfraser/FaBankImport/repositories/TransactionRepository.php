<?php

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    /* Original DB queries replaced by repository pattern */
    public function findById(int $id): ?array
    {
        $result = db_query("SELECT * FROM bi_transactions WHERE id = ?", [$id]);
        return $result ? $result[0] : null;
    }

    public function findAll(): array
    {
        $result = db_query("SELECT * FROM bi_transactions");
        return $result ?: [];
    }

    public function findByStatus(string $status): array
    {
        $result = db_query("SELECT * FROM bi_transactions WHERE status = ?", [$status]);
        return $result ?: [];
    }

    public function save(array $transaction): bool
    {
        // Insert new transaction
        $query = "INSERT INTO bi_transactions (amount, valueTimestamp, memo, status) VALUES (?, ?, ?, ?)";
        return db_query($query, [
            $transaction['amount'],
            $transaction['valueTimestamp'],
            $transaction['memo'],
            $transaction['status']
        ]) !== false;
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $id;
        $query = "UPDATE bi_transactions SET " . implode(', ', $setClauses) . " WHERE id = ?";
        
        return db_query($query, $params) !== false;
    }
}
