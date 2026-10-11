<?php

class fa_bank_accounts
{
    protected $lineitem;

    public function __construct($lineitem)
    {
        $this->lineitem = $lineitem;
    }

    public function getByBankAccountNumber($bankAccountNumber)
    {
        // Return a dummy structure for testing
        return [
            'account_code' => '1061',
            'account_type' => 0,
            'bank_account_name' => 'CIBC Savings account',
            'bank_account_number' => $bankAccountNumber,
            'bank_name' => 'CIBC',
            'bank_address' => '',
            'bank_curr_code' => 'CAD',
            'dflt_curr_act' => 1,
            'id' => 1,
            'bank_charge_act' => 5690,
            'last_reconciled_date' => '0000-00-00 00:00:00',
            'ending_reconcile_balance' => 0,
            'inactive' => 0
        ];
    }
}