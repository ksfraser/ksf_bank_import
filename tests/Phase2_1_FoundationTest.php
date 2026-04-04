<?php

namespace Tests\Ksfraser\FaBankImport\Import;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\DTOs\{
    ImportProgressDTO,
    ImportSessionDTO,
    ParsedStatementDTO,
    ValidationResultDTO
};
use Ksfraser\FaBankImport\Import\Exceptions\{
    ImportException,
    DuplicateDetectedException,
    ImportCancelledException
};
use Ksfraser\Exceptions\Utility\{
    ParserException,
    ValidationException,
    TransformException
};
use Ksfraser\FaBankImport\Import\Handlers\BaseImportHandler;
use Ksfraser\FaBankImport\Import\Services\{
    ValidatorInterface,
    TransformerInterface,
    DuplicateDetectorInterface,
    ParserInterface,
    ParserFactoryInterface,
    OrchestratorInterface,
    ReviewStagerInterface
};

/**
 * Foundation tests for Phase 2.1 import pipeline
 *
 * Validates:
 * - DTOs can be instantiated and manipulated
 * - Exception hierarchy is correct
 * - Handler base class functionality works
 * - Service interfaces are properly defined
 */
class Phase2_1_FoundationTest extends TestCase
{
    // ==================== ParsedStatementDTO Tests ====================

