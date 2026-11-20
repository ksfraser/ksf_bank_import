<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Guard: the merchant-detail columns must exist everywhere they are needed.
 *
 * Adding a field to the transaction object is not enough — a field that is
 * missing from the DDL, from fields_array, or from hand_insert_sql() is
 * silently dropped by banking_base::__set() or never persisted.
 *
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
final class StagingSchemaFieldParityTest extends TestCase
{
    private const NEW_COLUMNS = array(
        'city',
        'province',
        'country',
        'postalcode',
        'address',
        'rewards',
        'cardnumber',
        'nameoncard',
    );

    private function source(string $relative): string
    {
        $content = file_get_contents(__DIR__ . '/../..' . $relative);
        $this->assertIsString($content, $relative . ' must exist.');

        return $content;
    }

    public function testTheTransactionObjectDeclaresEveryColumn(): void
    {
        $banking = $this->source('/includes/banking.php');

        foreach (self::NEW_COLUMNS as $column) {
            $this->assertMatchesRegularExpression(
                '/var\s+\$' . $column . '\b/',
                $banking,
                '$' . $column . ' must be declared on transaction.'
            );
        }
    }

    public function testTheInstallSchemaHasEveryColumnAndAWideMemo(): void
    {
        $sql = $this->source('/update.sql');

        foreach (self::NEW_COLUMNS as $column) {
            $this->assertMatchesRegularExpression(
                '/`' . $column . '`\s+(VARCHAR|INT|TEXT|DECIMAL)/i',
                $sql,
                'update.sql must define ' . $column . '.'
            );
        }

        $this->assertMatchesRegularExpression('/`memo`\s+VARCHAR\(255\)/i', $sql);
    }

    public function testTheModelFieldsArrayCoversEveryColumn(): void
    {
        $model = $this->source('/class.bi_transactions.php');

        foreach (self::NEW_COLUMNS as $column) {
            $this->assertStringContainsString("'{$column}'", $model, 'fields_array must list ' . $column . '.');
        }
        $this->assertMatchesRegularExpression("/'name'\\s*=>\\s*'memo'[^\\n]*'type'\\s*=>\\s*'varchar\\(255\\)'/", $model);
    }

    public function testTheWriteSqlPersistsEveryColumn(): void
    {
        $model = $this->source('/class.bi_transactions.php');

        $insert = $this->methodBody($model, 'hand_insert_sql');
        $update = $this->methodBody($model, 'hand_update_sql');

        foreach (self::NEW_COLUMNS as $column) {
            $this->assertStringContainsString($column, $insert, 'hand_insert_sql must persist ' . $column . '.');
            $this->assertStringContainsString($column, $update, 'hand_update_sql must persist ' . $column . '.');
        }
    }

    public function testEnsureSchemaUpgradesExistingInstalls(): void
    {
        $model = $this->source('/class.bi_transactions.php');
        $ensure = $this->methodBody($model, 'ensure_schema');

        foreach (self::NEW_COLUMNS as $column) {
            $this->assertStringContainsString("'{$column}'", $ensure, 'ensure_schema must add ' . $column . '.');
        }
        $this->assertStringContainsString('widen_column', $ensure, 'memo must be widened on existing installs.');
        $this->assertMatchesRegularExpression('/function\s+widen_column\s*\(/', $model);
    }

    private function methodBody(string $source, string $method): string
    {
        $start = strpos($source, 'function ' . $method . '(');
        $this->assertNotFalse($start, $method . '() must exist.');

        $next = strpos($source, 'function ', $start + 10);
        $body = $next === false ? substr($source, $start) : substr($source, $start, $next - $start);

        return $body;
    }
}
