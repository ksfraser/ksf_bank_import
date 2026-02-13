<?php

namespace Tests\Unit\Repository;

use Ksfraser\FaBankImport\Repository\DatabaseUploadedFileRepository;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class DatabaseUploadedFileRepositoryTest extends TestCase
{
    public function testFindByIdUsesInjectedAdapterAndHydratesEntity(): void
    {
        $adapter = new class([
            // ensureTablesExist(): SHOW TABLES ... (non-zero rows => tables exist)
            new \TestDbResult([['table' => TB_PREF . 'bi_uploaded_files']]),
            // findById(): SELECT ...
            new \TestDbResult([
                [
                    'id' => 10,
                    'filename' => 'stored.qfx',
                    'original_filename' => 'orig.qfx',
                    'file_path' => 'C:/tmp/stored.qfx',
                    'file_size' => 123,
                    'file_type' => 'application/qfx',
                    'upload_date' => '2025-01-01 00:00:00',
                    'upload_user' => 'tester',
                    'parser_type' => 'qfx',
                    'bank_account_id' => null,
                    'statement_count' => 0,
                    'notes' => '',
                ],
            ]),
        ]) implements DbAdapterInterface {
            /** @var array<int, string> */
            public $queries = [];

            /** @var array<int, mixed> */
            private $results;

            public function __construct(array $results)
            {
                $this->results = $results;
            }

            public function query(string $sql, string $errorMsg = '')
            {
                $this->queries[] = $sql;
                return array_shift($this->results) ?? new \TestDbResult([]);
            }

            public function fetch($result)
            {
                if ($result instanceof \TestDbResult) {
                    return $result->fetch();
                }
                return false;
            }
        };

        $repo = new DatabaseUploadedFileRepository($adapter);
        $file = $repo->findById(10);

        $this->assertNotNull($file);
        $this->assertSame(10, $file->getId());
        $this->assertCount(2, $adapter->queries);
        $this->assertStringContainsString('SHOW TABLES', $adapter->queries[0]);
    }
}
