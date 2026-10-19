<?php

/**
 * CustomerDataProvider Tests
 * 
 * TDD tests for CustomerDataProvider singleton.
 * Tests customer + branch data loading, caching, and relationship handling.
 * 
 * @package    KsfBankImport\Tests\Unit\Views\DataProviders
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250422
 */

use PHPUnit\Framework\TestCase;
use KsfBankImport\Views\DataProviders\CustomerDataProvider;

require_once __DIR__ . '/../../../../views/DataProviders/CustomerDataProvider.php';

/**
 * Test CustomerDataProvider
 * 
 * Most complex provider - tests:
 * - Customer loading
 * - Branch loading
 * - Customer-branch relationships
 * - Multi-branch customer support
 * - Singleton behavior
 * - Lazy loading
 * - Memory caching
 * 
 * @coversDefaultClass KsfBankImport\Views\DataProviders\CustomerDataProvider
 */
class CustomerDataProviderTest extends TestCase
{
    /**
     * Reset singleton before each test
     * 
     * @return void
     */
    /** @var array Rows the FA database double returned before this test */
    private $faTableBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        CustomerDataProvider::reset();

        // The provider reads the branch table through FA's global db_query()
        // double, which serves the rows held in $GLOBALS['__fa_table'].
        // Seed a customer that owns two branches so the branch relationship
        // can be verified without a live database.
        $this->faTableBackup = $GLOBALS['__fa_table'] ?? [];
        $GLOBALS['__fa_table'] = [
            [
                'branch_code' => 'BR1',
                'debtor_no'   => 1,
                'br_name'     => 'Test Customer 1 - Branch 1',
                'br_address'  => '1 Branch Street',
                'contact_name' => 'Branch Contact 1',
                'email'       => 'branch1@example.com',
                'inactive'    => 0,
            ],
            [
                'branch_code' => 'BR2',
                'debtor_no'   => 1,
                'br_name'     => 'Test Customer 1 - Branch 2',
                'br_address'  => '2 Branch Street',
                'contact_name' => 'Branch Contact 2',
                'email'       => 'branch2@example.com',
                'inactive'    => 0,
            ],
        ];
    }
    
    /**
     * Reset singleton after each test
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        CustomerDataProvider::reset();
        $GLOBALS['__fa_table'] = $this->faTableBackup;
        parent::tearDown();
    }
    
    /**
     * Test singleton returns same instance
     * 
     * @covers ::getInstance
     * @return void
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = CustomerDataProvider::getInstance();
        $instance2 = CustomerDataProvider::getInstance();
        
        $this->assertSame($instance1, $instance2, 'getInstance() should return the same instance');
    }
    
    /**
     * Test reset creates new instance
     * 
     * @covers ::reset
     * @covers ::getInstance
     * @return void
     */
    public function testResetCreatesNewInstance(): void
    {
        $instance1 = CustomerDataProvider::getInstance();
        CustomerDataProvider::reset();
        $instance2 = CustomerDataProvider::getInstance();
        
        $this->assertNotSame($instance1, $instance2, 'reset() should create a new instance');
    }
    
    /**
     * Test getCustomers returns array
     * 
     * @covers ::getCustomers
     * @return void
     */
    public function testGetCustomersReturnsArray(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        $this->assertIsArray($customers, 'getCustomers() should return an array');
    }
    
    /**
     * Test getCustomers structure
     * 
     * Requires database fixtures to have actual data.
     * 
     * @covers ::getCustomers
     * @return void
     */
    public function testGetCustomersHasCorrectStructure(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        if (empty($customers)) {
            $this->markTestIncomplete('No customers in database - needs fixtures');
            return;
        }
        
        // Check first customer has expected keys
        $firstCustomer = reset($customers);
        $this->assertArrayHasKey('debtor_no', $firstCustomer, 'Customer should have debtor_no key');
        $this->assertArrayHasKey('name', $firstCustomer, 'Customer should have name key');
    }
    
    /**
     * Test getCustomer by ID returns correct customer
     * 
     * @covers ::getCustomer
     * @return void
     */
    public function testGetCustomerReturnsCorrectCustomer(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        if (empty($customers)) {
            $this->markTestIncomplete('No customers in database - needs fixtures');
            return;
        }
        
        $firstCustomerId = key($customers);
        $customer = $provider->getCustomer($firstCustomerId);
        
        $this->assertNotNull($customer, 'getCustomer() should return customer data');
        $this->assertEquals($firstCustomerId, $customer['debtor_no'], 'getCustomer() should return correct customer');
    }
    
    /**
     * Test getCustomer with non-existent ID returns null
     * 
     * @covers ::getCustomer
     * @return void
     */
    public function testGetCustomerWithNonExistentIdReturnsNull(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customer = $provider->getCustomer(999999);
        
        $this->assertNull($customer, 'getCustomer() should return null for non-existent ID');
    }
    
    /**
     * Test getPartners (interface method) returns customers
     * 
     * @covers ::getPartners
     * @return void
     */
    public function testGetPartnersReturnsCustomers(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $partners = $provider->getPartners();
        $customers = $provider->getCustomers();
        
        $this->assertEquals($customers, $partners, 'getPartners() should return same data as getCustomers()');
    }
    
    /**
     * Test getPartnerLabel returns customer name
     * 
     * @covers ::getPartnerLabel
     * @return void
     */
    public function testGetPartnerLabelReturnsCustomerName(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        if (empty($customers)) {
            $this->markTestIncomplete('No customers in database - needs fixtures');
            return;
        }
        
        $firstCustomerId = key($customers);
        $firstCustomer = reset($customers);
        $expectedLabel = $firstCustomer['name'];
        
        $label = $provider->getPartnerLabel($firstCustomerId);
        
        $this->assertEquals($expectedLabel, $label, 'getPartnerLabel() should return customer name');
    }
    
    /**
     * Test getPartnerLabel with non-existent ID returns null
     * 
     * @covers ::getPartnerLabel
     * @return void
     */
    public function testGetPartnerLabelWithNonExistentIdReturnsNull(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $label = $provider->getPartnerLabel(999999);
        
        $this->assertNull($label, 'getPartnerLabel() should return null for non-existent ID');
    }
    
    /**
     * Test hasPartner returns true for existing customer
     * 
     * @covers ::hasPartner
     * @return void
     */
    public function testHasPartnerReturnsTrueForExistingCustomer(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        if (empty($customers)) {
            $this->markTestIncomplete('No customers in database - needs fixtures');
            return;
        }
        
        $firstCustomerId = key($customers);
        $hasPartner = $provider->hasPartner($firstCustomerId);
        
        $this->assertTrue($hasPartner, 'hasPartner() should return true for existing customer');
    }
    
    /**
     * Test hasPartner returns false for non-existent customer
     * 
     * @covers ::hasPartner
     * @return void
     */
    public function testHasPartnerReturnsFalseForNonExistentCustomer(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $hasPartner = $provider->hasPartner(999999);
        
        $this->assertFalse($hasPartner, 'hasPartner() should return false for non-existent customer');
    }
    
    /**
     * Test getCount returns correct count
     * 
     * @covers ::getCount
     * @return void
     */
    public function testGetCountReturnsCorrectCount(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        $count = $provider->getCount();
        
        $this->assertEquals(count($customers), $count, 'getCount() should return correct customer count');
    }
    
    /**
     * Test getBranches returns array
     * 
     * @covers ::getBranches
     * @return void
     */
    public function testGetBranchesReturnsArray(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        if (empty($customers)) {
            $this->markTestIncomplete('No customers in database - needs fixtures');
            return;
        }
        
        $firstCustomerId = key($customers);
        $branches = $provider->getBranches($firstCustomerId);
        
        $this->assertIsArray($branches, 'getBranches() should return an array');
    }
    
    /**
     * Test getBranches returns empty array for customer with no branches
     * 
     * @covers ::getBranches
     * @return void
     */
    public function testGetBranchesReturnsEmptyArrayForCustomerWithNoBranches(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $branches = $provider->getBranches(999999);
        
        $this->assertIsArray($branches, 'getBranches() should return an array');
        $this->assertEmpty($branches, 'getBranches() should return empty array for non-existent customer');
    }
    
    /**
     * Test hasBranches returns boolean
     * 
     * @covers ::hasBranches
     * @return void
     */
    public function testHasBranchesReturnsBoolean(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $hasBranches = $provider->hasBranches(999999);
        
        $this->assertIsBool($hasBranches, 'hasBranches() should return a boolean');
    }
    
    /**
     * Test hasBranches returns false for customer with no branches
     * 
     * @covers ::hasBranches
     * @return void
     */
    public function testHasBranchesReturnsFalseForCustomerWithNoBranches(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $hasBranches = $provider->hasBranches(999999);
        
        $this->assertFalse($hasBranches, 'hasBranches() should return false for non-existent customer');
    }
    
    /**
     * Test getBranch returns correct branch
     * 
     * @covers ::getBranch
     * @return void
     */
    public function testGetBranchReturnsCorrectBranch(): void
    {
        $provider = CustomerDataProvider::getInstance();
        
        // Customer 1 owns the two seeded branches.
        $this->assertTrue($provider->hasBranches(1), 'Customer 1 should have branches');
        
        $branches = $provider->getBranches(1);
        $this->assertCount(2, $branches, 'Customer 1 should own two branches');
        
        $firstBranchCode = key($branches);
        
        $branch = $provider->getBranch(1, $firstBranchCode);
        
        $this->assertNotNull($branch, 'getBranch() should return branch data');
        $this->assertEquals($firstBranchCode, $branch['branch_code'], 'getBranch() should return correct branch');
    }
    
    /**
     * Test getBranch returns null for wrong customer
     * 
     * @covers ::getBranch
     * @return void
     */
    public function testGetBranchReturnsNullForWrongCustomer(): void
    {
        $provider = CustomerDataProvider::getInstance();
        
        $branches = $provider->getBranches(1);
        $this->assertNotEmpty($branches, 'Customer 1 should own branches');
        $firstBranchCode = key($branches);
        
        // Try to get branch with wrong customer ID
        $branch = $provider->getBranch(999999, $firstBranchCode);
        
        $this->assertNull($branch, 'getBranch() should return null for wrong customer ID');
    }
    
    /**
     * Test getBranch returns null for non-existent branch
     * 
     * @covers ::getBranch
     * @return void
     */
    public function testGetBranchReturnsNullForNonExistentBranch(): void
    {
        $provider = CustomerDataProvider::getInstance();
        $customers = $provider->getCustomers();
        
        if (empty($customers)) {
            $this->markTestIncomplete('No customers in database - needs fixtures');
            return;
        }
        
        $firstCustomerId = key($customers);
        $branch = $provider->getBranch($firstCustomerId, 999999);
        
        $this->assertNull($branch, 'getBranch() should return null for non-existent branch');
    }
    
    /**
     * Test data is loaded only once (lazy loading)
     * 
     * This is a performance test - verify data loaded on first access.
     * 
     * @covers ::getCustomers
     * @covers ::loadCustomers
     * @return void
     */
    public function testDataIsLoadedOnlyOnce(): void
    {
        $provider = CustomerDataProvider::getInstance();
        
        // First call should load data
        $customers1 = $provider->getCustomers();
        
        // Second call should return cached data
        $customers2 = $provider->getCustomers();
        
        // Verify same data returned (cached)
        $this->assertSame($customers1, $customers2, 'getCustomers() should return cached data on subsequent calls');
    }
    
    /**
     * Test branch relationship integrity
     * 
     * Verifies that branches returned by getBranches() belong to the customer.
     * 
     * @covers ::getBranches
     * @covers ::getBranch
     * @return void
     */
    public function testBranchRelationshipIntegrity(): void
    {
        $provider = CustomerDataProvider::getInstance();
        
        $customerId = 1;
        $branches = $provider->getBranches($customerId);
        $this->assertNotEmpty($branches, 'Customer 1 should own branches');
        
        // Verify all branches belong to this customer
        foreach ($branches as $branchCode => $branch) {
            $this->assertEquals(
                $customerId,
                $branch['debtor_no'],
                'Branch should belong to the correct customer'
            );
            
            // Verify getBranch() returns same data
            $branchData = $provider->getBranch($customerId, $branchCode);
            $this->assertEquals(
                $branch,
                $branchData,
                'getBranch() should return same data as getBranches()'
            );
        }
    }
}
