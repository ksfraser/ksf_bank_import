<?php

/**
 * Test for bi_lineitem Partner Types Refactoring
 * 
 * REFACTOR STEP 2: Verify that bi_lineitem can accept either:
 * - Legacy array format: ['SP' => 'Supplier', ...]
 * - PartnerTypeConstants (future)
 *
 * NOTE: These tests verify the COMPATIBILITY of the optypes parameter,
 * not the full bi_lineitem functionality (which has external dependencies).
 *
 * @package    KsfBankImport
 * @subpackage Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\PartnerTypeConstants;

/**
 * Test bi_lineitem Partner Types Compatibility
 *
 * These tests verify that PartnerTypeConstants::getAll() returns an array
 * structure that is 100% compatible with what bi_lineitem expects.
 */
class BiLineitemPartnerTypesTest extends TestCase
{
    /**
     * Sample transaction data for testing
     *
     * @var array<string, mixed>
     */
    private array $sampleTransaction;

    /**
     * Sample vendor list for testing
     *
     * @var array<int, string>
     */
    private array $sampleVendorList;

    /**
     * Legacy optypes array (what process_statements.php USED to pass)
     *
     * @var array<string, string>
     */
    private array $legacyOptypes;

    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Sample transaction data (minimal required fields)
        $this->sampleTransaction = [
            'transactionDC' => 'C',
            'memo' => 'Test Memo',
            'our_account' => 'Test Account',
            'valueTimestamp' => '2025-10-20',
            'entryTimestamp' => '2025-10-20',
            'accountName' => 'Test Account Name',
            'transactionTitle' => 'Test Transaction',
            'transactionCode' => 'TC001',
            'transactionCodeDesc' => 'Test Code',
            'currency' => 'USD',
            'status' => 0,
            'id' => 123,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'transactionAmount' => 100.50,
            'transactionType' => 'TRN'
        ];

        // Sample vendor list
        $this->sampleVendorList = [
            1 => 'Vendor One',
            2 => 'Vendor Two',
        ];

