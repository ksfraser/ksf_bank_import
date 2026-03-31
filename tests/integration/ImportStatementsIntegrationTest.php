<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\FileUploadService;
use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;
use Ksfraser\FaBankImport\Services\ParserRegistry;
use Ksfraser\Superglobals\FormSubmission;
use Ksfraser\FaBankImport\Request\ParserSelector;
use Ksfraser\Superglobals\PostParameterProvider;

/**
 * Integration tests for import_statements.php main functions
 *
 * Tests the core business logic of the import workflow:
 * - File upload processing
 * - Parser selection and validation
 * - Statement import logic
 * - State machine transitions
 * - Error handling
 */
class ImportStatementsIntegrationTest extends TestCase
{
    private $mockUploadService;
    private $mockConfigRepo;
    private $mockParserRegistry;
    private $mockFormSubmission;
    private $mockParameterProvider;
    private $mockParserSelector;

    protected function setUp(): void
    {
        // Skip ImportStatements integration tests - legacy entry point
        // These tests validate legacy import_statements.php workflow
        // Phase 0 replaces with UploadFormHandler and ProcessTransactionCommandHandler
        // TODO: Create proper integration tests for Phase 0 handlers
        $this->markTestSkipped(
            'ImportStatements integration tests disabled. '
            . 'Tests legacy import_statements.php entry point replaced by Phase 0. '
            . 'Phase 0 uses handler-based workflow with proper dependency injection. '
            . 'See: src/Ksfraser/FaBankImport/Handlers/ for Phase 0 implementation. '
            . 'See: tests/integration/ImportStatementsIntegrationTest.php'
        );
    }

    /**
     * Test do_upload_form renders upload form correctly
     */
    public function testDoUploadFormRendersCorrectly(): void
    {
        // Mock parser registry to return sample parsers
        $mockParsers = [
            'qfx' => ['name' => 'QFX Parser', 'select' => []],
            'ofx' => ['name' => 'OFX Parser', 'select' => []]
        ];
        $this->mockParserRegistry->method('getParsersArray')->willReturn($mockParsers);
        $this->mockParserSelector->method('getSelectedParser')->willReturn('qfx');

        // Capture output
        ob_start();
        do_upload_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('Upload Bank Statement', $output);
        $this->assertStringContainsString('QFX Parser', $output);
        $this->assertStringContainsString('OFX Parser', $output);
        $this->assertStringContainsString('selected', $output); // Selected parser
    }

    /**
     * Test do_upload_form with error message
     */
    public function testDoUploadFormWithError(): void
    {
        $this->mockParserRegistry->method('getParsersArray')->willReturn([]);
        $this->mockParserSelector->method('getSelectedParser')->willReturn('');

        ob_start();
        do_upload_form('Test error message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test error message', $output);
        $this->assertStringContainsString('error', $output);
    }

    /**
     * Test importStatement with new statement
     */
    public function testImportStatementNewStatement(): void
    {
        // Mock statement data
        $statement = new \stdClass();
        $statement->bank = 'Test Bank';
        $statement->statementId = 'TEST-001';
        $statement->transactions = [];

        // Mock bi_statements_model
        $mockStatementsModel = $this->createMock(\bi_statements_model::class);
        $mockStatementsModel->method('statement_exists')->willReturn(false);
        $mockStatementsModel->method('set');
        $mockStatementsModel->method('get')->willReturn(123);

        // We can't easily mock the global functions, so we'll test the logic conceptually
        // In a real integration test, we'd need to set up the database

        $this->markTestIncomplete('Integration test requires database setup');
    }

    /**
     * Test parse_uploaded_files validates file uploads
     */
    public function testParseUploadedFilesValidatesUploads(): void
    {
        // Mock $_FILES
        $_FILES = [
            'files' => [
                'name' => ['test.qfx'],
                'type' => ['application/octet-stream'],
                'tmp_name' => ['/tmp/test'],
                'error' => [UPLOAD_ERR_OK],
                'size' => [1024]
            ]
        ];

        // Mock form submission
        $this->mockFormSubmission->method('get')->willReturnMap([
            ['parser', 'qfx'],
            ['bankAccount', '123']
        ]);

        // Mock parser registry
        $this->mockParserRegistry->method('getAvailableParsers')->willReturn([
            'qfx' => ['name' => 'QFX Parser']
        ]);

        // This would require significant mocking of FA functions
        $this->markTestIncomplete('Full integration test requires extensive FA mocking');
    }

    /**
     * Test fa_bank_account_number_exists function
     */
    public function testFaBankAccountNumberExists(): void
    {
        // Mock the bi_bank_accounts_model
        $mockModel = $this->createMock(\bi_bank_accounts_model::class);
        $mockModel->method('fa_get_bank_account_id_by_number')
                  ->willReturn(123);

        // Test with existing account
        $result = fa_bank_account_number_exists('123456789');
        $this->assertTrue($result);

        // Test with non-existing account
        $result = fa_bank_account_number_exists('999999999');
        $this->assertFalse($result);
    }

