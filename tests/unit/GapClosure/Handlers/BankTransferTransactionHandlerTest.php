<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Handlers;

use Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\FailingLegacyHelperStub;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FailingLegacyHelperStub.php';

/**
 * Handler that looks for its legacy helper somewhere else.
 *
 * The handler resolves ksf_modules_common relative to the module (it sits next
 * to it in the FrontAccounting modules/ directory). The real helper needs a live
 * FrontAccounting installation, so this subclass points it at a stand-in.
 */
final class BankTransferHandlerWithStubbedHelper extends BankTransferTransactionHandler
{
    /** @var string */
    private $helperPath;

    public function setHelperPath(string $path): void
    {
        $this->helperPath = $path;
    }

    protected function getFaBankTransferClassPath(): string
    {
        return $this->helperPath;
    }
}

/**
 * Coverage for the bank transfer handler.
 *
 * @see \Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler
 */
final class BankTransferTransactionHandlerTest extends TestCase
{
    /** @var FailingLegacyHelperStub */
    private $legacyHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->legacyHelper = new FailingLegacyHelperStub(FA_ROOT);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->legacyHelper->cleanup();
    }

    /**
     * The handler resolves its helper from ksf_modules_common, which sits next
     * to the module in the FrontAccounting modules/ directory.
     *
     * @return void
     */
    public function testTheHelperPathPointsAtTheSiblingModule(): void
    {
        $handler = new BankTransferTransactionHandler();

        $method = new \ReflectionMethod($handler, 'getFaBankTransferClassPath');
        $method->setAccessible(true);

        $path = (string) $method->invoke($handler);

        $this->assertStringEndsWith(
            '/ksf_modules_common/class.fa_bank_transfer.php',
            $path,
            'The helper must be loaded from ksf_modules_common, next to this module'
        );
        $this->assertStringNotContainsString(
            'src/ksf_modules_common',
            $path,
            'The helper must not be looked for inside the module source tree'
        );
    }

    /**
     * @return void
     */
    public function testTheHandlerRefusesToWorkWithoutItsHelper(): void
    {
        $handler = new BankTransferHandlerWithStubbedHelper();
        $handler->setHelperPath($this->legacyHelper->link());

        $result = $handler->process(
            [
                'transactionAmount' => 100.0,
                'transactionDC' => 'D',
                'valueTimestamp' => '2026-01-31',
            ],
            ['partnerId' => 5],
            42,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertSame('Failed to load fa_bank_transfer class', $result->getMessage());
    }
}
