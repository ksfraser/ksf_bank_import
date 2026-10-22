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
    private function writeStatementFile(string $name, string $fitid = '23095000001', bool $withBom = false): string
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
            '<ACCTID>1518404</ACCTID>',
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
}
