<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Config;

use Ksfraser\FaBankImport\Config\BankImportConfig;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class BankImportConfigTest extends TestCase
{
    /**
     * @var array
     */
    private $prefsBackup = [];

    /**
     * @var array
     */
    private $tableBackup = [];

    /**
     * @var array
     */
    private $resultSetBackup = [];

    /**
     * @var array
     */
    private $resultPosBackup = [];

    /**
     * @var array
     */
    private $chartBackup = [];

    /**
     * @var mixed
     */
    private $dbBackup;

    protected function setUp(): void
    {
        $this->prefsBackup = $GLOBALS['__fa_company_prefs'] ?? [];
        $this->tableBackup = $GLOBALS['__fa_table'] ?? [];
        $this->resultSetBackup = $GLOBALS['__fa_result_set'] ?? [];
        $this->resultPosBackup = $GLOBALS['__fa_result_pos'] ?? [];
        $this->chartBackup = $GLOBALS['__fa_chart_accounts'] ?? [];
        $this->dbBackup = $GLOBALS['db'] ?? null;

        $GLOBALS['__fa_company_prefs'] = [];
        $GLOBALS['__fa_table'] = [];
        $GLOBALS['__fa_result_set'] = [];
        $GLOBALS['__fa_result_pos'] = [];
        // Reset the famock chart fixture: an earlier suite in the process
        // (tests/helpers/fa_functions.php) seeds it, and an unreset fixture
        // would make the refusal test accept the account it must reject.
        $GLOBALS['__fa_chart_accounts'] = [];
        // Simulate a live FrontAccounting runtime: BankImportConfig detects
        // FA by the global $db handle (FA's connect_db.inc sets it; famock
        // and standalone contexts do not).
        $GLOBALS['db'] = new \stdClass();
    }

    protected function tearDown(): void
    {
        $GLOBALS['__fa_company_prefs'] = $this->prefsBackup;
        $GLOBALS['__fa_table'] = $this->tableBackup;
        $GLOBALS['__fa_result_set'] = $this->resultSetBackup;
        $GLOBALS['__fa_result_pos'] = $this->resultPosBackup;
        $GLOBALS['__fa_chart_accounts'] = $this->chartBackup;
        if ($this->dbBackup === null) {
            unset($GLOBALS['db']);
        } else {
            $GLOBALS['db'] = $this->dbBackup;
        }
    }

    public function testTransactionReferenceLoggingIsEnabledByDefault(): void
    {
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
    }

    public function testTransactionReferenceLoggingCanBeTurnedOff(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(false);

        $this->assertSame('0', $GLOBALS['__fa_company_prefs']['bank_import_trans_ref_logging']);
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
    }

    public function testTransactionReferenceLoggingCanBeTurnedOn(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(true);

        $this->assertSame('1', $GLOBALS['__fa_company_prefs']['bank_import_trans_ref_logging']);
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
    }

    public function testTheTransactionReferenceAccountFallsBackToTheDefault(): void
    {
        $this->assertSame('0000', BankImportConfig::getTransRefAccount());
    }

    public function testTheTransactionReferenceAccountIsReadFromThePreferences(): void
    {
        $GLOBALS['__fa_company_prefs']['bank_import_trans_ref_account'] = '2100';

        $this->assertSame('2100', BankImportConfig::getTransRefAccount());
    }

    public function testAnUnknownTransactionReferenceAccountIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("GL account '9999' does not exist");

        BankImportConfig::setTransRefAccount('9999');
    }

    public function testGetAllSettingsReturnsBothSettings(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(false);
        $GLOBALS['__fa_company_prefs']['bank_import_trans_ref_account'] = '2100';

        $this->assertSame(
            [
                'trans_ref_logging_enabled' => false,
                'trans_ref_account' => '2100',
                'scoring_weights' => ['recency' => 1.0, 'amount' => 1.0, 'type' => 1.0],
            ],
            BankImportConfig::getAllSettings()
        );
    }

    public function testResetToDefaultsRestoresTheDefaults(): void
    {
        BankImportConfig::setTransRefLoggingEnabled(false);
        $GLOBALS['__fa_company_prefs']['bank_import_trans_ref_account'] = '2100';

        BankImportConfig::resetToDefaults();

        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertSame('0000', BankImportConfig::getTransRefAccount());
    }

    public function testOutsideOfFrontAccountingAnyAccountIsAccepted(): void
    {
        $process = (new IsolatedProcess())->run('bank_import_config');

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('IS_FA_RUNTIME=0', $process->getStdout());
        $this->assertStringContainsString('ACCOUNT=1234', $process->getStdout());
        $this->assertStringContainsString('LOGGING_ENABLED=0', $process->getStdout());
        $this->assertStringContainsString('BANK_IMPORT_CONFIG_COMPLETED', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
