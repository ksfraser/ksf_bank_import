<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Represents a single line item from a parsed credit-card statement.
 *
 * Immutable value object: identity is determined by all field values.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject
 * @author  Kevin Fraser
 */
final class StatementLine
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT  = 'debit';

    /** @var string Unique identifier for this line within the statement. */
    private $lineId;

    /** @var \DateTimeImmutable Transaction date. */
    private $date;

    /** @var string Transaction description as extracted. */
    private $description;

    /**
     * @var string Amount as a decimal string (e.g. "123.45").
     * Stored as string to avoid floating-point precision issues.
     */
    private $amount;

    /** @var string One of TYPE_CREDIT or TYPE_DEBIT. */
    private $type;

    /** @var string Raw text line from OCR for audit purposes. */
    private $rawText;

    /**
     * @param string            $lineId
     * @param \DateTimeImmutable $date
     * @param string            $description
     * @param string            $amount       Decimal string, must be >= 0.
     * @param string            $type         'credit' or 'debit'.
     * @param string            $rawText
     */
    public function __construct(
        string $lineId,
        \DateTimeImmutable $date,
        string $description,
        string $amount,
        string $type,
        string $rawText
    ) {
        if (trim($lineId) === '') {
            throw new InvalidArgumentException('StatementLine lineId cannot be empty');
        }
        if (!in_array($type, [self::TYPE_CREDIT, self::TYPE_DEBIT], true)) {
            throw new InvalidArgumentException(
                'StatementLine type must be "credit" or "debit", got: ' . $type
            );
        }
        if (!is_numeric($amount) || (float) $amount < 0) {
            throw new InvalidArgumentException(
                'StatementLine amount must be a non-negative numeric string, got: ' . $amount
            );
        }

        $this->lineId      = $lineId;
        $this->date        = $date;
        $this->description = $description;
        $this->amount      = $amount;
        $this->type        = $type;
        $this->rawText     = $rawText;
    }

    /**
     * Factory: create from an associative array (e.g. decoded JSON).
     *
     * Expected keys: line_id, date (YYYY-MM-DD), description, amount, type, raw_text
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $required = ['line_id', 'date', 'description', 'amount', 'type'];
        foreach ($required as $key) {
            if (!isset($data[$key]) || (string) $data[$key] === '') {
                throw new InvalidArgumentException(
                    'StatementLine::fromArray missing required key: ' . $key
                );
            }
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data['date']);
        if ($date === false) {
            throw new InvalidArgumentException(
                'StatementLine date must be YYYY-MM-DD, got: ' . $data['date']
            );
        }

        return new self(
            (string) $data['line_id'],
            $date,
            (string) $data['description'],
            (string) $data['amount'],
            (string) $data['type'],
            (string) ($data['raw_text'] ?? '')
        );
    }

    public function getLineId(): string
    {
        return $this->lineId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Returns the amount as a float for arithmetic. Use getAmount() for persistence.
     */
    public function getAmountFloat(): float
    {
        return (float) $this->amount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function getRawText(): string
    {
        return $this->rawText;
    }

    public function toArray(): array
    {
        return [
            'line_id'     => $this->lineId,
            'date'        => $this->date->format('Y-m-d'),
            'description' => $this->description,
            'amount'      => $this->amount,
            'type'        => $this->type,
            'raw_text'    => $this->rawText,
        ];
    }
}
