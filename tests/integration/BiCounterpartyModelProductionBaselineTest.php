<?php
namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * PRODUCTION BASELINE TEST for class.bi_counterparty_model.php
 *
 * Originally documented the PROD behavior of bi_counterparty_model via
 * source-text pins. CONVERTED (refactor-psr): now verifies BEHAVIOR at runtime —
 *
 * - Class loads (under KSF_TEST_COMPAT) and extends generic_fa_interface_model
 * - Counterparty data properties are declared on the class
 * - Accessors round-trip values
 * - Pure data model: no business-logic methods declared
 *
 * PROD BEHAVIOR (documented here):
 * - Stores counterparty data (card info, receipt details, contact info)
 * - Handles Dream Payments, Square, Paypal, etc. data
 * - Pure data model, no business logic
 */
class BiCounterpartyModelProductionBaselineTest extends TestCase
{
    /**
     * Load the model under test compat mode.
     */
    private function loadModel(): void
    {
        $modelFile = __DIR__ . '/../../class.bi_counterparty_model.php';
        $this->assertFileExists($modelFile, 'class.bi_counterparty_model.php should exist');
        if (!class_exists('bi_counterparty_model')) {
            require_once $modelFile;
        }
    }

    /**
     * Test that bi_counterparty_model loads and extends the legacy base model
     */
    public function testProdBaseline_ClassExistsAndExtendsBaseModel()
    {
        $this->loadModel();

        $this->assertTrue(class_exists('bi_counterparty_model'), 'bi_counterparty_model must load');
        $this->assertContains(
            'generic_fa_interface_model',
            class_parents('bi_counterparty_model'),
            'bi_counterparty_model should extend generic_fa_interface_model'
        );
    }

    /**
     * Test that the class declares counterparty-specific properties
     */
    public function testProdBaseline_HasCounterpartyProperties()
    {
        $this->loadModel();

        // Payment-provider specific fields
        $expectedProperties = [
            'card_type',
            'card_number',
            'receipt_sent',
            'receipt_email',
            'receipt_mobile_number'
        ];

        $ref = new \ReflectionClass('bi_counterparty_model');
        $declared = array_map(
            fn($p) => $p->getName(),
            $ref->getProperties()
        );

        foreach ($expectedProperties as $property) {
            $this->assertContains($property, $declared,
                "Property \${$property} should be declared for counterparty data");
        }
    }

    /**
     * Test that accessors round-trip counterparty data
     */
    public function testProdBaseline_AccessorRoundTrip()
    {
        $this->loadModel();
        $model = new \bi_counterparty_model();

        $model->set('card_type', 'VISA');
        $this->assertSame('VISA', $model->get('card_type'),
            'get()/set() must round-trip card_type');
    }

    /**
     * Test that class is a pure data model (no business logic entry points)
     */
    public function testProdBaseline_PureDataModel()
    {
        $this->loadModel();

        $ref = new \ReflectionClass('bi_counterparty_model');
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            $this->assertFalse(str_starts_with($name, 'process'),
                'Data model should not declare process*() methods');
            $this->assertFalse(str_starts_with($name, 'validate'),
                'Data model should not declare validate*() methods');
        }
        $this->assertTrue(true,
            'bi_counterparty_model is a pure data model: stores payment-processor '
            . '(Square/Paypal/Dream) counterparty details; business logic lives elsewhere.'
        );
    }
}
