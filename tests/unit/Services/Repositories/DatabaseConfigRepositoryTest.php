<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services\Repositories;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/../Support/fa_db_stubs_repository.php';

/**
 * Unit tests for the database backed configuration repository.
 */
final class DatabaseConfigRepositoryTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    /** @var DatabaseConfigRepository */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();
        $this->repository = new DatabaseConfigRepository();
    }

    protected function tearDown(): void
    {
        FaDbFake::deactivate();

        parent::tearDown();
    }

    public function testGetReturnsTheCachedValueForAKey(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', [
            ['config_key' => 'upload.max_upload_size_mb', 'config_value' => '25', 'config_type' => 'integer'],
            ['config_key' => 'upload.check_duplicates', 'config_value' => '1', 'config_type' => 'boolean'],
        ]);

        $this->assertSame(25, $this->repository->get('upload.max_upload_size_mb'));
        $this->assertTrue($this->repository->get('upload.check_duplicates'));
    }

    public function testGetReturnsTheDefaultForAnUnknownKey(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', []);

        $this->assertSame('fallback', $this->repository->get('missing.key', 'fallback'));
    }

    public function testGetOnlyLoadsTheCacheOnce(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', [
            ['config_key' => 'a', 'config_value' => '1', 'config_type' => 'integer'],
        ]);

        $this->repository->get('a');
        $queriesAfterFirstRead = $this->fake->queryCount();

        $this->repository->get('a');
        $this->repository->get('b', null);

        $this->assertSame($queriesAfterFirstRead, $this->fake->queryCount());
    }

    public function testHasReportsWhetherAKeyExists(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', [
            ['config_key' => 'a', 'config_value' => '1', 'config_type' => 'integer'],
        ]);

        $this->assertTrue($this->repository->has('a'));
        $this->assertFalse($this->repository->has('b'));
    }

    public function testSetStoresTheValueAndRecordsTheChangeInTheHistory(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', []);
        $this->fake->on('SELECT is_system FROM', [['is_system' => 0]]);
        $this->fake->on('SELECT config_type FROM', [['config_type' => 'integer']]);
        $this->fake->on('(config_key, config_value, updated_at, updated_by)', [['affected' => 1]]);
        $this->fake->on('bi_config_history', [['affected' => 1]]);

        $this->assertTrue($this->repository->set('upload.batch_size', 500, 'admin', 'because'));

        $sqls = array_column($this->fake->queries, 'sql');
        $insertSql = '';
        $historySql = '';
        foreach ($sqls as $sql) {
            if (stripos($sql, 'INSERT INTO') === 0 && stripos($sql, 'bi_config_history') === false) {
                $insertSql = $sql;
            }
            if (stripos($sql, 'INSERT INTO') === 0 && stripos($sql, 'bi_config_history') !== false) {
                $historySql = $sql;
            }
        }

        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $insertSql);
        $this->assertStringContainsString('500', $insertSql);
        $this->assertStringContainsString('admin', $insertSql);
        $this->assertStringContainsString('because', $historySql);
        $this->assertStringContainsString('500', $historySql);
    }

    public function testSetUpdatesTheInMemoryCacheWithTheTypedValue(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', []);
        $this->fake->on('SELECT is_system FROM', [['is_system' => 0]]);
        $this->fake->on('SELECT config_type FROM', [['config_type' => 'boolean']]);
        $this->fake->on('(config_key, config_value, updated_at, updated_by)', [['affected' => 1]]);
        $this->fake->on('bi_config_history', [['affected' => 1]]);

        $this->repository->set('upload.check_duplicates', true);

        $this->assertTrue($this->repository->get('upload.check_duplicates'));
    }

    public function testSetRefusesToChangeSystemConfiguration(): void
    {
        $this->fake->on('SELECT is_system FROM', [['is_system' => 1]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot modify system configuration: security.require_file_permission');

        $this->repository->set('security.require_file_permission', false);
    }

    public function testSetReturnsFalseWhenTheUpdateFails(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', []);
        $this->fake->on('SELECT is_system FROM', [['is_system' => 0]]);
        $this->fake->on('(config_key, config_value, updated_at, updated_by)', false);

        $this->assertFalse($this->repository->set('upload.batch_size', 500));
    }

    public function testDeleteRemovesTheValueFromTheCache(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', [
            ['config_key' => 'a', 'config_value' => '1', 'config_type' => 'integer'],
        ]);
        $this->fake->on('SELECT is_system FROM', [['is_system' => 0]]);
        $this->fake->on('DELETE FROM', [['affected' => 1]]);

        // Load the cache first so that the delete can evict the entry.
        $this->assertSame(1, $this->repository->get('a'));

        $this->assertTrue($this->repository->delete('a'));
        $this->assertFalse($this->repository->has('a'));
    }

    public function testDeleteReturnsFalseWhenTheDeleteFails(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', []);
        $this->fake->on('SELECT is_system FROM', [['is_system' => 0]]);
        $this->fake->on('DELETE FROM', false);

        $this->assertFalse($this->repository->delete('a'));
    }

    public function testDeleteRefusesToRemoveSystemConfiguration(): void
    {
        $this->fake->on('SELECT is_system FROM', [['is_system' => 1]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete system configuration: security.allow_file_download');

        $this->repository->delete('security.allow_file_download');
    }

    public function testSetFallsBackToAStringTypeWhenTheTypeIsUnknown(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', []);
        // Neither the system flag nor the type are known for this key.
        $this->fake->on('SELECT is_system FROM', []);
        $this->fake->on('SELECT config_type FROM', []);
        $this->fake->on('(config_key, config_value, updated_at, updated_by)', [['affected' => 1]]);
        $this->fake->on('bi_config_history', [['affected' => 1]]);

        $this->assertTrue($this->repository->set('unknown.key', 'a value'));

        $this->assertSame('a value', $this->repository->get('unknown.key'));
    }

    public function testFloatValuesAreCastToFloat(): void
    {
        $this->fake->on('SHOW TABLES LIKE', [['id' => 1]]);
        $this->fake->on('SELECT config_key, config_value, config_type', [
            ['config_key' => 'rate', 'config_value' => '1.5', 'config_type' => 'float'],
        ]);

        $value = $this->repository->get('rate');

        $this->assertIsFloat($value);
        $this->assertEqualsWithDelta(1.5, $value, 1e-9);
    }

    public function testGetByCategoryReturnsTheTypedValuesOfThatCategory(): void
    {
        $this->fake->on('SELECT config_key, config_value, config_type, description, is_system', [
            [
                'config_key' => 'duplicate_action',
                'config_value' => 'warn',
                'config_type' => 'string',
                'description' => 'Duplicate handling',
                'is_system' => 0,
            ],
            [
                'config_key' => 'retention_days',
                'config_value' => '365',
                'config_type' => 'integer',
                'description' => 'Retention',
                'is_system' => 1,
            ],
        ]);

        $configs = $this->repository->getByCategory('upload');

        $this->assertArrayHasKey('duplicate_action', $configs);
        $this->assertSame('warn', $configs['duplicate_action']['value']);
        $this->assertSame('string', $configs['duplicate_action']['type']);
        $this->assertSame('Duplicate handling', $configs['duplicate_action']['description']);
        $this->assertFalse($configs['duplicate_action']['is_system']);
        $this->assertSame(365, $configs['retention_days']['value']);
        $this->assertTrue($configs['retention_days']['is_system']);
    }

    public function testGetAllGroupsTheConfigurationByCategory(): void
    {
        $this->fake->on('SELECT category, config_key', [
            [
                'category' => 'upload',
                'config_key' => 'duplicate_action',
                'config_value' => 'block',
                'config_type' => 'string',
                'description' => 'Duplicate handling',
                'is_system' => 0,
            ],
            [
                'category' => 'storage',
                'config_key' => 'retention_days',
                'config_value' => '30',
                'config_type' => 'integer',
                'description' => 'Retention',
                'is_system' => 0,
            ],
        ]);

        $configs = $this->repository->getAll();

        $this->assertArrayHasKey('upload', $configs);
        $this->assertArrayHasKey('storage', $configs);
        $this->assertSame('block', $configs['upload']['duplicate_action']['value']);
        $this->assertSame(30, $configs['storage']['retention_days']['value']);
    }

    public function testGetHistoryReturnsTheAuditTrail(): void
    {
        $this->fake->on('SELECT config_key, old_value, new_value', [
            [
                'config_key' => 'upload.batch_size',
                'old_value' => '100',
                'new_value' => '500',
                'changed_at' => '2026-01-15 10:00:00',
                'changed_by' => 'admin',
                'change_reason' => 'larger batches',
            ],
        ]);

        $history = $this->repository->getHistory('upload.batch_size', 10);

        $this->assertCount(1, $history);
        $this->assertSame('500', $history[0]['new_value']);
        $this->assertStringContainsString('LIMIT 10', $this->fake->lastSql());
        $this->assertStringContainsString("config_key = 'upload.batch_size'", $this->fake->lastSql());
    }

    public function testGetHistoryWithoutAKeyReturnsEveryRecord(): void
    {
        $this->fake->on('SELECT config_key, old_value, new_value', []);

        $this->assertSame([], $this->repository->getHistory());
        $this->assertStringNotContainsString('WHERE', $this->fake->lastSql());
    }

    public function testMissingTablesAreCreatedAndSeededOnFirstUse(): void
    {
        // No rows for the SHOW TABLES probe: the tables have to be created.
        $this->fake->on('SHOW TABLES LIKE', []);
        $this->fake->on('SELECT config_key, config_value, config_type', []);

        $this->repository->get('anything');

        $sqls = array_column($this->fake->queries, 'sql');
        $createStatements = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'CREATE TABLE') === 0;
        }));
        $defaultInserts = array_values(array_filter($sqls, function (string $sql): bool {
            return stripos($sql, 'INSERT INTO') === 0
                && stripos($sql, 'config_type, description, category, is_system') !== false;
        }));

        $this->assertCount(2, $createStatements);
        $this->assertStringContainsString('bi_config', $createStatements[0]);
        $this->assertStringContainsString('bi_config_history', $createStatements[1]);
        $this->assertNotEmpty($defaultInserts);
        $this->assertStringContainsString('check_duplicates', $defaultInserts[0]);
    }
}
