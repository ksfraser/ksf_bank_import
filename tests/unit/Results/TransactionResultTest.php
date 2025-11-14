<?php

/**
 * Transaction Result Test
 *
 * Tests for the TransactionResult value object.
 *
 * @package    Tests\Unit\Results
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit\Results;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Transaction Result Test
 *
 * Tests the TransactionResult value object including:
 * - Factory methods (success, error, warning)
 * - Immutability
 * - Array conversion (backward compatibility)
 * - HTML generation
 * - Display integration
 */
class TransactionResultTest extends TestCase
{
    /**
     * Test success result creation
     *
     * @test
     */
    public function it_creates_success_result(): void
    {
        $result = TransactionResult::success(
            42,
            20,
            'Payment processed successfully'
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isError());
        $this->assertFalse($result->isWarning());
        $this->assertSame(42, $result->getTransNo());
        $this->assertSame(20, $result->getTransType());
        $this->assertSame('Payment processed successfully', $result->getMessage());
        $this->assertSame('success', $result->getLevel());
    }

    /**
     * Test success result with additional data
     *
     * @test
     */
    public function it_creates_success_result_with_data(): void
    {
        $result = TransactionResult::success(
            42,
            20,
            'Payment processed',
            ['charge' => 5.00, 'reference' => 'REF-001']
        );

        $this->assertSame(5.00, $result->getData('charge'));
        $this->assertSame('REF-001', $result->getData('reference'));
        $this->assertNull($result->getData('nonexistent'));
    }

