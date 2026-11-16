<?php

namespace Ksfraser\FaBankImport\DTOs;

/**
 * Data Transfer Object for BiLineItem
 *
 * Used for cross-module communication. Separate from the entity to avoid
 * tight coupling between modules.
 *
 * @since 2025-01-15
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
 */
final class BiLineItemDTO
{
    // Core identifiers
    private int $id;
    private string $transactionDc;
    private string $our_account;
    private string $valueTimestamp;
    private string $entryTimestamp;
    private string $otherBankaccount;
    private string $otherBankaccountName;
    private string $transactionTitle;
    private int $status;
    private string $currency;

    // FA Transaction references
    private int $fa_trans_type;
    private int $fa_trans_no;

    // Transaction characteristics
    private int $has_trans;
    private float $amount;
    private float $charge;
    private string $transactionTypeLabel;

    // Partner/vendor information
    private array $vendor_list;
    private ?string $partnerType;
    private ?int $partnerId;
    private ?int $partnerDetailId;
    private ?string $oplabel;

    // Matching and state
    private array $matching_trans;
    private int $days_spread;

    // Additional transaction codes and notes
    private string $transactionCode;
    private string $transactionCodeDesc;
    private array $optypes;
    private string $memo;

    // Bank account details
    private array $ourBankDetails;
    private string $ourBankAccount;
    private string $ourBankAccountName;
    private string $ourBankAccountCode;
    private ?object $fa_bank_accounts;

    // Status flags
    private bool $matched;
    private bool $created;

    // Form data handler
    private ?object $formData;

