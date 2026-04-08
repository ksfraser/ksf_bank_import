<?php

namespace Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Parsers\SynonymResolver;

/**
 * Unit Tests for SynonymResolver
 *
 * Tests configurable synonym resolution with 3-tier priority:
 * 1. Runtime-provided synonyms (highest)
 * 2. Config file synonyms
 * 3. Hardcoded defaults (fallback)
 *
 * Features tested:
 * - Default hardcoded synonyms for all fields
 * - Config file loading (JSON)
 * - Runtime synonym injection
 * - Priority resolution (runtime > config > hardcoded)
 * - Parser-specific synonyms vs universal
 * - Synonym retrieval and field lookup
 * - Fluent API for runtime additions
 * - Error handling for invalid config
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Parsers\SynonymResolver
 */
class SynonymResolverTest extends TestCase
{
    private SynonymResolver $resolver;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->resolver = new SynonymResolver();
        $this->tempDir = sys_get_temp_dir() . '/synonym_resolver_tests_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    /**
     * Test 1: Default synonyms for transactionDate field
     */
    public function testDefaultSynonymsForTransactionDate(): void
    {
        $synonyms = $this->resolver->getSynonymsForField('transactionDate', 'ALL');

        $this->assertIsArray($synonyms);
        $this->assertContains('Date', $synonyms);
        $this->assertContains('Transaction Date', $synonyms);
        $this->assertContains('Posted Date', $synonyms);
    }

    /**
     * Test 2: Default synonyms for amount field
     */
    public function testDefaultSynonymsForAmount(): void
    {
        $synonyms = $this->resolver->getSynonymsForField('amount', 'ALL');

        $this->assertIsArray($synonyms);
        $this->assertContains('Amount', $synonyms);
        $this->assertContains('Transaction Amount', $synonyms);
        $this->assertContains('Sum', $synonyms);
    }

    /**
     * Test 3: Default synonyms for merchant field
     */
    public function testDefaultSynonymsForMerchant(): void
    {
        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');

        $this->assertIsArray($synonyms);
        $this->assertContains('Merchant Name', $synonyms);
        $this->assertContains('Beneficiary', $synonyms);
        $this->assertContains('Counterparty', $synonyms);
    }

    /**
     * Test 4: Default synonyms for description field
     */
    public function testDefaultSynonymsForDescription(): void
    {
        $synonyms = $this->resolver->getSynonymsForField('description', 'ALL');

        $this->assertIsArray($synonyms);
        $this->assertContains('Description', $synonyms);
        $this->assertContains('Activity Type', $synonyms);
        $this->assertContains('Transaction Type', $synonyms);
    }

    /**
     * Test 5: Get supported fields returns all 8 fields
     */
    public function testGetSupportedFieldsReturnsAllFields(): void
    {
        $fields = $this->resolver->getSupportedFields();

        $this->assertIsArray($fields);
        $this->assertCount(8, $fields);
        $this->assertContains('transactionDate', $fields);
        $this->assertContains('amount', $fields);
        $this->assertContains('merchant', $fields);
        $this->assertContains('description', $fields);
        $this->assertContains('reference', $fields);
        $this->assertContains('category', $fields);
        $this->assertContains('account', $fields);
        $this->assertContains('currency', $fields);
    }

    /**
     * Test 6: Load config file with universal synonyms
     */
    public function testLoadConfigFileWithUniversalSynonyms(): void
    {
        $config = [
            'synonyms' => [
                'merchant' => ['shop', 'store', 'retailer']
            ]
        ];
        $configFile = $this->tempDir . '/synonyms.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));

        $this->resolver->loadConfigFile($configFile);
        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');

        // Config replaces defaults for that field
        $this->assertContains('shop', $synonyms);
        $this->assertContains('store', $synonyms);
        $this->assertContains('retailer', $synonyms);
        $this->assertCount(3, $synonyms);
    }

