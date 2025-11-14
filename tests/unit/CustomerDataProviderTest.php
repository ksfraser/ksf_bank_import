<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\CustomerDataProvider;

/**
 * CustomerDataProviderTest
 *
 * Tests for CustomerDataProvider class - static caching for customer and branch data.
 *
 * This provider is more complex than SupplierDataProvider because it handles
 * both customers AND their branches (two-level hierarchy).
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Claude AI Assistant
 * @since      20251019
 */
class CustomerDataProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset static cache after each test
        CustomerDataProvider::resetCache();
    }

    // ========== Construction Tests ==========

    public function testConstruction(): void
    {
        $provider = new CustomerDataProvider();
        $this->assertInstanceOf(CustomerDataProvider::class, $provider);
    }

    // ========== Customer Data Tests ==========

    public function testGetCustomersReturnsArray(): void
    {
        $provider = new CustomerDataProvider();
        $customers = $provider->getCustomers();

        $this->assertIsArray($customers);
    }

    public function testGetCustomersWithMockData(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
            ['debtor_no' => '2', 'name' => 'Customer B'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $customers = $provider->getCustomers();

        $this->assertCount(2, $customers);
        $this->assertEquals('Customer A', $customers[0]['name']);
        $this->assertEquals('Customer B', $customers[1]['name']);
    }

    public function testStaticCachingPreventsDuplicateLoads(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];

        // First provider - sets cache
        $provider1 = new CustomerDataProvider();
        $provider1->setCustomers($mockCustomers);
        $customers1 = $provider1->getCustomers();

        // Second provider - should use cache
        $provider2 = new CustomerDataProvider();
        $customers2 = $provider2->getCustomers();

        // Both should return same data (from cache)
        $this->assertEquals($customers1, $customers2);
        $this->assertCount(1, $customers2);
    }

    public function testGetCustomerNameById(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
            ['debtor_no' => '2', 'name' => 'Customer B'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $name = $provider->getCustomerNameById('1');

        $this->assertEquals('Customer A', $name);
    }

    public function testGetCustomerNameByIdReturnsNullForUnknown(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $name = $provider->getCustomerNameById('999');

        $this->assertNull($name);
    }

    public function testGetCustomerCount(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
            ['debtor_no' => '2', 'name' => 'Customer B'],
            ['debtor_no' => '3', 'name' => 'Customer C'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $count = $provider->getCustomerCount();

        $this->assertEquals(3, $count);
    }

    // ========== Branch Data Tests ==========

    public function testGetBranchesReturnsArray(): void
    {
        $provider = new CustomerDataProvider();
        $branches = $provider->getBranches('1');

        $this->assertIsArray($branches);
    }

    public function testGetBranchesWithMockData(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
                ['branch_code' => '2', 'br_name' => 'Branch A2'],
            ],
            '2' => [
                ['branch_code' => '3', 'br_name' => 'Branch B1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $branches = $provider->getBranches('1');

        $this->assertCount(2, $branches);
        $this->assertEquals('Branch A1', $branches[0]['br_name']);
        $this->assertEquals('Branch A2', $branches[1]['br_name']);
    }

    public function testGetBranchesForUnknownCustomerReturnsEmptyArray(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $branches = $provider->getBranches('999');

        $this->assertIsArray($branches);
        $this->assertEmpty($branches);
    }

    public function testGetBranchNameById(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
                ['branch_code' => '2', 'br_name' => 'Branch A2'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $name = $provider->getBranchNameById('1', '2');

        $this->assertEquals('Branch A2', $name);
    }

    public function testGetBranchNameByIdReturnsNullForUnknownCustomer(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $name = $provider->getBranchNameById('999', '1');

        $this->assertNull($name);
    }

    public function testGetBranchNameByIdReturnsNullForUnknownBranch(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $name = $provider->getBranchNameById('1', '999');

        $this->assertNull($name);
    }

    public function testGetBranchCount(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
                ['branch_code' => '2', 'br_name' => 'Branch A2'],
            ],
            '2' => [
                ['branch_code' => '3', 'br_name' => 'Branch B1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $count = $provider->getBranchCount('1');

        $this->assertEquals(2, $count);
    }

    // ========== HTML Generation Tests ==========

    public function testGenerateCustomerSelectHtmlReturnsString(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $html = $provider->generateCustomerSelectHtml('partnerId_123', null);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    public function testGenerateCustomerSelectHtmlContainsFieldName(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $html = $provider->generateCustomerSelectHtml('partnerId_123', null);

        $this->assertStringContainsString('partnerId_123', $html);
    }

    public function testGenerateCustomerSelectHtmlContainsCustomerNames(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
            ['debtor_no' => '2', 'name' => 'Customer B'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $html = $provider->generateCustomerSelectHtml('partnerId_123', null);

        $this->assertStringContainsString('Customer A', $html);
        $this->assertStringContainsString('Customer B', $html);
    }

    public function testGenerateBranchSelectHtmlReturnsString(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $html = $provider->generateBranchSelectHtml('1', 'partnerDetailId_123', null);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }

    public function testGenerateBranchSelectHtmlContainsFieldName(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $html = $provider->generateBranchSelectHtml('1', 'partnerDetailId_123', null);

        $this->assertStringContainsString('partnerDetailId_123', $html);
    }

    public function testGenerateBranchSelectHtmlContainsBranchNames(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
                ['branch_code' => '2', 'br_name' => 'Branch A2'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $html = $provider->generateBranchSelectHtml('1', 'partnerDetailId_123', null);

        $this->assertStringContainsString('Branch A1', $html);
        $this->assertStringContainsString('Branch A2', $html);
    }

    // ========== Cache Tests ==========

    public function testResetCacheClearsBothCaches(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider1 = new CustomerDataProvider();
        $provider1->setCustomers($mockCustomers);
        $provider1->setBranches($mockBranches);
        $provider1->getCustomers(); // Populate cache

        // Reset cache
        CustomerDataProvider::resetCache();

        // Create new provider - caches should be empty
        $provider2 = new CustomerDataProvider();
        $customers = $provider2->getCustomers();
        $branches = $provider2->getBranches('1');

        $this->assertIsArray($customers);
        $this->assertIsArray($branches);
    }

    public function testIsLoadedReturnsFalseInitially(): void
    {
        CustomerDataProvider::resetCache();

        $provider = new CustomerDataProvider();
        $isLoaded = $provider->isLoaded();

        $this->assertFalse($isLoaded);
    }

    public function testIsLoadedReturnsTrueAfterLoadingCustomers(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);
        $provider->getCustomers(); // Trigger load

        $isLoaded = $provider->isLoaded();

        $this->assertTrue($isLoaded);
    }

    public function testMultipleInstancesShareCache(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        // Instance 1 loads data
        $provider1 = new CustomerDataProvider();
        $provider1->setCustomers($mockCustomers);
        $provider1->setBranches($mockBranches);
        $provider1->getCustomers();

        // Instance 2 should access same cache
        $provider2 = new CustomerDataProvider();
        $this->assertTrue($provider2->isLoaded());
        $this->assertEquals('Customer A', $provider2->getCustomerNameById('1'));
        $this->assertEquals('Branch A1', $provider2->getBranchNameById('1', '1'));
    }

    public function testGetCustomerById(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A', 'curr_code' => 'USD'],
            ['debtor_no' => '2', 'name' => 'Customer B', 'curr_code' => 'EUR'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $customer = $provider->getCustomerById('1');

        $this->assertIsArray($customer);
        $this->assertEquals('1', $customer['debtor_no']);
        $this->assertEquals('Customer A', $customer['name']);
        $this->assertEquals('USD', $customer['curr_code']);
    }

    public function testGetCustomerByIdReturnsNullForUnknown(): void
    {
        $mockCustomers = [
            ['debtor_no' => '1', 'name' => 'Customer A'],
        ];

        $provider = new CustomerDataProvider();
        $provider->setCustomers($mockCustomers);

        $customer = $provider->getCustomerById('999');

        $this->assertNull($customer);
    }

    public function testGetBranchById(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1', 'br_address' => '123 Main St'],
                ['branch_code' => '2', 'br_name' => 'Branch A2', 'br_address' => '456 Oak Ave'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $branch = $provider->getBranchById('1', '2');

        $this->assertIsArray($branch);
        $this->assertEquals('2', $branch['branch_code']);
        $this->assertEquals('Branch A2', $branch['br_name']);
        $this->assertEquals('456 Oak Ave', $branch['br_address']);
    }

    public function testGetBranchByIdReturnsNullForUnknown(): void
    {
        $mockBranches = [
            '1' => [
                ['branch_code' => '1', 'br_name' => 'Branch A1'],
            ],
        ];

        $provider = new CustomerDataProvider();
        $provider->setBranches($mockBranches);

        $branch = $provider->getBranchById('1', '999');

        $this->assertNull($branch);
    }
}
