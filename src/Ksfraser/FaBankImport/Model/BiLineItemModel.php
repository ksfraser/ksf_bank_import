<?php
namespace Ksfraser\FaBankImport\Model;

use Ksfraser\common\GenericFaInterface;
use Ksfraser\frontaccounting\FaBankAccounts;
use Ksfraser\frontaccounting\FaCustomerPayment;
use Ksfraser\frontaccounting\FaGl;

/**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001

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
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
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
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
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
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
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
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
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
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
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
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
     * Find matching existing journal entries.
     *
     * @return iterable|array The matches the finder returned.
     */
    public function findMatchingExistingJE(): iterable
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

    /**
     * Convert a transaction object's members onto this model
     * (legacy bi_lineitem::trz2obj() parity).
     *
     * @param object $trz Source transaction object.
     * @return int Number of members copied.
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
     */
    public function trz2obj($trz): int
    {
        return $this->obj2obj($trz);
    }

    /**
     * Look up a partner by the bank account name through the legacy
     * partner data helper, which is loaded relative to the working
     * directory (legacy bi_lineitem parity).
     *
     * @param int|string $partnerType The partner type to search in.
     * @return array The partner match the helper returned.
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
     */
    public function seekPartnerByBankAccount($partnerType): array
    {
        require_once 'class.bi_partners_data.php';

        $partnerData = new \Ksfraser\FaBankImport\Model\bi_partners_data();

        return (array) $partnerData->search_partner_by_bank_account($partnerType, $this->otherBankAccount);
    }

    /**
     * Whether the transaction has a paired transaction (legacy parity:
     * pairing is not implemented yet, always false).
     *
     * @return bool Always false for now.
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
     */
    public function isPaired(): bool
    {
        return false;
    }

    /**
     * Find transactions that look like a pair of this one (legacy parity:
     * pairing is not implemented yet).
     *
     * @return int The number of paired transactions found.
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-009, FR-PROC-012 | BR-PROC-001 | UAT-PROC-001
     */
    public function findPaired(): int
    {
        return 0;
    }
}