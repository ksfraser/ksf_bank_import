<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

use Ksfraser\FaBankImport\Shared\Exceptions\InvalidRepositoryStateException;

/**
 * BankPartner - Immutable entity for bank partner (counterparty) information
 * 
 * Represents a partner/counterparty linked to transactions in the bank import.
 * Maps OFX counterparty data to FrontAccounting partner records (Customers, Suppliers, etc.).
 * Immutable after creation.
 * 
 * Invariants:
 * - faPartnerId must be > 0
 * - partnerType must be one of valid FA types (Customer, Supplier, Employee, etc.)
 * - bankCode uniquely identifies the partner within bank data
 * 
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @stable - Part of Shared Kernel API
 */
final class BankPartner
{
    private int $id;
    private int $faPartnerId;
    private string $partnerType;
    private string $bankCode;
    private string $bankName;
    private string $matchReason;
    private int $matchConfidence;
    private int $transactionCount;

    /**
     * Private constructor - use factory methods instead
     */
    private function __construct(
        int $faPartnerId,
        string $partnerType,
        string $bankCode
    ) {
        if ($faPartnerId <= 0) {
            throw new InvalidRepositoryStateException('faPartnerId must be > 0');
        }
        if (empty($bankCode)) {
            throw new InvalidRepositoryStateException('bankCode cannot be empty');
        }

        $this->id = 0;
        $this->faPartnerId = $faPartnerId;
        $this->partnerType = $partnerType;
        $this->bankCode = $bankCode;
        $this->bankName = '';
        $this->matchReason = '';
        $this->matchConfidence = 0;
        $this->transactionCount = 0;
    }

    /**
     * Create a new partner
     */
    public static function create(
        int $faPartnerId,
        string $partnerType,
        string $bankCode
    ): self {
        return new self($faPartnerId, $partnerType, $bankCode);
    }

    /**
     * Recreate partner from database row
     */
    public static function fromDatabase(array $row): self {
        $partner = new self(
            (int)($row['partner_id'] ?? (int)($row['faPartnerId'] ?? 0)),
            (string)($row['partner_type'] ?? ''),
            (string)($row['bank_code'] ?? $row['bankCode'] ?? '')
        );

        $partner->id = (int)($row['id'] ?? 0);
        $partner->bankName = (string)($row['bank_name'] ?? $row['bankName'] ?? '');
        $partner->matchReason = (string)($row['match_reason'] ?? $row['matchReason'] ?? '');
        $partner->matchConfidence = (int)($row['match_confidence'] ?? $row['matchConfidence'] ?? 0);
        $partner->transactionCount = (int)($row['transaction_count'] ?? $row['transactionCount'] ?? 0);

        return $partner;
    }

    // Getters only - no setters (immutable)

    public function getId(): int { return $this->id; }
    public function getFAPartnerId(): int { return $this->faPartnerId; }
    public function getPartnerType(): string { return $this->partnerType; }
    public function getBankCode(): string { return $this->bankCode; }
    public function getBankName(): string { return $this->bankName; }
    public function getMatchReason(): string { return $this->matchReason; }
    public function getMatchConfidence(): int { return $this->matchConfidence; }
    public function getTransactionCount(): int { return $this->transactionCount; }

    /**
     * Check if match confidence is high
     */
    public function isHighConfidenceMatch(): bool {
        return $this->matchConfidence >= 80;
    }

    /**
     * Export to database-ready array
     */
    public function toDatabase(): array {
        return [
            'id' => $this->id,
            'partner_id' => $this->faPartnerId,
            'partner_type' => $this->partnerType,
            'bank_code' => $this->bankCode,
            'bank_name' => $this->bankName,
            'match_reason' => $this->matchReason,
            'match_confidence' => $this->matchConfidence,
            'transaction_count' => $this->transactionCount,
        ];
    }
}
