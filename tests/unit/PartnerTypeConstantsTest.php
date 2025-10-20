<?php

/**
 * Unit Tests for PartnerTypeConstants Class
 *
 * Tests the constants class for partner type identifiers.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      October 19, 2025
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\PartnerTypeConstants;

/**
 * Test cases for PartnerTypeConstants class
 *
 * Verifies all partner type constants are defined correctly.
 */
class PartnerTypeConstantsTest extends TestCase
{
    /**
     * Test that SUPPLIER constant is defined
     *
     * @test
     */
    public function testSupplierConstantIsDefined(): void
    {
        $this->assertSame(
            'SP',
            PartnerTypeConstants::SUPPLIER,
            'SUPPLIER constant should be "SP"'
        );
    }

    /**
     * Test that CUSTOMER constant is defined
     *
     * @test
     */
    public function testCustomerConstantIsDefined(): void
    {
        $this->assertSame(
            'CU',
            PartnerTypeConstants::CUSTOMER,
            'CUSTOMER constant should be "CU"'
        );
    }

    /**
     * Test that BANK_TRANSFER constant is defined
     *
     * @test
     */
    public function testBankTransferConstantIsDefined(): void
    {
        $this->assertSame(
            'BT',
            PartnerTypeConstants::BANK_TRANSFER,
            'BANK_TRANSFER constant should be "BT"'
        );
    }

    /**
     * Test that QUICK_ENTRY constant is defined
     *
     * @test
     */
    public function testQuickEntryConstantIsDefined(): void
    {
        $this->assertSame(
            'QE',
            PartnerTypeConstants::QUICK_ENTRY,
            'QUICK_ENTRY constant should be "QE"'
        );
    }

    /**
     * Test that MATCHED constant is defined
     *
     * @test
     */
    public function testMatchedConstantIsDefined(): void
    {
        $this->assertSame(
            'MA',
            PartnerTypeConstants::MATCHED,
            'MATCHED constant should be "MA"'
        );
    }

    /**
     * Test that UNKNOWN constant is defined
     *
     * @test
     */
    public function testUnknownConstantIsDefined(): void
    {
        $this->assertSame(
            'ZZ',
            PartnerTypeConstants::UNKNOWN,
            'UNKNOWN constant should be "ZZ"'
        );
    }

    /**
     * Test that all constants are unique
     *
     * @test
     */
    public function testAllConstantsAreUnique(): void
    {
        $constants = [
            PartnerTypeConstants::SUPPLIER,
            PartnerTypeConstants::CUSTOMER,
            PartnerTypeConstants::BANK_TRANSFER,
            PartnerTypeConstants::QUICK_ENTRY,
            PartnerTypeConstants::MATCHED,
            PartnerTypeConstants::UNKNOWN,
        ];

        $unique = array_unique($constants);

        $this->assertCount(
            count($constants),
            $unique,
            'All partner type constants should be unique'
        );
    }

    /**
     * Test that all constants are exactly 2 characters
     *
     * @test
     */
    public function testAllConstantsAreTwoCharacters(): void
    {
        $constants = [
            'SUPPLIER' => PartnerTypeConstants::SUPPLIER,
            'CUSTOMER' => PartnerTypeConstants::CUSTOMER,
            'BANK_TRANSFER' => PartnerTypeConstants::BANK_TRANSFER,
            'QUICK_ENTRY' => PartnerTypeConstants::QUICK_ENTRY,
            'MATCHED' => PartnerTypeConstants::MATCHED,
            'UNKNOWN' => PartnerTypeConstants::UNKNOWN,
        ];

        foreach ($constants as $name => $value) {
            $this->assertSame(
                2,
                strlen($value),
                "Constant {$name} should be exactly 2 characters"
            );
        }
    }

    /**
     * Test that all constants are uppercase
     *
     * @test
     */
    public function testAllConstantsAreUppercase(): void
    {
        $constants = [
            'SUPPLIER' => PartnerTypeConstants::SUPPLIER,
            'CUSTOMER' => PartnerTypeConstants::CUSTOMER,
            'BANK_TRANSFER' => PartnerTypeConstants::BANK_TRANSFER,
            'QUICK_ENTRY' => PartnerTypeConstants::QUICK_ENTRY,
            'MATCHED' => PartnerTypeConstants::MATCHED,
            'UNKNOWN' => PartnerTypeConstants::UNKNOWN,
        ];

        foreach ($constants as $name => $value) {
            $this->assertSame(
                strtoupper($value),
                $value,
                "Constant {$name} should be uppercase"
            );
        }
    }

    /**
     * Test getAll() returns all constants
     *
     * @test
     */
    public function testGetAllReturnsAllConstants(): void
    {
        $expected = [
            'SUPPLIER' => 'SP',
            'CUSTOMER' => 'CU',
            'BANK_TRANSFER' => 'BT',
            'QUICK_ENTRY' => 'QE',
            'MATCHED' => 'MA',
            'UNKNOWN' => 'ZZ',
        ];

        $this->assertSame(
            $expected,
            PartnerTypeConstants::getAll(),
            'getAll() should return array of all constants'
        );
    }

    /**
     * Test isValid() accepts valid constants
     *
     * @test
     */
    public function testIsValidAcceptsValidConstants(): void
    {
        $validTypes = ['SP', 'CU', 'BT', 'QE', 'MA', 'ZZ'];

        foreach ($validTypes as $type) {
            $this->assertTrue(
                PartnerTypeConstants::isValid($type),
                "isValid() should return true for '{$type}'"
            );
        }
    }

    /**
     * Test isValid() rejects invalid constants
     *
     * @test
     */
    public function testIsValidRejectsInvalidConstants(): void
    {
        $invalidTypes = ['XX', 'sp', 'Cu', 'ABC', '', '12'];

        foreach ($invalidTypes as $type) {
            $this->assertFalse(
                PartnerTypeConstants::isValid($type),
                "isValid() should return false for '{$type}'"
            );
        }
    }

    /**
     * Test getLabel() returns human-readable labels
     *
     * @test
     */
    public function testGetLabelReturnsHumanReadableLabels(): void
    {
        $this->assertSame(
            'Supplier',
            PartnerTypeConstants::getLabel(PartnerTypeConstants::SUPPLIER)
        );

        $this->assertSame(
            'Customer',
            PartnerTypeConstants::getLabel(PartnerTypeConstants::CUSTOMER)
        );

        $this->assertSame(
            'Bank Transfer',
            PartnerTypeConstants::getLabel(PartnerTypeConstants::BANK_TRANSFER)
        );

        $this->assertSame(
            'Quick Entry',
            PartnerTypeConstants::getLabel(PartnerTypeConstants::QUICK_ENTRY)
        );

        $this->assertSame(
            'Matched Transaction',
            PartnerTypeConstants::getLabel(PartnerTypeConstants::MATCHED)
        );

        $this->assertSame(
            'Unknown',
            PartnerTypeConstants::getLabel(PartnerTypeConstants::UNKNOWN)
        );
    }

    /**
     * Test getLabel() returns Unknown for invalid types
     *
     * @test
     */
    public function testGetLabelReturnsUnknownForInvalidTypes(): void
    {
        $this->assertSame(
            'Unknown',
            PartnerTypeConstants::getLabel('INVALID')
        );

        $this->assertSame(
            'Unknown',
            PartnerTypeConstants::getLabel('')
        );
    }
}
