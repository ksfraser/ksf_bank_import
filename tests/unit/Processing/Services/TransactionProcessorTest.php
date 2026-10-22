<?php

declare(strict_types=1);

/**
 * Tests for the transaction processor.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Services
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Services;

use Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException;
use Ksfraser\FaBankImport\Handlers\CustomerTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Services\TransactionProcessor;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\HandlerDiscoveryFixtures;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\RecordingTransactionHandler;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';
require_once __DIR__ . '/../Support/RecordingTransactionHandler.php';
require_once __DIR__ . '/../Support/HandlerDiscoveryFixtures.php';

/**
 * TransactionProcessor coverage.
 */
final class TransactionProcessorTest extends ProcessingTestCase
{
    /** @var HandlerDiscoveryFixtures */
    private $fixtures;

    /**
     * Publish the handler discovery fixtures.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fixtures = new HandlerDiscoveryFixtures(dirname(__DIR__, 4));
        $fixtures->publish();
    }

    /**
     * Remove the handler discovery fixtures again.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        $fixtures = new HandlerDiscoveryFixtures(dirname(__DIR__, 4));
        $fixtures->remove();

        parent::tearDownAfterClass();
    }

    /**
     * Reset the failure switch between the tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = new HandlerDiscoveryFixtures(dirname(__DIR__, 4));

        unset($GLOBALS['ksf_processing_handler_failure']);
    }

    /**
     * The processor discovers the handlers that ship with the module.
     *
     * @return void
     */
    public function test_it_discovers_the_built_in_handlers(): void
    {
        $processor = new TransactionProcessor();

        $types = $processor->getRegisteredTypes();

        $this->assertContains('SP', $types);
        $this->assertContains('CU', $types);
        $this->assertContains('QE', $types);
        $this->assertContains('BT', $types);
        $this->assertContains('MA', $types);
        $this->assertContains('ZZ', $types);

        $this->assertTrue($processor->hasHandler('SP'));
        $this->assertFalse($processor->hasHandler('XX'));
        $this->assertInstanceOf(CustomerTransactionHandler::class, $processor->getHandler('CU'));
        $this->assertNull($processor->getHandler('XX'));
        $this->assertCount(count($types), $processor->getRegisteredHandlers());
    }

    /**
     * Handlers can be handed over to the constructor.
     *
     * @return void
     */
    public function test_it_accepts_handlers_through_the_constructor(): void
    {
        $handler = new RecordingTransactionHandler('XX');

        $processor = new TransactionProcessor([$handler]);

        $this->assertSame(['XX'], $processor->getRegisteredTypes());
        $this->assertSame($handler, $processor->getHandler('XX'));
    }

    /**
     * A handler can be registered after the processor was built.
     *
     * @return void
     */
    public function test_a_handler_can_be_registered_afterwards(): void
    {
        $processor = new TransactionProcessor([]);

        $returned = $processor->registerHandler(new RecordingTransactionHandler('YY'));

        $this->assertSame($processor, $returned);
        $this->assertTrue($processor->hasHandler('YY'));
    }

