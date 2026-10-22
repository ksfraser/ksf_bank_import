<?php

/**
 * The FA partner lookup the legacy line item view uses.
 *
 * The double is declared before the mock package is loaded, so the mock
 * package leaves it alone. The lookup can be told to fail, which is the
 * branch the view takes when no partner is known.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

if (!function_exists('search_partner_by_bank_account')) {
    /**
     * Look a partner up by bank account number.
     *
     * @param int $partnerType Partner type.
     * @param string $bankAccount Bank account number.
     * @return array The match, or an empty array when the lookup fails.
     */
    function search_partner_by_bank_account($partnerType, $bankAccount)
    {
        if (!empty($GLOBALS['__no_partner_match'])) {
            return [];
        }

        return [
            'partner_id' => 1,
            'partner_detail_id' => 1,
            'partner_name' => 'Mock Partner',
            'partner_ref' => 'MOCK001',
        ];
    }
}