    /**
     * Test error result creation
     *
     * @test
     */
    public function it_creates_error_result(): void
    {
        $result = TransactionResult::error('Partner ID not found');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isError());
        $this->assertFalse($result->isWarning());
        $this->assertSame(0, $result->getTransNo());
        $this->assertSame(0, $result->getTransType());
        $this->assertSame('Partner ID not found', $result->getMessage());
        $this->assertSame('error', $result->getLevel());
    }

    /**
     * Test error result with additional data
     *
     * @test
     */
    public function it_creates_error_result_with_data(): void
    {
        $result = TransactionResult::error(
            'Validation failed',
            ['field' => 'partnerId', 'value' => null]
        );

        $this->assertSame('partnerId', $result->getData('field'));
        $this->assertNull($result->getData('value'));
    }

    /**
     * Test warning result creation
     *
     * @test
     */
    public function it_creates_warning_result(): void
    {
        $result = TransactionResult::warning('Transaction already processed');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isError());
        $this->assertTrue($result->isWarning());
        $this->assertSame(0, $result->getTransNo());
        $this->assertSame(0, $result->getTransType());
        $this->assertSame('Transaction already processed', $result->getMessage());
        $this->assertSame('warning', $result->getLevel());
    }

    /**
     * Test warning result with transaction details
     *
     * @test
     */
    public function it_creates_warning_result_with_transaction_details(): void
    {
        $result = TransactionResult::warning(
            'Duplicate detected',
            42,
            20,
            ['original_trans_no' => 39]
        );

        $this->assertSame(42, $result->getTransNo());
        $this->assertSame(20, $result->getTransType());
        $this->assertSame(39, $result->getData('original_trans_no'));
    }

    /**
     * Test toArray conversion
     *
     * @test
     */
    public function it_converts_success_to_array(): void
    {
        $result = TransactionResult::success(
            42,
            20,
            'Success',
            ['charge' => 5.00]
        );

        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame(42, $array['trans_no']);
        $this->assertSame(20, $array['trans_type']);
        $this->assertSame('Success', $array['message']);
        $this->assertSame('success', $array['level']);
        $this->assertSame(5.00, $array['charge']);
    }

    /**
     * Test toArray conversion for error
     *
     * @test
     */
    public function it_converts_error_to_array(): void
    {
        $result = TransactionResult::error('Error message');

        $array = $result->toArray();

        $this->assertFalse($array['success']);
        $this->assertSame(0, $array['trans_no']);
        $this->assertSame(0, $array['trans_type']);
        $this->assertSame('Error message', $array['message']);
        $this->assertSame('error', $array['level']);
    }

    /**
     * Test fromArray conversion
     *
     * @test
     */
    public function it_creates_from_array(): void
    {
        $array = [
            'success' => true,
            'trans_no' => 42,
            'trans_type' => 20,
            'message' => 'Converted from array',
            'level' => 'success',
            'charge' => 5.00
        ];

        $result = TransactionResult::fromArray($array);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(42, $result->getTransNo());
        $this->assertSame(20, $result->getTransType());
        $this->assertSame('Converted from array', $result->getMessage());
        $this->assertSame(5.00, $result->getData('charge'));
    }

    /**
     * Test fromArray with minimal data
     *
     * @test
     */
    public function it_creates_from_minimal_array(): void
    {
        $array = ['message' => 'Minimal'];

        $result = TransactionResult::fromArray($array);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getTransNo());
        $this->assertSame('Minimal', $result->getMessage());
    }

    /**
     * Test toHtml for success
     *
     * @test
     */
    public function it_generates_success_html(): void
    {
        $result = TransactionResult::success(
            42,
            20,
            'Payment processed'
        );

        $html = $result->toHtml();

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Payment processed', $html);
        $this->assertStringContainsString('Transaction #42', $html);
        $this->assertStringContainsString('Type: 20', $html);
        $this->assertStringContainsString('✓', $html);
    }

    /**
     * Test toHtml for error
     *
     * @test
     */
    public function it_generates_error_html(): void
    {
        $result = TransactionResult::error('Partner not found');

        $html = $result->toHtml();

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Partner not found', $html);
        $this->assertStringContainsString('✗', $html);
        $this->assertStringNotContainsString('Transaction #', $html); // No trans_no in error
    }

    /**
     * Test toHtml for warning
     *
     * @test
     */
    public function it_generates_warning_html(): void
    {
        $result = TransactionResult::warning('Already processed');

        $html = $result->toHtml();

        $this->assertStringContainsString('alert-warning', $html);
        $this->assertStringContainsString('Already processed', $html);
        $this->assertStringContainsString('⚠', $html);
    }

    /**
     * Test toHtml escapes HTML
     *
     * @test
     */
    public function it_escapes_html_in_message(): void
    {
        $result = TransactionResult::error('<script>alert("xss")</script>');

        $html = $result->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * Test toString conversion
     *
     * @test
     */
    public function it_converts_to_string(): void
    {
        $result = TransactionResult::success(42, 20, 'Success message');

        $this->assertSame('Success message', (string)$result);
    }

    /**
     * Test getData returns all data when no key specified
     *
     * @test
     */
    public function it_returns_all_data_when_no_key_specified(): void
    {
        $result = TransactionResult::success(
            42,
            20,
            'Success',
            ['charge' => 5.00, 'ref' => 'REF-001']
        );

        $data = $result->getData();

        $this->assertIsArray($data);
        $this->assertSame(5.00, $data['charge']);
        $this->assertSame('REF-001', $data['ref']);
    }

    /**
     * Test result is immutable
     *
     * @test
     */
    public function it_is_immutable(): void
    {
        $result = TransactionResult::success(42, 20, 'Original');

        // Get data returns copy, modifications don't affect result
        $data = $result->getData();
        $data['new_key'] = 'new_value';

        $this->assertNull($result->getData('new_key'));
    }

    /**
     * Test backward compatibility with old array format
     *
     * @test
     */
    public function it_maintains_backward_compatibility(): void
    {
        $result = TransactionResult::success(
            42,
            20,
            'Compatible',
            ['charge' => 5.00]
        );

        // Old code expecting array
        $array = $result->toArray();

        // All expected keys present
        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('trans_no', $array);
        $this->assertArrayHasKey('trans_type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('charge', $array);

        // Values correct
        $this->assertTrue($array['success']);
        $this->assertSame(42, $array['trans_no']);
    }
}
