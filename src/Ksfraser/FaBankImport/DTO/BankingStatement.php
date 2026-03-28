<?php
namespace Ksfraser\FaBankImport\DTO;
use Ksfraser\Traits\EnforceDeclaredPropsTrait;

/**
 * DTO replacement for legacy `statement` from includes/banking.php
 */
class BankingStatement
{
    use EnforceDeclaredPropsTrait;
    public $bank = '';
    public $account = '';
    public $transactions = [];
    public $currency = '';
    public $startBalance = 0;
    public $endBalance = 0;
    public $timestamp = 0;
    public $number = '';
    public $sequence = '';
    public $statementId = '';
    public $acctid;
    public $fitid;
    public $intu_bid;
    public $bankid;

    public function addTransaction($transaction)
    {
        $this->transactions[] = $transaction;
    }

    public function dump()
    {
        echo "-------------------------------------------------------------------\n";
        echo "bank        ={${'this'}->bank}\n";
        echo "account     ={${'this'}->account}\n";
        echo "startBalance={${'this'}->startBalance}\n";
        echo "endBalance  ={${'this'}->endBalance}\n";
        echo "currency    ={${'this'}->currency}\n";
        echo "timestamp   ={${'this'}->timestamp}\n";
        echo "number      ={${'this'}->number}\n";
        echo "sequence    ={${'this'}->sequence}\n";
        echo "id          ={${'this'}->statementId}\n";
        foreach ($this->transactions as $trz) {
            if (is_object($trz) && method_exists($trz, 'dump')) {
                $trz->dump();
            }
        }
    }

    public function validate($debug = false)
    {
        $vars = ['bank', 'account', 'startBalance', 'endBalance', 'currency', 'timestamp', 'number', 'sequence', 'statementId'];
        foreach ($vars as $var) {
            if ($this->$var == "") {
                if ($debug) echo "statement: validate: $var is empty: ::" . $this->$var . "::\n";
                return false;
            }
        }
        foreach ($this->transactions as $id => $trz) {
            if ($debug) echo "  transaction #$id:";
            if (is_object($trz) && method_exists($trz, 'validate')) {
                if (!$trz->validate($debug)) return false;
            }
        }
        return true;
    }
}
