<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Database\DatabaseFactory;

/**
 * @BABOK RTM: FR-008 / BR-003; file: Models/BankTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
class BankTransaction implements BankTransactionInterface
{
    private $data;
    private $connection;

    /** @BABOK FR-008 / BR-003 — method: __construct */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->connection = DatabaseFactory::getConnection();
    }

    /** @BABOK FR-008 / BR-003 — method: getId */
    public function getId(): int
    {
        return (int)$this->data['id'];
    }

    /** @BABOK FR-008 / BR-003 — method: getAmount */
    public function getAmount(): float
    {
        return (float)$this->data['amount'];
    }

    /** @BABOK FR-008 / BR-003 — method: getDate */
    public function getDate(): string
    {
        return $this->data['valueTimestamp'];
    }

    /** @BABOK FR-008 / BR-003 — method: getMemo */
    public function getMemo(): string
    {
        return $this->data['memo'] ?? '';
    }

    /** @BABOK FR-008 / BR-003 — method: getTransactionType */
    public function getTransactionType(): string
    {
        return $this->data['transactionDC'];
    }

    /** @BABOK FR-008 / BR-003 — method: getAccountDetails */
    public function getAccountDetails(): array
    {
        return [
            'name' => $this->data['accountName'] ?? '',
            'number' => $this->data['account'] ?? ''
        ];
    }

    /** @BABOK FR-008 / BR-003 — method: getOtherPartyDetails */
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

    /** @BABOK FR-008 / BR-003 — method: processTransaction */
    public function processTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'processed' WHERE id = ?"
        );
        return $stmt->execute([$this->getId()]);
    }
}