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
    /** @var array Saved FAMock table state for restoration after each test */
    private $savedFaTable = [];

    /**
     * Reset configuration before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear FAMock's in-memory company preferences (used by get/set_company_pref)
        global $__fa_company_prefs;
        $__fa_company_prefs = [];
        
        // Also clear the legacy test global for compatibility
        global $_test_company_prefs;
        $_test_company_prefs = [];
        
        // Save and seed FAMock's virtual table with GL accounts needed by tests.
        // glAccountExists() queries chart_master; FAMock's db_fetch searches
        // __fa_table by pref_name, returning any matching row as evidence
        // the account exists.
        global $__fa_table;
        $this->savedFaTable = $__fa_table ?? [];
        if (!is_array($__fa_table)) {
            $__fa_table = [];
        }
        foreach (['1060', '2100', '9999', '1234'] as $code) {
            $__fa_table[] = ['pref_name' => $code, 'pref_value' => 'Test Account ' . $code];
        }
    }

    /**
     * Restore FAMock table state after each test to prevent leaking
     */
    protected function tearDown(): void
    {
        global $__fa_table;
        $__fa_table = $this->savedFaTable;
        parent::tearDown();
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
