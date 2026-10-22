<?php

declare(strict_types=1);

/**
 * Tests for the matched transaction handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Handlers\MatchedTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\PartnerTypes\MatchedPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * MatchedTransactionHandler coverage.
 */
final class MatchedTransactionHandlerTest extends ProcessingTestCase
{
    /** @var MatchedTransactionHandler */
    private $handler;

    /**
     * Build the handler under test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new MatchedTransactionHandler($this->referenceService());
    }

    /**
     * A complete transaction record.
     *
     * @return array
     */
    private function transaction(): array
    {
        return [
            'transactionDC' => 'C',
            'transactionAmount' => 40.00,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Matched transaction',
            'memo' => 'Memo',
        ];
    }

    /**
     * The handler is bound to the matched partner type.
     *
     * @return void
     */
    public function test_it_handles_the_matched_partner_type(): void
    {
        $this->assertSame('ZZ', $this->handler->getPartnerType());
        $this->assertInstanceOf(MatchedPartnerType::class, $this->handler->getPartnerTypeObject());
        $this->assertTrue($this->handler->canProcess('ZZ'));
        $this->assertFalse($this->handler->canProcess('MA'));
    }

    /**
     * A missing transaction number is reported.
     *
     * @return void
     */
    public function test_a_missing_transaction_number_is_reported(): void
    {
        $result = $this->handler->process($this->transaction(), [], 1, '', ['id' => 1]);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Matched transaction number is required', $result->getMessage());
    }

    /**
     * A missing transaction type is reported.
     *
     * @return void
     */
    public function test_a_missing_transaction_type_is_reported(): void
    {
        $result = $this->handler->process($this->transaction(), ['transNo' => 4], 1, '', ['id' => 1]);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Matched transaction type is required', $result->getMessage());
    }

    /**
     * A non positive transaction number is reported.
     *
     * @return void
     */
    public function test_a_non_positive_transaction_number_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 0, 'transType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid matched transaction number', $result->getMessage());
    }

    /**
     * A negative transaction type is reported.
     *
     * @return void
     */
    public function test_a_negative_transaction_type_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => -2],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid matched transaction type', $result->getMessage());
    }

    /**
     * A failing counterparty lookup is reported.
     *
     * @return void
     */
    public function test_a_failing_counterparty_lookup_is_reported(): void
    {
        FaStubRegistry::setException('get_trans_counterparty', new \Exception('lookup failed'));

        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to get counterparty information: lookup failed', $result->getMessage());
    }

    /**
     * An empty counterparty result is reported.
     *
     * @return void
     */
    public function test_an_empty_counterparty_result_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Could not find counterparty for matched entry', $result->getMessage());
    }

    /**
     * A failing transaction update is reported.
     *
     * @return void
     */
    public function test_a_failing_transaction_update_is_reported(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', [['person_id' => 2, 'person_type' => 'C', 'person_type_id' => 3]]);
        FaStubRegistry::setException('update_transactions', new \Exception('update failed'));

        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to update transaction: update failed', $result->getMessage());
    }

    /**
     * A failing partner data update does not stop the settlement.
     *
     * @return void
     */
    public function test_a_failing_partner_data_update_is_not_fatal(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', [['person_id' => 2, 'person_type' => 'C', 'person_type_id' => 3]]);
        FaStubRegistry::setException('set_partner_data', new \Exception('partner write failed'));

        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Transaction was MATCH settled', $result->getMessage());
    }

    /**
     * A matched transaction confirms the link to the existing entry.
     *
     * @return void
     */
    public function test_a_matched_transaction_confirms_the_link(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', [
            ['person_id' => 2, 'person_type' => 'C', 'person_type_id' => 3],
        ]);

        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => ST_CUSTPAYMENT, 'partnerId' => 8, 'memo' => 'From POST'],
            42,
            '7',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(4, $result->getTransNo());
        $this->assertSame(ST_CUSTPAYMENT, $result->getTransType());
        $this->assertSame('C', $result->getData('person_type'));
        $this->assertSame(3, $result->getData('person_type_id'));
        $this->assertStringContainsString('view_receipt.php', $result->getData('view_receipt_link'));

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(42, $update[0]);
        $this->assertSame(4, $update[3]);
        $this->assertSame(ST_CUSTPAYMENT, $update[4]);
        $this->assertTrue($update[5]);
        $this->assertFalse($update[6]);
        $this->assertSame('ZZ', $update[7]);
        $this->assertSame(8, $update[8]);

        $partner = FaStubRegistry::lastCall('set_partner_data');
        $this->assertSame('C', $partner[0]);
        $this->assertSame(ST_CUSTPAYMENT, $partner[1]);
        $this->assertSame(3, $partner[2]);
        $this->assertSame('From POST', $partner[3]);
    }

    /**
     * The transaction title is used as memo when no memo was posted.
     *
     * @return void
     */
    public function test_the_title_is_used_as_memo_when_no_memo_was_posted(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', [
            ['person_id' => 2, 'person_type' => 'C', 'person_type_id' => 3],
        ]);

        $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => 12, 'title' => 'Posted title'],
            1,
            '',
            ['id' => 1]
        );

        $partner = FaStubRegistry::lastCall('set_partner_data');
        $this->assertSame('Posted title', $partner[3]);
    }

    /**
     * The partner id is optional for a matched transaction.
     *
     * @return void
     */
    public function test_the_partner_id_is_optional(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', [
            ['person_id' => 2, 'person_type' => '', 'person_type_id' => 0],
        ]);

        $result = $this->handler->process(
            $this->transaction(),
            ['transNo' => 4, 'transType' => ST_SUPPAYMENT],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getData('view_receipt_link'));
        $this->assertSame(0, FaStubRegistry::callCount('set_partner_data'));

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(0, $update[8]);
    }
}
