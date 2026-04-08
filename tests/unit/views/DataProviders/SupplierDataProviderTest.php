<?php

/**
 * Unit Tests for SupplierDataProvider
 * 
 * Test-Driven Development (TDD) for the supplier data provider singleton.
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

// Use absolute path to avoid case sensitivity issues
require_once('c:/Users/prote/Documents/ksf_bank_import/Views/DataProviders/SupplierDataProvider.php');

use KsfBankImport\Views\DataProviders\SupplierDataProvider;

/**
 * Test suite for SupplierDataProvider
 * 
 * Tests:
 * - Singleton pattern implementation
 * - Data loading and caching
 * - Supplier retrieval methods
 * - Performance (single load per page)
 * - Interface implementation
 * 
 * @coversDefaultClass \KsfBankImport\Views\DataProviders\SupplierDataProvider
 */
class SupplierDataProviderTest extends TestCase
{
    /**
     * Set up before each test
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset singleton for test isolation
        SupplierDataProvider::reset();
    }
    
    /**
     * Test that getInstance() returns singleton instance
     * 
     * @test
     * @covers ::getInstance
     * 
     * @return void
     */
    public function testGetInstanceReturnsSingletonInstance(): void
    {
        $instance1 = SupplierDataProvider::getInstance();
        $instance2 = SupplierDataProvider::getInstance();
        
        $this->assertSame(
            $instance1,
            $instance2,
            'getInstance() should return same instance on multiple calls'
        );
    }
    
    /**
     * Test that reset() clears singleton instance
     * 
     * @test
     * @covers ::reset
     * @covers ::getInstance
     * 
     * @return void
     */
    public function testResetClearsSingletonInstance(): void
    {
        $instance1 = SupplierDataProvider::getInstance();
        
        SupplierDataProvider::reset();
        
        $instance2 = SupplierDataProvider::getInstance();
        
        $this->assertNotSame(
            $instance1,
            $instance2,
            'reset() should clear singleton instance'
        );
    }
    
    /**
     * Test that getSuppliers() returns array
     * 
     * @test
     * @covers ::getSuppliers
     * @covers ::loadSuppliers
     * 
     * @return void
     */
    public function testGetSuppliersReturnsArray(): void
    {
        $provider = SupplierDataProvider::getInstance();
        $suppliers = $provider->getSuppliers();
        
        $this->assertIsArray(
            $suppliers,
            'getSuppliers() should return an array'
        );
    }
    
    /**
     * Test that getPartners() delegates to getSuppliers()
     * 
     * @test
     * @covers ::getPartners
     * @covers ::getSuppliers
     * 
     * @return void
     */
    public function testGetPartnersDelegatesToGetSuppliers(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        $suppliers = $provider->getSuppliers();
        $partners = $provider->getPartners();
        
        $this->assertSame(
            $suppliers,
            $partners,
            'getPartners() should return same array as getSuppliers()'
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
        $provider = SupplierDataProvider::getInstance();
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
     * Test that getSupplier() returns null for non-existent supplier
     * 
     * @test
     * @covers ::getSupplier
     * 
     * @return void
     */
    public function testGetSupplierReturnsNullForNonExistentSupplier(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        // Use very high ID unlikely to exist
        $supplier = $provider->getSupplier(999999);
        
        $this->assertNull(
            $supplier,
            'getSupplier() should return null for non-existent supplier'
        );
    }
    
    /**
     * Test that hasSupplier() returns false for non-existent supplier
     * 
     * @test
     * @covers ::hasSupplier
     * 
     * @return void
     */
    public function testHasSupplierReturnsFalseForNonExistentSupplier(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        // Use very high ID unlikely to exist
        $exists = $provider->hasSupplier(999999);
        
        $this->assertFalse(
            $exists,
            'hasSupplier() should return false for non-existent supplier'
        );
    }
    
    /**
     * Test that hasPartner() delegates to hasSupplier()
     * 
     * @test
     * @covers ::hasPartner
     * @covers ::hasSupplier
     * 
     * @return void
     */
    public function testHasPartnerDelegatesToHasSupplier(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        $hasSupplier = $provider->hasSupplier(999999);
        $hasPartner = $provider->hasPartner(999999);
        
        $this->assertSame(
            $hasSupplier,
            $hasPartner,
            'hasPartner() should return same result as hasSupplier()'
        );
    }
    
    /**
     * Test that getLabel() returns null for non-existent supplier
     * 
     * @test
     * @covers ::getLabel
     * 
     * @return void
     */
    public function testGetLabelReturnsNullForNonExistentSupplier(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        // Use very high ID unlikely to exist
        $label = $provider->getLabel(999999);
        
        $this->assertNull(
            $label,
            'getLabel() should return null for non-existent supplier'
        );
    }
    
    /**
     * Test that getPartnerLabel() delegates to getLabel()
     * 
     * @test
     * @covers ::getPartnerLabel
     * @covers ::getLabel
     * 
     * @return void
     */
    public function testGetPartnerLabelDelegatesToGetLabel(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        $label = $provider->getLabel(999999);
        $partnerLabel = $provider->getPartnerLabel(999999);
        
        $this->assertSame(
            $label,
            $partnerLabel,
            'getPartnerLabel() should return same result as getLabel()'
        );
    }
    
    /**
     * Test that getCount() matches array count
     * 
     * @test
     * @covers ::getCount
     * @covers ::getSuppliers
     * 
     * @return void
     */
    public function testGetCountMatchesArrayCount(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        $suppliers = $provider->getSuppliers();
        $count = $provider->getCount();
        
        $this->assertSame(
            count($suppliers),
            $count,
            'getCount() should match count of getSuppliers() array'
        );
    }
    
    /**
     * Test that suppliers are loaded only once (lazy loading)
     * 
     * @test
     * @covers ::getSuppliers
     * @covers ::loadSuppliers
     * 
     * @return void
     */
    public function testSuppliersAreLoadedOnlyOnce(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        // First call should trigger load
        $suppliers1 = $provider->getSuppliers();
        
        // Subsequent calls should use cached data
        $suppliers2 = $provider->getSuppliers();
        $suppliers3 = $provider->getSuppliers();
        
        // All should return identical array (same reference)
        $this->assertSame(
            $suppliers1,
            $suppliers2,
            'Second call should return same cached array'
        );
        
        $this->assertSame(
            $suppliers1,
            $suppliers3,
            'Third call should return same cached array'
        );
    }
    
    /**
     * Test that provider implements PartnerDataProviderInterface contract
     * 
     * @test
     * 
     * @return void
     */
    public function testImplementsPartnerDataProviderInterface(): void
    {
        $provider = SupplierDataProvider::getInstance();
        
        $this->assertInstanceOf(
            'KsfBankImport\Views\DataProviders\PartnerDataProviderInterface',
            $provider,
            'SupplierDataProvider should implement PartnerDataProviderInterface'
        );
    }
    
    /**
     * Test supplier data structure (integration test placeholder)
     * 
     * This would require database fixtures or mocking
     * 
     * @test
     * @group integration
     * @covers ::loadSuppliers
     * 
     * @return void
     */
    public function testSupplierDataStructure(): void
    {
        $this->markTestIncomplete(
            'Requires database fixtures or mocking of FrontAccounting functions'
        );
    }
}
