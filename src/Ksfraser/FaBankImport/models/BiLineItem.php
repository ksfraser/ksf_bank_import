<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Exceptions\InvalidBiLineItemException;

/**
 * Immutable domain entity representing a single bank statement line item
 *
 * This entity represents a line item from a bank statement. It is immutable,
 * meaning all state transitions produce new instances rather than modifying
 * the existing instance.
 *
 * @since 2025-01-15
 */
final class BiLineItem
{
    private const STATUS_UNPROCESSED = 0;

    // Core identifiers
    private int $id;

    // Transaction details
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
     * Private constructor - use factories to create instances
     *
     * @param string $transactionDc Debit/Credit indicator
     */
    private function __construct(string $transactionDc)
    {
        if (empty($transactionDc)) {
            throw new InvalidBiLineItemException('transactionDc cannot be empty');
        }

        $this->transactionDc = $transactionDc;
        $this->id = 0;
        $this->status = self::STATUS_UNPROCESSED;
        $this->fa_trans_type = 0;
        $this->fa_trans_no = 0;
        $this->has_trans = 1;
        $this->amount = 0.00;
        $this->charge = 0.00;
        $this->matched = false;
        $this->created = false;
        $this->days_spread = 2;
        $this->vendor_list = [];
        $this->optypes = [];
        $this->matching_trans = [];
        $this->ourBankDetails = [];
        $this->our_account = '';
        $this->valueTimestamp = '';
        $this->entryTimestamp = '';
        $this->otherBankaccount = '';
        $this->otherBankaccountName = '';
        $this->transactionTitle = '';
        $this->currency = '';
        $this->transactionTypeLabel = '';
        $this->transactionCode = '';
        $this->transactionCodeDesc = '';
        $this->memo = '';
        $this->ourBankAccount = '';
        $this->ourBankAccountName = '';
        $this->ourBankAccountCode = '';
        $this->partnerType = null;
        $this->partnerId = null;
        $this->partnerDetailId = null;
        $this->oplabel = null;
        $this->fa_bank_accounts = null;
        $this->formData = null;
    }

    /**
     * Factory method for creating new line items
     *
     * @param array $data Initial data for the line item
     * @return self New BiLineItem instance
     * @throws InvalidBiLineItemException If required data is missing or invalid
     */
    public static function create(array $data): self
    {
        $transactionDc = $data['transactionDc'] ?? '';
        if (empty($transactionDc)) {
            throw new InvalidBiLineItemException('transactionDc cannot be empty');
        }

        $entity = new self($transactionDc);
        $entity->populateFromArray($data);
        return $entity;
    }

    /**
     * Factory method for loading from database
     *
     * @param array $row Database row data
     * @return self BiLineItem instance loaded from database
     */
    public static function fromDatabase(array $row): self
    {
        $entity = new self((string)($row['transactionDc'] ?? ''));
        $entity->id = (int)($row['id'] ?? 0);
        $entity->populateFromArray($row);
        return $entity;
    }

    /**
     * Populate properties from array
     *
     * @param array $data Data to populate from
     * @return void
     */
    private function populateFromArray(array $data): void
    {
        $this->id = (int)($data['id'] ?? $this->id);
        $this->our_account = (string)($data['our_account'] ?? '');
        $this->valueTimestamp = (string)($data['valueTimestamp'] ?? '');
        $this->entryTimestamp = (string)($data['entryTimestamp'] ?? '');
        $this->otherBankaccount = (string)($data['otherBankaccount'] ?? '');
        $this->otherBankaccountName = (string)($data['otherBankaccountName'] ?? '');
        $this->transactionTitle = (string)($data['transactionTitle'] ?? '');
        $this->status = (int)($data['status'] ?? 0);
        $this->currency = (string)($data['currency'] ?? '');
        $this->fa_trans_type = (int)($data['fa_trans_type'] ?? 0);
        $this->fa_trans_no = (int)($data['fa_trans_no'] ?? 0);
        $this->has_trans = (int)($data['has_trans'] ?? 1);
        $this->amount = (float)($data['amount'] ?? 0.00);
        $this->charge = (float)($data['charge'] ?? 0.00);
        $this->transactionTypeLabel = (string)($data['transactionTypeLabel'] ?? '');
        $this->vendor_list = $data['vendor_list'] ?? [];
        $this->partnerType = $data['partnerType'] ?? null;
        $this->partnerId = $data['partnerId'] ?? null;
        $this->partnerDetailId = $data['partnerDetailId'] ?? null;
        $this->oplabel = $data['oplabel'] ?? null;
        $this->matching_trans = $data['matching_trans'] ?? [];
        $this->days_spread = (int)($data['days_spread'] ?? 2);
        $this->transactionCode = (string)($data['transactionCode'] ?? '');
        $this->transactionCodeDesc = (string)($data['transactionCodeDesc'] ?? '');
        $this->optypes = $data['optypes'] ?? [];
        $this->memo = (string)($data['memo'] ?? '');
        $this->ourBankDetails = $data['ourBankDetails'] ?? [];
        $this->ourBankAccount = (string)($data['ourBankAccount'] ?? '');
        $this->ourBankAccountName = (string)($data['ourBankAccountName'] ?? '');
        $this->ourBankAccountCode = (string)($data['ourBankAccountCode'] ?? '');
        $this->fa_bank_accounts = $data['fa_bank_accounts'] ?? null;
        $this->matched = (bool)($data['matched'] ?? false);
        $this->created = (bool)($data['created'] ?? false);
        $this->formData = $data['formData'] ?? null;
    }

