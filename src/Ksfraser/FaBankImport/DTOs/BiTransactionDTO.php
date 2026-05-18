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
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get all getters
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getSmtId(): int
    {
        return $this->smtId;
    }

    public function getValueTimestamp(): ?string
    {
        return $this->valueTimestamp;
    }

    public function getEntryTimestamp(): ?string
    {
        return $this->entryTimestamp;
    }

    public function getAccount(): ?string
    {
        return $this->account;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function getTransactionCode(): string
    {
        return $this->transactionCode;
    }

    public function getTransactionCodeDesc(): ?string
    {
        return $this->transactionCodeDesc;
    }

    public function getTransactionDC(): string
    {
        return $this->transactionDC;
    }

    public function getTransactionAmount(): float
    {
        return $this->transactionAmount;
    }

    public function getTransactionTitle(): ?string
    {
        return $this->transactionTitle;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getMatchinfo(): ?string
    {
        return $this->matchinfo;
    }

    public function getFaTransType(): ?int
    {
        return $this->faTransType;
    }

    public function getFaTransNo(): ?int
    {
        return $this->faTransNo;
    }

    public function getFitid(): ?string
    {
        return $this->fitid;
    }

    public function getAcctid(): ?string
    {
        return $this->acctid;
    }

    public function getMerchant(): ?string
    {
        return $this->merchant;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getSic(): ?string
    {
        return $this->sic;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function getChecknumber(): ?string
    {
        return $this->checknumber;
    }

    public function isMatched(): bool
    {
        return $this->matched;
    }

    public function isCreated(): bool
    {
        return $this->created;
    }

    public function getGPartner(): ?string
    {
        return $this->gPartner;
    }

    public function getGOption(): ?string
    {
        return $this->gOption;
    }

    /**
     * Serialize to array
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
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
