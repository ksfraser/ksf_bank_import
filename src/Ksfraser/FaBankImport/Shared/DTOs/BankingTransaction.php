<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;
use Ksfraser\Traits\EnforceDeclaredPropsTrait;

/**
 * DTO replacement for legacy `transaction` from includes/banking.php
 * Keep behaviour parity: public properties and helper methods.
 */
class BankingTransaction
{
    use EnforceDeclaredPropsTrait;
    public $valueTimestamp = '';
    public $entryTimestamp = '';

    public $account = '';
    public $accountName = '';
    public $accountName1 = '';
    public $accountName2 = '';
    public $transactionType = '';
    public $transactionCode = '';
    public $transactionCodeDesc = '';
    public $transactionDC = '';
    public $transactionAmount = 0;
    public $transactionTitle1 = '';
    public $transactionTitle2 = '';
    public $transactionTitle3 = '';
    public $transactionTitle4 = '';
    public $transactionTitle5 = '';
    public $transactionTitle6 = '';
    public $transactionTitle7 = '';
    public $merchant = '';
    public $category = '';
    public $reference = '';
    public $status = '';
    public $memo;
    public $sic;
    public $address;
    public $checknumber;
    public $acctid;
    public $fitid;
    public $intu_bid;
    public $bankid;
    public $contact_id; // FK to 0_bi_contact
    public $contact;    // parser-provided contact object

    public function getTransactionTitle()
    {
        $title = '';
        for ($i = 1; $i <= 7; $i++) {
            $var = "transactionTitle{$i}";
            $title .= $this->$var . " ";
        }
        return $title;
    }

    public function getAccountName()
    {
        return $this->accountName1 . $this->accountName2;
    }

    public function dump()
    {
        echo "    -------------------------------------------------------------------\n";
        echo "    account            ={${'this'}->account}\n";
        echo "    accountName        ={${'this'}->accountName}\n";
        echo "    amount             ={${'this'}->transactionAmount}\n";
        echo "    transactionType    ={${'this'}->transactionType}\n";
        echo "    valuetimestamp     ={${'this'}->valueTimestamp}\n";
        echo "    entrytimestamp     ={${'this'}->entryTimestamp}\n";
        echo "    transactionCode    ={${'this'}->transactionCode}\n";
        echo "    transactionCodeDesc={${'this'}->transactionCodeDesc}\n";
        echo "    transactionDC      ={${'this'}->transactionDC}\n";
        echo "    transactionTitle   ={${'this'}->transactionTitle}\n";
        echo "    Merchant           ={${'this'}->merchant}\n";
        echo "    Category           ={${'this'}->category}\n";
        echo "    Reference          ={${'this'}->reference}\n";
        echo "    Status             ={${'this'}->status}\n";
        echo "    Memo               ={${'this'}->memo}\n";
        echo "    SIC                ={${'this'}->sic}\n";
        echo "    Address            ={${'this'}->address}\n";
    }
}
