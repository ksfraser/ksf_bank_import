<?php

declare(strict_types=1);

/**
 * Tests for the manual settlement handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Handlers\ManualSettlementHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\PartnerTypes\ManualSettlementPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * ManualSettlementHandler coverage.
 */
final class ManualSettlementHandlerTest extends ProcessingTestCase
{
    /** @var ManualSettlementHandler */
    private $handler;

    /**
     * Build the handler under test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new ManualSettlementHandler($this->referenceService());
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
            'transactionAmount' => 75.00,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Manual settlement',
            'memo' => 'Settlement memo',
        ];
    }

    /**
     * The handler is bound to the manual settlement partner type.
     *
     * @return void
     */
    public function test_it_handles_the_manual_settlement_partner_type(): void
    {
        $this->assertSame('MA', $this->handler->getPartnerType());
        $this->assertInstanceOf(ManualSettlementPartnerType::class, $this->handler->getPartnerTypeObject());
        $this->assertTrue($this->handler->canProcess('MA'));
        $this->assertFalse($this->handler->canProcess('ZZ'));
    }

    /**
     * A missing existing entry number is reported.
     *
     * @return void
     */
    public function test_a_missing_existing_entry_is_reported(): void
    {
        $result = $this->handler->process($this->transaction(), [], 1, '', ['id' => 1]);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Existing Entry number is required', $result->getMessage());
    }

    /**
     * A missing existing entry type is reported.
     *
     * @return void
     */
    public function test_a_missing_existing_type_is_reported(): void
    {
        $result = $this->handler->process($this->transaction(), ['existingEntry' => 5], 1, '', ['id' => 1]);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Existing Entry type is required', $result->getMessage());
    }

    /**
     * A non positive existing entry number is reported.
     *
     * @return void
     */
    public function test_a_non_positive_existing_entry_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 0, 'existingType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid existing entry number', $result->getMessage());
    }

    /**
     * A negative existing entry type is reported.
     *
     * @return void
     */
    public function test_a_negative_existing_type_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => -1],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid existing entry type', $result->getMessage());
    }

    /**
     * A failing counterparty lookup is reported.
     *
     * @return void
     */
    public function test_a_failing_counterparty_lookup_is_reported(): void
    {
        FaStubRegistry::setException('get_trans_counterparty', new \Exception('database down'));

        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to get counterparty information: database down', $result->getMessage());
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
            ['existingEntry' => 5, 'existingType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Could not find counterparty for existing entry', $result->getMessage());
    }

    /**
     * A failing transaction update is reported.
     *
     * @return void
     */
    public function test_a_failing_transaction_update_is_reported(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', ['person_type' => 'C', 'person_type_id' => 3]);
        FaStubRegistry::setException('update_transactions', new \Exception('write failed'));

        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to update transaction: write failed', $result->getMessage());
    }

    /**
     * A failing partner data update does not stop the settlement.
     *
     * @return void
     */
    public function test_a_failing_partner_data_update_is_not_fatal(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', ['person_type' => 'C', 'person_type_id' => 3]);
        FaStubRegistry::setException('set_partner_data', new \Exception('partner write failed'));

        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Transaction manually settled against existing entry', $result->getMessage());
    }

    /**
     * A successful settlement links the bank transaction to the existing entry.
     *
     * @return void
     */
    public function test_a_successful_settlement_links_the_transaction(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', ['person_type' => 'C', 'person_type_id' => 3]);

        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => ST_CUSTPAYMENT],
            42,
            '7',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(5, $result->getTransNo());
        $this->assertSame(ST_CUSTPAYMENT, $result->getTransType());
        $this->assertSame(5, $result->getData('existing_entry'));
        $this->assertSame(ST_CUSTPAYMENT, $result->getData('existing_type'));
        $this->assertStringContainsString('gl_trans_view.php', $result->getData('view_gl_link'));
        $this->assertStringContainsString('view_receipt.php', $result->getData('view_receipt_link'));

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(42, $update[0]);
        $this->assertSame('7', $update[1]);
        $this->assertSame(1, $update[2]);
        $this->assertSame(5, $update[3]);
        $this->assertSame(ST_CUSTPAYMENT, $update[4]);
        $this->assertTrue($update[5]);
        $this->assertFalse($update[6]);
        $this->assertNull($update[7]);
        $this->assertSame('', $update[8]);

        $partner = FaStubRegistry::lastCall('set_partner_data');
        $this->assertSame('C', $partner[0]);
        $this->assertSame(ST_CUSTPAYMENT, $partner[1]);
        $this->assertSame(3, $partner[2]);
        $this->assertSame('Settlement memo', $partner[3]);
    }

    /**
     * A settlement against a non payment type has no receipt link.
     *
     * @return void
     */
    public function test_a_settlement_without_a_person_skips_the_partner_update(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', ['person_type' => '', 'person_type_id' => 0]);

        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => ST_SUPPAYMENT],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getData('view_receipt_link'));
        $this->assertSame(0, FaStubRegistry::callCount('set_partner_data'));
    }

    /**
     * The handler reads the counterparty as a single row.
     *
     * FrontAccounting returns one row per counterparty (the matched handler
     * iterates over the rows) while this handler indexes the result as if it
     * were one flat row. This test pins the current behaviour so the
     * difference between the two handlers is visible.
     *
     * @return void
     */
    public function test_a_row_shaped_counterparty_result_does_not_update_partner_data(): void
    {
        FaStubRegistry::setReturn('get_trans_counterparty', [
            ['person_type' => 'C', 'person_type_id' => 3],
        ]);

        $result = $this->handler->process(
            $this->transaction(),
            ['existingEntry' => 5, 'existingType' => 12],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(0, FaStubRegistry::callCount('set_partner_data'));
    }
}