        // Legacy optypes (what was hardcoded in process_statements.php)
        $this->legacyOptypes = [
            'SP' => 'Supplier',
            'CU' => 'Customer',
            'QE' => 'Quick Entry',
            'BT' => 'Bank Transfer',
            'MA' => 'Manual settlement',
            'ZZ' => 'Matched',
        ];
    }

    /**
     * Test that PartnerTypeConstants returns exact same structure as legacy array
     *
     * Note: Order may differ due to priority sorting, but all keys and values must match
     *
     * @test
     */
    public function partner_type_constants_matches_legacy_optypes_exactly(): void
    {
        // Get optypes from PartnerTypeConstants (NEW way)
        $newOptypes = PartnerTypeConstants::getAll();

        // Keys should match (order-independent)
        $legacyKeys = array_keys($this->legacyOptypes);
        $newKeys = array_keys($newOptypes);
        sort($legacyKeys);
        sort($newKeys);
        
        $this->assertSame(
            $legacyKeys,
            $newKeys,
            'Must have same keys (order independent)'
        );

        // Values should match for each key
        foreach ($this->legacyOptypes as $code => $expectedLabel) {
            $this->assertArrayHasKey($code, $newOptypes);
            $this->assertSame(
                $expectedLabel,
                $newOptypes[$code],
                "Label for '{$code}' must match legacy value"
            );
        }

        // Count should match
        $this->assertCount(
            count($this->legacyOptypes),
            $newOptypes,
            'Must have same number of partner types'
        );
    }

    /**
     * Test that all legacy keys exist in PartnerTypeConstants
     *
     * @test
     */
    public function all_legacy_keys_exist_in_partner_type_constants(): void
    {
        $newOptypes = PartnerTypeConstants::getAll();

        foreach (array_keys($this->legacyOptypes) as $legacyKey) {
            $this->assertArrayHasKey(
                $legacyKey,
                $newOptypes,
                "PartnerTypeConstants must have legacy key '{$legacyKey}'"
            );
        }
    }

    /**
     * Test that all legacy values match in PartnerTypeConstants
     *
     * @test
     */
    public function all_legacy_values_match_in_partner_type_constants(): void
    {
        $newOptypes = PartnerTypeConstants::getAll();

        foreach ($this->legacyOptypes as $code => $legacyLabel) {
            $this->assertSame(
                $legacyLabel,
                $newOptypes[$code],
                "Label for '{$code}' must match legacy value"
            );
        }
    }

    /**
     * Test that optypes are suitable for array_selector() function
     *
     * array_selector() expects associative array with string keys and values
     *
     * @test
     */
    public function optypes_structure_compatible_with_array_selector(): void
    {
        $optypes = PartnerTypeConstants::getAll();

        // Must be an array
        $this->assertIsArray($optypes);

        // Must not be empty
        $this->assertNotEmpty($optypes);

        // Must be associative array with string keys and values
        foreach ($optypes as $key => $value) {
            $this->assertIsString($key, "Key must be string for array_selector");
            $this->assertIsString($value, "Value must be string for array_selector");
            $this->assertNotEmpty($value, "Value must not be empty");
        }
    }

    /**
     * Test migration scenario - code change simulation
     *
     * @test
     */
    public function process_statements_migration_scenario(): void
    {
        // BEFORE (line 51-58 in old process_statements.php):
        $oldCode = [
            'SP' => 'Supplier',
            'CU' => 'Customer',
            'QE' => 'Quick Entry',
            'BT' => 'Bank Transfer',
            'MA' => 'Manual settlement',
            'ZZ' => 'Matched',
        ];

        // AFTER (line 54 in new process_statements.php):
        $newCode = PartnerTypeConstants::getAll();

        // They must be identical
        $this->assertEquals(
            $oldCode,
            $newCode,
            'Migration: PartnerTypeConstants::getAll() must return same array as legacy code'
        );
    }

    /**
     * Test each partner type individually
     *
     * @test
     * @dataProvider partnerTypeProvider
     */
    public function each_partner_type_exists_with_correct_label(string $code, string $expectedLabel): void
    {
        $optypes = PartnerTypeConstants::getAll();

        $this->assertArrayHasKey(
            $code,
            $optypes,
            "Partner type '{$code}' must exist"
        );

        $this->assertSame(
            $expectedLabel,
            $optypes[$code],
            "Partner type '{$code}' must have label '{$expectedLabel}'"
        );
    }

    /**
     * Data provider for partner types
     *
     * @return array<array{string, string}>
     */
    public function partnerTypeProvider(): array
    {
        return [
            'Supplier' => ['SP', 'Supplier'],
            'Customer' => ['CU', 'Customer'],
            'Quick Entry' => ['QE', 'Quick Entry'],
            'Bank Transfer' => ['BT', 'Bank Transfer'],
            'Manual settlement' => ['MA', 'Manual settlement'],
            'Matched' => ['ZZ', 'Matched'],
        ];
    }

    /**
     * Test that bi_lineitem constructor signature is compatible
     *
     * Verifies the optypes parameter can accept the new array format
     *
     * @test
     */
    public function bi_lineitem_constructor_signature_is_compatible(): void
    {
        $optypes = PartnerTypeConstants::getAll();

        // Verify it's an array (matches $optypes = array() default parameter)
        $this->assertIsArray($optypes);

        // Verify structure matches what bi_lineitem stores in $this->optypes
        // bi_lineitem uses: label_row("Partner:", array_selector(..., $this->optypes, ...))
        // array_selector expects: array('code' => 'Label', ...)
        
        foreach ($optypes as $code => $label) {
            $this->assertIsString($code, "Code must be string");
            $this->assertIsString($label, "Label must be string");
            $this->assertMatchesRegularExpression(
                '/^[A-Z]{2}$/',
                $code,
                "Code must be 2 uppercase letters"
            );
        }
    }

    /**
     * Test count matches legacy array
     *
     * @test
     */
    public function partner_type_count_matches_legacy(): void
    {
        $optypes = PartnerTypeConstants::getAll();

        $this->assertCount(
            count($this->legacyOptypes),
            $optypes,
            'Must have same number of partner types as legacy array'
        );

        $this->assertCount(
            6,
            $optypes,
            'Must have exactly 6 partner types'
        );
    }
}
