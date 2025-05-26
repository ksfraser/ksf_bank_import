<?php

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Database\DatabaseFactory;
use Ksfraser\FaBankImport\Database\QueryBuilder;

class TransactionRepository extends AbstractRepository implements TransactionRepositoryInterface
{
    protected $table = 'bi_transactions';
    private $connection;

    public function __construct()
    {
        parent::__construct();
        $this->connection = DatabaseFactory::getConnection();
    }

    public function findById(int $id): ?array
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return $this->queryBuilder->get();
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function save(array $transaction): bool
    {
        $sql = "INSERT INTO {$this->table} (amount, valueTimestamp, memo, status) VALUES (:amount, :valueTimestamp, :memo, :status)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            ':amount' => $transaction['amount'],
            ':valueTimestamp' => $transaction['valueTimestamp'],
            ':memo' => $transaction['memo'],
            ':status' => $transaction['status']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $params[':id'] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = :id";
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
}