<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens;

use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenProcess;
use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenScaffold;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ScreenProcess.php';
require_once __DIR__ . '/Support/ScreenScaffold.php';

/**
 * Behaviour of the "Import Bank Statement" screen (import_statements.php).
 */
final class ImportStatementsScreenTest extends TestCase
{
    /**
     * @var ScreenScaffold
     */
    private $scaffold;

    /**
     * @var string
     */
    private $workDir;

    protected function setUp(): void
    {
        $this->scaffold = new ScreenScaffold('fa');
        $this->workDir = sys_get_temp_dir() . '/ksf_import_' . uniqid('', true);
        mkdir($this->workDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->scaffold->cleanup();
        foreach (glob($this->workDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->workDir);
    }

    /**
     * Runs the screen in a child process.
     *
     * @param array $config Scenario configuration.
     *
     * @return ScreenProcess
     */
    private function runScreen(array $config): ScreenProcess
    {
        $config['script'] = ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport/import_statements.php';

        $process = (new ScreenProcess())->run('screen', $this->scaffold->cwd(), $config);
        $process->mergeCoverage($this);

        return $process;
    }

    /**
     * Writes a minimal OFX/QFX statement file.
     *
     * The body is written across several lines on purpose: the OFX parser
     * only leaves a single line document alone, and a single line document
     * would be broken apart into invalid XML.
     *
     * @param string $name        File name.
     * @param string $fitid       The FITID of the single transaction.
     * @param bool   $withBom     Prefix the file with an UTF-8 byte order mark.
     *
     * @return string Absolute path of the written file.
     */
    private function writeStatementFile(string $name, string $fitid = '23095000001', bool $withBom = false, string $acctid = 'ACC-0001'): string
    {
        $header = [
            'OFXHEADER:100',
            'DATA:OFXSGML',
            'VERSION:102',
            'SECURITY:NONE',
            'ENCODING:USASCII',
            'CHARSET:1252',
            'COMPRESSION:NONE',
            'OLDFILEUID:NONE',
            'NEWFILEUID:NONE',
            '',
        ];

        $body = [
            '<OFX>',
            '<SIGNONMSGSRSV1>',
            '<SONRS>',
            '<STATUS>',
            '<CODE>0</CODE>',
            '<SEVERITY>INFO</SEVERITY>',
            '<MESSAGE>OK</MESSAGE>',
            '</STATUS>',
            '<DTSERVER>20240214135526</DTSERVER>',
            '<LANGUAGE>ENG</LANGUAGE>',
            '<INTU.BID>00034</INTU.BID>',
            '</SONRS>',
            '</SIGNONMSGSRSV1>',
            '<BANKMSGSRSV1>',
            '<STMTTRNRS>',
            '<TRNUID>0</TRNUID>',
            '<STATUS>',
            '<CODE>0</CODE>',
            '<SEVERITY>INFO</SEVERITY>',
            '<MESSAGE>OK</MESSAGE>',
            '</STATUS>',
            '<STMTRS>',
            '<CURDEF>CAD</CURDEF>',
            '<BANKACCTFROM>',
            '<BANKID>1</BANKID>',
            '<ACCTID>' . $acctid . '</ACCTID>',
            '<ACCTTYPE>CHECKING</ACCTTYPE>',
            '</BANKACCTFROM>',
            '<BANKTRANLIST>',
            '<DTSTART>20230401</DTSTART>',
            '<DTEND>20240214</DTEND>',
            '<STMTTRN>',
            '<TRNTYPE>CREDIT</TRNTYPE>',
            '<DTPOSTED>20230405082850</DTPOSTED>',
            '<TRNAMT>142.61</TRNAMT>',
            '<FITID>' . $fitid . '</FITID>',
            '<CHECKNUM>0</CHECKNUM>',
            '<NAME>GST CANADA</NAME>',
            '<MEMO>GST CANADA</MEMO>',
            '</STMTTRN>',
            '</BANKTRANLIST>',
            '<LEDGERBAL>',
            '<BALAMT>1587.35</BALAMT>',
            '<DTASOF>20240214</DTASOF>',
            '</LEDGERBAL>',
            '<AVAILBAL>',
            '<BALAMT>1587.35</BALAMT>',
            '<DTASOF>20240214</DTASOF>',
            '</AVAILBAL>',
            '</STMTRS>',
            '</STMTTRNRS>',
            '</BANKMSGSRSV1>',
            '</OFX>',
        ];

        $path = $this->workDir . '/' . $name;
        $content = implode("\n", array_merge($header, $body)) . "\n";
        if ($withBom) {
            $content = "\xEF\xBB\xBF" . $content;
        }
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Builds a serialised statement for the import step.
     *
     * @param string $statementId Statement id.
     * @param int    $transactions Number of transactions.
     *
     * @return string
     */
    private function serialisedStatements(string $statementId, int $transactions = 1): string
    {
        $statement = new \stdClass();
        $statement->statementId = $statementId;
        $statement->bank = 'Test Bank';
        $statement->transactions = [];

        for ($i = 0; $i < $transactions; $i++) {
            $transaction = new \stdClass();
            $transaction->transactionCode = 'CODE-' . $i;
            $transaction->accountName = 'ACCOUNT ' . $i;
            $statement->transactions[] = $transaction;
        }

        return serialize([$statement]);
    }

    public function testTheScreenOffersTheUploadForm(): void
    {
        $process = $this->runScreen([
            'post' => ['parser' => 'QFX'],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Import Bank Statement', $output);
        $this->assertStringContainsString('Select File(s) and type', $output);
        $this->assertStringContainsString('Format:', $output);
        $this->assertStringContainsString('QFX/OFX/Quickbooks (QBO) format', $output);
        $this->assertStringContainsString('name="parser"', $output);
        $this->assertStringContainsString('Select bank account', $output);
        $this->assertStringContainsString("type='file' name='files[]'", $output);
        $this->assertStringContainsString("name='upload'", $output);
    }

    public function testChangingTheParserRefreshesTheForm(): void
    {
        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', '_parser_update' => '1'],
        ]);

        $info = $process->getInfo();

        $this->assertSame(['doc_tbl'], $info['ajax'] ?? []);
    }

    public function testAnUploadedFileIsParsedAndCounted(): void
    {
        $file = $this->writeStatementFile('statement.qfx');

        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'upload' => 'Upload', 'bank_account' => 1],
            'files' => [
                ['name' => '', 'tmp_name' => 'never-read', 'error' => 0],
                ['name' => 'statement.qfx', 'tmp_name' => $file, 'error' => 0],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Valid statements   : 1', $output);
        $this->assertStringContainsString('Invalid statements : 0', $output);
        $this->assertStringContainsString('Total transactions : 1', $output);
        $this->assertStringContainsString('is valid, 1 transactions', $output);
        $this->assertStringContainsString("name='import'", $output, 'A valid statement can be imported.');

        $info = $process->getInfo();
        $this->assertNotEmpty($info['session']['statements'] ?? null, 'The parsed statements are kept in the session.');
    }

    public function testAStatementWithABrokenTransactionCannotBeImported(): void
    {
        $file = $this->writeStatementFile('broken.qfx', '');

        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'upload' => 'Upload', 'bank_account' => 1],
            'files' => [
                ['name' => 'broken.qfx', 'tmp_name' => $file, 'error' => 0],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Invalid statements : 1', $output);
        $this->assertStringContainsString('is invalid!!!!!!!!!', $output);
        $this->assertStringNotContainsString("name='import'", $output, 'An invalid statement is not offered for import.');

        $info = $process->getInfo();
        $this->assertArrayNotHasKey('statements', $info['session'] ?? []);
    }

    public function testAByteOrderMarkIsStrippedFromTheUploadedFile(): void
    {
        $file = $this->writeStatementFile('bom.qfx', '23095000001', true);

        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'upload' => 'Upload', 'bank_account' => 1],
            'files' => [
                ['name' => 'bom.qfx', 'tmp_name' => $file, 'error' => 0],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Valid statements   : 1', $output);
        $this->assertStringContainsString('Invalid statements : 0', $output);
    }

    public function testAFailingTransactionModelIsReported(): void
    {
        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'import' => 'Import'],
            'session' => ['statements' => $this->serialisedStatements('STMT-3', 1)],
            'transactions_model_throws' => true,
            'display_error_throws' => true,
            'define_tru' => true,
        ]);

        $info = $process->getInfo();

        $errors = $info['errors'] ?? [];
        $this->assertNotEmpty($errors, 'The failure of the transaction model is reported as an error.');
        $this->assertStringContainsString('the transaction model is not available', $errors[0]);

        $notifications = $info['notifications'] ?? [];
        $reported = false;
        foreach ($notifications as $notification) {
            if (strpos((string) $notification, 'display_error failed') !== false) {
                $reported = true;
            }
        }
        $this->assertTrue($reported, 'The outer error handler of the import loop reports the failure.');
    }

