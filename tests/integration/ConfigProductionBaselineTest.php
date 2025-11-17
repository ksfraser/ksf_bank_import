<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Config\Config;

/**
 * Production Baseline Test for Config.php
 * 
 * This test documents the PRODUCTION state of Config.php.
 * 
 * Purpose: Verify that the Config class on main branch maintains
 * backward compatibility with the production version.
 * 
 * File under test: src/Ksfraser/FaBankImport/config/Config.php
 * 
 * Key behaviors documented:
 * 1. Singleton pattern implementation
 * 2. Three configuration sections: db, logging, transaction
 * 3. Dot notation for nested config access
 * 4. Environment variable support for database config
 * 5. Default values for missing keys
 * 
 * Changes on main (expected):
 * - Added 'upload' configuration section (new feature)
 * 
 * @package KsfBankImport\Tests\Integration
 */
class ConfigProductionBaselineTest extends TestCase
{
    /**
     * @test
     * PROD BASELINE: Config class exists in correct namespace
     */
    public function testProdBaseline_ClassExists()
    {
        $this->assertTrue(
            class_exists('Ksfraser\FaBankImport\Config\Config'),
            'PROD BASELINE: Config class must exist in Ksfraser\FaBankImport\Config namespace'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Implements singleton pattern
     */
    public function testProdBaseline_ImplementsSingletonPattern()
    {
        $instance1 = Config::getInstance();
        $instance2 = Config::getInstance();
        
        $this->assertInstanceOf(
            Config::class,
            $instance1,
            'PROD BASELINE: getInstance() must return Config instance'
        );
        
        $this->assertSame(
            $instance1,
            $instance2,
            'PROD BASELINE: Singleton must return same instance'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has database configuration section
     */
    public function testProdBaseline_HasDatabaseConfig()
    {
        $config = Config::getInstance();
        
        $this->assertIsArray(
            $config->get('db'),
            'PROD BASELINE: db config section must exist and be an array'
        );
        
        // Verify all db config keys exist
        $this->assertNotNull($config->get('db.host'), 'PROD BASELINE: db.host must exist');
        $this->assertNotNull($config->get('db.name'), 'PROD BASELINE: db.name must exist');
        $this->assertNotNull($config->get('db.user'), 'PROD BASELINE: db.user must exist');
        $this->assertIsString($config->get('db.pass'), 'PROD BASELINE: db.pass must exist (can be empty string)');
    }
    
    /**
     * @test
     * PROD BASELINE: Has logging configuration section
     */
    public function testProdBaseline_HasLoggingConfig()
    {
        $config = Config::getInstance();
        
        $this->assertIsArray(
            $config->get('logging'),
            'PROD BASELINE: logging config section must exist and be an array'
        );
        
        $this->assertTrue(
            $config->get('logging.enabled'),
            'PROD BASELINE: logging.enabled must be true'
        );
        
        $this->assertIsString(
            $config->get('logging.path'),
            'PROD BASELINE: logging.path must be a string'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has transaction configuration section
     */
    public function testProdBaseline_HasTransactionConfig()
    {
        $config = Config::getInstance();
        
        $this->assertIsArray(
            $config->get('transaction'),
            'PROD BASELINE: transaction config section must exist and be an array'
        );
        
        $this->assertEquals(
            ['C', 'D', 'B'],
            $config->get('transaction.allowed_types'),
            'PROD BASELINE: transaction.allowed_types must be [C, D, B]'
        );
        
        $this->assertEquals(
            1000000.00,
            $config->get('transaction.max_amount'),
            'PROD BASELINE: transaction.max_amount must be 1000000.00'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Should NOT have upload configuration section (added in main)
     */
    public function testProdBaseline_NoUploadConfig()
    {
        $config = Config::getInstance();
        
        $this->assertNull(
            $config->get('upload'),
            'PROD BASELINE: upload config section should NOT exist on prod (added in main)'
        );
        
        $this->assertNull(
            $config->get('upload.check_duplicates'),
            'PROD BASELINE: upload.check_duplicates should NOT exist (added in main)'
        );
        
        $this->assertNull(
            $config->get('upload.duplicate_window_days'),
            'PROD BASELINE: upload.duplicate_window_days should NOT exist (added in main)'
        );
        
        $this->assertNull(
            $config->get('upload.duplicate_action'),
            'PROD BASELINE: upload.duplicate_action should NOT exist (added in main)'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: get() supports dot notation for nested access
     */
    public function testProdBaseline_GetSupportsDotNotation()
    {
        $config = Config::getInstance();
        
        // Test nested access
        $dbHost = $config->get('db.host');
        $this->assertIsString($dbHost, 'PROD BASELINE: Dot notation must work for nested keys');
        
        // Test default value for missing key
        $missing = $config->get('nonexistent.key', 'default_value');
        $this->assertEquals(
            'default_value',
            $missing,
            'PROD BASELINE: Must return default value for missing keys'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: set() supports dot notation for nested assignment
     */
    public function testProdBaseline_SetSupportsDotNotation()
    {
        $config = Config::getInstance();
        
        // Set a new nested value
        $config->set('test.nested.key', 'test_value');
        
        $this->assertEquals(
            'test_value',
            $config->get('test.nested.key'),
            'PROD BASELINE: set() must support dot notation for nested assignment'
        );
        
        // Set an existing value
        $config->set('transaction.max_amount', 500000.00);
        
        $this->assertEquals(
            500000.00,
            $config->get('transaction.max_amount'),
            'PROD BASELINE: set() must update existing values'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Database config uses environment variables with fallbacks
     */
    public function testProdBaseline_DatabaseConfigUsesEnvVariables()
    {
        $config = Config::getInstance();
        
        // Test that environment variable fallbacks work
        // (We can't test env vars directly in unit tests, but we can verify defaults exist)
        $host = $config->get('db.host');
        $name = $config->get('db.name');
        $user = $config->get('db.user');
        
        $this->assertNotEmpty($host, 'PROD BASELINE: db.host must have a default value');
        $this->assertNotEmpty($name, 'PROD BASELINE: db.name must have a default value');
        $this->assertNotEmpty($user, 'PROD BASELINE: db.user must have a default value');
    }
    
    /**
     * @test
     * PROD BASELINE: Only three configuration sections exist (db, logging, transaction)
     */
    public function testProdBaseline_HasExactlyThreeConfigSections()
    {
        $reflection = new \ReflectionClass(Config::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        
        // Create a fresh instance to check initial settings
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);
        
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settings = $settingsProperty->getValue($instance);
        
        $this->assertCount(
            3,
            $settings,
            'PROD BASELINE: Config should have exactly 3 sections (db, logging, transaction). Main adds upload section.'
        );
        
        $this->assertArrayHasKey('db', $settings, 'PROD BASELINE: Must have db section');
        $this->assertArrayHasKey('logging', $settings, 'PROD BASELINE: Must have logging section');
        $this->assertArrayHasKey('transaction', $settings, 'PROD BASELINE: Must have transaction section');
    }
}
