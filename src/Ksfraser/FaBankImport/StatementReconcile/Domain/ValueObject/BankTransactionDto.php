<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
 * DTO representing a single FA bank transaction for matching purposes.
 *
 * Keeps the domain layer decoupled from FA's internal data structures.
 * Only carries the fields required by the matching engine.
 *
 * `faTransType` and `faTransNo` together form FA's composite primary key
 * for `0_bank_trans`.  They are nullable only when the DTO is used in unit
 * tests that pre-date the FA-native-reconciliation requirement; all production
 * code that loads from `0_bank_trans` must populate both fields.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject
 * @author  Kevin Fraser
 */
final class BankTransactionDto
{
    /** @var int Session-local sequence ID (assigned during load; not an FA PK). */
    private $id;

    /** @var int|null FA transaction type code (e.g. 41=bank payment, 42=bank deposit). */
    private $faTransType;

    /** @var int|null FA transaction number (from 0_bank_trans.trans_no). */
    private $faTransNo;

    /** @var \DateTimeImmutable Transaction date. */
    private $date;

    /** @var string Amount as decimal string. */
    private $amount;

    /** @var string Transaction description / memo. */
    private $description;

    /** @var string 'credit' or 'debit'. */
    private $type;

    /**
     * @param int                $id          Session-local sequence id.
     * @param \DateTimeImmutable $date
     * @param string             $amount      Non-negative decimal string.
     * @param string             $description
     * @param string             $type        'credit' or 'debit'.
     * @param int|null           $faTransType FA transaction type (nullable for legacy tests).
     * @param int|null           $faTransNo   FA transaction number (nullable for legacy tests).
     */
    public function __construct(
        int $id,
        \DateTimeImmutable $date,
        string $amount,
        string $description,
        string $type,
        ?int $faTransType = null,
        ?int $faTransNo = null
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException('BankTransactionDto id must be a positive integer');
        }
        if (!is_numeric($amount) || (float) $amount < 0) {
            throw new InvalidArgumentException(
                'BankTransactionDto amount must be non-negative numeric, got: ' . $amount
            );
        }
        if (!in_array($type, ['credit', 'debit'], true)) {
            throw new InvalidArgumentException(
                'BankTransactionDto type must be "credit" or "debit", got: ' . $type
            );
        }

        $this->id          = $id;
        $this->date        = $date;
        $this->amount      = $amount;
        $this->description = $description;
        $this->type        = $type;
        $this->faTransType = $faTransType;
        $this->faTransNo   = $faTransNo;
    }

    /**
     * Build from an associative array (e.g. fetched from FA DB row).
     * Expected keys: id, date (YYYY-MM-DD), amount, description, type,
     * fa_trans_type (optional), fa_trans_no (optional)
     *
     * @param array $row
     * @return self
     */
    public static function fromArray(array $row): self
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($row['date'] ?? ''));
        if ($date === false) {
            throw new InvalidArgumentException(
                'BankTransactionDto::fromArray date must be YYYY-MM-DD, got: ' . ($row['date'] ?? '')
            );
        }

        return new self(
            (int)    ($row['id'] ?? 0),
            $date,
            (string) ($row['amount'] ?? '0'),
            (string) ($row['description'] ?? ''),
            (string) ($row['type'] ?? 'debit'),
            isset($row['fa_trans_type']) ? (int) $row['fa_trans_type'] : null,
            isset($row['fa_trans_no'])   ? (int) $row['fa_trans_no']   : null
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * FA transaction type code (0_bank_trans.type).
     * Null only in unit-test fixtures that pre-date the FA-native-reconciliation requirement.
     */
    public function getFaTransType(): ?int
    {
        return $this->faTransType;
    }

    /**
     * FA transaction number (0_bank_trans.trans_no).
     * Null only in unit-test fixtures that pre-date the FA-native-reconciliation requirement.
     */
    public function getFaTransNo(): ?int
    {
        return $this->faTransNo;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAmountFloat(): float
    {
        return (float) $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
