<?php

namespace Tests\Unit\Service\Schema;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\Schema\ModuleSchemaInstaller;

/**
 * Test ModuleSchemaInstaller - orchestrates idempotent schema initialization
 * 
 * Responsibility: Schema ensure() calls and data migration
 * Changes when: Database schema structure or bootstrap logic changes
 */
class ModuleSchemaInstallerTest extends TestCase
{
    private ModuleSchemaInstaller $installer;

    protected function setUp(): void
    {
        $this->installer = new ModuleSchemaInstaller();
    }

    /**
     * @test
     */
    public function ensureAll_returns_array_of_installation_statuses(): void
    {
        $result = $this->installer->ensureAll();

        $this->assertIsArray($result, 'ensureAll should return array');
        $this->assertNotEmpty($result, 'ensureAll should return non-empty array');
    }

    /**
     * @test
     */
    public function ensureAll_returns_boolean_statuses(): void
    {
        $result = $this->installer->ensureAll();

        foreach ($result as $tableName => $status) {
            $this->assertIsString($tableName, 'Key should be table name (string)');
            $this->assertIsBool($status, "Status for $tableName should be boolean");
        }
    }

    /**
     * @test
     */
    public function ensureAll_is_idempotent(): void
    {
        $result1 = $this->installer->ensureAll();
        $result2 = $this->installer->ensureAll();

        $this->assertEquals($result1, $result2, 'ensureAll should be idempotent');
    }

    /**
     * @test
     */
    public function has_method_to_ensure_legacy_models(): void
    {
        $this->assertTrue(
            method_exists($this->installer, 'ensureLegacyModels'),
            'ModuleSchemaInstaller should have ensureLegacyModels method'
        );
    }

    /**
     * @test
     */
    public function has_method_to_ensure_new_schema_installers(): void
    {
        $this->assertTrue(
            method_exists($this->installer, 'ensureNewSchemaInstallers'),
            'ModuleSchemaInstaller should have ensureNewSchemaInstallers method'
        );
    }

    /**
     * @test
     */
    public function has_method_to_run_data_migration(): void
    {
        $this->assertTrue(
            method_exists($this->installer, 'runDataMigration'),
            'ModuleSchemaInstaller should have runDataMigration method'
        );
    }
}
