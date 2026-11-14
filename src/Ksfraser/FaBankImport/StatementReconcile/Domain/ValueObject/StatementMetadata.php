<?php

declare(strict_types=1);
// @BABOK RTM: FR-007 / BR-004 / ARCH-010 (domain layer)
/** @BABOK RTM: FR-007 / BR-004 / ARCH-010*/

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001

 * Statement-level metadata extracted from a CC statement PDF.
 *
 * Immutable value object. All monetary amounts stored as decimal strings
 * to avoid floating-point precision loss.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject
 * @author  Kevin Fraser
 */
final class StatementMetadata
{
    /** @var string|null */
    private $accountIdentifier;

    /** @var \DateTimeImmutable */
    private $statementStartDate;

    /** @var \DateTimeImmutable */
    private $statementEndDate;

    /** @var string Opening balance as decimal string. */
    private $openingBalance;

    /** @var string Closing balance as decimal string. */
    private $closingBalance;

    /** @var \DateTimeImmutable|null */
    private $dueDate;

    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     * @param string|null            $accountIdentifier Last-4 or account label, may be null.
     * @param \DateTimeImmutable      $statementStartDate
     * @param \DateTimeImmutable      $statementEndDate
     * @param string                 $openingBalance  Decimal string, may be negative.
     * @param string                 $closingBalance  Decimal string, may be negative.
     * @param \DateTimeImmutable|null $dueDate         Optional payment due date.
     */
    public function __construct(
        ?string $accountIdentifier,
        \DateTimeImmutable $statementStartDate,
        \DateTimeImmutable $statementEndDate,
        string $openingBalance,
        string $closingBalance,
        ?\DateTimeImmutable $dueDate = null
    ) {
        if ($statementEndDate < $statementStartDate) {
            throw new InvalidArgumentException(
                'StatementMetadata: end date cannot be before start date'
            );
        }
        if (!is_numeric($openingBalance)) {
            throw new InvalidArgumentException(
                'StatementMetadata: openingBalance must be numeric, got: ' . $openingBalance
            );
        }
        if (!is_numeric($closingBalance)) {
            throw new InvalidArgumentException(
                'StatementMetadata: closingBalance must be numeric, got: ' . $closingBalance
            );
        }

        $this->accountIdentifier  = $accountIdentifier;
        $this->statementStartDate = $statementStartDate;
        $this->statementEndDate   = $statementEndDate;
        $this->openingBalance     = $openingBalance;
        $this->closingBalance     = $closingBalance;
        $this->dueDate            = $dueDate;
    }

    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     * Factory: build from an associative array with keys:
     *   account_identifier (nullable string)
     *   statement_start_date (YYYY-MM-DD)
     *   statement_end_date (YYYY-MM-DD)
     *   opening_balance (numeric string)
     *   closing_balance (numeric string)
     *   due_date (YYYY-MM-DD, optional/nullable)
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        foreach (['statement_start_date', 'statement_end_date', 'opening_balance', 'closing_balance'] as $key) {
            if (!isset($data[$key]) || (string) $data[$key] === '') {
                throw new InvalidArgumentException(
                    'StatementMetadata::fromArray missing required key: ' . $key
                );
            }
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $data['statement_start_date']);
        $end   = \DateTimeImmutable::createFromFormat('Y-m-d', $data['statement_end_date']);

        if ($start === false || $end === false) {
            throw new InvalidArgumentException(
                'StatementMetadata dates must be in YYYY-MM-DD format'
            );
        }

        $dueDate = null;
        if (!empty($data['due_date'])) {
            $dueDate = \DateTimeImmutable::createFromFormat('Y-m-d', $data['due_date']);
            if ($dueDate === false) {
                throw new InvalidArgumentException(
                    'StatementMetadata due_date must be in YYYY-MM-DD format, got: ' . $data['due_date']
                );
            }
        }

        return new self(
            isset($data['account_identifier']) ? (string) $data['account_identifier'] : null,
            $start,
            $end,
            (string) $data['opening_balance'],
            (string) $data['closing_balance'],
            $dueDate
        );
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getAccountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getStatementStartDate(): \DateTimeImmutable
    {
        return $this->statementStartDate;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getStatementEndDate(): \DateTimeImmutable
    {
        return $this->statementEndDate;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getOpeningBalance(): string
    {
        return $this->openingBalance;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getOpeningBalanceFloat(): float
    {
        return (float) $this->openingBalance;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getClosingBalance(): string
    {
        return $this->closingBalance;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getClosingBalanceFloat(): float
    {
        return (float) $this->closingBalance;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function toArray(): array
    {
        return [
            'account_identifier'   => $this->accountIdentifier,
            'statement_start_date' => $this->statementStartDate->format('Y-m-d'),
            'statement_end_date'   => $this->statementEndDate->format('Y-m-d'),
            'opening_balance'      => $this->openingBalance,
            'closing_balance'      => $this->closingBalance,
            'due_date'             => $this->dueDate !== null ? $this->dueDate->format('Y-m-d') : null,
        ];
    }
}
