<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services\Repositories;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repository\DatabaseUploadedFileRepository;
use Ksfraser\FaBankImport\Entity\UploadedFile;
use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/../Support/fa_db_stubs_repository.php';

/**
 * Unit tests for the database backed uploaded file repository.
 */
final class DatabaseUploadedFileRepositoryTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    /** @var DatabaseUploadedFileRepository */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();
        $this->repository = new DatabaseUploadedFileRepository();
    }

    protected function tearDown(): void
    {
        FaDbFake::deactivate();

        parent::tearDown();
    }

    public function testSaveStoresTheMetadataAndAssignsTheNewId(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('INSERT INTO 0_bi_uploaded_files', [['affected' => 1]]);
        $this->fake->insertId = 42;

        $file = $this->newFile();

        $fileId = $this->repository->save($file);

        $this->assertSame(42, $fileId);
        $this->assertSame(42, $file->getId());
        $this->assertStringContainsString('bi_uploaded_files', $this->fake->lastSql());
        $this->assertStringContainsString('stored.qfx', $this->fake->lastSql());
    }

    public function testSaveThrowsWhenTheInsertFails(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('INSERT INTO 0_bi_uploaded_files', false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to save file metadata to database');

        $this->repository->save($this->newFile());
    }

    public function testFindByIdReturnsTheHydratedEntity(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT * FROM 0_bi_uploaded_files', [$this->row()]);

        $file = $this->repository->findById(7);

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame(7, $file->getId());
        $this->assertSame('stored.qfx', $file->getFilename());
        $this->assertSame('original.qfx', $file->getOriginalFilename());
        $this->assertSame('/tmp/stored.qfx', $file->getFilePath());
        $this->assertSame(1234, $file->getFileSize());
        $this->assertSame('application/x-qfx', $file->getFileType());
        $this->assertSame('2026-01-15 10:00:00', $file->getUploadDate()->format('Y-m-d H:i:s'));
        $this->assertSame('admin', $file->getUploadUser());
        $this->assertSame('qfx', $file->getParserType());
        $this->assertSame(3, $file->getBankAccountId());
        $this->assertSame(2, $file->getStatementCount());
        $this->assertSame('some notes', $file->getNotes());
    }

    public function testFindByIdReturnsNullForAnUnknownFile(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT * FROM 0_bi_uploaded_files', []);

        $this->assertNull($this->repository->findById(7));
    }

    public function testFindDuplicateReturnsTheMatchingUpload(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT * FROM 0_bi_uploaded_files', [$this->row()]);

        $fileInfo = new FileInfo('original.qfx', '/tmp/source.qfx', 1234, 'application/x-qfx');
        $duplicate = $this->repository->findDuplicate($fileInfo, 90);

        $this->assertInstanceOf(UploadedFile::class, $duplicate);
        $this->assertStringContainsString('DATE_SUB(NOW(), INTERVAL 90 DAY)', $this->fake->lastSql());
    }

    public function testFindDuplicateReturnsNullWhenNothingMatches(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT * FROM 0_bi_uploaded_files', []);

        $fileInfo = new FileInfo('original.qfx', '/tmp/source.qfx', 1234, 'application/x-qfx');

        $this->assertNull($this->repository->findDuplicate($fileInfo, 30));
    }

    public function testLinkToStatementsLinksEveryStatementAndUpdatesTheCount(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('INSERT IGNORE INTO 0_bi_file_statements', [['affected' => 1]]);
        $this->fake->on('UPDATE 0_bi_uploaded_files', [['affected' => 1]]);

        $this->assertTrue($this->repository->linkToStatements(7, [10, 11]));

        $sqls = array_column($this->fake->queries, 'sql');
        $inserts = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'INSERT IGNORE') === 0;
        }));
        $updates = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'UPDATE') === 0;
        }));

        $this->assertCount(2, $inserts);
        $this->assertCount(1, $updates);
        $this->assertStringContainsString('statement_count', $updates[0]);
    }

    public function testLinkToStatementsWithoutStatementsIsANoOp(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);

        $this->assertTrue($this->repository->linkToStatements(7, []));
        $this->assertSame(1, $this->fake->queryCount());
    }

    public function testLinkToStatementsReportsAFailure(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('INSERT IGNORE INTO 0_bi_file_statements', false);

        $this->assertFalse($this->repository->linkToStatements(7, [10]));
    }

    public function testGetLinkedStatementsReturnsTheJoinedStatementRows(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT s.*', [['id' => 10, 'smtDate' => '2026-01-15']]);

        $statements = $this->repository->getLinkedStatements(7);

        $this->assertCount(1, $statements);
        $this->assertSame(10, $statements[0]['id']);
    }

    public function testUpdateStatementCountRunsTheCountingUpdate(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('UPDATE 0_bi_uploaded_files', [['affected' => 1]]);

        $this->assertTrue($this->repository->updateStatementCount(7));
        $this->assertStringContainsString('SELECT COUNT(*)', $this->fake->lastSql());
    }

    public function testDeleteRemovesTheLinksAndTheMetadata(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('DELETE FROM 0_bi_file_statements', [['affected' => 1]]);
        $this->fake->on('DELETE FROM 0_bi_uploaded_files', [['affected' => 1]]);

        $this->assertTrue($this->repository->delete(7));

        $sqls = array_column($this->fake->queries, 'sql');
        $deletes = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'DELETE FROM') === 0;
        }));

        $this->assertCount(2, $deletes);
        $this->assertStringContainsString('bi_file_statements', $deletes[0]);
        $this->assertStringContainsString('bi_uploaded_files', $deletes[1]);
    }

    public function testFindAllAppliesTheFiltersAndHydratesTheRows(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT * FROM 0_bi_uploaded_files', [$this->row()]);

        $files = $this->repository->findAll([
            'user' => 'admin',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'parser_type' => 'qfx',
        ], 10, 20);

        $this->assertCount(1, $files);
        $this->assertInstanceOf(UploadedFile::class, $files[0]);

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString("upload_user = 'admin'", $sql);
        $this->assertStringContainsString("upload_date >= '2026-01-01'", $sql);
        $this->assertStringContainsString("upload_date <= '2026-12-31'", $sql);
        $this->assertStringContainsString("parser_type = 'qfx'", $sql);
        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $sql);
    }

    public function testCountReturnsTheNumberOfFiles(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT COUNT(*) as cnt', [['cnt' => 5]]);

        $this->assertSame(5, $this->repository->count());
    }

    public function testCountAppliesTheFilters(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT COUNT(*) as cnt', [['cnt' => 5]]);

        $this->repository->count(['parser_type' => 'qfx']);

        $this->assertStringContainsString("parser_type = 'qfx'", $this->fake->lastSql());
    }

    public function testCountAppliesEveryFilter(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT COUNT(*) as cnt', [['cnt' => 5]]);

        $this->repository->count([
            'user' => 'admin',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]);

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString("upload_user = 'admin'", $sql);
        $this->assertStringContainsString("upload_date >= '2026-01-01'", $sql);
        $this->assertStringContainsString("upload_date <= '2026-12-31'", $sql);
    }

    public function testGetStatisticsReturnsTheAggregatedNumbers(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('COUNT(*) as total_files', [[
            'total_files' => 3,
            'total_size' => 4096,
            'latest_upload' => '2026-01-15 10:00:00',
            'first_upload' => '2026-01-01 09:00:00',
        ]]);

        $stats = $this->repository->getStatistics();

        $this->assertSame(3, $stats['total_files']);
        $this->assertSame(4096, $stats['total_size']);
        $this->assertSame('2026-01-15 10:00:00', $stats['latest_upload']);
        $this->assertSame('2026-01-01 09:00:00', $stats['first_upload']);
    }

    public function testMissingTablesAreCreatedOnFirstUse(): void
    {
        $this->fake->on('SHOW TABLES LIKE', []);
        $this->fake->on('SELECT * FROM 0_bi_uploaded_files', []);

        $this->repository->findById(7);

        $sqls = array_column($this->fake->queries, 'sql');
        $createStatements = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'CREATE TABLE') === 0;
        }));

        $this->assertCount(2, $createStatements);
        $this->assertStringContainsString('bi_uploaded_files', $createStatements[0]);
        $this->assertStringContainsString('bi_file_statements', $createStatements[1]);
    }

    private function newFile(): UploadedFile
    {
        return new UploadedFile(
            null,
            'stored.qfx',
            'original.qfx',
            '/tmp/stored.qfx',
            1234,
            'application/x-qfx',
            new \DateTime('2026-01-15 10:00:00'),
            'admin',
            'qfx',
            3,
            0,
            'some notes'
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function row(): array
    {
        return [
            'id' => 7,
            'filename' => 'stored.qfx',
            'original_filename' => 'original.qfx',
            'file_path' => '/tmp/stored.qfx',
            'file_size' => 1234,
            'file_type' => 'application/x-qfx',
            'upload_date' => '2026-01-15 10:00:00',
            'upload_user' => 'admin',
            'parser_type' => 'qfx',
            'bank_account_id' => 3,
            'statement_count' => 2,
            'notes' => 'some notes',
        ];
    }
}