    /**
     * Test fa_get_bank_account_id_by_number function
     */
    public function testFaGetBankAccountIdByNumber(): void
    {
        $result = fa_get_bank_account_id_by_number('123456789');
        $this->assertIsInt($result);

        $result = fa_get_bank_account_id_by_number('nonexistent');
        $this->assertNull($result);
    }

    /**
     * Test bi_bank_accounts_table_exists function
     */
    public function testBiBankAccountsTableExists(): void
    {
        $result = bi_bank_accounts_table_exists();
        $this->assertIsBool($result);
    }

    /**
     * Test collect_desired_bi_bank_accounts_rows processes statements correctly
     */
    public function testCollectDesiredBiBankAccountsRows(): void
    {
        $multistatements = [
            [
                (object)[
                    'acctid' => '12345',
                    'bankid' => 'BANK001',
                    'intu_bid' => 'INTU001',
                    'curdef' => 'USD',
                    'accttype' => 'CHECKING'
                ]
            ]
        ];

        $result = collect_desired_bi_bank_accounts_rows($multistatements);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('12345', $result);
        $this->assertEquals('12345', $result['12345']['acctid']);
        $this->assertEquals('BANK001', $result['12345']['bankid']);
    }

    /**
     * Test bi_bank_accounts_meta_differs detects changes
     */
    public function testBiBankAccountsMetaDiffers(): void
    {
        $existing = [
            'acctid' => '12345',
            'bankid' => 'BANK001',
            'intu_bid' => 'INTU001'
        ];

        $desired = [
            'acctid' => '12345',
            'bankid' => 'BANK002', // Changed
            'intu_bid' => 'INTU001'
        ];

        $result = bi_bank_accounts_meta_differs($existing, $desired);
        $this->assertTrue($result);

        // Test no difference
        $desired['bankid'] = 'BANK001';
        $result = bi_bank_accounts_meta_differs($existing, $desired);
        $this->assertFalse($result);
    }

    /**
     * Test render_parser_management generates HTML
     */
    public function testRenderParserManagement(): void
    {
        $this->mockParserRegistry->method('getActiveParsers')->willReturn(['qfx']);
        $this->mockParserRegistry->method('getInactiveParsers')->willReturn(['ofx']);
        $this->mockParserRegistry->method('getNewParsers')->willReturn(['csv']);
        $this->mockParserRegistry->method('getDiscoveredParsers')->willReturn([]);

        ob_start();
        render_parser_management();
        $output = ob_get_clean();

        $this->assertStringContainsString('Parser Management', $output);
        $this->assertStringContainsString('Active Parsers', $output);
        $this->assertStringContainsString('Inactive Parsers', $output);
        $this->assertStringContainsString('New Parsers', $output);
        $this->assertStringContainsString('qfx', $output);
        $this->assertStringContainsString('ofx', $output);
        $this->assertStringContainsString('csv', $output);
    }

    /**
     * Test handle_parser_management processes actions
     */
    public function testHandleParserManagement(): void
    {
        // Mock form submission for activate action
        $this->mockFormSubmission->method('get')->willReturnMap([
            ['activate_parser', 'qfx']
        ]);

        $this->mockConfigRepo->expects($this->once())
                            ->method('setParserActive')
                            ->with('qfx', true);

        // This requires global function mocking which is complex
        $this->markTestIncomplete('Requires extensive global function mocking');
    }

    /**
     * Test bank_import_get_logger creates logger
     */
    public function testBankImportGetLogger(): void
    {
        // Clear any existing session logger
        unset($_SESSION['bank_import_run_log_path']);

        $logger = bank_import_get_logger();
        $this->assertNull($logger); // Should be null when no path set

        // Set a mock path
        $_SESSION['bank_import_run_log_path'] = '/tmp/test.log';
        $logger = bank_import_get_logger();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Service\ImportRunLogger::class, $logger);
    }

    /**
     * Test bank_import_log_event calls logger methods
     */
    public function testBankImportLogEvent(): void
    {
        $mockLogger = $this->createMock(\Ksfraser\FaBankImport\Service\ImportRunLogger::class);
        $mockLogger->expects($this->once())
                  ->method('event')
                  ->with('test.event', ['key' => 'value']);

        bank_import_log_event($mockLogger, 'test.event', ['key' => 'value']);
    }

    /**
     * Test bank_import_log_event handles null logger
     */
    public function testBankImportLogEventWithNullLogger(): void
    {
        // Should not throw exception
        bank_import_log_event(null, 'test.event', ['key' => 'value']);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }
}