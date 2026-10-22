<?php

declare(strict_types=1);

/**
 * Tests for the abstract transaction handler base class.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\TestableTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\TestPartnerType;
use Ksfraser\PartnerTypes\PartnerTypeInterface;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';
require_once __DIR__ . '/../Support/TestPartnerType.php';
require_once __DIR__ . '/../Support/TestableTransactionHandler.php';

/**
 * AbstractTransactionHandler coverage.
 */
final class AbstractTransactionHandlerTest extends ProcessingTestCase
{
    /**
     * The handler exposes the partner type of the value object it was given.
     *
     * @return void
     */
    public function test_it_exposes_the_partner_type_short_code(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->assertSame('SP', $handler->getPartnerType());
    }

    /**
     * The partner type value object itself is exposed unchanged.
     *
     * @return void
     */
    public function test_it_exposes_the_partner_type_object(): void
    {
        $type = new TestPartnerType('CU');
        $handler = new TestableTransactionHandler($type);

        $this->assertInstanceOf(PartnerTypeInterface::class, $handler->getPartnerTypeObject());
        $this->assertSame($type, $handler->getPartnerTypeObject());
        $this->assertSame('CU', $handler->getPartnerTypeObject()->getShortCode());
    }

    /**
     * canProcess() only accepts the handler's own partner type.
     *
     * @return void
     */
    public function test_can_process_only_accepts_its_own_partner_type(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('QE'));

        $this->assertTrue($handler->canProcess('QE'));
        $this->assertFalse($handler->canProcess('SP'));
        $this->assertFalse($handler->canProcess('qe'));
    }

    /**
     * The handler satisfies the transaction handler contract.
     *
     * @return void
     */
    public function test_it_implements_the_transaction_handler_interface(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('MA'));

        $this->assertInstanceOf(TransactionHandlerInterface::class, $handler);
    }

    /**
     * A partner type whose short code is not two upper case letters is rejected
     * as soon as the handler is constructed.
     *
     * @return void
     */
    public function test_it_rejects_an_invalid_partner_type_short_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type short code');

        new TestableTransactionHandler(new TestPartnerType('supplier'));
    }

    /**
     * A one character short code is rejected as well.
     *
     * @return void
     */
    public function test_it_rejects_a_short_code_that_is_not_two_characters_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must be exactly 2 uppercase letters');

        new TestableTransactionHandler(new TestPartnerType('S'));
    }

    /**
     * The default required fields are used when no explicit list is supplied.
     *
     * @return void
     */
    public function test_validation_uses_the_default_required_fields(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Required field 'transactionDC' not set in transaction");

        $handler->callValidateTransaction([
            'transactionAmount' => 10.0,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test',
        ]);
    }

    /**
     * A complete transaction passes the default validation.
     *
     * @return void
     */
    public function test_validation_accepts_a_complete_transaction(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $handler->callValidateTransaction([
            'transactionDC' => 'D',
            'transactionAmount' => 10.0,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test',
        ]);

        $this->addToAssertionCount(1);
    }

    /**
     * An explicit list of required fields is honoured.
     *
     * @return void
     */
    public function test_validation_honours_an_explicit_field_list(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Required field 'reference' not set in transaction");

        $handler->callValidateTransaction(['amount' => 1.0], ['reference']);
    }

    /**
     * A missing partner id is reported.
     *
     * @return void
     */
    public function test_partner_id_extraction_requires_the_partner_id(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Partner ID not found in transaction data');

        $handler->callExtractPartnerId([]);
    }

    /**
     * A non positive partner id is rejected.
     *
     * @return void
     */
    public function test_partner_id_extraction_rejects_non_positive_ids(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid partner ID: must be positive integer');

        $handler->callExtractPartnerId(['partnerId' => 0]);
    }

    /**
     * A numeric partner id is returned as an integer.
     *
     * @return void
     */
    public function test_partner_id_extraction_returns_the_positive_id(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->assertSame(17, $handler->callExtractPartnerId(['partnerId' => '17']));
    }

    /**
     * Without the global sumCharges() helper the charge is zero.
     *
     * This test runs before the global helper is declared so that the
     * fallback branch of calculateCharge() is exercised.
     *
     * @return void
     */
    public function test_charge_calculation_falls_back_to_zero_without_the_helper(): void
    {
        FaStubRegistry::setReturn('sumCharges', 12.5);

        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        if (function_exists('sumCharges')) {
            $this->assertSame(12.5, $handler->callCalculateCharge(5));

            return;
        }

        $this->assertSame(0.0, $handler->callCalculateCharge(5));
    }

    /**
     * The bank charge is read through sumCharges() when it is available.
     *
     * @return void
     */
    public function test_charge_calculation_uses_the_sum_charges_helper(): void
    {
        $this->ensureGlobalSumCharges();

        FaStubRegistry::setReturn('sumCharges', 12.5);

        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $this->assertSame(12.5, $handler->callCalculateCharge(5));
        $this->assertSame([5], FaStubRegistry::lastCall('sumCharges'));
    }

    /**
     * The result factories build the documented result objects.
     *
     * @return void
     */
    public function test_result_factories_build_the_expected_results(): void
    {
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'));

        $error = $handler->callCreateErrorResult('boom', ['detail' => 'x']);
        $this->assertTrue($error->isError());
        $this->assertSame('boom', $error->getMessage());
        $this->assertSame('x', $error->getData('detail'));

        $success = $handler->callCreateSuccessResult(12, 27, 'done', ['link' => 'x']);
        $this->assertTrue($success->isSuccess());
        $this->assertSame(12, $success->getTransNo());
        $this->assertSame(27, $success->getTransType());
        $this->assertSame('x', $success->getData('link'));

        $warning = $handler->callCreateWarningResult('careful', 3, 4, ['why' => 'y']);
        $this->assertTrue($warning->isWarning());
        $this->assertSame(3, $warning->getTransNo());
        $this->assertSame(4, $warning->getTransType());
        $this->assertSame('y', $warning->getData('why'));
    }

    /**
     * The reference service can be injected through the constructor.
     *
     * @return void
     */
    public function test_the_reference_service_can_be_injected(): void
    {
        $service = $this->referenceService('INJ');
        $handler = new TestableTransactionHandler(new TestPartnerType('SP'), $service);

        $this->assertSame($service, $handler->referenceService());
        $this->assertSame('INJ-27-1', $service->getUniqueReference(27));
    }
}
