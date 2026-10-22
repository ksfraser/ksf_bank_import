<?php

/**
 * Support for the isolated scenario that exercises the legacy line item
 * view.
 *
 * The view reads the state of the line item from the view object itself,
 * although the state lives on the model. The scenario therefore publishes
 * the properties the view reads as public members of a model subclass, and
 * assigns the view state as dynamic properties of the view.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

use Ksfraser\FaBankImport\Model\BiLineItemModel;

/**
 * A line item model whose state the view can read.
 */
class PublicLineItemModel extends BiLineItemModel
{
    /** @var string */
    public $transactionDC;
    /** @var string */
    public $our_account;
    /** @var string */
    public $valueTimestamp;
    /** @var string */
    public $entryTimestamp;
    /** @var string */
    public $otherBankAccount;
    /** @var string */
    public $otherBankAccountName;
    /** @var string */
    public $transactionTitle;
    /** @var int */
    public $status;
    /** @var string */
    public $currency;
    /** @var int */
    public $fa_trans_type;
    /** @var int */
    public $fa_trans_no;
    /** @var int */
    public $id;
    /** @var bool */
    public $has_trans;
    /** @var float */
    public $amount;
    /** @var float */
    public $charge;
    /** @var string */
    public $transactionTypeLabel;
    /** @var array */
    public $vendor_list;
    /** @var string */
    public $partnerType;
    /** @var int */
    public $partnerId;
    /** @var int */
    public $partnerDetailId;
    /** @var string */
    public $oplabel;
    /** @var array */
    public $matching_trans;
    /** @var int */
    public $days_spread;
    /** @var string */
    public $transactionCode;
    /** @var string */
    public $transactionCodeDesc;
    /** @var array */
    public $optypes;
    /** @var string */
    public $memo;
    /** @var array */
    public $ourBankDetails;
    /** @var string */
    public $ourBankAccount;
    /** @var string */
    public $ourBankAccountName;
    /** @var string */
    public $ourBankAccountCode;
    /** @var object */
    public $fa_bank_accounts;

    /**
     * @return string The value date of the transaction.
     */
    public function getDate(): string
    {
        return (string) $this->valueTimestamp;
    }

    /**
     * @return string The description of the transaction.
     */
    public function getDescription(): string
    {
        return (string) $this->transactionTitle;
    }

    /**
     * @return float The amount of the transaction.
     */
    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    /**
     * @return bool Whether the transaction is paired.
     */
    public function isPaired(): bool
    {
        return true;
    }
}
