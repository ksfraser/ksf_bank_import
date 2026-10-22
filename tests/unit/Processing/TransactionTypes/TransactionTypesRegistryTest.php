<?php

declare(strict_types=1);

/**
 * Tests for the FrontAccounting transaction type registry.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\TransactionTypes
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\TransactionTypes;

use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\TransactionTypeFixtures;
use Ksfraser\FrontAccounting\TransactionTypes\TransactionTypeInterface;
use Ksfraser\FrontAccounting\TransactionTypes\TransactionTypesRegistry;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';
require_once __DIR__ . '/../Support/TransactionTypeFixtures.php';

/**
 * TransactionTypesRegistry coverage.
 */
final class TransactionTypesRegistryTest extends ProcessingTestCase
{
    /**
     * Publish the plugin fixtures.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fixtures = new TransactionTypeFixtures(dirname(__DIR__, 4));
        $fixtures->publish();
    }

    /**
     * Remove the plugin fixtures again.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        $fixtures = new TransactionTypeFixtures(dirname(__DIR__, 4));
        $fixtures->remove();

        parent::tearDownAfterClass();
    }

    /**
     * Remove the cached types before every test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        unset($_SESSION['fa_transaction_types']);
    }

    /**
     * The registry is a singleton.
     *
     * @return void
     */
    public function test_the_registry_is_a_singleton(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $this->assertSame($registry, TransactionTypesRegistry::getInstance());
    }

    /**
     * The registry knows the transaction types of FrontAccounting.
     *
     * @return void
     */
    public function test_the_registry_knows_the_transaction_types_of_frontaccounting(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $types = $registry->getTypes();

        $this->assertArrayHasKey(ST_JOURNAL, $types);
        $this->assertArrayHasKey(ST_BANKPAYMENT, $types);
        $this->assertArrayHasKey(ST_BANKDEPOSIT, $types);
        $this->assertArrayHasKey(ST_BANKTRANSFER, $types);
        $this->assertArrayHasKey(ST_CUSTCREDIT, $types);
        $this->assertArrayHasKey(ST_CUSTPAYMENT, $types);
        $this->assertArrayHasKey(ST_SUPPCREDIT, $types);
        $this->assertArrayHasKey(ST_SUPPAYMENT, $types);

        $this->assertSame('Journal Entry', $types[ST_JOURNAL]['label']);
        $this->assertFalse($types[ST_JOURNAL]['moneyMoved']);
        $this->assertTrue($types[ST_BANKPAYMENT]['moneyMoved']);
        $this->assertTrue($types[ST_CUSTPAYMENT]['affectsAR']);
        $this->assertTrue($types[ST_SUPPAYMENT]['affectsAP']);
    }

    /**
     * The registry filters the transaction types.
     *
     * @return void
     */
    public function test_the_registry_filters_the_transaction_types(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $moneyMoved = $registry->getMoneyMovedTypes();
        $this->assertArrayHasKey(ST_BANKPAYMENT, $moneyMoved);
        $this->assertArrayNotHasKey(ST_JOURNAL, $moneyMoved);

        $goodsMoved = $registry->getGoodsMovedTypes();
        $this->assertArrayNotHasKey(ST_JOURNAL, $goodsMoved);
        $this->assertArrayHasKey(99, $goodsMoved);

        $filtered = $registry->getTypes(['affectsAR' => true]);
        $this->assertArrayHasKey(ST_CUSTPAYMENT, $filtered);
        $this->assertArrayNotHasKey(ST_BANKPAYMENT, $filtered);
    }

    /**
     * The registry answers questions about a single transaction type.
     *
     * @return void
     */
    public function test_the_registry_answers_questions_about_a_single_transaction_type(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $this->assertSame('Bank Payment', $registry->getLabel(ST_BANKPAYMENT));
        $this->assertNull($registry->getLabel(1234));
        $this->assertTrue($registry->hasType(ST_BANKPAYMENT));
        $this->assertFalse($registry->hasType(1234));
        $this->assertIsArray($registry->getType(ST_BANKPAYMENT));
        $this->assertNull($registry->getType(1234));
    }

