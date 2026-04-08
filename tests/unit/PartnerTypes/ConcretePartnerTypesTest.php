<?php

/**
 * Unit Tests for Concrete Partner Types
 *
 * Tests each concrete partner type implementation.
 *
 * @package    Ksfraser\Tests\Unit\PartnerTypes
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\PartnerTypes;

use PHPUnit\Framework\TestCase;
use Ksfraser\PartnerTypes\SupplierPartnerType;
use Ksfraser\PartnerTypes\CustomerPartnerType;
use Ksfraser\PartnerTypes\BankTransferPartnerType;
use Ksfraser\PartnerTypes\QuickEntryPartnerType;
use Ksfraser\PartnerTypes\MatchedPartnerType;
use Ksfraser\PartnerTypes\UnknownPartnerType;

/**
 * Test cases for concrete partner type implementations
 */
class ConcretePartnerTypesTest extends TestCase
{
    /**
     * Test SupplierPartnerType
     *
     * @test
     */
    public function testSupplierPartnerType(): void
    {
        $type = new SupplierPartnerType();
        
        $this->assertSame('SP', $type->getShortCode());
        $this->assertSame('Supplier', $type->getLabel());
        $this->assertSame('SUPPLIER', $type->getConstantName());
        $this->assertSame(10, $type->getPriority());
        $this->assertNotNull($type->getDescription());
        $this->assertStringContainsString('Vendor', $type->getDescription());
    }

    /**
     * Test CustomerPartnerType
     *
     * @test
     */
    public function testCustomerPartnerType(): void
    {
        $type = new CustomerPartnerType();
        
        $this->assertSame('CU', $type->getShortCode());
        $this->assertSame('Customer', $type->getLabel());
        $this->assertSame('CUSTOMER', $type->getConstantName());
        $this->assertSame(20, $type->getPriority());
        $this->assertNotNull($type->getDescription());
        $this->assertStringContainsString('Customer', $type->getDescription());
    }

    /**
     * Test BankTransferPartnerType
     *
     * @test
     */
    public function testBankTransferPartnerType(): void
    {
        $type = new BankTransferPartnerType();
        
        $this->assertSame('BT', $type->getShortCode());
        $this->assertSame('Bank Transfer', $type->getLabel());
        $this->assertSame('BANK_TRANSFER', $type->getConstantName());
        $this->assertSame(30, $type->getPriority());
        $this->assertNotNull($type->getDescription());
    }

    /**
     * Test QuickEntryPartnerType
     *
     * @test
     */
    public function testQuickEntryPartnerType(): void
    {
        $type = new QuickEntryPartnerType();
        
        $this->assertSame('QE', $type->getShortCode());
        $this->assertSame('Quick Entry', $type->getLabel());
        $this->assertSame('QUICK_ENTRY', $type->getConstantName());
        $this->assertSame(40, $type->getPriority());
        $this->assertNotNull($type->getDescription());
    }

    /**
     * Test MatchedPartnerType
     *
     * @test
     */
    public function testMatchedPartnerType(): void
    {
        $type = new MatchedPartnerType();
        
        $this->assertSame('MA', $type->getShortCode());
        $this->assertSame('Matched Transaction', $type->getLabel());
        $this->assertSame('MATCHED', $type->getConstantName());
        $this->assertSame(50, $type->getPriority());
        $this->assertNotNull($type->getDescription());
    }

    /**
     * Test UnknownPartnerType
     *
     * @test
     */
    public function testUnknownPartnerType(): void
    {
        $type = new UnknownPartnerType();
        
        $this->assertSame('ZZ', $type->getShortCode());
        $this->assertSame('Unknown', $type->getLabel());
        $this->assertSame('UNKNOWN', $type->getConstantName());
        $this->assertSame(999, $type->getPriority());
        $this->assertNotNull($type->getDescription());
    }

    /**
     * Test __toString returns label
     *
     * @test
     */
    public function testToStringReturnsLabel(): void
    {
        $type = new SupplierPartnerType();
        
        $this->assertSame('Supplier', (string) $type);
    }

    /**
     * Test all types have two-character codes
     *
     * @test
     */
    public function testAllTypesHaveTwoCharacterCodes(): void
    {
        $types = [
            new SupplierPartnerType(),
            new CustomerPartnerType(),
            new BankTransferPartnerType(),
            new QuickEntryPartnerType(),
            new MatchedPartnerType(),
            new UnknownPartnerType(),
        ];
        
        foreach ($types as $type) {
            $code = $type->getShortCode();
            $this->assertSame(2, strlen($code), "Code '{$code}' should be 2 characters");
        }
    }

    /**
     * Test all types have uppercase codes
     *
     * @test
     */
    public function testAllTypesHaveUppercaseCodes(): void
    {
        $types = [
            new SupplierPartnerType(),
            new CustomerPartnerType(),
            new BankTransferPartnerType(),
            new QuickEntryPartnerType(),
            new MatchedPartnerType(),
            new UnknownPartnerType(),
        ];
        
        foreach ($types as $type) {
            $code = $type->getShortCode();
            $this->assertSame(strtoupper($code), $code, "Code '{$code}' should be uppercase");
        }
    }

    /**
     * Test priorities are unique and ordered
     *
     * @test
     */
    public function testPrioritiesAreUniqueAndOrdered(): void
    {
        $types = [
            new SupplierPartnerType(),
            new CustomerPartnerType(),
            new BankTransferPartnerType(),
            new QuickEntryPartnerType(),
            new MatchedPartnerType(),
            new UnknownPartnerType(),
        ];
        
        $priorities = array_map(function ($type) {
            return $type->getPriority();
        }, $types);
        
        // Check uniqueness
        $this->assertSame(count($priorities), count(array_unique($priorities)), 'Priorities should be unique');
        
        // Check Unknown has highest priority (lowest precedence)
        $unknown = new UnknownPartnerType();
        $this->assertGreaterThan(900, $unknown->getPriority(), 'Unknown should have very high priority number');
    }
}
