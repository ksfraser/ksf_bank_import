<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler;

require_once __DIR__ . '/fa_bank_transfer_double.php';

/**
 * Bank transfer handler that looks for its legacy helper somewhere else and
 * injects the controllable bank-transfer double.
 *
 * The production handler resolves ksf_modules_common relative to the module
 * (it sits next to it in the FrontAccounting modules/ directory). The real
 * helper needs a live FrontAccounting installation, so the tests point the
 * handler at a published stand-in through this seam, and instantiate the
 * double through the overridable createBankTransfer() factory so the test
 * never depends on global class-declaration order.
 */
final class BankTransferHandlerWithStubbedHelper extends BankTransferTransactionHandler
{
    /** @var string */
    private $helperPath;

    /**
     * @param string $helperPath
     * @return void
     */
    public function setHelperPath(string $helperPath): void
    {
        $this->helperPath = $helperPath;
    }

    /**
     * @return string
     */
    protected function getFaBankTransferClassPath(): string
    {
        return $this->helperPath;
    }

    /**
     * @return \fa_bank_transfer_double
     */
    protected function createBankTransfer()
    {
        return new \fa_bank_transfer_double();
    }
}

