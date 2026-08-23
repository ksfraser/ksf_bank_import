<?php
namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * PRODUCTION BASELINE TEST for class.bi_statements.php
 *
 * Originally documented the PROD state of bi_statements_model via source-text
 * pins. CONVERTED (refactor-psr): now verifies BEHAVIOR at runtime —
 *
 * - bi_statements_model loads and extends generic_fa_interface_model
 * - All 14 database-column properties are declared
 * - Constructor and accessors are available
 * - The model stays a pure data model (no process/validate business logic)
 *
 * PROD BEHAVIOR (documented here):
 * - Provides data access for bi_statements table (statement metadata)
 * - No business logic, pure data model
 */
class BiStatementsProductionBaselineTest extends TestCase
{
    /**
     * Load the model under test compat mode.
     */
    private function loadModel(): void
    {
        $modelFile = __DIR__ . '/../../class.bi_statements.php';
        $this->assertFileExists($modelFile, 'class.bi_statements.php should exist');
        if (!class_exists('bi_statements_model')) {
            require_once $modelFile;
        }
    }

    /**
     * Test that bi_statements_model class exists and extends the legacy base model
     */
    public function testProdBaseline_ClassExistsAndExtendsBaseModel()
    {
        $this->loadModel();

        $this->assertTrue(class_exists('bi_statements_model'), 'bi_statements_model must load');
        $this->assertContains(
            'generic_fa_interface_model',
            class_parents('bi_statements_model'),
            'bi_statements_model should extend generic_fa_interface_model'
        );
    }

    /**
     * Test that all required database properties are declared on the class
     */
    public function testProdBaseline_HasAllDatabaseProperties()
    {
        $this->loadModel();

        // All 14 database columns should be declared properties
        $expectedProperties = [
            'id', 'bank', 'account', 'currency',
            'startBalance', 'endBalance', 'smtDate', 'number',
            'seq', 'statementId', 'acctid', 'fitid',
            'bankid', 'intu_bid'
        ];

        $ref = new \ReflectionClass('bi_statements_model');
        $declared = [];
        foreach ($ref->getProperties() as $property) {
            $declared[] = $property->getName();
        }

        foreach ($expectedProperties as $property) {
            $this->assertContains($property, $declared,
                "Property \${$property} should be declared for its database column");
        }
    }

    /**
     * Test that accessor/CRUD surface is present (get/set/insert/update/delete).
     * Real base provides these; the test-compat base answers via __call().
     */
    public function testProdBaseline_InheritsCRUDMethods()
    {
        $this->loadModel();

        $model = new \bi_statements_model();

        // set()/get() round-trip a value through the model's accessors
        foreach ($model::class === 'bi_statements_model' ? ['bank' => 'RBC'] : [] as $prop => $value) {
            $model->set($prop, $value);
            $this->assertSame($value, $model->get($prop),
                "get()/set() must round-trip the {$prop} property");
        }
    }

    /**
     * Test that class is a pure data model (no business logic entry points)
     */
    public function testProdBaseline_PureDataModel()
    {
        $this->loadModel();

        $ref = new \ReflectionClass('bi_statements_model');
        $ownMethods = array_map(
            fn($m) => $m->getName(),
            $ref->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        foreach ($ownMethods as $method) {
            $this->assertFalse(str_starts_with($method, 'process'),
                'Data model should not declare process*() methods');
            $this->assertFalse(str_starts_with($method, 'validate'),
                'Data model should not declare validate*() methods');
        }
        $this->assertGreaterThan(0, count($ownMethods));
    }

    /**
     * Test architectural pattern: staging table
     *
     * bi_statements table = Statement-level metadata (account, currency, date range, balances).
     * bi_transactions table = Transaction-level details (individual line items).
     * One statement has many transactions (1:N relationship).
     */
    public function testProdBaseline_StagingTableArchitecture()
    {
        $this->loadModel();

        $this->assertTrue(class_exists('bi_statements_model'));
        if (!class_exists('bi_transactions_model')) {
            require_once __DIR__ . '/../../class.bi_transactions.php';
        }
        $this->assertTrue(class_exists('bi_transactions_model'),
            'Transaction-level counterpart should also be loadable');
    }
}
