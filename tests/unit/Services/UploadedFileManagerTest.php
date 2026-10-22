<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\UploadedFileManager;
use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/Support/fa_db_stubs_services.php';
require_once __DIR__ . '/Support/fa_company_path.php';

/**
 * Behavioural tests for the uploaded file manager.
 */
final class UploadedFileManagerTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    /** @var string */
    private $uploadDir;

    /** @var string */
    private $sourceDir;

    /** @var mixed */
    private $originalCheckDuplicates;

    /** @var mixed */
    private $originalDuplicateAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();

        $this->uploadDir = sys_get_temp_dir() . '/ksf_bank_import_uploads_' . uniqid('', true);
        $this->sourceDir = sys_get_temp_dir() . '/ksf_bank_import_source_' . uniqid('', true);
        mkdir($this->uploadDir, 0777, true);
        mkdir($this->sourceDir, 0777, true);

        // The manager reads the FA database handle from the global scope.
        $GLOBALS['db'] = new \stdClass();

        $config = Config::getInstance();
        $this->originalCheckDuplicates = $config->get('upload.check_duplicates');
        $this->originalDuplicateAction = $config->get('upload.duplicate_action');
        $config->set('upload.check_duplicates', false);
        $config->set('upload.duplicate_action', 'warn');

        $_SESSION['wa_current_user'] = new class {
            /** @var string */
            public $username = 'admin';

            /** @var int */
            public $access = 1;
        };
    }

    protected function tearDown(): void
    {
        Config::getInstance()->set('upload.check_duplicates', $this->originalCheckDuplicates);
        Config::getInstance()->set('upload.duplicate_action', $this->originalDuplicateAction);

        unset($_SESSION['wa_current_user'], $_SESSION['duplicate_warnings']);

        FaDbFake::deactivate();

        $this->removeDirectory($this->uploadDir);
        $this->removeDirectory($this->sourceDir);

        parent::tearDown();
    }

    public function testConstructorCreatesTheUploadDirectoryAndProtectsIt(): void
    {
        $target = $this->uploadDir . '/nested/uploads';

        new UploadedFileManager($target);

        $this->assertDirectoryExists($target);
        $this->assertFileExists($target . '/.htaccess');
        $this->assertStringContainsString('Deny from all', (string) file_get_contents($target . '/.htaccess'));
    }

    public function testConstructorUsesTheCompanyUploadDirectoryByDefault(): void
    {
        $companyPath = sys_get_temp_dir() . '/ksf_bank_import_company_' . uniqid('', true);
        $GLOBALS['__ksf_test_company_path'] = $companyPath;

        try {
            $manager = new UploadedFileManager();

            $reflection = new \ReflectionClass(UploadedFileManager::class);
            $property = $reflection->getProperty('upload_dir');
            $property->setAccessible(true);

            $this->assertSame(
                rtrim($companyPath, '/\\') . '/bank_imports/uploads',
                (string) $property->getValue($manager)
            );
            $this->assertDirectoryExists($companyPath . '/bank_imports/uploads');
        } finally {
            unset($GLOBALS['__ksf_test_company_path']);
            $this->removeDirectory($companyPath . '/bank_imports/uploads');
            $this->removeDirectory($companyPath . '/bank_imports');
            $this->removeDirectory($companyPath);
        }
    }

    public function testConstructorKeepsAnExistingHtaccessFile(): void
    {
        file_put_contents($this->uploadDir . '/.htaccess', 'custom rules');

        new UploadedFileManager($this->uploadDir);

        $this->assertSame('custom rules', file_get_contents($this->uploadDir . '/.htaccess'));
    }

    public function testSaveUploadedFileStoresTheFileAndRecordsTheMetadata(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('INSERT INTO', true);
        $this->fake->insertId = 77;

        $fileId = $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx', 3, 'some notes');

        $this->assertSame(77, $fileId);

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString('bi_uploaded_files', $sql);
        $this->assertStringContainsString('statement.qfx', $sql);
        $this->assertStringContainsString('qfx', $sql);
        $this->assertStringContainsString('admin', $sql);

        $stored = glob($this->uploadDir . '/statement_*.qfx');
        $this->assertCount(1, $stored);
        $this->assertFileExists($stored[0]);
    }

    public function testSaveUploadedFileRejectsAFileThatWasNotUploaded(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->uploadIsValid = false;

        $this->assertFalse($manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
        $this->assertFalse($manager->saveUploadedFile([], 'qfx'));
    }

    public function testSaveUploadedFileReturnsFalseWhenTheFileCannotBeMoved(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->moveSucceeds = false;

        $this->assertFalse($manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
    }

    public function testSaveUploadedFileReturnsFalseWhenTheInsertFails(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('INSERT INTO', false);

        $this->assertFalse($manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
    }

    public function testSaveUploadedFileBlocksADuplicateWhenTheActionIsBlock(): void
    {
        Config::getInstance()->set('upload.check_duplicates', true);
        Config::getInstance()->set('upload.duplicate_action', 'block');

        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT id, filename, file_path', [[
            'id' => 5,
            'filename' => 'stored.qfx',
            'file_path' => $this->uploadDir . '/stored.qfx',
            'upload_date' => '2026-01-15 10:00:00',
            'upload_user' => 'admin',
        ]]);

        $this->assertSame(-999, $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
    }

    public function testSaveUploadedFileWarnsAboutADuplicateWhenTheActionIsWarn(): void
    {
        Config::getInstance()->set('upload.check_duplicates', true);
        Config::getInstance()->set('upload.duplicate_action', 'warn');

        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT id, filename, file_path', [[
            'id' => 5,
            'filename' => 'stored.qfx',
            'file_path' => $this->uploadDir . '/stored.qfx',
            'upload_date' => '2026-01-15 10:00:00',
            'upload_user' => 'admin',
        ]]);

        $result = $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx');

        $this->assertSame(-5, $result);
        $this->assertArrayHasKey('duplicate_warnings', $_SESSION);
        $warning = $_SESSION['duplicate_warnings'][0];
        $this->assertSame('statement.qfx', $warning['filename']);
        $this->assertSame(5, $warning['existing_id']);
        $this->assertSame('admin', $warning['upload_user']);
    }

    public function testSaveUploadedFileAllowsADuplicateWhenTheActionIsAllow(): void
    {
        Config::getInstance()->set('upload.check_duplicates', true);
        Config::getInstance()->set('upload.duplicate_action', 'allow');

        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT id, filename, file_path', [[
            'id' => 5,
            'filename' => 'stored.qfx',
            'file_path' => $this->uploadDir . '/stored.qfx',
            'upload_date' => '2026-01-15 10:00:00',
            'upload_user' => 'admin',
        ]]);

        $this->assertSame(-5, $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
        $this->assertArrayNotHasKey('duplicate_warnings', $_SESSION);
    }

    public function testSaveUploadedFileSkipsTheDuplicateCheckWhenTheUploadIsForced(): void
    {
        Config::getInstance()->set('upload.check_duplicates', true);
        Config::getInstance()->set('upload.duplicate_action', 'block');

        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('INSERT INTO', true);
        $this->fake->insertId = 12;

        $this->assertSame(12, $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx', null, null, true));
    }

    public function testAnIdenticalFileIsDetectedAsADuplicate(): void
    {
        Config::getInstance()->set('upload.check_duplicates', true);

        $manager = new UploadedFileManager($this->uploadDir);

        // The previously stored file has exactly the same content.
        $existingPath = $this->uploadDir . '/stored.qfx';
        file_put_contents($existingPath, 'file body');

        $this->fake->on('SELECT id, filename, file_path', [[
            'id' => 5,
            'filename' => 'stored.qfx',
            'file_path' => $existingPath,
            'upload_date' => '2026-01-15 10:00:00',
            'upload_user' => 'admin',
        ]]);

        $this->assertSame(-5, $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
    }

    public function testAFileWithTheSameNameAndSizeButDifferentContentIsNotADuplicate(): void
    {
        Config::getInstance()->set('upload.check_duplicates', true);

        $manager = new UploadedFileManager($this->uploadDir);

        $existingPath = $this->uploadDir . '/stored.qfx';
        file_put_contents($existingPath, 'completely different content');

        $this->fake->on('SELECT id, filename, file_path', [[
            'id' => 5,
            'filename' => 'stored.qfx',
            'file_path' => $existingPath,
            'upload_date' => '2026-01-15 10:00:00',
            'upload_user' => 'admin',
        ]]);
        $this->fake->on('INSERT INTO', true);
        $this->fake->insertId = 31;

        $this->assertSame(31, $manager->saveUploadedFile($this->uploadedFile('statement.qfx'), 'qfx'));
    }

    public function testLinkFileToStatementsLinksEveryStatementAndUpdatesTheCount(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('INSERT IGNORE INTO', true);
        $this->fake->on('UPDATE', true);

        $this->assertTrue($manager->linkFileToStatements(7, [10, 11]));

        $sqls = array_column($this->fake->queries, 'sql');
        $inserts = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'INSERT IGNORE') === 0;
        }));

        $this->assertCount(2, $inserts);
        $this->assertStringContainsString('bi_file_statements', $inserts[0]);
    }

    public function testLinkFileToStatementsWithoutStatementsIsANoOp(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->assertTrue($manager->linkFileToStatements(7, []));
        $this->assertSame(0, $this->fake->queryCount());
    }

    public function testLinkFileToStatementsReportsAFailure(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('INSERT IGNORE INTO', false);

        $this->assertFalse($manager->linkFileToStatements(7, [10]));
    }

    public function testGetUploadedFilesAppliesTheFilters(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT f.*', [['id' => 7], ['id' => 8]]);

        $files = $manager->getUploadedFiles([
            'user' => 'admin',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'parser_type' => 'qfx',
        ], 10, 20);

        $this->assertCount(2, $files);

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString("f.upload_user = 'admin'", $sql);
        $this->assertStringContainsString("f.upload_date >= '2026-01-01'", $sql);
        $this->assertStringContainsString("f.upload_date <= '2026-12-31'", $sql);
        $this->assertStringContainsString("f.parser_type = 'qfx'", $sql);
        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $sql);
    }

    public function testGetFileDetailsReturnsTheFileWithItsLinkedStatements(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT f.*', [[
            'id' => 7,
            'filename' => 'stored.qfx',
            'file_path' => $this->uploadDir . '/stored.qfx',
            'file_type' => 'application/x-qfx',
            'file_size' => 10,
            'original_filename' => 'statement.qfx',
        ]]);
        $this->fake->on('SELECT s.*', [['id' => 10]]);

        $details = $manager->getFileDetails(7);

        $this->assertSame(7, $details['id']);
        $this->assertCount(1, $details['statements']);
    }

    public function testGetFileDetailsReturnsNullForAnUnknownFile(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT f.*', []);

        $this->assertNull($manager->getFileDetails(7));
    }

    public function testGetLinkedStatementsReturnsTheStatementRows(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT s.*', [['id' => 10], ['id' => 11]]);

        $this->assertCount(2, $manager->getLinkedStatements(7));
    }

    public function testDownloadFileStreamsTheFileToTheBrowser(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $path = $this->uploadDir . '/stored.qfx';
        file_put_contents($path, 'statement body');

        $this->fake->on('SELECT f.*', [[
            'id' => 7,
            'file_path' => $path,
            'file_type' => 'application/x-qfx',
            'file_size' => 13,
            'original_filename' => 'statement.qfx',
        ]]);
        $this->fake->on('SELECT s.*', []);

        $this->assertTrue($manager->downloadFile(7));
        $this->assertCount(1, $this->fake->readFiles);
        $this->assertSame($path, $this->fake->readFiles[0]['path']);
        $this->assertSame('statement body', $this->fake->readFiles[0]['contents']);
        $this->assertContains('Content-Disposition: attachment; filename="statement.qfx"', $this->fake->headers);
    }

    public function testDownloadFileFailsWhenTheFileIsMissingOnDisk(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT f.*', [[
            'id' => 7,
            'file_path' => $this->uploadDir . '/does-not-exist.qfx',
            'file_type' => 'application/x-qfx',
            'file_size' => 13,
            'original_filename' => 'statement.qfx',
        ]]);
        $this->fake->on('SELECT s.*', []);

        $this->assertFalse($manager->downloadFile(7));
    }

    public function testDownloadFileFailsWhenTheUserHasNoAccess(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $path = $this->uploadDir . '/stored.qfx';
        file_put_contents($path, 'statement body');

        $this->fake->on('SELECT f.*', [[
            'id' => 7,
            'file_path' => $path,
            'file_type' => 'application/x-qfx',
            'file_size' => 13,
            'original_filename' => 'statement.qfx',
        ]]);
        $this->fake->on('SELECT s.*', []);
        $this->fake->accessAllowed = false;

        $this->assertFalse($manager->downloadFile(7));
    }

    public function testDeleteFileRemovesTheFileFromDiskAndTheDatabase(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $path = $this->uploadDir . '/stored.qfx';
        file_put_contents($path, 'statement body');

        $this->fake->on('SELECT f.*', [[
            'id' => 7,
            'file_path' => $path,
            'file_type' => 'application/x-qfx',
            'file_size' => 13,
            'original_filename' => 'statement.qfx',
        ]]);
        $this->fake->on('SELECT s.*', []);
        $this->fake->on('DELETE FROM', true);

        $this->assertTrue($manager->deleteFile(7));
        $this->assertFileDoesNotExist($path);
        $this->assertStringContainsString('DELETE FROM', $this->fake->lastSql());
    }

    public function testDeleteFileReturnsFalseForAnUnknownFile(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT f.*', []);

        $this->assertFalse($manager->deleteFile(7));
    }

    public function testGetTotalFileCountAppliesTheFilters(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT COUNT(*) as cnt', [['cnt' => 9]]);

        $this->assertSame(9, $manager->getTotalFileCount(['parser_type' => 'qfx']));
        $this->assertStringContainsString("f.parser_type = 'qfx'", $this->fake->lastSql());
    }

    public function testGetTotalFileCountAppliesEveryFilter(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('SELECT COUNT(*) as cnt', [['cnt' => 9]]);

        $count = $manager->getTotalFileCount([
            'user' => 'admin',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'parser_type' => 'qfx',
        ]);

        $this->assertSame(9, $count);

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString("f.upload_user = 'admin'", $sql);
        $this->assertStringContainsString("f.upload_date >= '2026-01-01'", $sql);
        $this->assertStringContainsString("f.upload_date <= '2026-12-31'", $sql);
        $this->assertStringContainsString("f.parser_type = 'qfx'", $sql);
    }

    public function testGetStorageStatsReturnsTheAggregateRow(): void
    {
        $manager = new UploadedFileManager($this->uploadDir);

        $this->fake->on('total_files', [[
            'total_files' => 4,
            'total_size' => 1000,
            'latest_upload' => '2026-01-15 10:00:00',
            'first_upload' => '2026-01-01 10:00:00',
        ]]);

        $stats = $manager->getStorageStats();

        $this->assertSame(4, $stats['total_files']);
        $this->assertSame(1000, $stats['total_size']);
    }

    /**
     * @return array<string,mixed>
     */
    private function uploadedFile(string $name): array
    {
        $tmp = $this->sourceDir . '/' . $name;
        file_put_contents($tmp, 'file body');

        return [
            'name' => $name,
            'tmp_name' => $tmp,
            'size' => 9,
            'type' => 'application/x-qfx',
            'error' => UPLOAD_ERR_OK,
        ];
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . '/*') ?: [] as $entry) {
            if (is_dir($entry)) {
                $this->removeDirectory($entry);
            } elseif (is_file($entry)) {
                unlink($entry);
            }
        }
        foreach (glob($directory . '/.*') ?: [] as $entry) {
            if (is_file($entry) && basename($entry) !== '.' && basename($entry) !== '..') {
                unlink($entry);
            }
        }

        rmdir($directory);
    }
}