    /**
     * Private constructor - use factory to create instances
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    private function __construct() {}

    /**
     * Factory method to create DTO from array
     *
     * @param array $data Data for the DTO
     * @return self New BiLineItemDTO instance
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = (int)($data['id'] ?? 0);
        $dto->transactionDc = (string)($data['transactionDc'] ?? '');
        $dto->our_account = (string)($data['our_account'] ?? '');
        $dto->valueTimestamp = (string)($data['valueTimestamp'] ?? '');
        $dto->entryTimestamp = (string)($data['entryTimestamp'] ?? '');
        $dto->otherBankaccount = (string)($data['otherBankaccount'] ?? '');
        $dto->otherBankaccountName = (string)($data['otherBankaccountName'] ?? '');
        $dto->transactionTitle = (string)($data['transactionTitle'] ?? '');
        $dto->status = (int)($data['status'] ?? 0);
        $dto->currency = (string)($data['currency'] ?? '');
        $dto->fa_trans_type = (int)($data['fa_trans_type'] ?? 0);
        $dto->fa_trans_no = (int)($data['fa_trans_no'] ?? 0);
        $dto->has_trans = (int)($data['has_trans'] ?? 1);
        $dto->amount = (float)($data['amount'] ?? 0.00);
        $dto->charge = (float)($data['charge'] ?? 0.00);
        $dto->transactionTypeLabel = (string)($data['transactionTypeLabel'] ?? '');
        $dto->vendor_list = $data['vendor_list'] ?? [];
        $dto->partnerType = $data['partnerType'] ?? null;
        $dto->partnerId = $data['partnerId'] ?? null;
        $dto->partnerDetailId = $data['partnerDetailId'] ?? null;
        $dto->oplabel = $data['oplabel'] ?? null;
        $dto->matching_trans = $data['matching_trans'] ?? [];
        $dto->days_spread = (int)($data['days_spread'] ?? 2);
        $dto->transactionCode = (string)($data['transactionCode'] ?? '');
        $dto->transactionCodeDesc = (string)($data['transactionCodeDesc'] ?? '');
        $dto->optypes = $data['optypes'] ?? [];
        $dto->memo = (string)($data['memo'] ?? '');
        $dto->ourBankDetails = $data['ourBankDetails'] ?? [];
        $dto->ourBankAccount = (string)($data['ourBankAccount'] ?? '');
        $dto->ourBankAccountName = (string)($data['ourBankAccountName'] ?? '');
        $dto->ourBankAccountCode = (string)($data['ourBankAccountCode'] ?? '');
        $dto->fa_bank_accounts = $data['fa_bank_accounts'] ?? null;
        $dto->matched = (bool)($data['matched'] ?? false);
        $dto->created = (bool)($data['created'] ?? false);
        $dto->formData = $data['formData'] ?? null;

        return $dto;
    }

    // ============ GETTERS ============

    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getId(): int { return $this->id; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionDc(): string { return $this->transactionDc; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOurAccount(): string { return $this->our_account; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getValueTimestamp(): string { return $this->valueTimestamp; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getEntryTimestamp(): string { return $this->entryTimestamp; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOtherBankaccount(): string { return $this->otherBankaccount; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOtherBankaccountName(): string { return $this->otherBankaccountName; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionTitle(): string { return $this->transactionTitle; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getStatus(): int { return $this->status; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getCurrency(): string { return $this->currency; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFaTransType(): int { return $this->fa_trans_type; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFaTransNo(): int { return $this->fa_trans_no; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getHasTrans(): int { return $this->has_trans; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAmount(): float { return $this->amount; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getCharge(): float { return $this->charge; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionTypeLabel(): string { return $this->transactionTypeLabel; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getVendorList(): array { return $this->vendor_list; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPartnerType(): ?string { return $this->partnerType; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPartnerId(): ?int { return $this->partnerId; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPartnerDetailId(): ?int { return $this->partnerDetailId; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOpLabel(): ?string { return $this->oplabel; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMatchingTrans(): array { return $this->matching_trans; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDaysSpread(): int { return $this->days_spread; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionCode(): string { return $this->transactionCode; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionCodeDesc(): string { return $this->transactionCodeDesc; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOptypes(): array { return $this->optypes; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMemo(): string { return $this->memo; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOurBankDetails(): array { return $this->ourBankDetails; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOurBankAccount(): string { return $this->ourBankAccount; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOurBankAccountName(): string { return $this->ourBankAccountName; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOurBankAccountCode(): string { return $this->ourBankAccountCode; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFaBankAccounts(): ?object { return $this->fa_bank_accounts; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function isMatched(): bool { return $this->matched; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function isCreated(): bool { return $this->created; }
    /**
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getFormData(): ?object { return $this->formData; }

    // ============ SERIALIZATION ============

    /**
     * Convert to array representation
     *
     * @return array Array representation of the DTO
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'transactionDc' => $this->transactionDc,
            'our_account' => $this->our_account,
            'valueTimestamp' => $this->valueTimestamp,
            'entryTimestamp' => $this->entryTimestamp,
            'otherBankaccount' => $this->otherBankaccount,
            'otherBankaccountName' => $this->otherBankaccountName,
            'transactionTitle' => $this->transactionTitle,
            'status' => $this->status,
            'currency' => $this->currency,
            'fa_trans_type' => $this->fa_trans_type,
            'fa_trans_no' => $this->fa_trans_no,
            'has_trans' => $this->has_trans,
            'amount' => $this->amount,
            'charge' => $this->charge,
            'transactionTypeLabel' => $this->transactionTypeLabel,
            'vendor_list' => $this->vendor_list,
            'partnerType' => $this->partnerType,
            'partnerId' => $this->partnerId,
            'partnerDetailId' => $this->partnerDetailId,
            'oplabel' => $this->oplabel,
            'matching_trans' => $this->matching_trans,
            'days_spread' => $this->days_spread,
            'transactionCode' => $this->transactionCode,
            'transactionCodeDesc' => $this->transactionCodeDesc,
            'optypes' => $this->optypes,
            'memo' => $this->memo,
            'ourBankDetails' => $this->ourBankDetails,
            'ourBankAccount' => $this->ourBankAccount,
            'ourBankAccountName' => $this->ourBankAccountName,
            'ourBankAccountCode' => $this->ourBankAccountCode,
            'fa_bank_accounts' => $this->fa_bank_accounts,
            'matched' => $this->matched,
            'created' => $this->created,
            'formData' => $this->formData,
        ];
    }

    /**
     * Convert to JSON string
     *
     * @return string JSON representation of the DTO
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Prevent calling non-existent methods (especially setters)
     *
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @throws \BadMethodCallException
 * @BABOK FR-PROC-002, FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function __call(string $name, array $arguments): never
    {
        throw new \BadMethodCallException("Method {$name}() does not exist on " . self::class);
    }
}
