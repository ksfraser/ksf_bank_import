<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Models;

/**
 * Test double / null-object bank account details provider.
 *
 * Returns seeded details regardless of the requested number; unknown keys
 * resolve to empty strings so legacy renderers proceed safely.
 *
 * @since 20260822
 */
class FakeBankAccountDetailsProvider implements BankAccountDetailsProviderInterface
{
    /** @var array */
    protected $details;

    /**
     * Constructor.
     *
     * @param array $details Details row to serve.
     */
    public function __construct(array $details = array())
    {
        $this->details = $details + array(
            'account_code'            => '',
            'account_type'            => 0,
            'bank_account_name'       => '',
            'bank_account_number'     => '',
            'bank_name'               => '',
            'bank_address'            => '',
            'bank_curr_code'          => '',
            'dflt_curr_act'           => 1,
            'id'                      => 0,
            'bank_charge_act'         => 0,
            'last_reconciled_date'    => '',
            'ending_reconcile_balance'=> 0,
            'inactive'                => 0,
        );
    }

    /**
     * @inheritDoc
     */
    public function getByNumber(string $accountNumber): ?array
    {
        return $this->details;
    }
}
