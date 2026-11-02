<?php

declare(strict_types=1);
/** @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\Exception;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException
 */
class DomainExceptionsTest extends TestCase
{
    // ------------------------------------------------------------------
    // ReconciliationException
    // ------------------------------------------------------------------

    public function testSessionNotFoundContainsId(): void
    {
        $ex = ReconciliationException::sessionNotFound(42);

        $this->assertInstanceOf(ReconciliationException::class, $ex);
        $this->assertStringContainsString('42', $ex->getMessage());
    }

    public function testAlreadyApprovedContainsStatus(): void
    {
        $ex = ReconciliationException::alreadyApproved('approved');

        $this->assertInstanceOf(ReconciliationException::class, $ex);
        $this->assertStringContainsString('approved', $ex->getMessage());
    }

    public function testForReasonContainsReason(): void
    {
        $ex = ReconciliationException::forReason('session is locked');

        $this->assertInstanceOf(ReconciliationException::class, $ex);
        $this->assertStringContainsString('session is locked', $ex->getMessage());
    }

    // ------------------------------------------------------------------
    // StatementOcrException
    // ------------------------------------------------------------------

    public function testOcrForReasonContainsReason(): void
    {
        $ex = StatementOcrException::forReason('bad API key');

        $this->assertInstanceOf(StatementOcrException::class, $ex);
        $this->assertStringContainsString('bad API key', $ex->getMessage());
    }

    public function testMissingFieldContainsFieldName(): void
    {
        $ex = StatementOcrException::missingField('opening_balance');

        $this->assertInstanceOf(StatementOcrException::class, $ex);
        $this->assertStringContainsString('opening_balance', $ex->getMessage());
    }
}