    public function testANewStatementIsImported(): void
    {
        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'import' => 'Import'],
            'session' => ['statements' => $this->serialisedStatements('STMT-1', 2)],
            'db_rows' => [
                ['rows' => [], 'match' => 'bi_statements'],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('importing statement STMT-1', $output);
        $this->assertStringContainsString('new, imported', $output);
        $this->assertStringContainsString('2 transactions', $output);

        $info = $process->getInfo();
        $queries = $info['queries'] ?? [];

        $statementInsert = '';
        $transactionInserts = 0;
        foreach ($queries as $query) {
            if (strpos($query, 'INSERT IGNORE INTO 0_bi_statements') !== false) {
                $statementInsert = $query;
            }
            if (strpos($query, 'INSERT IGNORE INTO 0_bi_transactions') !== false) {
                $transactionInserts++;
            }
        }

        $this->assertNotSame('', $statementInsert, 'The statement is written to the staging table.');
        $this->assertStringContainsString('Test Bank', $statementInsert);
        $this->assertSame(2, $transactionInserts, 'Every transaction of the statement is written.');
    }

    public function testAnExistingStatementIsUpdatedAndDuplicatesAreCounted(): void
    {
        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'import' => 'Import'],
            'session' => ['statements' => $this->serialisedStatements('STMT-2', 1)],
            'db_rows' => [
                ['rows' => [['id' => 7, 'bank' => 'Test Bank', 'statementId' => 'STMT-2']], 'match' => 'bi_statements'],
                ['rows' => [['id' => 3, 'transactionCode' => 'CODE-0']], 'match' => 'bi_transactions'],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('existing, updated', $output);
        $this->assertStringContainsString('1 transactions', $output);

        $info = $process->getInfo();
        $queries = $info['queries'] ?? [];

        $updated = false;
        $transactionInserts = 0;
        foreach ($queries as $query) {
            if (strpos($query, 'UPDATE 0_bi_statements') !== false) {
                $updated = true;
            }
            if (strpos($query, 'INSERT IGNORE INTO 0_bi_transactions') !== false) {
                $transactionInserts++;
            }
        }

        $this->assertTrue($updated, 'An already imported statement is updated instead of inserted.');
        $this->assertSame(0, $transactionInserts, 'A duplicate transaction is not inserted again.');
    }

