<?php

declare(strict_types=1);

/**
 * Tests for the partner type value objects, the registry and the constants.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\PartnerTypes
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\PartnerTypes;

use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\PartnerTypeFixtures;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\TestablePartnerType;
use Ksfraser\PartnerTypeConstants;
use Ksfraser\PartnerTypes\BankTransferPartnerType;
use Ksfraser\PartnerTypes\CustomerPartnerType;
use Ksfraser\PartnerTypes\ManualSettlementPartnerType;
use Ksfraser\PartnerTypes\MatchedPartnerType;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\PartnerTypeRegistry;
use Ksfraser\PartnerTypes\QuickEntryPartnerType;
use Ksfraser\PartnerTypes\SupplierPartnerType;
use Ksfraser\PartnerTypes\UnknownPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';
require_once __DIR__ . '/../Support/TestablePartnerType.php';
require_once __DIR__ . '/../Support/PartnerTypeFixtures.php';

/**
 * PartnerTypes coverage.
 */
final class PartnerTypesTest extends ProcessingTestCase
{
    /**
     * Publish the partner type registry fixtures.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fixtures = new PartnerTypeFixtures(dirname(__DIR__, 4));
        $fixtures->publish();
    }

    /**
     * Remove the partner type registry fixtures again.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        $fixtures = new PartnerTypeFixtures(dirname(__DIR__, 4));
        $fixtures->remove();

        parent::tearDownAfterClass();
    }

    /**
     * Start every test with a fresh registry.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        PartnerTypeRegistry::reset();
    }

    /**
     * Restore the registry after every test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        PartnerTypeRegistry::reset();

        parent::tearDown();
    }

    /**
     * The base class provides the default behaviour.
     *
     * @return void
     */
    public function test_the_base_class_provides_the_default_behaviour(): void
    {
        $type = new TestablePartnerType('SP', 'SUPPLIER');

        $this->assertSame(100, $type->getPriority());
        $this->assertTrue($type->isDispatchable());
        $this->assertNull($type->getDescription());
        $this->assertSame('SupplierPartnerTypeView', $type->getViewClassName());
        $this->assertSame('displaySupplier', $type->getStrategyMethodName());
        $this->assertSame('Testable', (string) $type);
        $this->assertInstanceOf(PartnerTypeInterface::class, $type);
    }

    /**
     * A short code that is not two characters long is rejected.
     *
     * @return void
     */
    public function test_a_short_code_of_the_wrong_length_is_rejected(): void
    {
        $type = new TestablePartnerType('S', 'SUPPLIER');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Short code must be exactly 2 characters');

        $type->callValidateShortCode();
    }

    /**
     * A lower case short code is rejected.
     *
     * @return void
     */
    public function test_a_lower_case_short_code_is_rejected(): void
    {
        $type = new TestablePartnerType('sp', 'SUPPLIER');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Short code must be uppercase');

        $type->callValidateShortCode();
    }

    /**
     * A valid short code passes the validation.
     *
     * @return void
     */
    public function test_a_valid_short_code_passes_the_validation(): void
    {
        $type = new TestablePartnerType('SP', 'SUPPLIER');

        $type->callValidateShortCode();

        $this->addToAssertionCount(1);
    }

    /**
     * Every concrete partner type carries its own metadata.
     *
     * @return void
     */
    public function test_every_concrete_partner_type_carries_its_metadata(): void
    {
        $supplier = new SupplierPartnerType();
        $this->assertSame('SP', $supplier->getShortCode());
        $this->assertSame('Supplier', $supplier->getLabel());
        $this->assertSame('SUPPLIER', $supplier->getConstantName());
        $this->assertSame(10, $supplier->getPriority());
        $this->assertSame('Vendor or supplier transactions (accounts payable)', $supplier->getDescription());
        $this->assertTrue($supplier->isDispatchable());

        $customer = new CustomerPartnerType();
        $this->assertSame('CU', $customer->getShortCode());
        $this->assertSame('Customer', $customer->getLabel());
        $this->assertSame('CUSTOMER', $customer->getConstantName());
        $this->assertSame(20, $customer->getPriority());
        $this->assertSame('Customer transactions (accounts receivable)', $customer->getDescription());

        $bankTransfer = new BankTransferPartnerType();
        $this->assertSame('BT', $bankTransfer->getShortCode());
        $this->assertSame('Bank Transfer', $bankTransfer->getLabel());
        $this->assertSame('BANK_TRANSFER', $bankTransfer->getConstantName());
        $this->assertSame(30, $bankTransfer->getPriority());
        $this->assertSame('Transfers between bank accounts', $bankTransfer->getDescription());

        $quickEntry = new QuickEntryPartnerType();
        $this->assertSame('QE', $quickEntry->getShortCode());
        $this->assertSame('Quick Entry', $quickEntry->getLabel());
        $this->assertSame('QUICK_ENTRY', $quickEntry->getConstantName());
        $this->assertSame(40, $quickEntry->getPriority());
        $this->assertSame('Quick entry journal transactions', $quickEntry->getDescription());

        $manual = new ManualSettlementPartnerType();
        $this->assertSame('MA', $manual->getShortCode());
        $this->assertSame('Manual settlement', $manual->getLabel());
        $this->assertSame('MANUAL_SETTLEMENT', $manual->getConstantName());
        $this->assertSame(50, $manual->getPriority());
        $this->assertSame('Manually match to existing GL entries', $manual->getDescription());
        $this->assertSame('displayManualSettlement', $manual->getStrategyMethodName());

        $matched = new MatchedPartnerType();
        $this->assertSame('ZZ', $matched->getShortCode());
        $this->assertSame('Matched', $matched->getLabel());
        $this->assertSame('MATCHED', $matched->getConstantName());
        $this->assertSame(60, $matched->getPriority());
        $this->assertSame('Matched transactions', $matched->getDescription());
        $this->assertSame('displayMatchedExisting', $matched->getStrategyMethodName());

        $unknown = new UnknownPartnerType();
        $this->assertSame('UN', $unknown->getShortCode());
        $this->assertSame('Unknown', $unknown->getLabel());
        $this->assertSame('UNKNOWN', $unknown->getConstantName());
        $this->assertSame(999, $unknown->getPriority());
        $this->assertSame(
            'Unrecognized transactions requiring manual classification',
            $unknown->getDescription()
        );
        $this->assertFalse($unknown->isDispatchable());
    }

