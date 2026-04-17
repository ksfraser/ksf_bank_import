<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Infrastructure\Config;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Infrastructure\Config\ConfigManager;
use Ksfraser\FaBankImport\Infrastructure\Config\ConfigException;

/**
 * Tests for ConfigManager
 * 
 * Verifies configuration loading, type-safe accessors, and environment handling.
 * 
 * @author Kevin Fraser
 * @since 2.2.0
 */
class ConfigManagerTest extends TestCase
{
    private ConfigManager $config;

    protected function setUp(): void
    {
        // Save original environment
        $this->originalEnv = $_ENV;
        
        // Set test environment variables
        $_ENV['APP_ENV'] = 'test';
        $_ENV['DB_HOST'] = 'test-host';
        $_ENV['DB_PORT'] = '3307';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASS'] = 'test_pass';
        $_ENV['DB_CHARSET'] = 'utf8';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_LOG_LEVEL'] = 'debug';
        $_ENV['LOG_PATH'] = '/tmp/test.log';
        $_ENV['LOG_ENABLED'] = 'false';
        $_ENV['TRANSACTION_MAX_AMOUNT'] = '500000.00';
        $_ENV['TRANSACTION_TYPES'] = 'C,D';

        $this->config = new ConfigManager('test');
    }

    protected function tearDown(): void
    {
        // Restore original environment
        $_ENV = $this->originalEnv;
    }

    public function testGetString(): void
    {
        $host = $this->config->getString('database.host');
        $this->assertEquals('test-host', $host);
    }

    public function testGetStringWithDefault(): void
    {
        $value = $this->config->getString('nonexistent.key', 'default-value');
        $this->assertEquals('default-value', $value);
    }

    public function testGetStringThrowsOnMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->config->getString('nonexistent.key');
    }

    public function testGetInt(): void
    {
        $port = $this->config->getInt('database.port');
        $this->assertEquals(3307, $port);
        $this->assertIsInt($port);
    }

    public function testGetIntWithDefault(): void
    {
        $value = $this->config->getInt('nonexistent.port', 5432);
        $this->assertEquals(5432, $value);
    }

    public function testGetBool(): void
    {
        $debug = $this->config->getBool('app.debug');
        $this->assertTrue($debug);

        $enabled = $this->config->getBool('logging.enabled');
        $this->assertFalse($enabled);
    }

    public function testGetBoolWithStringValues(): void
    {
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['LOG_ENABLED'] = 'false';

        $config = new ConfigManager('test');
        
        $this->assertTrue($config->getBool('app.debug'));
        $this->assertFalse($config->getBool('logging.enabled'));
    }

    public function testGetWithDotNotation(): void
    {
        $user = $this->config->get('database.user');
        $this->assertEquals('test_user', $user);
    }

    public function testGetWithDefault(): void
    {
        $value = $this->config->get('nonexistent.nested.key', 'my-default');
        $this->assertEquals('my-default', $value);
    }

    public function testIsTest(): void
    {
        $config = new ConfigManager('test');
        $this->assertTrue($config->isTest());

        $config = new ConfigManager('development');
        $this->assertFalse($config->isTest());
    }

    public function testIsDevelopment(): void
    {
        $config = new ConfigManager('development');
        $this->assertTrue($config->isDevelopment());

        $config = new ConfigManager('dev');
        $this->assertTrue($config->isDevelopment());

        $config = new ConfigManager('production');
        $this->assertFalse($config->isDevelopment());
    }

    public function testIsDebug(): void
    {
        $this->assertTrue($this->config->isDebug());
    }

    public function testGetEnvironment(): void
    {
        $this->assertEquals('test', $this->config->getEnvironment());
    }

    public function testGetDatabaseDsn(): void
    {
        $dsn = $this->config->getDatabaseDsn();
        
        $this->assertStringContainsString('mysql:', $dsn);
        $this->assertStringContainsString('host=test-host', $dsn);
        $this->assertStringContainsString('port=3307', $dsn);
        $this->assertStringContainsString('dbname=test_db', $dsn);
        $this->assertStringContainsString('charset=utf8', $dsn);
    }

    public function testGetDatabaseCredentials(): void
    {
        $creds = $this->config->getDatabaseCredentials();
        
        $this->assertArrayHasKey('dsn', $creds);
        $this->assertArrayHasKey('user', $creds);
        $this->assertArrayHasKey('password', $creds);
        
        $this->assertEquals('test_user', $creds['user']);
        $this->assertEquals('test_pass', $creds['password']);
        $this->assertStringContainsString('test_db', $creds['dsn']);
    }

    public function testValidateSuccess(): void
    {
        $this->config->validate();
        $this->assertTrue(true); // Should not throw
    }

    public function testValidateUsesDefaults(): void
    {
        // Even if env vars are missing, validation passes because of defaults
        unset($_ENV['DB_HOST']);
        
        $config = new ConfigManager('test');
        // Should not throw - defaults are used
        $config->validate();
        
        // But we can still access the value (via default)
        $host = $config->getString('database.host');
        $this->assertNotEmpty($host);
    }

    public function testNestedArrayAccess(): void
    {
        $types = $this->config->get('transaction.types');
        $this->assertIsArray($types);
        $this->assertEquals(['C', 'D'], $types);
    }

    public function testEnvironmentVariablesPriority(): void
    {
        $_ENV['DB_HOST'] = 'env-host';
        $_ENV['DB_PORT'] = '9999';

        $config = new ConfigManager('test');
        
        $this->assertEquals('env-host', $config->getString('database.host'));
        $this->assertEquals(9999, $config->getInt('database.port'));
    }

    public function testTransactionMaxAmount(): void
    {
        $maxAmount = $this->config->get('transaction.max_amount');
        $this->assertEquals(500000.00, $maxAmount);
    }

    public function testLoggingConfiguration(): void
    {
        $path = $this->config->getString('logging.path');
        $enabled = $this->config->getBool('logging.enabled');

        $this->assertEquals('/tmp/test.log', $path);
        $this->assertFalse($enabled);
    }

    public function testMigrationConfiguration(): void
    {
        $table = $this->config->get('migrations.table');
        $path = $this->config->get('migrations.path');

        $this->assertEquals('schema_migrations', $table);
        $this->assertEquals('database/migrations', $path);
    }
}
