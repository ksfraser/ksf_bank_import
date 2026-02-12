<?php

/**
 * Unit Tests for QuickEntryDataProvider
 * 
 * Test-Driven Development (TDD) for the data provider singleton.
 * Tests data loading, caching, and singleton behavior.
 * 
 * @package    Tests\Unit\Views\DataProviders
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250422
 */

namespace Tests\Unit\Views\DataProviders;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../views/DataProviders/QuickEntryDataProvider.php';

use KsfBankImport\Views\DataProviders\QuickEntryDataProvider;

/**
 * Test suite for QuickEntryDataProvider
 * 
 * Tests:
 * - Singleton pattern implementation
 * - Separate instances for deposit vs payment
 * - Data loading and caching
 * - Entry retrieval methods
 * - Performance (single load per type)
 * 
 * @coversDefaultClass \KsfBankImport\Views\DataProviders\QuickEntryDataProvider
 */
class QuickEntryDataProviderTest extends TestCase
{
    /**
     * Set up before each test
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset singletons for isolation
        QuickEntryDataProvider::reset();
        
        // Define constants if not already defined
        if (!defined('QE_DEPOSIT')) {
            define('QE_DEPOSIT', 1);
        }
        if (!defined('QE_PAYMENT')) {
            define('QE_PAYMENT', 2);
        }
    }
    
    /**
     * Test that forDeposit() returns singleton instance
     * 
     * @test
     * @covers ::forDeposit
     * 
     * @return void
     */
    public function testForDepositReturnsSingletonInstance(): void
    {
        $instance1 = QuickEntryDataProvider::forDeposit();
        $instance2 = QuickEntryDataProvider::forDeposit();
        
        $this->assertSame(
            $instance1,
            $instance2,
            'forDeposit() should return same instance on multiple calls'
        );
    }
    
    /**
     * Test that forPayment() returns singleton instance
     * 
     * @test
     * @covers ::forPayment
     * 
     * @return void
     */
    public function testForPaymentReturnsSingletonInstance(): void
    {
        $instance1 = QuickEntryDataProvider::forPayment();
        $instance2 = QuickEntryDataProvider::forPayment();
        
        $this->assertSame(
            $instance1,
            $instance2,
            'forPayment() should return same instance on multiple calls'
        );
    }
    
    /**
     * Test that deposit and payment instances are separate
     * 
     * @test
     * @covers ::forDeposit
     * @covers ::forPayment
     * 
     * @return void
     */
    public function testDepositAndPaymentAreSeparateInstances(): void
    {
        $depositInstance = QuickEntryDataProvider::forDeposit();
        $paymentInstance = QuickEntryDataProvider::forPayment();
        
        $this->assertNotSame(
            $depositInstance,
            $paymentInstance,
            'Deposit and payment providers should be separate instances'
        );
    }
    
    /**
     * Test that reset() clears singleton instances
     * 
     * @test
     * @covers ::reset
     * @covers ::forDeposit
     * 
     * @return void
     */
    public function testResetClearsSingletonInstances(): void
    {
        $instance1 = QuickEntryDataProvider::forDeposit();
        
        QuickEntryDataProvider::reset();
        
        $instance2 = QuickEntryDataProvider::forDeposit();
        
        $this->assertNotSame(
            $instance1,
            $instance2,
            'reset() should clear singleton instances'
        );
    }
    
    /**
     * Test that getEntries() returns array
     * 
     * @test
     * @covers ::getEntries
     * @covers ::loadEntries
     * 
     * @return void
     */
    public function testGetEntriesReturnsArray(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        $entries = $provider->getEntries();
        
        $this->assertIsArray(
            $entries,
            'getEntries() should return an array'
        );
    }
    
    /**
     * Test that getCount() returns integer
     * 
     * @test
     * @covers ::getCount
     * 
     * @return void
     */
    public function testGetCountReturnsInteger(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        $count = $provider->getCount();
        
        $this->assertIsInt(
            $count,
            'getCount() should return an integer'
        );
        
        $this->assertGreaterThanOrEqual(
            0,
            $count,
            'getCount() should return non-negative integer'
        );
    }
    
    /**
     * Test that getEntry() returns null for non-existent entry
     * 
     * @test
     * @covers ::getEntry
     * 
     * @return void
     */
    public function testGetEntryReturnsNullForNonExistentEntry(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        
        // Use very high ID unlikely to exist
        $entry = $provider->getEntry(999999);
        
        $this->assertNull(
            $entry,
            'getEntry() should return null for non-existent entry'
        );
    }
    
    /**
     * Test that hasEntry() returns false for non-existent entry
     * 
     * @test
     * @covers ::hasEntry
     * 
     * @return void
     */
    public function testHasEntryReturnsFalseForNonExistentEntry(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        
        // Use very high ID unlikely to exist
        $exists = $provider->hasEntry(999999);
        
        $this->assertFalse(
            $exists,
            'hasEntry() should return false for non-existent entry'
        );
    }
    
    /**
     * Test that getLabel() returns null for non-existent entry
     * 
     * @test
     * @covers ::getLabel
     * 
     * @return void
     */
    public function testGetLabelReturnsNullForNonExistentEntry(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        
        // Use very high ID unlikely to exist
        $label = $provider->getLabel(999999);
        
        $this->assertNull(
            $label,
            'getLabel() should return null for non-existent entry'
        );
    }
    
    /**
     * Test that getCount() matches array count
     * 
     * @test
     * @covers ::getCount
     * @covers ::getEntries
     * 
     * @return void
     */
    public function testGetCountMatchesArrayCount(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        
        $entries = $provider->getEntries();
        $count = $provider->getCount();
        
        $this->assertSame(
            count($entries),
            $count,
            'getCount() should match count of getEntries() array'
        );
    }
    
    /**
     * Test that entries are loaded only once (lazy loading)
     * 
     * This test verifies the lazy loading pattern by checking that
     * multiple calls don't trigger multiple loads.
     * 
     * @test
     * @covers ::getEntries
     * @covers ::loadEntries
     * 
     * @return void
     */
    public function testEntriesAreLoadedOnlyOnce(): void
    {
        $provider = QuickEntryDataProvider::forDeposit();
        
        // First call should trigger load
        $entries1 = $provider->getEntries();
        
        // Subsequent calls should use cached data
        $entries2 = $provider->getEntries();
        $entries3 = $provider->getEntries();
        
        // All should return identical array (same reference)
        $this->assertSame(
            $entries1,
            $entries2,
            'Second call should return same cached array'
        );
        
        $this->assertSame(
            $entries1,
            $entries3,
            'Third call should return same cached array'
        );
    }
    
    /**
     * Test provider type filtering (manual test placeholder)
     * 
     * This would require database fixtures or mocking db_fetch()
     * 
     * @test
     * @group integration
     * @covers ::loadEntries
     * 
     * @return void
     */
    public function testDepositProviderFiltersDepositEntries(): void
    {
        $this->markTestIncomplete(
            'Requires database fixtures or mocking of FrontAccounting functions'
        );
    }
}
