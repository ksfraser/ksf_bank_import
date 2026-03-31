<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

use Ksfraser\Exceptions\Domain\InvalidRepositoryStateException;

/**
 * BiLineItem - Immutable domain entity for transaction line items
 * 
 * Represents a single GL account line item for a bank transaction.
 * Each transaction can have multiple line items for multi-leg journals.
 * Immutable after creation.
 * 
 * Invariants:
 * - biTransactionId must be > 0
 * - amount cannot be zero (must debit or credit)
 * - faGlAccount must be a valid FA account
 * 
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @stable - Part of Shared Kernel API
 */
final class BiLineItem
{
    private int $id;
    private int $biTransactionId;
    private float $amount;
    private ?int $faGlAccount;
    private string $faMemo;
    private int $status;
    private int $faTransType;
    private int $faTransNo;

    /**
     * Private constructor - use factory methods instead
     */
    private function __construct(
        int $biTransactionId,
        float $amount
    ) {
        if ($biTransactionId <= 0) {
            throw InvalidRepositoryStateException::stateFailed('biTransactionId must be > 0');
        }
        if ($amount == 0) {
            throw InvalidRepositoryStateException::zeroValueNotAllowed('amount');
        }

        $this->id = 0;
        $this->biTransactionId = $biTransactionId;
        $this->amount = $amount;
        $this->faGlAccount = null;
        $this->faMemo = '';
        $this->status = 0;
        $this->faTransType = 0;
        $this->faTransNo = 0;
    }

    /**
     * Create a new line item
     */
    public static function create(
        int $biTransactionId,
        float $amount
    ): self {
        return new self($biTransactionId, $amount);
    }

    /**
     * Recreate line item from database row
     */
    public static function fromDatabase(array $row): self {
        $item = new self(
            (int)($row['bi_transaction_id'] ?? (int)($row['biTransactionId'] ?? 0)),
            (float)($row['amount'] ?? 0)
        );

        $item->id = (int)($row['id'] ?? 0);
        $item->faGlAccount = isset($row['fa_gl_account']) ? (int)$row['fa_gl_account'] : null;
        $item->faMemo = (string)($row['fa_memo'] ?? '');
        $item->status = (int)($row['status'] ?? 0);
        $item->faTransType = (int)($row['fa_trans_type'] ?? 0);
        $item->faTransNo = (int)($row['fa_trans_no'] ?? 0);

        return $item;
    }

    // Getters only - no setters (immutable)

    public function getId(): int { return $this->id; }
    public function getBiTransactionId(): int { return $this->biTransactionId; }
    public function getAmount(): float { return $this->amount; }
    public function getFAGlAccount(): ?int { return $this->faGlAccount; }
    public function getFAMemo(): string { return $this->faMemo; }
    public function getStatus(): int { return $this->status; }
    public function getFATransType(): int { return $this->faTransType; }
    public function getFATransNo(): int { return $this->faTransNo; }

    /**
     * Check if this is a debit (amount > 0)
     */
    public function isDebit(): bool {
        return $this->amount > 0;
    }

    /**
     * Check if this is a credit (amount < 0)
     */
    public function isCredit(): bool {
        return $this->amount < 0;
    }

    /**
     * Get absolute amount
     */
    public function getAbsoluteAmount(): float {
        return abs($this->amount);
    }

    /**
     * Export to database-ready array
     */
    public function toDatabase(): array {
        return [
            'id' => $this->id,
            'bi_transaction_id' => $this->biTransactionId,
            'amount' => $this->amount,
            'fa_gl_account' => $this->faGlAccount,
            'fa_memo' => $this->faMemo,
            'status' => $this->status,
            'fa_trans_type' => $this->faTransType,
            'fa_trans_no' => $this->faTransNo,
        ];
    }
}
