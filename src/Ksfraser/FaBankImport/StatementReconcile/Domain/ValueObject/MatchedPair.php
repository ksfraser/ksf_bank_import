<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
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

    /** @var int FA bank transaction ID. */
    private $bankTransactionId;

    /**
     * @var float Confidence score in [0.0, 1.0].
     * 1.0 = exact match on amount + date + description.
     */
    private $matchConfidence;

    /** @var string[] Names of rules that contributed to the match. */
    private $rulesMatched;

    /**
     * @param string   $statementLineId
     * @param int      $bankTransactionId
     * @param float    $matchConfidence   Must be in [0.0, 1.0].
     * @param string[] $rulesMatched
     */
    public function __construct(
        string $statementLineId,
        int $bankTransactionId,
        float $matchConfidence,
        array $rulesMatched = []
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
    }

    /**
     * @param array $data Keys: statement_line_id, bank_transaction_id, match_confidence, rules_matched
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['statement_line_id'] ?? ''),
            (int)    ($data['bank_transaction_id'] ?? 0),
            (float)  ($data['match_confidence'] ?? 0.0),
            (array)  ($data['rules_matched'] ?? [])
        );
    }

    public function getStatementLineId(): string
    {
        return $this->statementLineId;
    }

    public function getBankTransactionId(): int
    {
        return $this->bankTransactionId;
    }

    public function getMatchConfidence(): float
    {
        return $this->matchConfidence;
    }

    public function isHighConfidence(float $threshold = 0.9): bool
    {
        return $this->matchConfidence >= $threshold;
    }

    /**
     * @return string[]
     */
    public function getRulesMatched(): array
    {
        return $this->rulesMatched;
    }

    public function toArray(): array
    {
        return [
            'statement_line_id'   => $this->statementLineId,
            'bank_transaction_id' => $this->bankTransactionId,
            'match_confidence'    => $this->matchConfidence,
            'rules_matched'       => $this->rulesMatched,
        ];
    }
}
