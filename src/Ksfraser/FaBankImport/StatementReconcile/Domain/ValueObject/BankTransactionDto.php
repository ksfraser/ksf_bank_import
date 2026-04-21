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
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject
 * @author  Kevin Fraser
 */
final class BankTransactionDto
{
    /** @var int FA bank transaction ID. */
    private $id;

    /** @var \DateTimeImmutable Transaction date. */
    private $date;

    /** @var string Amount as decimal string. */
    private $amount;

    /** @var string Transaction description / memo. */
    private $description;

    /** @var string 'credit' or 'debit'. */
    private $type;

    /**
     * @param int               $id
     * @param \DateTimeImmutable $date
     * @param string            $amount      Non-negative decimal string.
     * @param string            $description
     * @param string            $type        'credit' or 'debit'.
     */
    public function __construct(
        int $id,
        \DateTimeImmutable $date,
        string $amount,
        string $description,
        string $type
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
    }

    /**
     * Build from an associative array (e.g. fetched from FA DB row).
     * Expected keys: id, date (YYYY-MM-DD), amount, description, type
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
            (string) ($row['type'] ?? 'debit')
        );
    }

    public function getId(): int
    {
        return $this->id;
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
