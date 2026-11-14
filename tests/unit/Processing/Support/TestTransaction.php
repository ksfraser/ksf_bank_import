<?php

declare(strict_types=1);

/**
 * Concrete transaction used to exercise the legacy transaction base class.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\FaBankImport\Transaction;

/**
 * Testable transaction.
 */
class TestTransaction extends Transaction
{
    /**
     * @inheritDoc
     */
    function displayPartner()
    {
    }

    /**
     * @inheritDoc
     */
    function selectAndDisplayButton()
    {
        // Mirrors the legacy base class dispatch (class.bi_lineitem.php):
        // debit statements offer the Add Vendor button, credit statements
        // the Add Customer button, anything else renders nothing.
        if ($this->transactionDC === 'D') {
            $button = new \Ksfraser\FaBankImport\Views\AddVendorButton($this->id);
        } elseif ($this->transactionDC === 'C') {
            $button = new \Ksfraser\FaBankImport\Views\AddCustomerButton($this->id);
        } else {
            return;
        }
        $button->toHtml();
    }

    /**
     * Look the supplier up that belongs to a matched vendor.
     *
     * The legacy base class calls this method although it does not declare
     * it, so the double provides it.
     *
     * @param mixed $vendorId Matched vendor id.
     *
     * @return int
     */
    public function matchedSupplierId($vendorId): int
    {
        return 0;
    }

    /**
     * Provide the vendor list the legacy base class expects.
     *
     * @param array $vendorList Vendor list keyed by vendor id.
     *
     * @return void
     */
    public function setVendorList(array $vendorList): void
    {
        $this->vendor_list = $vendorList;
    }

    /**
     * Provide the bank account name the legacy base class expects.
     *
     * @param string $otherBankAccount Bank account name.
     *
     * @return void
     */
    public function setOtherBankAccount(string $otherBankAccount): void
    {
        $this->otherBankAccountt = $otherBankAccount;
    }
}
