<?php

declare(strict_types=1);
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
 * @BABOK CLASS ROLLUP: MatchedPair — 10 methods; FR-007 (10); BR-004 (10)
 * @BABOK RTM: FR-007 / BR-004 / ARCH-010; file: StatementReconcile/Domain/ValueObject/MatchedPair.php; coverage: docs/TestCases/ (file rollup)
 * A pair representing one statement line matched to one FA bank transaction.
 *
 * Immutable value object.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject
 * @author  Kevin Fraser
 */
final class MatchedPair
{
    /** @var string Statement line identifier. */
    private $statementLineId;

    /** @var int Session-local sequence ID for the bank transaction. */
    private $bankTransactionId;

    /**
     * @var float Confidence score in [0.0, 1.0].
     * 1.0 = exact match on amount + date + description.
     */
    private $matchConfidence;

    /** @var string[] Names of rules that contributed to the match. */
    private $rulesMatched;

    /**
     * FA transaction type code (0_bank_trans.type).
     * Populated when loading from FA's native bank_trans table.
     * Null only in legacy unit-test fixtures.
     *
     * @var int|null
     */
    private $faTransType;

    /**
     * FA transaction number (0_bank_trans.trans_no).
     * Populated when loading from FA's native bank_trans table.
     * Null only in legacy unit-test fixtures.
     *
     * @var int|null
     */
    private $faTransNo;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: __construct
     * @param string   $statementLineId
     * @param int      $bankTransactionId Session-local sequence id.
     * @param float    $matchConfidence   Must be in [0.0, 1.0].
     * @param string[] $rulesMatched
     * @param int|null $faTransType       FA 0_bank_trans.type (null for legacy tests).
     * @param int|null $faTransNo         FA 0_bank_trans.trans_no (null for legacy tests).
     */
    public function __construct(
        string $statementLineId,
        int $bankTransactionId,
        float $matchConfidence,
        array $rulesMatched = [],
        ?int $faTransType = null,
        ?int $faTransNo = null
    ) {
        if (trim($statementLineId) === '') {
            throw new InvalidArgumentException('MatchedPair: statementLineId cannot be empty');
        }
        if ($bankTransactionId <= 0) {
            throw new InvalidArgumentException(
                'MatchedPair: bankTransactionId must be a positive integer, got: ' . $bankTransactionId
            );
        }
        if ($matchConfidence < 0.0 || $matchConfidence > 1.0) {
            throw new InvalidArgumentException(
                'MatchedPair: matchConfidence must be in [0.0, 1.0], got: ' . $matchConfidence
            );
        }

        $this->statementLineId   = $statementLineId;
        $this->bankTransactionId = $bankTransactionId;
        $this->matchConfidence   = $matchConfidence;
        $this->rulesMatched      = array_values(array_map('strval', $rulesMatched));
        $this->faTransType       = $faTransType;
        $this->faTransNo         = $faTransNo;
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: fromArray
     * @param array $data Keys: statement_line_id, bank_transaction_id, match_confidence,
     *                    rules_matched, fa_trans_type (optional), fa_trans_no (optional)
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['statement_line_id'] ?? ''),
            (int)    ($data['bank_transaction_id'] ?? 0),
            (float)  ($data['match_confidence'] ?? 0.0),
            (array)  ($data['rules_matched'] ?? []),
            isset($data['fa_trans_type']) ? (int) $data['fa_trans_type'] : null,
            isset($data['fa_trans_no'])   ? (int) $data['fa_trans_no']   : null
        );
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: getStatementLineId */
    public function getStatementLineId(): string
    {
        return $this->statementLineId;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: getBankTransactionId */
    public function getBankTransactionId(): int
    {
        return $this->bankTransactionId;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: getMatchConfidence */
    public function getMatchConfidence(): float
    {
        return $this->matchConfidence;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: isHighConfidence */
    public function isHighConfidence(float $threshold = 0.9): bool
    {
        return $this->matchConfidence >= $threshold;
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: getRulesMatched
     * @return string[]
     */
    public function getRulesMatched(): array
    {
        return $this->rulesMatched;
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: getFaTransType
     * FA transaction type code (0_bank_trans.type).
     * Used by ReconciliationCommitService to build the UPDATE WHERE clause.
     */
    public function getFaTransType(): ?int
    {
        return $this->faTransType;
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: getFaTransNo
     * FA transaction number (0_bank_trans.trans_no).
     * Used by ReconciliationCommitService to build the UPDATE WHERE clause.
     */
    public function getFaTransNo(): ?int
    {
        return $this->faTransNo;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: toArray */
    public function toArray(): array
    {
        return [
            'statement_line_id'   => $this->statementLineId,
            'bank_transaction_id' => $this->bankTransactionId,
            'match_confidence'    => $this->matchConfidence,
            'rules_matched'       => $this->rulesMatched,
            'fa_trans_type'       => $this->faTransType,
            'fa_trans_no'         => $this->faTransNo,
        ];
    }
}
