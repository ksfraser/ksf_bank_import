<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Repository;

use Ksfraser\FaBankImport\FaGLWrapper;
use PHPUnit\Framework\TestCase;

/**
 * The GL matcher wrapper loads its helper from ksf_modules_common.
 *
 * ksf_modules_common sits next to this module inside FrontAccounting's
 * modules/ directory, so the path has to climb out of src/Ksfraser/FaBankImport
 * and back down into the sibling module.
 *
 * @see \Ksfraser\FaBankImport\FaGLWrapper
 */
final class FaGLWrapperPathTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheHelperIsLoadedFromTheSiblingModule(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3) . '/src/Ksfraser/FaBankImport/FaGLWrapper.php'
        );

        $this->assertStringContainsString(
            "__DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php'",
            $source,
            'The helper path must climb out of src/Ksfraser/FaBankImport into the sibling module'
        );
        $this->assertStringNotContainsString(
            "__DIR__ . '../../",
            $source,
            'The helper path must not be missing its directory separator'
        );
    }

    /**
     * The wrapper either finds the helper or fails with a clear message.
     *
     * Outside a FrontAccounting installation the helper may or may not be
     * present (it lives in the sibling ksf_modules_common module), so both
     * outcomes are acceptable - a silent failure is not.
     *
     * @return void
     */
    public function testItEitherLoadsTheHelperOrFailsClearly(): void
    {
        $transaction = new class {
            /** @var float */
            public $amount = 10.0;
            /** @var string */
            public $transactionDC = 'D';
            /** @var int */
            public $days_spread = 3;
            /** @var string */
            public $valueTimestamp = '2026-01-01';
            /** @var string */
            public $entryTimestamp = '2026-01-02';
            /** @var string */
            public $otherBankAccountName = 'ACME';
            /** @var string */
            public $transactionCode = '1';
            /** @var string */
            public $memo = 'invoice';
        };

        try {
            $wrapper = new FaGLWrapper($transaction);
        } catch (\Exception $e) {
            $this->assertStringContainsString(
                'class.fa_gl.php',
                $e->getMessage(),
                'A missing helper must be reported with the path that was tried'
            );

            return;
        }

        $this->assertInstanceOf(FaGLWrapper::class, $wrapper);
    }
}
