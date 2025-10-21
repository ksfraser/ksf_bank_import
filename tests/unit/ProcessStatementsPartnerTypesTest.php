<?php

/**
 * Test for Process Statements Partner Types Migration
 * 
 * REFACTOR STEP 1: Verify that the hardcoded $optypes array can be replaced
 * with PartnerTypeConstants class without breaking functionality.
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
use Ksfraser\PartnerTypes\PartnerTypeRegistry;

/**
 * Test Partner Types Migration from Hardcoded Array to Constants
 *
 * This test verifies that we can safely replace the hardcoded $optypes array
 * in process_statements.php with the PartnerTypeConstants class.
 *
 * Current code (process_statements.php line 51-58):
 * ```php
 * $optypes = array(
 *     'SP' => 'Supplier',
 *     'CU' => 'Customer',
 *     'QE' => 'Quick Entry',
 *     'BT' => 'Bank Transfer',
 *     'MA' => 'Manual settlement',
 *     'ZZ' => 'Matched',
 * );
 * ```
 *
 * Should become:
 * ```php
 * use Ksfraser\PartnerTypeConstants;
 * $optypes = PartnerTypeConstants::getAll();
 * ```
 */
class ProcessStatementsPartnerTypesTest extends TestCase
{
    /**
     * The hardcoded array from process_statements.php
     * This represents the CURRENT implementation
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

        // This is the EXACT array from process_statements.php lines 51-58
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
     * Test that PartnerTypeConstants contains all required partner types
     *
     * @test
     */
    public function it_has_all_required_partner_type_constants(): void
    {
        // Assert all expected constants exist
        $this->assertSame('SP', PartnerTypeConstants::SUPPLIER);
        $this->assertSame('CU', PartnerTypeConstants::CUSTOMER);
        $this->assertSame('QE', PartnerTypeConstants::QUICK_ENTRY);
        $this->assertSame('BT', PartnerTypeConstants::BANK_TRANSFER);
        $this->assertSame('MA', PartnerTypeConstants::MANUAL_SETTLEMENT);
        $this->assertSame('ZZ', PartnerTypeConstants::MATCHED);
    }

    /**
     * Test that PartnerTypeConstants::getAll() returns array with same keys
     *
     * Note: Order doesn't matter for array_selector() or switch statements,
     * so we only verify that all keys exist, not their order.
     *
     * @test
     */
    public function it_has_same_keys_as_legacy_array(): void
    {
        $constants = PartnerTypeConstants::getAll();
        
        // Get the keys from both arrays
        $legacyKeys = array_keys($this->legacyOptypes);
        $constantKeys = array_keys($constants);

        // Sort both for comparison (order doesn't matter functionally)
        sort($legacyKeys);
        sort($constantKeys);

        // Both arrays should have the same keys (partner type codes)
        $this->assertSame(
            $legacyKeys,
            $constantKeys,
            'PartnerTypeConstants::getAll() keys must match legacy $optypes keys'
        );
        
        // Also verify count
        $this->assertCount(
            count($this->legacyOptypes),
            $constants,
            'Should have same number of partner types'
        );
    }

    /**
     * Test that each partner type code is valid
     *
     * @test
     */
    public function it_validates_all_legacy_partner_types(): void
    {
        foreach (array_keys($this->legacyOptypes) as $partnerTypeCode) {
            $this->assertTrue(
                PartnerTypeConstants::isValid($partnerTypeCode),
                "Partner type '{$partnerTypeCode}' should be valid"
            );
        }
    }

    /**
     * Test that labels are retrievable for all partner types
     *
     * @test
     */
    public function it_provides_labels_for_all_partner_types(): void
    {
        foreach ($this->legacyOptypes as $code => $expectedLabel) {
            $actualLabel = PartnerTypeConstants::getLabel($code);
            
            $this->assertNotEmpty(
                $actualLabel,
                "Label for partner type '{$code}' should not be empty"
            );
        }
    }

