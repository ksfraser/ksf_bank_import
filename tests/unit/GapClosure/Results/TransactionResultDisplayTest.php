<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Results;

use Ksfraser\FaBankImport\Results\TransactionResult;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the display of a transaction result.
 *
 * @see \Ksfraser\FaBankImport\Results\TransactionResult
 */
final class TransactionResultDisplayTest extends TestCase
{
    /**
     * @return void
     */
    public function testAWarningIsDisplayedAsAWarning(): void
    {
        $GLOBALS['test_warnings'] = [];

        TransactionResult::warning('Careful')->display();

        $this->assertSame(['Careful'], $GLOBALS['test_warnings']);
    }

    /**
     * @return void
     */
    public function testAnErrorIsDisplayedAsAnError(): void
    {
        $GLOBALS['test_errors'] = [];

        TransactionResult::error('Broken')->display();

        $this->assertSame(['Broken'], $GLOBALS['test_errors']);
    }

    /**
     * @return void
     */
    public function testASuccessIsDisplayedAsANotification(): void
    {
        $GLOBALS['test_notifications'] = [];

        TransactionResult::success(1, 2, 'Done')->display();

        $this->assertSame(['Done'], $GLOBALS['test_notifications']);
    }
}