    /**
     * Test 7: Load config file with parser-specific synonyms
     */
    public function testLoadConfigFileWithParserSpecificSynonyms(): void
    {
        $config = [
            'parserSpecific' => [
                'csv' => [
                    'amount' => ['montant', 'somme'],
                    'merchant' => ['vendeur']
                ]
            ]
        ];
        $configFile = $this->tempDir . '/synonyms.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));

        $this->resolver->loadConfigFile($configFile);
        $synonyms = $this->resolver->getSynonymsForField('amount', 'csv');

        $this->assertContains('montant', $synonyms);
        $this->assertContains('somme', $synonyms);
    }

    /**
     * Test 8: Runtime synonyms override config file
     */
    public function testRuntimeSynonymsOverrideConfigFile(): void
    {
        // Load config
        $config = [
            'synonyms' => [
                'merchant' => ['shop']
            ]
        ];
        $configFile = $this->tempDir . '/synonyms.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->resolver->loadConfigFile($configFile);

        // Set runtime synonyms
        $this->resolver->setRuntimeSynonyms([
            'merchant' => ['boutique', 'magasin']
        ]);

        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');

        // Runtime should take priority (actually replace for this field)
        $this->assertContains('boutique', $synonyms);
        $this->assertContains('magasin', $synonyms);
    }

    /**
     * Test 9: Fluent API - addSynonym with chaining
     */
    public function testFluentAPIAddSynonymWithChaining(): void
    {
        $result = $this->resolver
            ->addSynonym('merchant', 'boutique', 'ALL')
            ->addSynonym('merchant', 'magasin', 'ALL')
            ->addSynonym('amount', 'total', 'ALL');

        $this->assertInstanceOf(SynonymResolver::class, $result);

        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');
        $this->assertContains('boutique', $synonyms);
        $this->assertContains('magasin', $synonyms);
    }

    /**
     * Test 10: getFieldNameForHeader matches header to field
     */
    public function testGetFieldNameForHeaderMatchesHeaderToField(): void
    {
        $field = $this->resolver->getFieldNameForHeader('Date', 'ALL');
        $this->assertEquals('transactionDate', $field);

        $field = $this->resolver->getFieldNameForHeader('Amount', 'ALL');
        $this->assertEquals('amount', $field);

        $field = $this->resolver->getFieldNameForHeader('Beneficiary', 'ALL');
        $this->assertEquals('merchant', $field);
    }

    /**
     * Test 11: getFieldNameForHeader with parser-specific synonyms
     */
    public function testGetFieldNameForHeaderWithParserSpecificSynonyms(): void
    {
        $config = [
            'parserSpecific' => [
                'csv' => [
                    'amount' => ['montant']
                ]
            ]
        ];
        $configFile = $this->tempDir . '/synonyms.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->resolver->loadConfigFile($configFile);

        $field = $this->resolver->getFieldNameForHeader('montant', 'csv');
        $this->assertEquals('amount', $field);
    }

    /**
     * Test 12: getFieldNameForHeader returns null for unknown header
     */
    public function testGetFieldNameForHeaderReturnsNullForUnknownHeader(): void
    {
        $field = $this->resolver->getFieldNameForHeader('unknown_header_xyz', 'ALL');
        $this->assertNull($field);
    }

    /**
     * Test 13: getAllSynonyms returns complete map for parser
     */
    public function testGetAllSynonymsReturnsCompleteMap(): void
    {
        $synonymsMap = $this->resolver->getAllSynonyms('ALL');

        $this->assertIsArray($synonymsMap);
        $this->assertArrayHasKey('transactionDate', $synonymsMap);
        $this->assertArrayHasKey('amount', $synonymsMap);
        $this->assertArrayHasKey('merchant', $synonymsMap);
        $this->assertArrayHasKey('description', $synonymsMap);

        // Each field should have array of synonyms
        $this->assertIsArray($synonymsMap['transactionDate']);
        $this->assertGreaterThan(0, count($synonymsMap['transactionDate']));
    }

    /**
     * Test 14: Parser-specific synonyms override universal
     */
    public function testParserSpecificSynonymsOverrideUniversal(): void
    {
        $config = [
            'synonyms' => [
                'amount' => ['montant']
            ],
            'parserSpecific' => [
                'csv' => [
                    'amount' => ['somme']
                ]
            ]
        ];
        $configFile = $this->tempDir . '/synonyms.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->resolver->loadConfigFile($configFile);

        $synonyms = $this->resolver->getSynonymsForField('amount', 'csv');

        // Parser-specific overrides universal (not merged)
        $this->assertContains('somme', $synonyms);
        $this->assertCount(1, $synonyms);
    }

