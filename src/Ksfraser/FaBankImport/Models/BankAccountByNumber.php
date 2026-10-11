<?php

namespace Ksfraser\FaBankImport\models;

/**
 * BankAccountByNumber - Retrieves bank account details by account number
 * 
 * This class encapsulates the logic for retrieving bank account information
 * from FrontAccounting's bank_accounts table using the bank account number.
 * 
 * Extracted from bi_lineitem::getBankAccountDetails() during refactoring.
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20251018
 */
class BankAccountByNumber
{
    protected $bankAccountNumber;
    protected $bankDetails;
    
    /**
     * Constructor
     * 
     * @param string $bankAccountNumber The bank account number to look up
     */
    public function __construct($bankAccountNumber)
    {
        $this->bankAccountNumber = $bankAccountNumber;
        $this->loadBankDetails();
    }
    
    /**
     * Load bank account details from the database
     * 
     * Uses the fa_bank_accounts class to retrieve bank account information
     * including account code, name, currency, GL account, etc.
     */
    protected function loadBankDetails()
    {
        //Info from 0_bank_accounts
        //      Account Name
        //      Type
        //      Currency
        //      GL Account
        //      Bank
        //      Number
        //      Address
        require_once( __DIR__ . '/../../../../ksf_modules_common/class.fa_bank_accounts.php' );
        $fa_bank_accounts = new \fa_bank_accounts( $this );
        //use Ksfraser\frontaccounting\FaBankAccounts;
        //$fa_bank_accounts = new FaBankAccounts( $this );
        $this->bankDetails = $fa_bank_accounts->getByBankAccountNumber( $this->bankAccountNumber );
        //var_dump( $this->bankDetails );
        /*
            Array ( [account_code] => 1061
                [account_type] => 0
                [bank_account_name] => CIBC Savings account
                [bank_account_number] => 00449 12-93230
                [bank_name] => CIBC
                [bank_address] =>
                [bank_curr_code] => CAD
                [dflt_curr_act] => 1
                [id] => 1
                [bank_charge_act] => 5690
                [last_reconciled_date] => 0000-00-00 00:00:00
                [ending_reconcile_balance] => 0
                [inactive] => 0 )
        */
    }
    
    /**
     * Get the loaded bank account details
     * 
     * @return array Associative array containing bank account details
     */
    public function getBankDetails()
    {
        return $this->bankDetails;
    }
    
    /**
     * Get the bank account name
     * 
     * @return string|null The bank account name
     */
    public function getBankAccountName()
    {
        return $this->bankDetails['bank_account_name'] ?? null;
    }
    
    /**
     * Get the bank account code (GL account)
     * 
     * @return string|null The bank account code
     */
    public function getBankAccountCode()
    {
        return $this->bankDetails['account_code'] ?? null;
    }
    
    /**
     * Magic getter to support fa_bank_accounts compatibility
     * 
     * The original fa_bank_accounts class expects a property to access,
     * this provides that interface.
     * 
     * @param string $name Property name
     * @return mixed Property value
     */
    public function __get($name)
    {
        if ($name === 'our_account') {
            return $this->bankAccountNumber;
        }
        return null;
    }
}
