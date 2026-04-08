<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Database\DatabaseFactory;

class BankTransaction implements BankTransactionInterface
{
    private $data;
    private $connection;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->connection = DatabaseFactory::getConnection();
    }

    public function getId(): int
    {
        return (int)$this->data['id'];
    }

    public function getAmount(): float
    {
        return (float)$this->data['amount'];
    }

    public function getDate(): string
    {
        return $this->data['valueTimestamp'];
    }

    public function getMemo(): string
    {
        return $this->data['memo'] ?? '';
    }

    public function getTransactionType(): string
    {
        return $this->data['transactionDC'];
    }

    public function getAccountDetails(): array
    {
        return [
            'name' => $this->data['accountName'] ?? '',
            'number' => $this->data['account'] ?? ''
        ];
    }

    public function getOtherPartyDetails(): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM bi_counterparty WHERE id_bi_counterparty_model = ?"
        );
        $stmt->execute([$this->data['partnerId'] ?? null]);
        $party = $stmt->fetch();

        return [
            'name' => $party['name'] ?? $this->data['merchant'] ?? '',
            'type' => $this->data['partnerType'] ?? ''
        ];
    }

    public function processTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'processed' WHERE id = ?"
        );
        return $stmt->execute([$this->getId()]);
    }
}