    /**
     * Test 15: Empty config file handling
     */
    public function testEmptyConfigFileHandling(): void
    {
        $configFile = $this->tempDir . '/empty.json';
        file_put_contents($configFile, json_encode([], JSON_PRETTY_PRINT));

        // Should not throw, default synonyms should remain
        $this->resolver->loadConfigFile($configFile);
        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');

        // Defaults should include Merchant Name
        $this->assertContains('Merchant Name', $synonyms);
    }

    /**
     * Test 16: Invalid JSON config file throws exception
     */
    public function testInvalidJSONConfigFileThrowsException(): void
    {
        $configFile = $this->tempDir . '/invalid.json';
        file_put_contents($configFile, 'not valid json {{{');

        $this->expectException(\RuntimeException::class);
        $this->resolver->loadConfigFile($configFile);
    }

    /**
     * Test 17: setRuntimeSynonyms replaces previous runtime synonyms
     */
    public function testSetRuntimeSynonymsReplacesPrevious(): void
    {
        $this->resolver->setRuntimeSynonyms([
            'merchant' => ['shop1']
        ]);

        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');
        $this->assertContains('shop1', $synonyms);

        // Set new runtime synonyms - should replace
        $this->resolver->setRuntimeSynonyms([
            'merchant' => ['shop2']
        ]);

        $synonyms = $this->resolver->getSynonymsForField('merchant', 'ALL');
        $this->assertContains('shop2', $synonyms);
        $this->assertNotContains('shop1', $synonyms);
    }

    /**
     * Test 18: Header matching is case-insensitive
     */
    public function testHeaderMatchingIsCaseInsensitive(): void
    {
        $field1 = $this->resolver->getFieldNameForHeader('Date', 'ALL');
        $field2 = $this->resolver->getFieldNameForHeader('DATE', 'ALL');
        $field3 = $this->resolver->getFieldNameForHeader('date', 'ALL');

        $this->assertEquals('transactionDate', $field1);
        $this->assertEquals('transactionDate', $field2);
        $this->assertEquals('transactionDate', $field3);
    }

    /**
     * Test 19: Multiple runtime synonyms for different fields
     */
    public function testMultipleRuntimeSynonymsForDifferentFields(): void
    {
        $this->resolver->setRuntimeSynonyms([
            'merchant' => ['shop'],
            'amount' => ['total'],
            'description' => ['note']
        ]);

        $this->assertContains('shop', $this->resolver->getSynonymsForField('merchant', 'ALL'));
        $this->assertContains('total', $this->resolver->getSynonymsForField('amount', 'ALL'));
        $this->assertContains('note', $this->resolver->getSynonymsForField('description', 'ALL'));
    }

    /**
     * Test 20: Different parser types maintain separate config
     */
    public function testDifferentParserTypesMaintainSeparateConfig(): void
    {
        $config = [
            'parserSpecific' => [
                'csv' => [
                    'amount' => ['montant']
                ],
                'qif' => [
                    'amount' => ['amount_qif']
                ]
            ]
        ];
        $configFile = $this->tempDir . '/synonyms.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->resolver->loadConfigFile($configFile);

        $csvAmount = $this->resolver->getSynonymsForField('amount', 'csv');
        $qifAmount = $this->resolver->getSynonymsForField('amount', 'qif');

        $this->assertContains('montant', $csvAmount);
        $this->assertNotContains('montant', $qifAmount);
        $this->assertContains('amount_qif', $qifAmount);
    }

    /**
     * Helper: Check that default synonyms are comprehensive
     */
    public function testDefaultSynonymsCoverAllFields(): void
    {
        $fields = $this->resolver->getSupportedFields();

        foreach ($fields as $field) {
            $synonyms = $this->resolver->getSynonymsForField($field, 'ALL');
            $this->assertIsArray($synonyms);
            $this->assertGreaterThan(0, count($synonyms), "Field '$field' should have at least one synonym");
        }
    }
}
