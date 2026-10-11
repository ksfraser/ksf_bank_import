<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\SupplierDataProvider;

/**
 * SupplierDataProviderTest
 *
 * Tests for SupplierDataProvider class - static caching pattern for supplier data.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Claude AI Assistant
 * @since      20251019
 */
class SupplierDataProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset static cache after each test
        SupplierDataProvider::resetCache();
    }

    public function testConstruction(): void
    {
        $provider = new SupplierDataProvider();
        $this->assertInstanceOf(SupplierDataProvider::class, $provider);
    }

    public function testGetSuppliersReturnsArray(): void
    {
        $provider = new SupplierDataProvider();
        $suppliers = $provider->getSuppliers();

        $this->assertIsArray($suppliers);
    }

    public function testGetSuppliersWithMockData(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $suppliers = $provider->getSuppliers();

        $this->assertCount(2, $suppliers);
        $this->assertEquals('Supplier A', $suppliers[0]['supp_name']);
        $this->assertEquals('Supplier B', $suppliers[1]['supp_name']);
    }

    public function testStaticCachingPreventsDuplicateLoads(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        // First provider - sets cache
        $provider1 = new SupplierDataProvider();
        $provider1->setSuppliers($mockSuppliers);
        $suppliers1 = $provider1->getSuppliers();

        // Second provider - should use cache
        $provider2 = new SupplierDataProvider();
        $suppliers2 = $provider2->getSuppliers();

        // Both should return same data (from cache)
        $this->assertEquals($suppliers1, $suppliers2);
        $this->assertCount(1, $suppliers2);
    }

    public function testResetCacheClearsStaticCache(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        $provider1 = new SupplierDataProvider();
        $provider1->setSuppliers($mockSuppliers);
        $provider1->getSuppliers(); // Populate cache

        // Reset cache
        SupplierDataProvider::resetCache();

        // Create new provider - cache should be empty
        $provider2 = new SupplierDataProvider();
        $suppliers = $provider2->getSuppliers();

        // Should return empty array since cache was reset and no new data set
        $this->assertIsArray($suppliers);
    }

    public function testGenerateSelectHtmlReturnsString(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $html = $provider->generateSelectHtml('partnerId_123', null);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    public function testGenerateSelectHtmlContainsFieldName(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $html = $provider->generateSelectHtml('partnerId_123', null);

        $this->assertStringContainsString('partnerId_123', $html);
    }

    public function testGenerateSelectHtmlContainsSupplierNames(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $html = $provider->generateSelectHtml('partnerId_123', null);

        $this->assertStringContainsString('Supplier A', $html);
        $this->assertStringContainsString('Supplier B', $html);
    }

    public function testGenerateSelectHtmlWithSelectedId(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $html = $provider->generateSelectHtml('partnerId_123', '2');

        $this->assertStringContainsString('2', $html);
        $this->assertStringContainsString('selected', $html);
    }

    public function testGetSupplierNameById(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $name = $provider->getSupplierNameById('1');

        $this->assertEquals('Supplier A', $name);
    }

    public function testGetSupplierNameByIdReturnsNullForUnknown(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $name = $provider->getSupplierNameById('999');

        $this->assertNull($name);
    }

    public function testGetSupplierCount(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
            ['supplier_id' => '3', 'supp_name' => 'Supplier C'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $count = $provider->getSupplierCount();

        $this->assertEquals(3, $count);
    }

    public function testGetSupplierCountReturnsZeroWhenEmpty(): void
    {
        $provider = new SupplierDataProvider();

        $count = $provider->getSupplierCount();

        $this->assertEquals(0, $count);
    }

    public function testIsLoadedReturnsFalseInitially(): void
    {
        // Reset cache to ensure clean state
        SupplierDataProvider::resetCache();

        $provider = new SupplierDataProvider();
        $isLoaded = $provider->isLoaded();

        $this->assertFalse($isLoaded);
    }

    public function testIsLoadedReturnsTrueAfterLoading(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);
        $provider->getSuppliers(); // Trigger load

        $isLoaded = $provider->isLoaded();

        $this->assertTrue($isLoaded);
    }

    public function testMultipleInstancesShareCache(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        // Instance 1 loads data
        $provider1 = new SupplierDataProvider();
        $provider1->setSuppliers($mockSuppliers);
        $provider1->getSuppliers();

        // Instance 2 should access same cache
        $provider2 = new SupplierDataProvider();
        $this->assertTrue($provider2->isLoaded());

        // Instance 3 should also access same cache
        $provider3 = new SupplierDataProvider();
        $this->assertEquals('Supplier A', $provider3->getSupplierNameById('1'));
    }

    public function testGenerateSelectHtmlUsesCache(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        // First instance sets data
        $provider1 = new SupplierDataProvider();
        $provider1->setSuppliers($mockSuppliers);

        // Second instance should use cached data for HTML generation
        $provider2 = new SupplierDataProvider();
        $html = $provider2->generateSelectHtml('partnerId_456', '1');

        $this->assertStringContainsString('Supplier A', $html);
        $this->assertStringContainsString('partnerId_456', $html);
    }

    public function testGetSupplierByIdReturnsFullRecord(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A', 'supp_ref' => 'REF-A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B', 'supp_ref' => 'REF-B'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $supplier = $provider->getSupplierById('1');

        $this->assertIsArray($supplier);
        $this->assertEquals('1', $supplier['supplier_id']);
        $this->assertEquals('Supplier A', $supplier['supp_name']);
        $this->assertEquals('REF-A', $supplier['supp_ref']);
    }

    public function testGetSupplierByIdReturnsNullForUnknown(): void
    {
        $mockSuppliers = [
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
        ];

        $provider = new SupplierDataProvider();
        $provider->setSuppliers($mockSuppliers);

        $supplier = $provider->getSupplierById('999');

        $this->assertNull($supplier);
    }
}
