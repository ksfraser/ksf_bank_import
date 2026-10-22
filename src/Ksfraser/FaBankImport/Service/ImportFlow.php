<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * The order of the steps of an import run.
 *
 * The steps have to run in this order:
 *
 *  1. duplicate review - the user decides which of the uploaded files are
 *     imported (FR-IMP-004);
 *  2. account resolution - the detected account identifiers of the files that
 *     are imported are mapped to existing bank accounts (FR-008, FR-002);
 *  3. import - the statements and transactions are written to the staging
 *     tables (FR-IMP-007, FR-IMP-008).
 *
 * The flow exists so that the order is stated once and can be asserted,
 * instead of being implied by the order of the code in a screen.
 *
 * @package Ksfraser\FaBankImport\Service
 * @since 20260822
 * @BABOK FR-008, FR-IMP-004, FR-IMP-007, FR-IMP-008 | BR-ACCT-001 | UAT-ACCT-001
 */
class ImportFlow
{
    public const STEP_DUPLICATE_REVIEW = 'duplicate_review';
    public const STEP_ACCOUNT_RESOLUTION = 'account_resolution';
    public const STEP_IMPORT = 'import';

    /**
     * The steps of an import run, in the order they have to run.
     *
     * @var array<int, string>
     */
    private $steps = [
        self::STEP_DUPLICATE_REVIEW,
        self::STEP_ACCOUNT_RESOLUTION,
        self::STEP_IMPORT,
    ];

    /**
     * All the steps of the flow.
     *
     * @return array<int, string> The steps in the order they run
 * @BABOK FR-008, FR-IMP-004, FR-IMP-007, FR-IMP-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function steps(): array
    {
        return $this->steps;
    }

    /**
     * The step that follows the given one.
     *
     * @param string $step The step that has just run
     * @return string|null The next step, or null when the flow is finished
 * @BABOK FR-008, FR-IMP-004, FR-IMP-007, FR-IMP-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function nextStep(string $step): ?string
    {
        $position = array_search($step, $this->steps, true);
        if ($position === false) {
            return null;
        }

        return $this->steps[$position + 1] ?? null;
    }

    /**
     * Whether the account resolution runs after the given step.
     *
     * @param string $step The step to check
     * @return bool True when the resolution belongs after the step
 * @BABOK FR-008, FR-IMP-004, FR-IMP-007, FR-IMP-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function runsAccountResolutionAfter(string $step): bool
    {
        $position = array_search($step, $this->steps, true);
        if ($position === false) {
            return false;
        }

        $resolution = array_search(self::STEP_ACCOUNT_RESOLUTION, $this->steps, true);

        return $resolution !== false && $resolution > $position;
    }
}
