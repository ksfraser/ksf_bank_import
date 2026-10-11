<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Infrastructure\Database\Migrations;

use Ksfraser\FaBankImport\Infrastructure\Database\MigrationException;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations\CreateReconciliationSessionTable;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations\CreateStatementOcrTable;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations\CreateStatementUploadTable;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the three StatementReconcile database migrations.
 *
 * Uses a mock PDO so we exercise every branch (happy path + PDOException)
 * without requiring a real database.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations\CreateReconciliationSessionTable
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations\CreateStatementOcrTable
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Database\Migrations\CreateStatementUploadTable
 */
class MigrationsTest extends TestCase
{
    // ------------------------------------------------------------------
    // CreateStatementOcrTable
    // ------------------------------------------------------------------

    public function testOcrTableNameReturnsExpectedString(): void
    {
        $m = new CreateStatementOcrTable();
        $this->assertStringContainsString('statement_ocr', $m->name());
    }

    public function testOcrTableDescriptionIsNonEmpty(): void
    {
        $m = new CreateStatementOcrTable();
        $this->assertNotEmpty($m->description());
    }

    public function testOcrTableUpCallsExec(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->atLeastOnce())->method('exec');

        $m = new CreateStatementOcrTable();
        $m->up($pdo);
    }

    public function testOcrTableUpThrowsMigrationExceptionOnPdoException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('exec')->willThrowException(new PDOException('db error'));

        $m = new CreateStatementOcrTable();
        $this->expectException(MigrationException::class);
        $m->up($pdo);
    }

    public function testOcrTableDownCallsExec(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec');

        $m = new CreateStatementOcrTable();
        $m->down($pdo);
    }

    public function testOcrTableDownThrowsMigrationExceptionOnPdoException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('exec')->willThrowException(new PDOException('db error'));

        $m = new CreateStatementOcrTable();
        $this->expectException(MigrationException::class);
        $m->down($pdo);
    }

    // ------------------------------------------------------------------
    // CreateReconciliationSessionTable
    // ------------------------------------------------------------------

    public function testSessionTableNameReturnsExpectedString(): void
    {
        $m = new CreateReconciliationSessionTable();
        $this->assertStringContainsString('reconciliation_session', $m->name());
    }

    public function testSessionTableDescriptionIsNonEmpty(): void
    {
        $m = new CreateReconciliationSessionTable();
        $this->assertNotEmpty($m->description());
    }

    public function testSessionTableUpCallsExec(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->atLeastOnce())->method('exec');

        $m = new CreateReconciliationSessionTable();
        $m->up($pdo);
    }

    public function testSessionTableUpThrowsMigrationExceptionOnPdoException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('exec')->willThrowException(new PDOException('db error'));

        $m = new CreateReconciliationSessionTable();
        $this->expectException(MigrationException::class);
        $m->up($pdo);
    }

    public function testSessionTableDownCallsExec(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec');

        $m = new CreateReconciliationSessionTable();
        $m->down($pdo);
    }

    public function testSessionTableDownThrowsMigrationExceptionOnPdoException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('exec')->willThrowException(new PDOException('db error'));

        $m = new CreateReconciliationSessionTable();
        $this->expectException(MigrationException::class);
        $m->down($pdo);
    }

    // ------------------------------------------------------------------
    // CreateStatementUploadTable
    // ------------------------------------------------------------------

    public function testUploadTableNameReturnsExpectedString(): void
    {
        $m = new CreateStatementUploadTable();
        $this->assertStringContainsString('upload', $m->name());
    }

    public function testUploadTableDescriptionIsNonEmpty(): void
    {
        $m = new CreateStatementUploadTable();
        $this->assertNotEmpty($m->description());
    }

    public function testUploadTableUpCallsExec(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->atLeastOnce())->method('exec');

        $m = new CreateStatementUploadTable();
        $m->up($pdo);
    }

    public function testUploadTableUpThrowsMigrationExceptionOnPdoException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('exec')->willThrowException(new PDOException('db error'));

        $m = new CreateStatementUploadTable();
        $this->expectException(MigrationException::class);
        $m->up($pdo);
    }

    public function testUploadTableDownCallsExec(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec');

        $m = new CreateStatementUploadTable();
        $m->down($pdo);
    }

    public function testUploadTableDownThrowsMigrationExceptionOnPdoException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('exec')->willThrowException(new PDOException('db error'));

        $m = new CreateStatementUploadTable();
        $this->expectException(MigrationException::class);
        $m->down($pdo);
    }
}
