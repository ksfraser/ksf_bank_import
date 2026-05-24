<?php
namespace Ksfraser\FaBankImport\Models;

use Ksfraser\common\GenericFaInterface;
use Ksfraser\frontaccounting\FaBankAccounts;
use Ksfraser\frontaccounting\FaCustomerPayment;
use Ksfraser\frontaccounting\FaGl;

/**
 * Model class for handling line item data.
 */
class BiLineItemModel extends GenericFaInterface
{
    /** @var string */
    protected $transactionDC;

    /** @var string */
    protected $our_account;

    /** @var string */
    protected $valueTimestamp;

    /** @var string */
    protected $entryTimestamp;

    /** @var string */
    protected $otherBankAccount;

    /** @var string */
    protected $otherBankAccountName;

    /** @var string */
    protected $transactionTitle;

    /** @var int */
    protected $status;

    /** @var string */
    protected $currency;

    /** @var int */
    protected $fa_trans_type;

    /** @var int */
    protected $fa_trans_no;

    /** @var int */
    protected $id;

    /** @var bool */
    protected $has_trans;

    /** @var float */
    protected $amount;

    /** @var float */
    protected $charge;

    /** @var string */
    protected $transactionTypeLabel;

    /** @var array */
    protected $vendor_list;

    /** @var string */
    protected $partnerType;

    /** @var int */
    protected $partnerId;

    /** @var int */
    protected $partnerDetailId;

    /** @var string */
    protected $oplabel;

    /** @var array */
    protected $matching_trans;

    /** @var int */
    protected $days_spread;

    /** @var string */
    protected $transactionCode;

    /** @var string */
    protected $transactionCodeDesc;

    /** @var array */
    protected $optypes;

    /** @var string */
    protected $memo;

    /** @var array */
    protected $ourBankDetails;

    /** @var string */
    protected $ourBankAccount;

    /** @var string */
    protected $ourBankAccountName;

    /** @var string */
    protected $ourBankAccountCode;

    /** @var FaBankAccounts */
    protected $fa_bank_accounts;

    /**
     * Constructor for BiLineItemModel.
     *
     * @param array $trz
     * @param array $vendor_list
     * @param array $optypes
     */
    public function __construct(array $trz, array $vendor_list = [], array $optypes = [])
    {
        parent::__construct(null, null, null, null, null);
        $this->initialize($trz, $vendor_list, $optypes);
    }

    /**
     * Initialize the model with transaction data.
     *
     * @param array $trz
     * @param array $vendor_list
     * @param array $optypes
     */
    private function initialize(array $trz, array $vendor_list, array $optypes): void
    {
        $this->transactionDC = $trz['transactionDC'];
        $this->determineTransactionTypeLabel();
        $this->memo = $trz['memo'];
        $this->our_account = $trz['our_account'];
        $this->valueTimestamp = $trz['valueTimestamp'];
        $this->entryTimestamp = $trz['entryTimestamp'];
        $this->otherBankAccount = $trz['accountName'];
        $this->otherBankAccountName = $trz['accountName'];
        $this->transactionTitle = $trz['transactionTitle'];
        $this->transactionCode = $trz['transactionCode'];
        $this->transactionCodeDesc = $trz['transactionCodeDesc'];
        $this->currency = $trz['currency'];
        $this->status = $trz['status'];
        $this->id = $trz['id'];
        $this->fa_trans_type = $trz['fa_trans_type'];
        $this->fa_trans_no = $trz['fa_trans_no'];
        $this->amount = $trz['transactionAmount'];
        $this->vendor_list = $vendor_list;
        $this->optypes = $optypes;
    }

    /**
     * Determine the transaction type label based on transactionDC.
     */
    public function determineTransactionTypeLabel(): void
    {
        switch ($this->transactionDC) {
            case 'C':
                $this->transactionTypeLabel = "Credit";
                break;
            case 'D':
                $this->transactionTypeLabel = "Debit";
                break;
            case 'B':
                $this->transactionTypeLabel = "Bank Transfer";
                break;
        }
    }

    /**
     * Retrieve bank account details.
     */
    public function getBankAccountDetails(): void
    {
        $this->fa_bank_accounts = new FaBankAccounts($this);
        $this->ourBankDetails = $this->fa_bank_accounts->getByBankAccountNumber($this->our_account);
        $this->ourBankAccountName = $this->ourBankDetails['bank_account_name'];
        $this->ourBankAccountCode = $this->ourBankDetails['account_code'];
    }

    /**
     * Set the partner type and operation label based on transactionDC.
     */
    public function setPartnerType(): void
    {
        switch ($this->transactionDC) {
            case 'C':
                $this->partnerType = 'CU';
                $this->oplabel = "Deposit";
                break;
            case 'D':
                $this->partnerType = 'SP';
                $this->oplabel = "Payment";
                break;
            case 'B':
                $this->partnerType = 'BT';
                $this->oplabel = "Bank Transfer";
                break;
            default:
                $this->partnerType = 'QE';
                $this->oplabel = "Quick Entry";
                break;
        }
    }

    /**
     * Find matching existing journal entries.
     *
     * @return array
     */
    public function findMatchingExistingJE(): array
    {
        $fa_gl = new FaGl();
        $fa_gl->set("amount_min", $this->amount);
        $fa_gl->set("amount_max", $this->amount);
        $fa_gl->set("transactionDC", $this->transactionDC);
        $fa_gl->set("days_spread", $this->days_spread);
        $fa_gl->set("startdate", $this->valueTimestamp);
        $fa_gl->set("enddate", $this->entryTimestamp);
        $fa_gl->set("accountName", $this->otherBankAccountName);
        $fa_gl->set("transactionCode", $this->transactionCode);

        $this->matching_trans = $fa_gl->find_matching_transactions();
        return $this->matching_trans;
    }
}
