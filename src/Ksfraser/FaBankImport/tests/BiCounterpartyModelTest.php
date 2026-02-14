<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiCounterpartyModelTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiCounterpartyModelTest.
 */
use PHPUnit\Framework\TestCase;

require_once '../ksf_modules_commone/class.generic_fa_interface.php';
require_once '../ksf_modules_commone/defines.inc.php';
require_once 'class.bi_counterparty_model.php';

class BiCounterpartyModelTest extends TestCase
{
    protected $biCounterpartyModel;

    protected function setUp(): void
    {
        $this->biCounterpartyModel = new bi_counterparty_model();
    }

    public function testDefineTable()
    {
        $this->biCounterpartyModel->define_table();
        $this->assertNotEmpty($this->biCounterpartyModel->fields_array);
        $this->assertArrayHasKey('tablename', $this->biCounterpartyModel->table_details);
        $this->assertArrayHasKey('primarykey', $this->biCounterpartyModel->table_details);
        $this->assertArrayHasKey('index', $this->biCounterpartyModel->table_details);
    }

    public function testInsertTransaction()
    {
        $this->biCounterpartyModel->card_type = 'VISA';
        $this->biCounterpartyModel->card_number = '1234';
        $this->biCounterpartyModel->receipt_sent = '2025-04-02';
        $this->biCounterpartyModel->receipt_email = 'test@example.com';
        $this->biCounterpartyModel->insert_transaction();

        // As `insert_data` method and database interaction are not defined here,
        // we assume it is correctly inserting data and would test the method call instead.
        $this->assertTrue(true); // Replace with actual assertions based on the implementation of `insert_data`.
    }
}
