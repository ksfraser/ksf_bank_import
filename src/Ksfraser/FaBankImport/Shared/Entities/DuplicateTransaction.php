<?php

namespace Ksfraser\FaBankImport\Shared\Entities;

use DateTime;

/**
 * DuplicateTransaction Entity
 *
 * Represents a transaction flagged as a duplicate, stored in staging
 * pending review and decision.
 *
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @since 2026-04-08
 */
final class DuplicateTransaction
{
    /**
     * Decision status enumeration
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_INVESTIGATE = 'INVESTIGATE';

    private const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_INVESTIGATE,
    ];

    /**
     * Match type enumeration
     */
    public const MATCH_TYPE_EXACT = 'EXACT_MATCH';
    public const MATCH_TYPE_FUZZY = 'FUZZY_MATCH';
    public const MATCH_TYPE_CODE_AND_AMOUNT = 'CODE_AND_AMOUNT';

    /**
     * Partner type enumeration
     */
    public const PARTNER_TYPE_SUPPLIER = 'SUPPLIER';
    public const PARTNER_TYPE_CUSTOMER = 'CUSTOMER';
    public const PARTNER_TYPE_BANK_TRANSFER = 'BANK_TRANSFER';
    public const PARTNER_TYPE_QUICK_ENTRY = 'QUICK_ENTRY';

    /**
     * @var int Unique identifier
     */
    private int $duplicateId;

    /**
     * @var string Transaction code from bi_transactions
     */
    private string $transactionCode;

    /**
     * @var DateTime Transaction date
     */
    private DateTime $transDate;

    /**
     * @var float Transaction amount
     */
    private float $amount;

    /**
     * @var string Counterparty name
     */
    private string $counterpartyName;

    /**
     * @var string|null Transaction description
     */
    private ?string $description = null;

    /**
     * @var string|null Bank reference number
     */
    private ?string $referenceNumber = null;

    /**
     * @var int Bank account ID
     */
    private int $bankAccountId;

    /**
     * @var string|null Partner type (SUPPLIER, CUSTOMER, etc.)
     */
    private ?string $partnerType = null;

    /**
     * @var string|null Bank code
     */
    private ?string $bankCode = null;

    /**
     * @var string|null Code of matched transaction
     */
    private ?string $matchedToCode = null;

    /**
     * @var float|null Confidence score (0-100)
     */
    private ?float $confidenceScore = null;

    /**
     * @var string|null Match type
     */
    private ?string $matchType = null;

    /**
     * @var string Decision status
     */
    private string $decisionStatus = self::STATUS_PENDING;

    /**
     * @var string|null User who made the decision
     */
    private ?string $decidedBy = null;

    /**
     * @var DateTime|null When decision was made
     */
    private ?DateTime $decidedAt = null;

    /**
     * @var string|null Reason for decision
     */
    private ?string $reason = null;

    /**
     * @var string|null Detailed notes
     */
    private ?string $notes = null;

    /**
     * @var DateTime When record was created
     */
    private DateTime $createdAt;

    /**
     * @var DateTime Last update time
     */
    private DateTime $updatedAt;

