<?php

/**
 * Unit Tests for PartnerTypeRegistry
 *
 * Tests the dynamic partner type discovery and registration system.
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
use Ksfraser\PartnerTypes\PartnerTypeRegistry;
use Ksfraser\PartnerTypes\SupplierPartnerType;
use Ksfraser\PartnerTypes\CustomerPartnerType;

/**
 * Test cases for PartnerTypeRegistry
 */
class PartnerTypeRegistryTest extends TestCase
{
    /**
     * Reset registry before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        PartnerTypeRegistry::reset();
    }

    /**
     * Test singleton pattern
     *
     * @test
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = PartnerTypeRegistry::getInstance();
        $instance2 = PartnerTypeRegistry::getInstance();
        
        $this->assertSame($instance1, $instance2, 'Should return same singleton instance');
    }

    /**
     * Test registry auto-discovers partner types
     *
     * @test
     */
    public function testRegistryAutoDiscoversPartnerTypes(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $all = $registry->getAll();
        
        $this->assertNotEmpty($all, 'Should discover at least one partner type');
        $this->assertGreaterThanOrEqual(6, count($all), 'Should discover at least 6 types');
    }

    /**
     * Test get by code returns correct type
     *
     * @test
     */
    public function testGetByCodeReturnsCorrectType(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $supplier = $registry->getByCode('SP');
        
        $this->assertNotNull($supplier, 'Should find supplier by code');
        $this->assertSame('SP', $supplier->getShortCode());
        $this->assertSame('Supplier', $supplier->getLabel());
        $this->assertSame('SUPPLIER', $supplier->getConstantName());
    }

    /**
     * Test get by code returns null for invalid code
     *
     * @test
     */
    public function testGetByCodeReturnsNullForInvalidCode(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $result = $registry->getByCode('XX');
        
        $this->assertNull($result, 'Should return null for invalid code');
    }

    /**
     * Test get by constant returns correct type
     *
     * @test
     */
    public function testGetByConstantReturnsCorrectType(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $customer = $registry->getByConstant('CUSTOMER');
        
        $this->assertNotNull($customer, 'Should find customer by constant');
        $this->assertSame('CU', $customer->getShortCode());
        $this->assertSame('Customer', $customer->getLabel());
    }

    /**
     * Test get by constant returns null for invalid constant
     *
     * @test
     */
    public function testGetByConstantReturnsNullForInvalidConstant(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $result = $registry->getByConstant('INVALID');
        
        $this->assertNull($result, 'Should return null for invalid constant');
    }

    /**
     * Test isValid validates codes correctly
     *
     * @test
     */
    public function testIsValidValidatesCodes(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        $this->assertTrue($registry->isValid('SP'), 'Should validate SP');
        $this->assertTrue($registry->isValid('CU'), 'Should validate CU');
        $this->assertTrue($registry->isValid('BT'), 'Should validate BT');
        $this->assertTrue($registry->isValid('QE'), 'Should validate QE');
        $this->assertTrue($registry->isValid('MA'), 'Should validate MA');
        $this->assertTrue($registry->isValid('ZZ'), 'Should validate ZZ');
        
        $this->assertFalse($registry->isValid('XX'), 'Should reject XX');
        $this->assertFalse($registry->isValid(''), 'Should reject empty string');
    }

    /**
     * Test getLabel returns correct labels
     *
     * @test
     */
    public function testGetLabelReturnsCorrectLabels(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        $this->assertSame('Supplier', $registry->getLabel('SP'));
        $this->assertSame('Customer', $registry->getLabel('CU'));
        $this->assertSame('Bank Transfer', $registry->getLabel('BT'));
        $this->assertSame('Quick Entry', $registry->getLabel('QE'));
        $this->assertSame('Matched Transaction', $registry->getLabel('MA'));
        $this->assertSame('Unknown', $registry->getLabel('ZZ'));
    }

    /**
     * Test getLabel returns Unknown for invalid code
     *
     * @test
     */
    public function testGetLabelReturnsUnknownForInvalidCode(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        $this->assertSame('Unknown', $registry->getLabel('XX'));
    }