    // ============ MAGIC METHODS ============

    /**
     * Prevent calling non-existent methods (especially setters)
     * Immutable entities do not have setters
     *
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $arguments): never
    {
        throw new \BadMethodCallException("Method {$name}() does not exist on " . self::class);
    }

    // ============ GETTERS ============

    public function getId(): int { return $this->id; }
    public function getTransactionDc(): string { return $this->transactionDc; }
    public function getOurAccount(): string { return $this->our_account; }
    public function getValueTimestamp(): string { return $this->valueTimestamp; }
    public function getEntryTimestamp(): string { return $this->entryTimestamp; }
    public function getOtherBankaccount(): string { return $this->otherBankaccount; }
    public function getOtherBankaccountName(): string { return $this->otherBankaccountName; }
    public function getTransactionTitle(): string { return $this->transactionTitle; }
    public function getStatus(): int { return $this->status; }
    public function getCurrency(): string { return $this->currency; }
    public function getFaTransType(): int { return $this->fa_trans_type; }
    public function getFaTransNo(): int { return $this->fa_trans_no; }
    public function getHasTrans(): int { return $this->has_trans; }
    public function getAmount(): float { return $this->amount; }
    public function getCharge(): float { return $this->charge; }
    public function getTransactionTypeLabel(): string { return $this->transactionTypeLabel; }
    public function getVendorList(): array { return $this->vendor_list; }
    public function getPartnerType(): ?string { return $this->partnerType; }
    public function getPartnerId(): ?int { return $this->partnerId; }
    public function getPartnerDetailId(): ?int { return $this->partnerDetailId; }
    public function getOpLabel(): ?string { return $this->oplabel; }
    public function getMatchingTrans(): array { return $this->matching_trans; }
    public function getDaysSpread(): int { return $this->days_spread; }
    public function getTransactionCode(): string { return $this->transactionCode; }
    public function getTransactionCodeDesc(): string { return $this->transactionCodeDesc; }
    public function getOptypes(): array { return $this->optypes; }
    public function getMemo(): string { return $this->memo; }
    public function getOurBankDetails(): array { return $this->ourBankDetails; }
    public function getOurBankAccount(): string { return $this->ourBankAccount; }
    public function getOurBankAccountName(): string { return $this->ourBankAccountName; }
    public function getOurBankAccountCode(): string { return $this->ourBankAccountCode; }
    public function getFaBankAccounts(): ?object { return $this->fa_bank_accounts; }
    public function isMatched(): bool { return $this->matched; }
    public function isCreated(): bool { return $this->created; }
    public function getFormData(): ?object { return $this->formData; }

    // ============ STATE TRANSITIONS (Return new instances) ============

    /**
     * Return new instance with updated matched status
     *
     * @param bool $matched New matched status
     * @return self New instance with updated status
     */
    public function withMatchedStatus(bool $matched): self
    {
        $new = clone $this;
        $new->matched = $matched;
        return $new;
    }

    /**
     * Return new instance with updated created status
     *
     * @param bool $created New created status
     * @return self New instance with updated status
     */
    public function withCreatedStatus(bool $created): self
    {
        $new = clone $this;
        $new->created = $created;
        return $new;
    }

    /**
     * Return new instance with updated partner info
     *
     * @param string|null $partnerType Partner type
     * @param int|null $partnerId Partner ID
     * @param int|null $partnerDetailId Partner detail ID
     * @return self New instance with updated partner info
     */
    public function withPartnerInfo(?string $partnerType, ?int $partnerId, ?int $partnerDetailId = null): self
    {
        $new = clone $this;
        $new->partnerType = $partnerType;
        $new->partnerId = $partnerId;
        $new->partnerDetailId = $partnerDetailId;
        return $new;
    }

    /**
     * Return new instance with updated FA transaction reference
     *
     * @param int $faTransType FA transaction type
     * @param int $faTransNo FA transaction number
     * @return self New instance with updated FA reference
     */
    public function withFaTransactionReference(int $faTransType, int $faTransNo): self
    {
        $new = clone $this;
        $new->fa_trans_type = $faTransType;
        $new->fa_trans_no = $faTransNo;
        return $new;
    }

    /**
     * Return new instance with updated status
     *
     * @param int $status New status code
     * @return self New instance with updated status
     */
    public function withStatus(int $status): self
    {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }

    // ============ SERIALIZATION ============

    /**
     * Convert to database array format
     *
     * @return array Array suitable for database insertion/update
     */
    public function toDatabase(): array
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
     * Convert to array format
     *
     * @return array Array representation of the entity
     */
    public function toArray(): array
    {
        return $this->toDatabase();
    }
}