    /**
     * @test
     */
    public function parsed_statement_dto_can_be_created(): void
    {
        $dto = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'CA001',
            'currency' => 'CAD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['amount' => 100.00, 'dc' => 'C', 'ref' => 'DEP001'],
                ['amount' => 50.00, 'dc' => 'D', 'ref' => 'CHK001'],
            ],
            'parserType' => 'csv',
            'metadata' => ['url' => 'http://bank.com'],
        ]);

        $this->assertEquals('2024-01-15', $dto->statementDate);
        $this->assertEquals('CA001', $dto->accountReference);
        $this->assertEquals('CAD', $dto->currency);
        $this->assertEquals(1000.00, $dto->openingBalance);
        $this->assertEquals(1500.00, $dto->closingBalance);
        $this->assertEquals(2, $dto->getTransactionCount());
        $this->assertEquals('csv', $dto->parserType);
    }

    /**
     * @test
     */
    public function parsed_statement_dto_throws_on_missing_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields');

        ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            // Missing other required fields
        ]);
    }

    /**
     * @test
     */
    public function parsed_statement_dto_calculates_debits_and_credits(): void
    {
        $dto = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'CA001',
            'currency' => 'CAD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['amount' => 100.00, 'dc' => 'C'],
                ['amount' => 50.00, 'dc' => 'C'],
                ['amount' => 30.00, 'dc' => 'D'],
            ],
            'parserType' => 'csv',
        ]);

        $this->assertEquals(150.00, $dto->getTotalCredits());
        $this->assertEquals(30.00, $dto->getTotalDebits());
        $this->assertEquals(120.00, $dto->getNetChange());
    }

    // ==================== ValidationResultDTO Tests ====================

    /**
     * @test
     */
    public function validation_result_dto_success(): void
    {
        $result = ValidationResultDTO::success();

        $this->assertTrue($result->isValid());
        $this->assertEquals(0, $result->getErrorCount());
        $this->assertEquals(0, $result->getWarningCount());
    }

    /**
     * @test
     */
    public function validation_result_dto_with_errors(): void
    {
        $result = ValidationResultDTO::failure(['Field missing']);

        $this->assertFalse($result->isValid());
        $this->assertEquals(1, $result->getErrorCount());
        $this->assertEquals('Field missing', $result->getErrors()[0]);
    }

    /**
     * @test
     */
    public function validation_result_dto_with_warnings(): void
    {
        $result = ValidationResultDTO::success(['Warning 1', 'Warning 2']);

        $this->assertTrue($result->isValid());
        $this->assertEquals(2, $result->getWarningCount());
    }

    /**
     * @test
     */
    public function validation_result_dto_with_rule_violations(): void
    {
        $result = ValidationResultDTO::failure(
            [],
            [],
            ['amount_range' => ['Too small'], 'date_validation' => ['Future date']]
        );

        $this->assertTrue($result->hasRuleViolation('amount_range'));
        $this->assertFalse($result->hasRuleViolation('unknown_rule'));
    }

    // ==================== ImportProgressDTO Tests ====================

    /**
     * @test
     */
    public function import_progress_dto_can_be_created(): void
    {
        $progress = ImportProgressDTO::create('SESSION-123', 100);

        $this->assertEquals('SESSION-123', $progress->sessionId);
        $this->assertEquals(100, $progress->totalItems);
        $this->assertEquals(0, $progress->processedItems);
        $this->assertEquals(0, $progress->getPercentComplete());
    }

    /**
     * @test
     */
    public function import_progress_dto_tracks_completion(): void
    {
        $progress = ImportProgressDTO::create('SESSION-123', 100);

        $progress = $progress->withItemProcessed(true);
        $this->assertEquals(1, $progress->processedItems);
        $this->assertEquals(1, $progress->successItems);

        $progress = $progress->withItemProcessed(false);
        $this->assertEquals(2, $progress->processedItems);
        $this->assertEquals(1, $progress->errorItems);

        $this->assertEquals(2, $progress->getPercentComplete());
    }

    /**
     * @test
     */
    public function import_progress_dto_formats_elapsed_time(): void
    {
        $progress = ImportProgressDTO::create('SESSION-123', 10);
        $formatted = $progress->getFormattedElapsed();

        $this->assertStringContainsString('s', $formatted); // Should have seconds
    }

    // ==================== ImportSessionDTO Tests ====================

    /**
     * @test
     */
    public function import_session_dto_can_be_created(): void
    {
        $session = ImportSessionDTO::create('SESSION-456', 42, 'statement.csv');

        $this->assertEquals('SESSION-456', $session->sessionId);
        $this->assertEquals(42, $session->uploadedFileId);
        $this->assertEquals('statement.csv', $session->fileName);
        $this->assertEquals('uploaded', $session->step);
        $this->assertEquals('in_progress', $session->status);
    }

    /**
     * @test
     */
    public function import_session_dto_can_transition_steps(): void
    {
        $session = ImportSessionDTO::create('SESSION-456', 42, 'statement.csv');
        $session = $session->withStep('parsing');

        $this->assertEquals('parsing', $session->step);
    }

    /**
     * @test
     */
    public function import_session_dto_can_store_parsed_data(): void
    {
        $session = ImportSessionDTO::create('SESSION-456', 42, 'statement.csv');
        $parsed = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'CA001',
            'currency' => 'CAD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [],
            'parserType' => 'csv',
        ]);

        $session = $session->withParsedData($parsed);

        $this->assertNotNull($session->parsedData);
        $this->assertEquals('2024-01-15', $session->parsedData->statementDate);
    }

    /**
     * @test
     */
    public function import_session_dto_can_store_validation_result(): void
    {
        $session = ImportSessionDTO::create('SESSION-456', 42, 'statement.csv');
        $validation = ValidationResultDTO::success();

        $session = $session->withValidationResult($validation);

        $this->assertNotNull($session->validationResult);
        $this->assertTrue($session->validationResult->isValid());
    }

    /**
     * @test
     */
    public function import_session_dto_tracks_age(): void
    {
        $session = ImportSessionDTO::create('SESSION-456', 42, 'statement.csv');
        $age = $session->getAgeSeconds();

        $this->assertGreaterThanOrEqual(0, $age);
        $this->assertLessThan(2, $age); // Should be nearly immediate
    }

    // ==================== Exception Hierarchy Tests ====================

    /**
     * @test
     */
    public function exception_hierarchy_is_correct(): void
    {
        $base = new ImportException('Base error');
        $parser = ParserException::fileNotFound('/tmp/file.csv');
        $validation = ValidationException::error('Invalid data');
        $duplicate = DuplicateDetectedException::exactDuplicate(1, [], []);
        $transform = TransformException::entityCreationFailed('BiStatement', 'Missing field');
        $cancelled = ImportCancelledException::byUser('User cancelled import');

        // All extend Exception
        $this->assertInstanceOf(\Exception::class, $base);
        $this->assertInstanceOf(\Exception::class, $parser);
        $this->assertInstanceOf(\Exception::class, $validation);
        $this->assertInstanceOf(\Exception::class, $duplicate);
        $this->assertInstanceOf(\Exception::class, $transform);
        $this->assertInstanceOf(\Exception::class, $cancelled);

        // Import-specific ones extend ImportException
        $this->assertInstanceOf(ImportException::class, $base);
        $this->assertInstanceOf(ImportException::class, $duplicate);
        $this->assertInstanceOf(ImportException::class, $cancelled);

        // Generic utility exceptions extend RuntimeException (not ImportException)
        $this->assertInstanceOf(\RuntimeException::class, $parser);
        $this->assertInstanceOf(\RuntimeException::class, $validation);
        $this->assertInstanceOf(\RuntimeException::class, $transform);
    }

    /**
     * @test
     */
    public function parser_exception_factory_methods_work(): void
    {
        $e1 = ParserException::unsupportedFileType('pdf', ['csv', 'xls']);
        $e2 = ParserException::parsingFailed('Invalid format', 42);
        $e3 = ParserException::fileNotFound('/tmp/missing.csv');
        $e4 = ParserException::encodingMismatch('UTF-16', 'UTF-8');

        $this->assertInstanceOf(ParserException::class, $e1);
        $this->assertInstanceOf(ParserException::class, $e2);
        $this->assertInstanceOf(ParserException::class, $e3);
        $this->assertInstanceOf(ParserException::class, $e4);
    }

    /**
     * @test
     */
    public function validation_exception_has_error_collection(): void
    {
        $e = ValidationException::missingFields(['field1', 'field2']);

        $this->assertEquals(2, $e->getErrorCount());
        $this->assertGreaterThan(0, count($e->getErrors()));
    }

    /**
     * @test
     */
    public function duplicate_exception_stores_matches(): void
    {
        $matches = [
            ['id' => 1, 'amount' => 100],
            ['id' => 2, 'amount' => 100],
        ];

        $e = DuplicateDetectedException::probableDuplicate($matches);

        $this->assertEquals(2, $e->getMatchCount());
    }

    // ==================== BaseImportHandler Tests ====================

    /**
     * @test
     */
    public function base_handler_can_be_extended(): void
    {
        $handler = new ConcreteTestHandler();

        $this->assertEquals('ConcreteTestHandler', $handler->getName());
    }

    /**
     * @test
     */
    public function base_handler_supports_success_result(): void
    {
        $handler = new ConcreteTestHandler();
        $session = ImportSessionDTO::create('S1', 1, 'test.csv');

        $handler->handle($session);

        $this->assertTrue($handler->wasSuccessful());
        $this->assertTrue($handler->getResult()['success']);
    }

    /**
     * @test
     */
    public function base_handler_supports_failure_result(): void
    {
        $handler = new FailingTestHandler();
        $session = ImportSessionDTO::create('S1', 1, 'test.csv');

        $handler->handle($session);

        $this->assertFalse($handler->wasSuccessful());
        $this->assertFalse($handler->getResult()['success']);
    }

    /**
     * @test
     */
    public function base_handler_supports_rollback(): void
    {
        $handler = new RollbackTestHandler();
        $session = ImportSessionDTO::create('S1', 1, 'test.csv');

        $handler->handle($session);
        $errors = $handler->rollback();

        $this->assertEquals(0, count($errors)); // No errors in rollback
    }

    /**
     * @test
     */
    public function base_handler_validates_step(): void
    {
        $handler = new StepValidatingHandler();
        $session = ImportSessionDTO::create('S1', 1, 'test.csv');

        $this->expectException(ImportException::class);
        $handler->handle($session);
    }

    // ==================== Service Interface Tests ====================

    /**
     * @test
     */
    public function service_interfaces_exist(): void
    {
        // Verify all service interfaces are defined
        $this->assertTrue(interface_exists(ValidatorInterface::class));
        $this->assertTrue(interface_exists(TransformerInterface::class));
        $this->assertTrue(interface_exists(DuplicateDetectorInterface::class));
        $this->assertTrue(interface_exists(ParserInterface::class));
        $this->assertTrue(interface_exists(ParserFactoryInterface::class));
        $this->assertTrue(interface_exists(OrchestratorInterface::class));
        $this->assertTrue(interface_exists(ReviewStagerInterface::class));
    }

    /**
     * @test
     */
    public function service_interfaces_have_expected_methods(): void
    {
        // Validator
        $this->assertTrue(method_exists(ValidatorInterface::class, 'validate'));
        $this->assertTrue(method_exists(ValidatorInterface::class, 'getName'));

        // Transformer
        $this->assertTrue(method_exists(TransformerInterface::class, 'transform'));
        $this->assertTrue(method_exists(TransformerInterface::class, 'getName'));

        // DuplicateDetector
        $this->assertTrue(method_exists(DuplicateDetectorInterface::class, 'detectDuplicates'));

        // Parser
        $this->assertTrue(method_exists(ParserInterface::class, 'parse'));
        $this->assertTrue(method_exists(ParserInterface::class, 'getSupportedTypes'));

        // Orchestrator
        $this->assertTrue(method_exists(OrchestratorInterface::class, 'executeImport'));
        $this->assertTrue(method_exists(OrchestratorInterface::class, 'cancelImport'));
    }
}

// ==================== Test Helper Classes ====================

class ConcreteTestHandler extends BaseImportHandler
{
    public function getName(): string
    {
        return 'ConcreteTestHandler';
    }

    public function handle(ImportSessionDTO $session): array
    {
        $this->success('Test handler succeeded', $session);
        return $this->getResult();
    }
}

class FailingTestHandler extends BaseImportHandler
{
    public function getName(): string
    {
        return 'FailingTestHandler';
    }

    public function handle(ImportSessionDTO $session): array
    {
        $this->failure('Test handler failed', $session, ['Error 1']);
        return $this->getResult();
    }
}

class RollbackTestHandler extends BaseImportHandler
{
    public function getName(): string
    {
        return 'RollbackTestHandler';
    }

    public function handle(ImportSessionDTO $session): array
    {
        $this->registerRollback(function () {
            // Do something
        });
        $this->success('Handler with rollback', $session);
        return $this->getResult();
    }
}

class StepValidatingHandler extends BaseImportHandler
{
    public function getName(): string
    {
        return 'StepValidatingHandler';
    }

    public function handle(ImportSessionDTO $session): array
    {
        $this->validateStep($session, 'parsing'); // Will throw since session is at 'uploaded'
        return [];
    }
}