    /**
     * Test getCodes returns all short codes
     *
     * @test
     */
    public function testGetCodesReturnsAllShortCodes(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $codes = $registry->getCodes();
        
        $this->assertContains('SP', $codes);
        $this->assertContains('CU', $codes);
        $this->assertContains('BT', $codes);
        $this->assertContains('QE', $codes);
        $this->assertContains('MA', $codes);
        $this->assertContains('ZZ', $codes);
    }

    /**
     * Test count returns correct number of types
     *
     * @test
     */
    public function testCountReturnsCorrectNumber(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        $this->assertGreaterThanOrEqual(6, $registry->count());
    }

    /**
     * Test types are sorted by priority
     *
     * @test
     */
    public function testTypesAreSortedByPriority(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $all = $registry->getAll();
        
        $priorities = array_map(function ($type) {
            return $type->getPriority();
        }, $all);
        
        $sorted = $priorities;
        sort($sorted);
        
        $this->assertSame($sorted, array_values($priorities), 'Types should be sorted by priority');
    }

    /**
     * Test manual registration
     *
     * @test
     */
    public function testManualRegistration(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        // Create a mock partner type
        $mockType = $this->createMock(\Ksfraser\PartnerTypes\PartnerTypeInterface::class);
        $mockType->method('getShortCode')->willReturn('TT');
        $mockType->method('getLabel')->willReturn('Test Type');
        $mockType->method('getConstantName')->willReturn('TEST');
        $mockType->method('getPriority')->willReturn(50);
        
        $registry->register($mockType);
        
        $result = $registry->getByCode('TT');
        $this->assertSame($mockType, $result);
    }

    /**
     * Test registration throws exception for duplicate code
     *
     * @test
     */
    public function testRegistrationThrowsExceptionForDuplicateCode(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        // First register a type with a unique code
        $firstType = $this->createMock(\Ksfraser\PartnerTypes\PartnerTypeInterface::class);
        $firstType->method('getShortCode')->willReturn('T1');
        $firstType->method('getLabel')->willReturn('Test 1');
        $firstType->method('getConstantName')->willReturn('TEST1');
        $registry->register($firstType);
        
        // Try to register another type with same code
        $duplicateType = $this->createMock(\Ksfraser\PartnerTypes\PartnerTypeInterface::class);
        $duplicateType->method('getShortCode')->willReturn('T1');
        $duplicateType->method('getLabel')->willReturn('Duplicate');
        $duplicateType->method('getConstantName')->willReturn('DUPLICATE');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already registered');
        
        $registry->register($duplicateType);
    }

    /**
     * Test reset clears singleton
     *
     * @test
     */
    public function testResetClearsSingleton(): void
    {
        $instance1 = PartnerTypeRegistry::getInstance();
        PartnerTypeRegistry::reset();
        $instance2 = PartnerTypeRegistry::getInstance();
        
        $this->assertNotSame($instance1, $instance2, 'Reset should create new instance');
    }

    /**
     * Test all discovered types implement interface
     *
     * @test
     */
    public function testAllDiscoveredTypesImplementInterface(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        foreach ($registry->getAll() as $type) {
            $this->assertInstanceOf(
                \Ksfraser\PartnerTypes\PartnerTypeInterface::class,
                $type,
                'All types should implement PartnerTypeInterface'
            );
        }
    }

    /**
     * Test all short codes are exactly 2 characters
     *
     * @test
     */
    public function testAllShortCodesAreTwoCharacters(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        foreach ($registry->getAll() as $type) {
            $code = $type->getShortCode();
            $this->assertSame(
                2,
                strlen($code),
                "Short code '{$code}' should be exactly 2 characters"
            );
        }
    }

    /**
     * Test all short codes are uppercase
     *
     * @test
     */
    public function testAllShortCodesAreUppercase(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        
        foreach ($registry->getAll() as $type) {
            $code = $type->getShortCode();
            $this->assertSame(
                strtoupper($code),
                $code,
                "Short code '{$code}' should be uppercase"
            );
        }
    }
}