    /**
     * The registry discovers every partner type of the directory.
     *
     * @return void
     */
    public function test_the_registry_discovers_every_partner_type(): void
    {
        $registry = PartnerTypeRegistry::getInstance();

        $this->assertSame($registry, PartnerTypeRegistry::getInstance());

        $codes = $registry->getCodes();

        $this->assertSame(['SP', 'CU', 'BT', 'QE', 'MA', 'ZZ', 'UN'], $codes);
        $this->assertSame(7, $registry->count());
        $this->assertTrue($registry->isValid('SP'));
        $this->assertFalse($registry->isValid('XX'));
        $this->assertSame('Supplier', $registry->getLabel('SP'));
        $this->assertSame('Unknown', $registry->getLabel('XX'));
        $this->assertInstanceOf(SupplierPartnerType::class, $registry->getByCode('SP'));
        $this->assertNull($registry->getByCode('XX'));
        $this->assertInstanceOf(SupplierPartnerType::class, $registry->getByConstant('SUPPLIER'));
        $this->assertNull($registry->getByConstant('NOT_A_TYPE'));
        $this->assertCount(7, $registry->getAll());
    }

    /**
     * The registry refuses a partner type that is already registered.
     *
     * @return void
     */
    public function test_the_registry_refuses_a_duplicate_partner_type(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $registry->getAll();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner type with code "SP" is already registered');

        $registry->register(new SupplierPartnerType());
    }

    /**
     * A partner type can be registered by hand.
     *
     * @return void
     */
    public function test_a_partner_type_can_be_registered_by_hand(): void
    {
        $registry = PartnerTypeRegistry::getInstance();

        $registry->register(new TestablePartnerType('XX', 'TEST_TYPE'));

        $this->assertTrue($registry->isValid('XX'));
        $this->assertInstanceOf(TestablePartnerType::class, $registry->getByCode('XX'));
        $this->assertInstanceOf(TestablePartnerType::class, $registry->getByConstant('TEST_TYPE'));
    }

    /**
     * The registry can be reset.
     *
     * @return void
     */
    public function test_the_registry_can_be_reset(): void
    {
        $first = PartnerTypeRegistry::getInstance();

        PartnerTypeRegistry::reset();

        $this->assertNotSame($first, PartnerTypeRegistry::getInstance());
    }

    /**
     * The constants facade exposes the dispatchable partner types.
     *
     * @return void
     */
    public function test_the_constants_facade_exposes_the_dispatchable_partner_types(): void
    {
        $all = PartnerTypeConstants::getAll();

        $this->assertSame([
            'SP' => 'Supplier',
            'CU' => 'Customer',
            'BT' => 'Bank Transfer',
            'QE' => 'Quick Entry',
            'MA' => 'Manual settlement',
            'ZZ' => 'Matched',
        ], $all);

        $this->assertTrue(PartnerTypeConstants::isValid('SP'));
        $this->assertFalse(PartnerTypeConstants::isValid('XX'));
        $this->assertSame('Supplier', PartnerTypeConstants::getLabel('SP'));
        $this->assertSame('Unknown', PartnerTypeConstants::getLabel('XX'));
        $this->assertInstanceOf(PartnerTypeRegistry::class, PartnerTypeConstants::getRegistry());
        $this->assertSame('SP', PartnerTypeConstants::getCodeByConstant('SUPPLIER'));
    }

    /**
     * An unknown constant name is refused.
     *
     * @return void
     */
    public function test_an_unknown_constant_name_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner type constant "NOT_A_TYPE" not found');

        PartnerTypeConstants::getCodeByConstant('NOT_A_TYPE');
    }

    /**
     * The constants facade cannot be instantiated.
     *
     * @return void
     */
    public function test_the_constants_facade_cannot_be_instantiated(): void
    {
        $reflection = new \ReflectionClass(PartnerTypeConstants::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $this->assertInstanceOf(PartnerTypeConstants::class, $instance);
    }

    /**
     * The registry cannot be instantiated from the outside.
     *
     * @return void
     */
    public function test_the_registry_cannot_be_instantiated_from_the_outside(): void
    {
        $reflection = new \ReflectionClass(PartnerTypeRegistry::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $this->assertInstanceOf(PartnerTypeRegistry::class, $instance);
    }
}
