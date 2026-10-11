<?php

/**
 * Unit Tests for FormFieldNameGenerator
 *
 * Tests the form field naming utility for consistent field name generation.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FormFieldNameGenerator;

/**
 * Test cases for FormFieldNameGenerator
 *
 * Verifies consistent field name generation for forms.
 */
class FormFieldNameGeneratorTest extends TestCase
{
    /**
     * Test basic field name without ID
     *
     * @test
     */
    public function testBasicFieldNameWithoutId(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'vendor_id',
            $generator->generate('vendor_id'),
            'Should generate basic field name'
        );
    }

    /**
     * Test field name with ID suffix
     *
     * @test
     */
    public function testFieldNameWithIdSuffix(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'vendor_id_123',
            $generator->generate('vendor_id', 123),
            'Should append ID to field name'
        );
    }

    /**
     * Test field name with ID prefix
     *
     * @test
     */
    public function testFieldNameWithIdPrefix(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            '123_vendor_id',
            $generator->generateWithPrefix('vendor_id', 123),
            'Should prepend ID to field name'
        );
    }

    /**
     * Test partner ID field name
     *
     * @test
     */
    public function testPartnerIdFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'partnerId_456',
            $generator->partnerIdField(456),
            'Should generate partnerId field name'
        );
    }

    /**
     * Test partner detail ID field name
     *
     * @test
     */
    public function testPartnerDetailIdFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'partnerDetailId_789',
            $generator->partnerDetailIdField(789),
            'Should generate partnerDetailId field name'
        );
    }

    /**
     * Test partner type field name
     *
     * @test
     */
    public function testPartnerTypeFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'partnerType_123',
            $generator->partnerTypeField(123),
            'Should generate partnerType field name'
        );
    }

    /**
     * Test vendor short field name
     *
     * @test
     */
    public function testVendorShortFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'vendor_short_100',
            $generator->vendorShortField(100),
            'Should generate vendor_short field name'
        );
    }

    /**
     * Test vendor long field name
     *
     * @test
     */
    public function testVendorLongFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'vendor_long_200',
            $generator->vendorLongField(200),
            'Should generate vendor_long field name'
        );
    }

    /**
     * Test transaction number field name
     *
     * @test
     */
    public function testTransactionNumberFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'trans_no_50',
            $generator->transactionNumberField(50),
            'Should generate trans_no field name'
        );
    }

    /**
     * Test transaction type field name
     *
     * @test
     */
    public function testTransactionTypeFieldName(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'trans_type_75',
            $generator->transactionTypeField(75),
            'Should generate trans_type field name'
        );
    }

    /**
     * Test zero ID is handled correctly
     *
     * @test
     */
    public function testZeroIdIsHandledCorrectly(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'field_0',
            $generator->generate('field', 0),
            'Should handle zero ID'
        );
    }

    /**
     * Test negative ID is handled correctly
     *
     * @test
     */
    public function testNegativeIdIsHandledCorrectly(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'field_-5',
            $generator->generate('field', -5),
            'Should handle negative ID'
        );
    }

    /**
     * Test field name sanitization
     *
     * @test
     */
    public function testFieldNameSanitization(): void
    {
        $generator = new FormFieldNameGenerator();
        
        // Spaces should be replaced with underscores
        $this->assertSame(
            'vendor_name',
            $generator->sanitize('vendor name'),
            'Should replace spaces with underscores'
        );
        
        // Special characters should be removed or replaced
        $this->assertSame(
            'vendor_id',
            $generator->sanitize('vendor-id'),
            'Should handle hyphens'
        );
    }

    /**
     * Test array of field names
     *
     * @test
     */
    public function testGenerateMultipleFields(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $fields = $generator->generateMultiple(['vendor_id', 'customer_id', 'amount'], 100);
        
        $expected = [
            'vendor_id_100',
            'customer_id_100',
            'amount_100'
        ];
        
        $this->assertSame($expected, $fields, 'Should generate multiple field names with same ID');
    }

    /**
     * Test custom separator
     *
     * @test
     */
    public function testCustomSeparator(): void
    {
        $generator = new FormFieldNameGenerator('-');
        
        $this->assertSame(
            'vendor_id-123',
            $generator->generate('vendor_id', 123),
            'Should use custom separator'
        );
    }

    /**
     * Test default separator is underscore
     *
     * @test
     */
    public function testDefaultSeparatorIsUnderscore(): void
    {
        $generator = new FormFieldNameGenerator();
        
        $this->assertSame(
            'field_123',
            $generator->generate('field', 123),
            'Default separator should be underscore'
        );
    }
}