    /**
     * Processing an unknown partner type is refused.
     *
     * @return void
     */
    public function test_processing_an_unknown_partner_type_is_refused(): void
    {
        $processor = new TransactionProcessor([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for partner type: ZZ');

        $processor->process('ZZ', [], [], 1, '', []);
    }

    /**
     * A handler that refuses the partner type produces an error result.
     *
     * @return void
     */
    public function test_a_handler_that_refuses_the_partner_type_produces_an_error(): void
    {
        $processor = new TransactionProcessor([new RecordingTransactionHandler('XX', false)]);

        $result = $processor->process('XX', [], [], 1, '', []);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Handler cannot process partner type: XX', $result->getMessage());
    }

    /**
     * Only the post data of the processed transaction is handed to the handler.
     *
     * @return void
     */
    public function test_only_the_post_data_of_the_transaction_is_forwarded(): void
    {
        $handler = new RecordingTransactionHandler('XX');
        $processor = new TransactionProcessor([$handler]);

        $postData = [
            'partnerId_7' => 11,
            'Invoice_7' => 'INV-1',
            'comment_7' => 'Because',
            'partnerDetailId_7' => 3,
            'partnerId_8' => 22,
            'unrelated' => 'x',
        ];

        $transaction = ['id' => 7, 'transactionAmount' => 10.0];

        $result = $processor->process('XX', $transaction, $postData, 7, '1,2', ['id' => 1]);

        $this->assertTrue($result->isSuccess());

        $calls = $handler->calls();
        $this->assertCount(1, $calls);
        $this->assertSame([
            'partnerId' => 11,
            'invoice' => 'INV-1',
            'comment' => 'Because',
            'partnerDetailId' => 3,
        ], $calls[0]['transactionPostData']);
        $this->assertSame($transaction, $calls[0]['transaction']);
        $this->assertSame(7, $calls[0]['transactionId']);
        $this->assertSame('1,2', $calls[0]['collectionIds']);
        $this->assertSame(['id' => 1], $calls[0]['ourAccount']);
    }

    /**
     * The processor routes a transaction to the handler of its partner type.
     *
     * @return void
     */
    public function test_it_routes_a_transaction_to_the_matching_handler(): void
    {
        FaStubRegistry::setReturn('sumCharges', 0.0);
        FaStubRegistry::setReturn('my_write_customer_payment', 12);

        $processor = new TransactionProcessor();

        $result = $processor->process(
            'CU',
            [
                'id' => 5,
                'transactionDC' => 'C',
                'transactionAmount' => 10.0,
                'valueTimestamp' => '2025-10-20',
                'transactionTitle' => 'Invoice',
            ],
            ['partnerId_5' => 4],
            5,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(12, $result->getTransNo());
    }

    /**
     * A handler with a broken constructor is reported.
     *
     * @return void
     */
    public function test_a_handler_with_a_broken_constructor_is_reported(): void
    {
        $GLOBALS['ksf_processing_handler_failure'] = 'argument_count';

        $this->expectException(HandlerDiscoveryException::class);
        $this->expectExceptionMessage('Handler has invalid constructor (wrong number of arguments)');

        new TransactionProcessor();
    }

    /**
     * A handler with an incompatible constructor is reported.
     *
     * @return void
     */
    public function test_a_handler_with_an_incompatible_constructor_is_reported(): void
    {
        $GLOBALS['ksf_processing_handler_failure'] = 'type';

        $this->expectException(HandlerDiscoveryException::class);
        $this->expectExceptionMessage('Handler has invalid constructor (type mismatch)');

        new TransactionProcessor();
    }

    /**
     * A handler with a missing dependency is reported.
     *
     * @return void
     */
    public function test_a_handler_with_a_missing_dependency_is_reported(): void
    {
        $GLOBALS['ksf_processing_handler_failure'] = 'error_not_found';

        $this->expectException(HandlerDiscoveryException::class);
        $this->expectExceptionMessage("Handler missing dependency 'Missing\\Dependency'");

        new TransactionProcessor();
    }

    /**
     * An unexpected error during the discovery is reported.
     *
     * @return void
     */
    public function test_an_unexpected_error_during_the_discovery_is_reported(): void
    {
        $GLOBALS['ksf_processing_handler_failure'] = 'error';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected error discovering handler');

        new TransactionProcessor();
    }

    /**
     * An unexpected exception during the discovery is reported.
     *
     * @return void
     */
    public function test_an_unexpected_exception_during_the_discovery_is_reported(): void
    {
        $GLOBALS['ksf_processing_handler_failure'] = 'exception';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected exception discovering handler');

        new TransactionProcessor();
    }

    /**
     * A handler that reports a discovery error is skipped.
     *
     * @return void
     */
    public function test_a_handler_that_reports_a_discovery_error_is_skipped(): void
    {
        $GLOBALS['ksf_processing_handler_failure'] = 'discovery';

        $processor = new TransactionProcessor();

        $this->assertContains('SP', $processor->getRegisteredTypes());
    }

    /**
     * A handler file without a class is skipped.
     *
     * @return void
     */
    public function test_a_handler_file_without_a_class_is_skipped(): void
    {
        $processor = new TransactionProcessor();

        $this->assertContains('SP', $processor->getRegisteredTypes());
        $this->assertNotContains('TempMissing', $processor->getRegisteredTypes());
    }

    /**
     * An abstract handler is skipped.
     *
     * @return void
     */
    public function test_an_abstract_handler_is_skipped(): void
    {
        $processor = new TransactionProcessor();

        $this->assertContains('SP', $processor->getRegisteredTypes());
        $this->assertNotContains('TempAbstract', $processor->getRegisteredTypes());
    }

    /**
     * The registered handlers implement the handler contract.
     *
     * @return void
     */
    public function test_the_registered_handlers_implement_the_contract(): void
    {
        $processor = new TransactionProcessor();

        foreach ($processor->getRegisteredHandlers() as $handler) {
            $this->assertInstanceOf(TransactionHandlerInterface::class, $handler);
        }
    }
}
