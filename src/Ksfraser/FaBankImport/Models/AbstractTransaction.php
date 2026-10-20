<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

/**
 * @BABOK RTM: FR-008 / BR-003; file: Models/AbstractTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
abstract class AbstractTransaction implements BankTransactionInterface
{
    protected $data;
    protected $id;
    protected $amount;
    protected $date;
    protected $memo;
    protected $status;
    protected $accountDetails;
    protected $otherPartyDetails;

    /** @BABOK FR-008 / BR-003 — method: __construct */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->initializeFromData();
    }

    /** @BABOK FR-008 / BR-003 — method: initializeFromData */
    protected function initializeFromData(): void
    {
        $this->id = $this->data['id'] ?? null;
        $this->amount = $this->data['amount'] ?? 0.0;
        $this->date = $this->data['valueTimestamp'] ?? '';
        $this->memo = $this->data['memo'] ?? '';
        $this->status = $this->data['status'] ?? 0;
        $this->accountDetails = [
            'account' => $this->data['our_account'] ?? '',
            'name' => $this->data['ourBankAccountName'] ?? '',
            'code' => $this->data['ourBankAccountCode'] ?? ''
        ];
        $this->otherPartyDetails = [
            'account' => $this->data['otherBankAccount'] ?? '',
            'name' => $this->data['otherBankAccountName'] ?? ''
        ];
    }

    /** @BABOK FR-008 / BR-003 — method: getId */
    public function getId(): int
    {
        return $this->id;
    }

    /** @BABOK FR-008 / BR-003 — method: getAmount */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /** @BABOK FR-008 / BR-003 — method: getDate */
    public function getDate(): string
    {
        return $this->date;
    }

    /** @BABOK FR-008 / BR-003 — method: getMemo */
    public function getMemo(): string
    {
        return $this->memo;
    }

    /** @BABOK FR-008 / BR-003 — method: getStatus */
    public function getStatus(): int
    {
        return $this->status;
    }

    /** @BABOK FR-008 / BR-003 — method: getAccountDetails */
    public function getAccountDetails(): array
    {
        return $this->accountDetails;
    }

    /** @BABOK FR-008 / BR-003 — method: getOtherPartyDetails */
    public function getOtherPartyDetails(): array
    {
        return $this->otherPartyDetails;
    }

    /** @BABOK FR-008 / BR-003 — method: getTransactionType */
    abstract public function getTransactionType(): string;
}
