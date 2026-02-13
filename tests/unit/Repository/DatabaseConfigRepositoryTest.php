<?php

namespace Tests\Unit\Repository;

use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class DatabaseConfigRepositoryTest extends TestCase
{
    public function testGetLoadsCacheViaInjectedAdapter(): void
    {
        $adapter = new class([
            // ensureTablesExist(): SHOW TABLES ... (non-zero rows => tables exist)
            new \TestDbResult([['table' => TB_PREF . 'bi_config']]),
            // loadCache(): SELECT config_key, config_value, config_type ...
            new \TestDbResult([
                [
                    'config_key' => 'upload.check_duplicates',
                    'config_value' => '1',
                    'config_type' => 'boolean',
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

        $repo = new DatabaseConfigRepository($adapter);
        $value = $repo->get('upload.check_duplicates', false);

        $this->assertTrue($value);
        $this->assertCount(2, $adapter->queries);
        $this->assertStringContainsString('SHOW TABLES', $adapter->queries[0]);
        $this->assertStringContainsString('SELECT config_key', $adapter->queries[1]);
    }
}
