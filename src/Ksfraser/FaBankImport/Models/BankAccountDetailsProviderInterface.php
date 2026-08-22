<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Models;

/**
 * Contract for retrieving bank account details by account number.
 *
 * Decouples bi_lineitem (and other legacy consumers) from ksf_modules_common.
 *
 * @since 20260822
 */
interface BankAccountDetailsProviderInterface
{
    /**
     * Fetch bank account details row for an account number.
     *
     * @param string $accountNumber Bank account number.
     * @return array|null Details row (0_bank_accounts shape) or null when unknown.
     */
    public function getByNumber(string $accountNumber): ?array;
}