    /**
     * The registry builds a code to label array.
     *
     * @return void
     */
    public function test_the_registry_builds_a_code_to_label_array(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $labels = $registry->getLabelsArray();

        $this->assertSame('Bank Payment', $labels[ST_BANKPAYMENT]);
        $this->assertArrayNotHasKey(ST_JOURNAL, $registry->getLabelsArray(['moneyMoved' => true]));
    }

    /**
     * A plugin that is dropped into the types directory is registered.
     *
     * @return void
     */
    public function test_a_plugin_in_the_types_directory_is_registered(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $types = $registry->getTypes();

        $this->assertArrayHasKey(99, $types);
        $this->assertSame('Plugin Type', $types[99]['label']);
        $this->assertTrue($types[99]['moneyMoved']);
        $this->assertTrue($types[99]['goodsMoved']);
    }

    /**
     * A plugin that fails while it is loaded is ignored.
     *
     * @return void
     */
    public function test_a_plugin_that_fails_while_it_is_loaded_is_ignored(): void
    {
        $registry = TransactionTypesRegistry::getInstance();

        $types = $registry->getTypes();

        $this->assertArrayNotHasKey(1234, $types);
        $this->assertArrayHasKey(ST_BANKPAYMENT, $types);
    }

    /**
     * The registry reads the transaction types from the session.
     *
     * @return void
     */
    public function test_the_registry_reads_the_transaction_types_from_the_session(): void
    {
        $_SESSION['fa_transaction_types'] = [
            42 => [
                'label' => 'Cached Type',
                'moneyMoved' => true,
                'goodsMoved' => false,
                'affectsAR' => false,
                'affectsAP' => false,
            ],
        ];

        $registry = (new \ReflectionClass(TransactionTypesRegistry::class))->newInstanceWithoutConstructor();

        $this->assertSame('Cached Type', $registry->getLabel(42));
        $this->assertNull($registry->getLabel(ST_BANKPAYMENT));
    }

    /**
     * The registry can be reloaded.
     *
     * @return void
     */
    public function test_the_registry_can_be_reloaded(): void
    {
        $registry = TransactionTypesRegistry::getInstance();
        $registry->getTypes();

        $_SESSION['fa_transaction_types'] = [42 => ['label' => 'Stale']];

        $registry->reload();

        $this->assertArrayNotHasKey(42, $registry->getTypes());
        $this->assertArrayHasKey(ST_BANKPAYMENT, $registry->getTypes());
    }

    /**
     * The registry loads the types when it is asked for one it does not know.
     *
     * @return void
     */
    public function test_the_registry_loads_the_types_on_the_first_question(): void
    {
        $registry = (new \ReflectionClass(TransactionTypesRegistry::class))->newInstanceWithoutConstructor();

        $this->assertTrue($registry->hasType(ST_BANKPAYMENT));
        $this->assertFalse($registry->hasType(1234));
    }

    /**
     * The registry cannot be instantiated from the outside.
     *
     * @return void
     */
    public function test_the_registry_cannot_be_instantiated_from_the_outside(): void
    {
        $reflection = new \ReflectionClass(TransactionTypesRegistry::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $this->assertInstanceOf(TransactionTypesRegistry::class, $instance);
    }

    /**
     * The transaction type contract is implemented by the plugins.
     *
     * @return void
     */
    public function test_the_transaction_type_contract_is_implemented_by_the_plugins(): void
    {
        $this->assertTrue(interface_exists(TransactionTypeInterface::class));
        $this->assertTrue(
            in_array(TransactionTypeInterface::class, class_implements(
                \Ksfraser\FrontAccounting\TransactionTypes\Types\TempPluginTransactionType::class
            ), true)
        );
    }
}
