<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler;

/**
 * Bank transfer handler that looks for its legacy helper somewhere else.
 *
 * The production handler resolves ksf_modules_common relative to the module
 * (it sits next to it in the FrontAccounting modules/ directory). The real
 * helper needs a live FrontAccounting installation, so the tests point the
 * handler at a published stand-in through this seam.
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
}
