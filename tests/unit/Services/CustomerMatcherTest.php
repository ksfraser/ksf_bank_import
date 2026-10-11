<?php

/**
 * Customer Matcher Test
 *
 * Tests unified partner matching for customer-specific scenarios.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit
 * @subpackage Services
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\PartnerMatcherFactory;

/**
 * Customer Matcher Tests
 *
 * Demonstrates customer matching works parallel to supplier matching
 * using the unified TransactionPartnerMatcher with customer-specific
 * scoring configuration.
 */
class CustomerMatcherTest extends TestCase
{
    /**
     * Test customer matcher can be created
     */
    public function testCustomerMatcherCreation(): void
    {
        $matcher = PartnerMatcherFactory::createCustomerMatcher();
        $this->assertNotNull($matcher);
    }

    /**
     * Test supplier matcher can be created
     */
    public function testSupplierMatcherCreation(): void
    {
        $matcher = PartnerMatcherFactory::createSupplierMatcher();
        $this->assertNotNull($matcher);
    }

    /**
     * Test unified matcher can be created
     */
    public function testUnifiedMatcherCreation(): void
    {
        $matcher = PartnerMatcherFactory::createUnifiedMatcher();
        $this->assertNotNull($matcher);
    }

    /**
     * Test customer matcher returns structured results
     */
    public function testCustomerMatcherReturnsStructuredResults(): void
    {
        $matcher = PartnerMatcherFactory::createCustomerMatcher();

        $transaction = [
            'account' => '123456',
            'partner_account' => '555999',
            'amount' => 250.00,
            'memo' => 'CUSTOMER PAYMENT',
            'is_invoice' => false,
            'type' => 12,
            'is_refund' => false,
        ];

        $customers = [
            [
                'partner_id' => 200,
                'name' => 'ACME RETAILER',
                'account' => '555999',
            ],
        ];

        $results = $matcher->matchTransaction($transaction, [], $customers);

        // Verify result structure
        $this->assertIsArray($results);
        $this->assertArrayHasKey('supplier', $results);
        $this->assertArrayHasKey('customer', $results);
        $this->assertArrayHasKey('bank_transfer', $results);
        $this->assertArrayHasKey('best_match', $results);
    }

    /**
     * Test matcher distinguishes between customer and supplier
     */
    public function testMatcherDistinguishesPartnerTypes(): void
    {
        $matcher = PartnerMatcherFactory::createUnifiedMatcher();

        $transaction = [
            'account' => '123456',
            'partner_account' => 'ACME',
            'amount' => 500.00,
            'memo' => 'ACME TRANSACTION',
            'is_invoice' => false,
            'type' => 12,
            'is_refund' => false,
        ];

        $suppliers = [
            [
                'partner_id' => 1,
                'name' => 'ACME CORP SUPPLIER',
                'account' => 'ACME',
            ],
        ];

        $customers = [
            [
                'partner_id' => 200,
                'name' => 'ACME CORP CUSTOMER',
                'account' => 'ACME',
            ],
        ];

        $results = $matcher->matchTransaction(
            $transaction,
            $suppliers,
            $customers
        );

        // Should have returned results organized by type
        $this->assertIsArray($results['supplier']);
        $this->assertIsArray($results['customer']);
    }

    /**
     * Test factory creates different matchers
     */
    public function testFactoryCreatesDistinctMatchers(): void
    {
        $supplierMatcher = PartnerMatcherFactory::createSupplierMatcher();
        $customerMatcher = PartnerMatcherFactory::createCustomerMatcher();
        $unifiedMatcher = PartnerMatcherFactory::createUnifiedMatcher();

        // All should be instances of TransactionPartnerMatcher
        $this->assertNotNull($supplierMatcher);
        $this->assertNotNull($customerMatcher);
        $this->assertNotNull($unifiedMatcher);

        // They are different instances
        $this->assertNotSame($supplierMatcher, $customerMatcher);
        $this->assertNotSame($supplierMatcher, $unifiedMatcher);
    }

    /**
     * Test matcher functionality with empty partner lists
     */
    public function testMatcherHandlesEmptyPartnerLists(): void
    {
        $matcher = PartnerMatcherFactory::createUnifiedMatcher();

        $transaction = [
            'account' => '123456',
            'partner_account' => 'UNKNOWN',
            'amount' => 100.00,
            'memo' => 'MYSTERY PAYMENT',
            'is_invoice' => false,
            'type' => 1,
            'is_refund' => false,
        ];

        $results = $matcher->matchTransaction($transaction, [], [], []);

        // Should return structured results even with no partners
        $this->assertIsArray($results);
        $this->assertNull($results['best_match']);
        $this->assertEquals(0, count($results['supplier']));
        $this->assertEquals(0, count($results['customer']));
    }
}
