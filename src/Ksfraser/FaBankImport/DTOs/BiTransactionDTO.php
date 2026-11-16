<?php

namespace Ksfraser\FaBankImport\DTOs;

/**
 * BiTransactionDTO
 * 
 * Data Transfer Object for BiTransaction.
 * Provides a serializable representation separate from domain entity.
 * Used for data transfer across module boundaries and API responses.
 * 
 * @package Ksfraser\FaBankImport\DTOs
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
 */
final class BiTransactionDTO
{
    private int $id;
    private int $smtId;
    private ?string $valueTimestamp;
    private ?string $entryTimestamp;
    private ?string $account;
    private ?string $accountName;
    private ?string $transactionType;
    private string $transactionCode;
    private ?string $transactionCodeDesc;
    private string $transactionDC;
    private float $transactionAmount;
    private ?string $transactionTitle;
    private ?string $status;
    private ?string $matchinfo;
    private ?int $faTransType;
    private ?int $faTransNo;
    private ?string $fitid;
    private ?string $acctid;
    private ?string $merchant;
    private ?string $category;
    private ?string $sic;
    private ?string $memo;
    private ?string $checknumber;
    private bool $matched;
    private bool $created;
    private ?string $gPartner;
    private ?string $gOption;

    /**
     * Private constructor - enforces factory method usage
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    private function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->smtId = (int)($data['smtId'] ?? 0);
        $this->valueTimestamp = $data['valueTimestamp'] ?? null;
        $this->entryTimestamp = $data['entryTimestamp'] ?? null;
        $this->account = $data['account'] ?? null;
        $this->accountName = $data['accountName'] ?? null;
        $this->transactionType = $data['transactionType'] ?? null;
        $this->transactionCode = (string)($data['transactionCode'] ?? '');
        $this->transactionCodeDesc = $data['transactionCodeDesc'] ?? null;
        $this->transactionDC = (string)($data['transactionDC'] ?? 'D');
        $this->transactionAmount = (float)($data['transactionAmount'] ?? 0.00);
        $this->transactionTitle = $data['transactionTitle'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->matchinfo = $data['matchinfo'] ?? null;
        $this->faTransType = isset($data['faTransType']) ? (int)$data['faTransType'] : null;
        $this->faTransNo = isset($data['faTransNo']) ? (int)$data['faTransNo'] : null;
        $this->fitid = $data['fitid'] ?? null;
        $this->acctid = $data['acctid'] ?? null;
        $this->merchant = $data['merchant'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->sic = $data['sic'] ?? null;
        $this->memo = $data['memo'] ?? null;
        $this->checknumber = $data['checknumber'] ?? null;
        $this->matched = (bool)($data['matched'] ?? false);
        $this->created = (bool)($data['created'] ?? false);
        $this->gPartner = $data['gPartner'] ?? null;
        $this->gOption = $data['gOption'] ?? null;
    }

    /**
     * Create DTO from array data
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get all getters
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getSmtId(): int
    {
        return $this->smtId;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getValueTimestamp(): ?string
    {
        return $this->valueTimestamp;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getEntryTimestamp(): ?string
    {
        return $this->entryTimestamp;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAccount(): ?string
    {
        return $this->account;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionCode(): string
    {
        return $this->transactionCode;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionCodeDesc(): ?string
    {
        return $this->transactionCodeDesc;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionDC(): string
    {
        return $this->transactionDC;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionAmount(): float
    {
        return $this->transactionAmount;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionTitle(): ?string
    {
        return $this->transactionTitle;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMatchinfo(): ?string
    {
        return $this->matchinfo;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFaTransType(): ?int
    {
        return $this->faTransType;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFaTransNo(): ?int
    {
        return $this->faTransNo;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFitid(): ?string
    {
        return $this->fitid;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAcctid(): ?string
    {
        return $this->acctid;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMerchant(): ?string
    {
        return $this->merchant;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getSic(): ?string
    {
        return $this->sic;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getChecknumber(): ?string
    {
        return $this->checknumber;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function isMatched(): bool
    {
        return $this->matched;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function isCreated(): bool
    {
        return $this->created;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getGPartner(): ?string
    {
        return $this->gPartner;
    }

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getGOption(): ?string
    {
        return $this->gOption;
    }

    /**
     * Serialize to array
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'smtId' => $this->smtId,
            'valueTimestamp' => $this->valueTimestamp,
            'entryTimestamp' => $this->entryTimestamp,
            'account' => $this->account,
            'accountName' => $this->accountName,
            'transactionType' => $this->transactionType,
            'transactionCode' => $this->transactionCode,
            'transactionCodeDesc' => $this->transactionCodeDesc,
            'transactionDC' => $this->transactionDC,
            'transactionAmount' => $this->transactionAmount,
            'transactionTitle' => $this->transactionTitle,
            'status' => $this->status,
            'matchinfo' => $this->matchinfo,
            'faTransType' => $this->faTransType,
            'faTransNo' => $this->faTransNo,
            'fitid' => $this->fitid,
            'acctid' => $this->acctid,
            'merchant' => $this->merchant,
            'category' => $this->category,
            'sic' => $this->sic,
            'memo' => $this->memo,
            'checknumber' => $this->checknumber,
            'matched' => $this->matched,
            'created' => $this->created,
            'gPartner' => $this->gPartner,
            'gOption' => $this->gOption,
        ];
    }

    /**
     * Serialize to JSON
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
