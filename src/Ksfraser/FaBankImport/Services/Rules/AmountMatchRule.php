<?php

/**
 * Amount Matching Rule
 *
 * Matches amounts within configured tolerance. Exact match scores higher,
 * within tolerance scores proportionally less.
 *
 * @package    Ksfraser\FaBankImport\Services\Rules
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @BABOK BR-001 (Auto Pair Detection) — FR-003 (Amount Matching Tolerance) — AC-003.1/003.2
 * @UML docs/FunctionalRequirements/FR-003.md
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Rules;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRule;
use Ksfraser\FaBankImport\Services\Scoring\SupplierCandidate;

/**
 * @BABOK CLASS ROLLUP: AmountMatchRule — 5 methods; BR-001 (5)
 * @BABOK RTM: BR-001 / ARCH-001; file: Services/Rules/AmountMatchRule.php; coverage: docs/TestCases/ (file rollup)
 * Amount Range Matching Rule
 *
 * @since 7.6
 */
class AmountMatchRule implements ScoringRule
{
    /**
     * @var float Tolerance percentage (default 1.0 = 1%)
     */
    private float $tolerance;

    /**
 * @BABOK BR-001 / ARCH-001 — method: __construct
     * Constructor
     *
     * @param float $tolerance Tolerance percentage (default 1.0 = 1%)
     * @since 7.6
     */
    public function __construct(float $tolerance = 1.0)
    {
        $this->tolerance = max(0.0, min(100.0, $tolerance));
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: calculateScore
     * @inheritDoc
     */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        $amount = (float)($transaction['amount'] ?? 0);
        // Partner detail amount could come from transaction context or partner repository
        $match_amount = (float)($transaction['partner_amount'] ?? 0);

        if ($amount == 0 || $match_amount == 0) {
            return -5.0;
        }

        // Exact match
        if ($amount == $match_amount) {
            return 20.0;
        }

        // Calculate percentage difference
        $diff = abs($amount - $match_amount);
        $percent_diff = ($diff / $match_amount) * 100;

        // Check if within tolerance
        if ($percent_diff <= $this->tolerance) {
            return (20.0 - ($percent_diff / $this->tolerance) * 10.0);
        }

        return -20.0;
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getName
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'amount_match';
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getMaxBoost
     * @inheritDoc
     */
    public function getMaxBoost(): float
    {
        return 20.0;
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getMinReduction
     * @inheritDoc
     */
    public function getMinReduction(): float
    {
        return -20.0;
    }
}