    /**
     * Builds the pending-resolution session for the submit scenarios.
     *
     * @return string The serialised multistatements
     */
    private function pendingResolutionStatements(): string
    {
        $statement = new \stdClass();
        $statement->statementId = 'STMT-9';
        $statement->bank = 'Test Bank';
        $statement->acctid = '1518404';
        $statement->account = 'ACC-0001';
        $statement->transactions = [];

        return serialize([0 => [$statement]]);
    }

    /**
     * The POST body of one resolution row.
     *
     * @param string|null $accountId The FA bank account the user chose
     * @param bool        $remember  Whether the user flagged the mapping
     *
     * @return array
     */
    private function resolutionPost(?string $accountId, bool $remember = true): array
    {
        $fieldKey = \Ksfraser\FaBankImport\Views\AccountResolutionView::fieldKeyFor('1518404');
        $post = [
            'resolve_accounts' => '1',
            'detected_account' => [$fieldKey => '1518404', 'empty_field_key' => ''],
        ];
        if ($accountId !== null) {
            $post['resolved_bank_account'] = [$fieldKey => $accountId];
        }
        if ($remember) {
            $post['remember_mapping'] = [$fieldKey => '1'];
        }

        return $post;
    }

    public function testAnUnmappedAccountRequiresResolutionBeforeTheImport(): void
    {
        $file = $this->writeStatementFile('statement.qfx', '23095000001', false, '1518404');

        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'upload' => 'Upload', 'bank_account' => 1],
            'files' => [
                ['name' => 'statement.qfx', 'tmp_name' => $file, 'error' => 0],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Bank Account Resolution Required', $output);
        $this->assertStringContainsString('name="resolved_bank_account[', $output);
        $this->assertStringContainsString('1518404', $output, 'The detected account is shown.');
        $this->assertStringNotContainsString("name='import'", $output, 'The import waits for the resolution.');

        $info = $process->getInfo();
        $this->assertArrayHasKey('bank_import_account_resolution', $info['session'] ?? []);
        $this->assertArrayNotHasKey('statements', $info['session'] ?? []);
    }

