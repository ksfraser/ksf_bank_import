<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Contracts;

/**
 * Read-only access to the FrontAccounting bank accounts.
 *
 * The account resolution step may only look at the bank accounts that
 * already exist: it must never create, modify or delete one
 * (FR-007). The interface therefore has no write method at all, so a
 * dependency on it cannot break that rule.
 *
 * @package Ksfraser\FaBankImport\Contracts
 * @since 20260822
 * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
 */
interface BankAccountDirectoryInterface
{
    /**
     * The bank accounts the user can choose from.
     *
     * @return array<int, array{id: int, number: string, name: string}> The bank accounts
 * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function listBankAccounts(): array;

    /**
     * Whether a bank account number exists.
     *
     * @param string $number Bank account number.
     * @return bool True when the account exists.
 * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function accountNumberExists(string $number): bool;
}
