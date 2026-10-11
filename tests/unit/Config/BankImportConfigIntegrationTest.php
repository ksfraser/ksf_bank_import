<?php

/**
 * Bank Import Config Integration Test
 *
 * Integration tests for BankImportConfig with setter methods
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

class BankImportConfigIntegrationTest extends TestCase
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
    public function it_can_set_and_get_trans_ref_logging_enabled(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(true);
        
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
    }

    /**
     * @test
     */
    public function it_can_set_and_get_trans_ref_logging_disabled(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(false);
        
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
    }

    /**
     * @test
     */
    public function it_can_set_and_get_trans_ref_account(): void
    {
        BankImportConfig::setTransRefAccount('1060');
        
        $this->assertEquals('1060', BankImportConfig::getTransRefAccount());
    }

    /**
     * @test
     */
    public function it_toggles_logging_correctly(): void
    {
        // Enable
        BankImportConfig::setTransRefLoggingEnabled(true);
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        
        // Disable
        BankImportConfig::setTransRefLoggingEnabled(false);
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
        
        // Enable again
        BankImportConfig::setTransRefLoggingEnabled(true);
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
    }

    /**
     * @test
     */
    public function it_persists_multiple_settings(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(false);
        BankImportConfig::setTransRefAccount('2100');
        
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertEquals('2100', BankImportConfig::getTransRefAccount());
    }

    /**
     * @test
     */
    public function it_resets_to_defaults(): void
    {
        // Change settings
        BankImportConfig::setTransRefLoggingEnabled(false);
        BankImportConfig::setTransRefAccount('9999');
        
        // Reset to defaults
        BankImportConfig::resetToDefaults();
        
        // Verify defaults
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertEquals('0000', BankImportConfig::getTransRefAccount());
    }

    /**
     * @test
     */
    public function it_returns_all_settings_as_array(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(false);
        BankImportConfig::setTransRefAccount('1234');
        
        $settings = BankImportConfig::getAllSettings();
        
        $this->assertIsArray($settings);
        $this->assertEquals(false, $settings['trans_ref_logging_enabled']);
        $this->assertEquals('1234', $settings['trans_ref_account']);
    }

    /**
     * @test
     */
    public function it_handles_string_to_boolean_conversion(): void
    {
        // Simulate FA storing '1' as string
        set_company_pref('bank_import_trans_ref_logging', '1');
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        
        // Simulate FA storing '0' as string
        set_company_pref('bank_import_trans_ref_logging', '0');
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
    }

    /**
     * @test
     */
    public function it_handles_empty_string_as_default(): void
    {
        // Simulate empty preference
        set_company_pref('bank_import_trans_ref_logging', '');
        
        // Should return default (true)
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
    }

    /**
     * @test
     */
    public function it_handles_null_preference_as_default(): void
    {
        // Don't set any preference (null)
        
        // Should return defaults
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertEquals('0000', BankImportConfig::getTransRefAccount());
    }
}