    /**
     * Test that we can build an optypes array from PartnerTypeConstants
     *
     * This is the KEY test - can we replace the hardcoded array?
     *
     * @test
     */
    public function it_can_build_optypes_array_from_constants(): void
    {
        // This is what the NEW code would look like
        $newOptypes = PartnerTypeConstants::getAll();

        // Verify same number of entries
        $this->assertCount(
            count($this->legacyOptypes),
            $newOptypes,
            'New optypes should have same count as legacy optypes'
        );

        // Verify all keys exist
        foreach (array_keys($this->legacyOptypes) as $key) {
            $this->assertArrayHasKey(
                $key,
                $newOptypes,
                "New optypes should have key '{$key}'"
            );
        }

        // Verify all values are non-empty strings
        foreach ($newOptypes as $code => $label) {
            $this->assertIsString($code);
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    /**
     * Test that PartnerTypeRegistry is accessible via constants
     *
     * @test
     */
    public function it_provides_access_to_registry(): void
    {
        $registry = PartnerTypeConstants::getRegistry();

        $this->assertInstanceOf(
            PartnerTypeRegistry::class,
            $registry,
            'Should provide access to PartnerTypeRegistry'
        );
    }

    /**
     * Test that array structure is compatible with array_selector() usage
     *
     * The process_statements.php code uses:
     * array_selector("partnerType[$id]", $_POST['partnerType'][$id], $optypes, ...)
     *
     * @test
     */
    public function it_is_compatible_with_array_selector_function(): void
    {
        $newOptypes = PartnerTypeConstants::getAll();

        // array_selector expects an associative array with string keys and values
        foreach ($newOptypes as $key => $value) {
            $this->assertIsString($key, 'Keys must be strings for array_selector');
            $this->assertIsString($value, 'Values must be strings for array_selector');
            $this->assertMatchesRegularExpression(
                '/^[A-Z]{2}$/',
                $key,
                'Keys should be 2-letter uppercase codes'
            );
        }
    }

    /**
     * Test that switch statement cases will still match
     *
     * The process_statements.php code has:
     * case ($_POST['partnerType'][$k] == 'SP'):
     *
     * @test
     */
    public function it_supports_switch_statement_comparisons(): void
    {
        $testCases = [
            'SP' => PartnerTypeConstants::SUPPLIER,
            'CU' => PartnerTypeConstants::CUSTOMER,
            'QE' => PartnerTypeConstants::QUICK_ENTRY,
            'BT' => PartnerTypeConstants::BANK_TRANSFER,
            'MA' => PartnerTypeConstants::MANUAL_SETTLEMENT,
            'ZZ' => PartnerTypeConstants::MATCHED,
        ];

        foreach ($testCases as $expected => $actual) {
            $this->assertSame(
                $expected,
                $actual,
                "Constant value must match string for switch comparison"
            );
        }
    }

    /**
     * Test backward compatibility - all legacy keys exist in new system
     *
     * @test
     * @dataProvider legacyPartnerTypeProvider
     */
    public function it_maintains_backward_compatibility(string $code, string $label): void
    {
        // Verify code is valid
        $this->assertTrue(
            PartnerTypeConstants::isValid($code),
            "Legacy partner type code '{$code}' must still be valid"
        );

        // Verify we can get a label
        $newLabel = PartnerTypeConstants::getLabel($code);
        $this->assertNotEmpty(
            $newLabel,
            "Must provide a label for legacy code '{$code}'"
        );
    }

    /**
     * Data provider for backward compatibility test
     *
     * @return array<array{string, string}>
     */
    public function legacyPartnerTypeProvider(): array
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
     * Test that replacing array doesn't break bi_lineitem usage
     *
     * The bi_lineitem constructor receives $optypes:
     * new bi_lineitem( $trz, $vendor_list, $optypes );
     *
     * @test
     */
    public function it_is_compatible_with_bi_lineitem_constructor(): void
    {
        $newOptypes = PartnerTypeConstants::getAll();

        // Verify it's an array (for type hint compatibility)
        $this->assertIsArray($newOptypes);

        // Verify it's not empty (bi_lineitem needs options)
        $this->assertNotEmpty($newOptypes);

        // Verify associative structure (code => label)
        foreach ($newOptypes as $code => $label) {
            $this->assertIsString($code);
            $this->assertIsString($label);
        }
    }

    /**
     * Test migration plan - the actual code change we'll make
     *
     * Note: Order may differ due to priority sorting, but all keys exist.
     *
     * @test
     */
    public function it_demonstrates_migration_plan(): void
    {
        // OLD CODE (currently in process_statements.php):
        $oldOptypes = [
            'SP' => 'Supplier',
            'CU' => 'Customer',
            'QE' => 'Quick Entry',
            'BT' => 'Bank Transfer',
            'MA' => 'Manual settlement',
            'ZZ' => 'Matched',
        ];

        // NEW CODE (what we'll replace it with):
        $newOptypes = PartnerTypeConstants::getAll();

        // Assert they have the same keys (order doesn't matter)
        $oldKeys = array_keys($oldOptypes);
        $newKeys = array_keys($newOptypes);
        sort($oldKeys);
        sort($newKeys);
        
        $this->assertSame(
            $oldKeys,
            $newKeys,
            'Migration: Keys must match (order irrelevant)'
        );

        $this->assertCount(
            count($oldOptypes),
            $newOptypes,
            'Migration: Count must match'
        );

        // All existing switch/if statements will still work
        foreach (array_keys($oldOptypes) as $code) {
            $this->assertArrayHasKey($code, $newOptypes);
        }
        
        // All labels should be non-empty
        foreach ($newOptypes as $code => $label) {
            $this->assertNotEmpty($label, "Label for {$code} should not be empty");
        }
    }
}
