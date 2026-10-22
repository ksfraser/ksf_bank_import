<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Database\DatabaseFactory;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class BankTransaction implements BankTransactionInterface
{
    private $data;
    private $connection;

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->connection = DatabaseFactory::getConnection();
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getId(): int
    {
        return (int)$this->data['id'];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAmount(): float
    {
        return (float)$this->data['amount'];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDate(): string
    {
        return $this->data['valueTimestamp'];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMemo(): string
    {
        return $this->data['memo'] ?? '';
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionType(): string
    {
        return $this->data['transactionDC'];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAccountDetails(): array
    {
        return [
            'name' => $this->data['accountName'] ?? '',
            'number' => $this->data['account'] ?? ''
        ];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
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

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function processTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'processed' WHERE id = ?"
        );
        return $stmt->execute([$this->getId()]);
    }
}