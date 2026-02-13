<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Schema\BiTransactionsSchema;
use Ksfraser\FaBankImport\Schema\BiStatementsSchema;
use Ksfraser\FaBankImport\Schema\BiPartnersDataSchema;
use Ksfraser\FaBankImport\Schema\BiUploadedFilesSchema;
use Ksfraser\FaBankImport\Schema\BiFileStatementsSchema;
use Ksfraser\FaBankImport\Schema\BiConfigSchema;
use Ksfraser\FaBankImport\Schema\BiConfigHistorySchema;
use Ksfraser\FaBankImport\Schema\BiBankAccountsSchema;

class ModuleSchemaDescriptorsTest extends TestCase
{
    /**
     * @return array<int, array{0: class-string, 1: string, 2: string, 3: string, 4: int}>
     */
    public function schemaProvider(): array
    {
        return [
            [BiStatementsSchema::class, 'bi_statements', 'bi_statements', 'id', 15],
            [BiTransactionsSchema::class, 'bi_transactions', 'bi_transactions', 'id', 30],
            [BiPartnersDataSchema::class, 'bi_partners_data', 'bi_partners_data', 'partner_id, partner_detail_id, partner_type, data', 6],
            [BiUploadedFilesSchema::class, 'bi_uploaded_files', 'bi_uploaded_files', 'id', 12],
            [BiFileStatementsSchema::class, 'bi_file_statements', 'bi_file_statements', 'file_id, statement_id', 2],
            [BiConfigSchema::class, 'bi_config', 'bi_config', 'id', 9],
            [BiConfigHistorySchema::class, 'bi_config_history', 'bi_config_history', 'id', 7],
            [BiBankAccountsSchema::class, 'bi_bank_accounts', 'bi_bank_accounts', 'id', 8],
        ];
    }

    /**
     * @dataProvider schemaProvider
     * @param class-string $schemaClass
     */
    public function testDescriptorShapeAndMemoization(string $schemaClass, string $entity, string $table, string $primaryKey, int $fieldCount): void
    {
        $a = $schemaClass::descriptor();
        $this->assertIsArray($a);
        $this->assertSame($entity, $a['entity']);
        $this->assertSame($table, $a['table']);
        $this->assertSame($primaryKey, $a['primaryKey']);

        $this->assertArrayHasKey('fields', $a);
        $this->assertIsArray($a['fields']);
        $this->assertCount($fieldCount, $a['fields']);

        $this->assertArrayHasKey('ui', $a);
        $this->assertIsArray($a['ui']);

        // Memoized
        $b = $schemaClass::descriptor();
        $this->assertSame($a, $b);
    }

    /**
     * @dataProvider schemaProvider
     * @param class-string $schemaClass
     */
    public function testTableNameUsesTbPref(string $schemaClass): void
    {
        if (!defined('TB_PREF')) {
            define('TB_PREF', '0_');
        }

        $this->assertSame('0_' . $schemaClass::descriptor()['table'], $schemaClass::tableName());
        $this->assertSame('X_' . $schemaClass::descriptor()['table'], $schemaClass::tableName('X_'));
    }

    /**
     * @dataProvider schemaProvider
     * @param class-string $schemaClass
     */
    public function testFieldNamesMatchesDescriptorKeys(string $schemaClass): void
    {
        $d = $schemaClass::descriptor();
        $this->assertSame(array_keys($d['fields']), $schemaClass::fieldNames());
    }
}
