<?php

/**
 * Bank Import Config Test
 *
 * Tests for BankImportConfig class
 *
 * @package    Tests\Unit\Config
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Unit\Config;

// Load FrontAccounting function stubs
require_once __DIR__ . '/../../helpers/fa_functions.php';

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Config\BankImportConfig;

class BankImportConfigTest extends TestCase
{
    /**
     * Reset configuration before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear test preferences
        global $_test_company_prefs;
        $_test_company_prefs = [];
    }
    /**
     * @test
     */
    public function it_returns_true_for_default_trans_ref_logging(): void
    {
        // Default should be enabled
        $enabled = BankImportConfig::getTransRefLoggingEnabled();
        
        $this->assertTrue($enabled);
    }

    /**
     * @test
     */
    public function it_returns_default_account_0000(): void
    {
        // Default account should be '0000'
        $account = BankImportConfig::getTransRefAccount();
        
        $this->assertEquals('0000', $account);
    }

    /**
     * @test
     */
    public function it_has_constant_for_default_account(): void
    {
        // Verify default is defined correctly
        $account = BankImportConfig::getTransRefAccount();
        
        $this->assertIsString($account);
        $this->assertNotEmpty($account);
    }

    /**
     * @test
     */
    public function it_returns_boolean_for_logging_enabled(): void
    {
        $enabled = BankImportConfig::getTransRefLoggingEnabled();
        
        $this->assertIsBool($enabled);
    }

    /**
     * @test
     */
    public function it_returns_string_for_account(): void
    {
        $account = BankImportConfig::getTransRefAccount();
        
        $this->assertIsString($account);
    }

    /**
     * @test
     */
    public function it_has_get_all_settings_method(): void
    {
        $this->assertTrue(
            method_exists(BankImportConfig::class, 'getAllSettings')
        );
    }

    /**
     * @test
     */
    public function it_returns_array_from_get_all_settings(): void
    {
        $settings = BankImportConfig::getAllSettings();
        
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('trans_ref_logging_enabled', $settings);
        $this->assertArrayHasKey('trans_ref_account', $settings);
    }

    /**
     * @test
     */
    public function it_has_reset_to_defaults_method(): void
    {
        $this->assertTrue(
            method_exists(BankImportConfig::class, 'resetToDefaults')
        );
    }

    /**
     * @test
     */
    public function it_validates_account_code_format(): void
    {
        // Account code should be a string
        $account = BankImportConfig::getTransRefAccount();
        
        $this->assertIsString($account);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $account);
    }

    /**
     * @test
     */
    public function it_has_static_methods_only(): void
    {
        $reflection = new \ReflectionClass(BankImportConfig::class);
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->assertTrue(
                $method->isStatic(),
                "Method {$method->getName()} should be static"
            );
        }
    }
}
