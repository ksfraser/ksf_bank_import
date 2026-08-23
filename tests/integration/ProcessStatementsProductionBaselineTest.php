<?php
/**
 * Modern Architecture Guard for process_statements.php
 *
 * INVERSE of the former ProductionBaselineTest: that file pinned the
 * pre-refactor state (hardcoded optypes, no command pattern, no services).
 * The refactor has landed, so this guard asserts the CURRENT contract:
 *
 * - Uses OperationTypesRegistry / PartnerTypeConstants (no hardcoded optypes)
 * - Uses the Command pattern (command_bootstrap, CommandDispatcher)
 * - Uses VendorListManager, paired-transfer services
 *
 * If these fail with "does not use ..." messages, someone has reverted the
 * process_statements refactor.
 */

use PHPUnit\Framework\TestCase;

class ProcessStatementsProductionBaselineTest extends TestCase
{
    private $filePath;
    private $fileContent;

    protected function setUp(): void
    {
        $this->filePath = __DIR__ . '/../../process_statements.php';
        $this->assertTrue(file_exists($this->filePath), "File must exist: {$this->filePath}");
        // Strip comments so documented examples don't trip guards.
        $raw = file_get_contents($this->filePath);
        $this->fileContent = preg_replace('/^\s*\/\/.*$/m', '', $raw);
    }

    /** Guard: file exists */
    public function testFileExists(): void
    {
        $this->assertFileExists($this->filePath);
        $this->assertFileIsReadable($this->filePath);
    }

    /** Guard: uses the Command pattern bootstrap */
    public function testUsesCommandBootstrap(): void
    {
        $this->assertStringContainsString('command_bootstrap.php', $this->fileContent,
            'Should include the Command Pattern bootstrap (which registers CommandDispatcher)');
    }

    /** Guard: uses PartnerTypeConstants instead of hardcoded optypes */
    public function testUsesPartnerTypeConstants(): void
    {
        $this->assertStringContainsString('PartnerTypeConstants', $this->fileContent,
            'Should reference PartnerTypeConstants');
        $this->assertSame(0, substr_count($this->fileContent, "'MA' => 'Manual settlement'"),
            'Reverted to hardcoded optypes array - use PartnerTypeConstants::getAll()');
    }

    /** Guard: uses OperationTypesRegistry */
    public function testUsesOperationTypesRegistry(): void
    {
        $this->assertStringContainsString('OperationTypesRegistry', $this->fileContent,
            'Should use OperationTypesRegistry');
    }

    /** Guard: uses VendorListManager for vendor data */
    public function testUsesVendorListManager(): void
    {
        $this->assertStringContainsString('VendorListManager', $this->fileContent,
            'Should use VendorListManager singleton for vendor list');
    }

    /** Guard: keeps direct POST handlers for UnsetTrans/AddCustomer/AddVendor */
    public function testKeepsDirectPostHandlers(): void
    {
        foreach (['UnsetTrans', 'AddCustomer', 'AddVendor'] as $handler) {
            $this->assertMatchesRegularExpression(
                "/if\\s*\\(\\s*isset\\s*\\(\\s*\\\$_POST\\['$handler'\\]\\s*\\)\\s*\\)/",
                $this->fileContent,
                "Direct $handler POST handler must remain"
            );
        }
    }

    /** Guard: ProcessTransaction flow present */
    public function testHasProcessTransactionHandler(): void
    {
        $this->assertMatchesRegularExpression(
            '/isset\\s*\\(\\s*\\$_POST\\[\'ProcessTransaction\'\\]\\s*\\)/',
            $this->fileContent,
            'ProcessTransaction handler must remain'
        );
    }
}