    /**
     * Constructor
     *
     * @param string $transactionCode
     * @param DateTime $transDate
     * @param float $amount
     * @param string $counterpartyName
     * @param int $bankAccountId
     */
    public function __construct(
        string $transactionCode,
        DateTime $transDate,
        float $amount,
        string $counterpartyName,
        int $bankAccountId
    ) {
        $this->transactionCode = $transactionCode;
        $this->transDate = $transDate;
        $this->amount = $amount;
        $this->counterpartyName = $counterpartyName;
        $this->bankAccountId = $bankAccountId;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * Create from database row
     *
     * @param array $row
     * @return static
     */
    public static function fromDatabaseRow(array $row): static
    {
        $entity = new static(
            $row['transaction_code'],
            new DateTime($row['trans_date']),
            (float)$row['amount'],
            $row['counterparty_name'],
            (int)$row['bank_account_id']
        );

        $entity->duplicateId = (int)$row['duplicate_id'];
        $entity->description = $row['description'] ?? null;
        $entity->referenceNumber = $row['reference_number'] ?? null;
        $entity->partnerType = $row['partner_type'] ?? null;
        $entity->bankCode = $row['bank_code'] ?? null;
        $entity->matchedToCode = $row['matched_to_code'] ?? null;
        $entity->confidenceScore = $row['confidence_score'] ? (float)$row['confidence_score'] : null;
        $entity->matchType = $row['match_type'] ?? null;
        $entity->decisionStatus = $row['decision_status'];
        $entity->decidedBy = $row['decided_by'] ?? null;
        $entity->decidedAt = $row['decided_at'] ? new DateTime($row['decided_at']) : null;
        $entity->reason = $row['reason'] ?? null;
        $entity->notes = $row['notes'] ?? null;
        $entity->createdAt = new DateTime($row['created_at']);
        $entity->updatedAt = new DateTime($row['updated_at']);

        return $entity;
    }

    /**
     * Get ID
     */
    public function getDuplicateId(): int
    {
        return $this->duplicateId;
    }

    /**
     * Get transaction code
     */
    public function getTransactionCode(): string
    {
        return $this->transactionCode;
    }

    /**
     * Get transaction date
     */
    public function getTransDate(): DateTime
    {
        return $this->transDate;
    }

    /**
     * Get amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get counterparty name
     */
    public function getCounterpartyName(): string
    {
        return $this->counterpartyName;
    }

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get bank account ID
     */
    public function getBankAccountId(): int
    {
        return $this->bankAccountId;
    }

    /**
     * Get partner type
     */
    public function getPartnerType(): ?string
    {
        return $this->partnerType;
    }

    /**
     * Set partner type
     */
    public function setPartnerType(?string $partnerType): self
    {
        $this->partnerType = $partnerType;
        return $this;
    }

    /**
     * Get matched transaction code
     */
    public function getMatchedToCode(): ?string
    {
        return $this->matchedToCode;
    }

    /**
     * Set matched transaction code and confidence
     */
    public function setMatchDetails(string $matchedToCode, float $confidenceScore, string $matchType): self
    {
        $this->matchedToCode = $matchedToCode;
        $this->confidenceScore = $confidenceScore;
        $this->matchType = $matchType;
        return $this;
    }

    /**
     * Get decision status
     */
    public function getDecisionStatus(): string
    {
        return $this->decisionStatus;
    }

    /**
     * approve duplicate
     */
    public function approve(string $decidedBy, ?string $reason = null): self
    {
        if ($this->decisionStatus !== self::STATUS_PENDING) {
            throw new \DomainException("Cannot approve a {$this->decisionStatus} duplicate");
        }

        $this->decisionStatus = self::STATUS_APPROVED;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = new DateTime();
        $this->reason = $reason ?? 'Approved';
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * Reject duplicate
     */
    public function reject(string $decidedBy, string $reason): self
    {
        if ($this->decisionStatus !== self::STATUS_PENDING) {
            throw new \DomainException("Cannot reject a {$this->decisionStatus} duplicate");
        }

        $this->decisionStatus = self::STATUS_REJECTED;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = new DateTime();
        $this->reason = $reason;
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * Flag for investigation
     */
    public function flagForInvestigation(string $decidedBy, string $reason): self
    {
        if ($this->decisionStatus !== self::STATUS_PENDING) {
            throw new \DomainException("Cannot investigate a {$this->decisionStatus} duplicate");
        }

        $this->decisionStatus = self::STATUS_INVESTIGATE;
        $this->decidedBy = $decidedBy;
        $this->reason = $reason;
        $this->notes = $reason; // Store detailed investigation reason
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * Is pending review
     */
    public function isPending(): bool
    {
        return $this->decisionStatus === self::STATUS_PENDING;
    }

    /**
     * Is approved
     */
    public function isApproved(): bool
    {
        return $this->decisionStatus === self::STATUS_APPROVED;
    }

    /**
     * Convert to array for persistence/API
     */
    public function toArray(): array
    {
        return [
            'duplicate_id' => $this->duplicateId ?? null,
            'transaction_code' => $this->transactionCode,
            'trans_date' => $this->transDate->format('Y-m-d'),
            'amount' => $this->amount,
            'counterparty_name' => $this->counterpartyName,
            'description' => $this->description,
            'reference_number' => $this->referenceNumber,
            'bank_account_id' => $this->bankAccountId,
            'partner_type' => $this->partnerType,
            'bank_code' => $this->bankCode,
            'matched_to_code' => $this->matchedToCode,
            'confidence_score' => $this->confidenceScore,
            'match_type' => $this->matchType,
            'decision_status' => $this->decisionStatus,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt ? $this->decidedAt->format('Y-m-d H:i:s') : null,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get created at
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Get updated at
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Get decided by
     */
    public function getDecidedBy(): ?string
    {
        return $this->decidedBy;
    }

    /**
     * Get confidence score
     */
    public function getConfidenceScore(): ?float
    {
        return $this->confidenceScore;
    }
}
