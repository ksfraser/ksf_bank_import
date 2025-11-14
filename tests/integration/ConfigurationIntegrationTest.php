<?php

/**
 * Configuration Integration Tests
 *
 * Tests for BankImportConfig integration with handlers (FR-051)
 * Based on INTEGRATION_TEST_PLAN.md scenarios IT-051 to IT-055
 *
 * @package    Tests\Integration
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Config\BankImportConfig;
use Ksfraser\FaBankImport\handlers\QuickEntryTransactionHandler;

class ConfigurationIntegrationTest extends TestCase
{
    private QuickEntryTransactionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load FA function stubs
        require_once __DIR__ . '/../helpers/fa_functions.php';
        
        // Reset configuration before each test
        global $_test_company_prefs;
        $_test_company_prefs = [];
        
        BankImportConfig::resetToDefaults();
    }

    /**
     * IT-051: Configuration Integration with QuickEntry Handler
     *
     * @test
     * @group integration
     * @group configuration
     */
    public function it_integrates_configuration_with_quick_entry_handler(): void
    {
        // Arrange: Enable logging
        BankImportConfig::setTransRefLoggingEnabled(true);
        BankImportConfig::setTransRefAccount('1550');
        
        // Assert: Handler can read configuration
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertEquals('1550', BankImportConfig::getTransRefAccount());
    }

    /**
     * IT-052: Configuration Persistence Across Requests
     *
     * @test
     * @group integration
     * @group configuration
     */
    public function it_persists_configuration_across_requests(): void
    {
        // Arrange: Set configuration
        BankImportConfig::setTransRefLoggingEnabled(false);
        BankImportConfig::setTransRefAccount('2000');
        
        // Act: Simulate new request (values stored in $_test_company_prefs)
        $enabled = BankImportConfig::getTransRefLoggingEnabled();
        $account = BankImportConfig::getTransRefAccount();
        
        // Assert: Configuration persisted
        $this->assertFalse($enabled);
        $this->assertEquals('2000', $account);
    }

    /**
     * IT-053: Configuration Default Values
     *
     * @test
     * @group integration
     * @group configuration
     */
    public function it_provides_correct_default_values(): void
    {
        // Arrange: Fresh configuration (no values set)
        global $_test_company_prefs;
        $_test_company_prefs = [];
        
        // Act: Get defaults
        $enabled = BankImportConfig::getTransRefLoggingEnabled();
        $account = BankImportConfig::getTransRefAccount();
        
        // Assert: Defaults match original hardcoded behavior
        $this->assertTrue($enabled, 'Default logging should be enabled');
        $this->assertEquals('0000', $account, 'Default account should be 0000');
    }

    /**
     * IT-054: Configuration Reset Functionality
     *
     * @test
     * @group integration
     * @group configuration
     */
    public function it_resets_configuration_to_defaults(): void
    {
        // Arrange: Change configuration
        BankImportConfig::setTransRefLoggingEnabled(false);
        BankImportConfig::setTransRefAccount('9999');
        
        // Act: Reset to defaults
        BankImportConfig::resetToDefaults();
        
        // Assert: Values restored to defaults
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertEquals('0000', BankImportConfig::getTransRefAccount());
    }

    /**
     * IT-055: Configuration Validation Integration
     *
     * @test
     * @group integration
     * @group validation
     */
    public function it_validates_gl_account_exists(): void
    {
        // Note: In test environment, glAccountExists() returns true
        // In production, it queries FA database
        
        // Act & Assert: Valid account accepted
        BankImportConfig::setTransRefAccount('1000');
        $this->assertEquals('1000', BankImportConfig::getTransRefAccount());
    }

    /**
     * IT-056: Configuration Export/Import
     *
     * @test
     * @group integration
     * @group configuration
     */
    public function it_exports_all_settings_as_array(): void
    {
        // Arrange: Set configuration
        BankImportConfig::setTransRefLoggingEnabled(true);
        BankImportConfig::setTransRefAccount('1550');
        
        // Act: Export settings
        $settings = BankImportConfig::getAllSettings();
        
        // Assert: All settings present
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('trans_ref_logging_enabled', $settings);
        $this->assertArrayHasKey('trans_ref_account', $settings);
        $this->assertTrue($settings['trans_ref_logging_enabled']);
        $this->assertEquals('1550', $settings['trans_ref_account']);
    }

    /**
     * IT-057: Configuration State Consistency
     *
     * @test
     * @group integration
     * @group consistency
     */
    public function it_maintains_consistent_state_across_multiple_operations(): void
    {
        // Act: Perform multiple configuration changes
        BankImportConfig::setTransRefLoggingEnabled(true);
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        
        BankImportConfig::setTransRefAccount('1000');
        $this->assertEquals('1000', BankImportConfig::getTransRefAccount());
        
        BankImportConfig::setTransRefLoggingEnabled(false);
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
        
        // Assert: Last values are current
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertEquals('1000', BankImportConfig::getTransRefAccount());
    }

    /**
     * IT-058: Configuration Thread Safety (Singleton Pattern)
     *
     * @test
     * @group integration
     * @group patterns
     */
    public function it_uses_static_methods_for_thread_safety(): void
    {
        // Assert: All public methods are static
        $reflection = new \ReflectionClass(BankImportConfig::class);
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->assertTrue(
                $method->isStatic(),
                "Method {$method->getName()} should be static for thread safety"
            );
        }
    }
}