    public function testARememberedAssociationResolvesTheAccountAutomatically(): void
    {
        $file = $this->writeStatementFile('statement.qfx', '23095000001', false, '1518404');
        $savedKey = \Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey::forDetectedAccount('1518404');

        $process = $this->runScreen([
            'post' => ['parser' => 'QFX', 'upload' => 'Upload', 'bank_account' => 1],
            'files' => [
                ['name' => 'statement.qfx', 'tmp_name' => $file, 'error' => 0],
            ],
            'db_rows' => [
                ['rows' => [['config_key' => $savedKey, 'config_value' => '1', 'config_type' => 'string']], 'match' => 'SELECT config_key, config_value, config_type'],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString("name='import'", $output, 'A remembered association auto-resolves the account.');
        $this->assertStringNotContainsString('Bank Account Resolution Required', $output);

        $info = $process->getInfo();
        $this->assertNotEmpty($info['session']['statements'] ?? null);
    }

    public function testTheResolutionDecisionAppliesAndOffersTheImport(): void
    {
        $process = $this->runScreen([
            'post' => $this->resolutionPost('1'),
            'session' => [
                'bank_import_account_resolution' => $this->pendingResolutionStatements(),
                'wa_current_user' => ['name' => 'ksf-test-user'],
            ],
            'files' => [
                ['name' => '', 'tmp_name' => 'not-needed', 'error' => 0],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Resolved detected accounts successfully.', $output);
        $this->assertStringContainsString("name='import'", $output, 'After the resolution the import is offered.');

        $info = $process->getInfo();
        $this->assertNotEmpty($info['session']['statements'] ?? null, 'The resolved statements are kept for the import.');
        $this->assertArrayNotHasKey('bank_import_account_resolution', $info['session'] ?? [], 'The pending resolution is dropped.');

        $remembered = false;
        foreach ($info['queries'] ?? [] as $query) {
            if (strpos((string) $query, 'INSERT INTO 0_bi_config_history') !== false) {
                $remembered = true;
            }
        }
        $this->assertTrue($remembered, 'A remembered choice is stored in the audit trail.');
    }

    public function testTheResolutionDecisionWithoutSelectionIsRefused(): void
    {
        $process = $this->runScreen([
            'post' => $this->resolutionPost(null),
            'session' => ['bank_import_account_resolution' => $this->pendingResolutionStatements()],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Please select a FrontAccounting bank account for detected account', $output);
        $this->assertStringNotContainsString("name='import'", $output);

        $info = $process->getInfo();
        $this->assertArrayHasKey('bank_import_account_resolution', $info['session'] ?? [], 'The pending resolution waits for a retry.');
    }

    public function testTheResolutionDecisionWithAnUnknownAccountIsRefused(): void
    {
        $process = $this->runScreen([
            'post' => $this->resolutionPost('99'),
            'session' => ['bank_import_account_resolution' => $this->pendingResolutionStatements()],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Invalid bank account selection for detected account', $output);
        $this->assertStringNotContainsString("name='import'", $output);
    }

    public function testAnAbortedResolutionReturnsToTheUploadForm(): void
    {
        $post = $this->resolutionPost('1');
        $post['resolve_accounts'] = '1';
        unset($post['detected_account'], $post['resolved_bank_account'], $post['remember_mapping']);

        $process = $this->runScreen([
            'post' => ['cancel_account_resolution' => '1'],
            'session' => ['bank_import_account_resolution' => $this->pendingResolutionStatements()],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString("name='upload'", $output, 'The user is back on the upload form.');

        $info = $process->getInfo();
        $this->assertArrayNotHasKey('bank_import_account_resolution', $info['session'] ?? []);
    }

    public function testAResolutionSubmitWithoutAPendingSessionIsReported(): void
    {
        $post = $this->resolutionPost('1');
        unset($post['detected_account'], $post['resolved_bank_account'], $post['remember_mapping']);

        $process = $this->runScreen([
            'post' => ['resolve_accounts' => '1', 'detected_account' => ['bdfeb16caa61' => '1518404']],
        ]);

        $info = $process->getInfo();

        $errors = $info['errors'] ?? [];
        $this->assertNotEmpty($errors, 'The missing pending session is reported.');
        $this->assertStringContainsString('No pending account resolution session found', $errors[0]);
    }
}
