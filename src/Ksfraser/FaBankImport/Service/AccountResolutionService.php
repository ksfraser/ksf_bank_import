<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\Contracts\BankAccountDirectoryInterface;

/**
 * The bank account resolution step of the import.
 *
 * After the statements are parsed, the detected account identifiers of the
 * files are compared with the bank accounts that exist in FrontAccounting.
 * Every identifier that cannot be resolved has to be mapped to an existing
 * bank account by the user before the import can continue (FR-002, FR-003).
 *
 * The step never creates a bank account: it depends on a read-only directory
 * of the existing accounts (FR-007), and it runs after the duplicate review,
 * on the final set of parsed statements (FR-008).
 *
 * @package Ksfraser\FaBankImport\Service
 * @since 20260822
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
 */
class AccountResolutionService
{
    /** @var StatementAccountMappingService */
    private $mapping;

    /** @var BankAccountDirectoryInterface */
    private $accounts;

    /**
     * @param StatementAccountMappingService    $mapping  Detects and applies the account mapping
     * @param BankAccountDirectoryInterface     $accounts The existing bank accounts (read only)
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function __construct(
        StatementAccountMappingService $mapping,
        BankAccountDirectoryInterface $accounts
    ) {
        $this->mapping = $mapping;
        $this->accounts = $accounts;
    }

    /**
     * The detected account identifiers that still need a decision.
     *
     * Every unresolved identifier is reported with the files it was detected
     * in, so the resolution screen can list them (FR-002).
     *
     * @param array<int, array<int|string, object>> $multistatements The parsed statements
     * @param array<string, string>                 $resolvedMap     Detected => bank account number
     * @return array<string, array<int, int>> Detected identifier => the file indexes it was found in
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function collectUnresolved(array $multistatements, array $resolvedMap = []): array
    {
        $detectedByFile = $this->mapping->collectDetectedAccountsByFile($multistatements);
        $unresolved = [];

        foreach ($detectedByFile as $fileIndex => $detectedList) {
            foreach ($detectedList as $detected) {
                $detected = trim((string) $detected);
                if ($detected === '') {
                    continue;
                }
                // A saved association or an existing account number resolves it.
                if (isset($resolvedMap[$detected]) && $this->accounts->accountNumberExists($resolvedMap[$detected])) {
                    continue;
                }
                if ($this->accounts->accountNumberExists($detected)) {
                    continue;
                }
                if (!isset($unresolved[$detected])) {
                    $unresolved[$detected] = [];
                }
                $unresolved[$detected][] = (int) $fileIndex;
            }
        }

        return $unresolved;
    }

    /**
     * The bank accounts the user can map an identifier to.
     *
     * @return array<int, array{id: int, number: string, name: string}> The bank accounts
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function availableBankAccounts(): array
    {
        return $this->accounts->listBankAccounts();
    }

    /**
     * Apply the saved associations to the parsed statements.
     *
     * The associations of a previous import are applied first, so only the
     * identifiers that are still unknown reach the user (FR-006).
     *
     * @param array<int, array<int|string, object>> $multistatements The parsed statements
     * @param array<string, string>                 $savedMap         Detected => bank account number
     * @return array<int, array<int|string, object>> The statements with the saved mapping applied
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function applySavedAssociations(array $multistatements, array $savedMap): array
    {
        if ($savedMap === []) {
            return $multistatements;
        }

        return $this->mapping->applyAccountNumberMapping($multistatements, $savedMap);
    }

    /**
     * Apply the mapping the user chose to the parsed statements.
     *
     * @param array<int, array<int|string, object>> $multistatements        The parsed statements
     * @param array<string, string>                 $detectedToTargetNumber Detected => bank account number
     * @return array<int, array<int|string, object>> The statements with the mapping applied
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function applyResolution(array $multistatements, array $detectedToTargetNumber): array
    {
        return $this->mapping->applyAccountNumberMapping($multistatements, $detectedToTargetNumber);
    }

    /**
     * The resolution runs on the statements the duplicate review left over.
     *
     * The duplicate review decides which files are imported, so the account
     * resolution has to run after it and on its result (FR-008).
     *
     * @param array<int, array<int|string, object>> $multistatements The statements of the import
     * @param array<int, bool>                     $skippedFiles   The files the duplicate review skipped
     * @return array<int, array<int|string, object>> The statements of the files that are imported
 * @BABOK FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function statementsAfterDuplicateReview(array $multistatements, array $skippedFiles = []): array
    {
        foreach (array_keys($multistatements) as $fileIndex) {
            if (isset($skippedFiles[(int) $fileIndex]) && $skippedFiles[(int) $fileIndex]) {
                unset($multistatements[(int) $fileIndex]);
            }
        }

        return $multistatements;
    }
}
