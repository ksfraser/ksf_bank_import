<?php

use PHPUnit\Framework\TestCase;



class BiTransactionTitleModelTest extends TestCase
{
    private $biTransactionTitleModel;

    protected function setUp(): void
    {
        $this->biTransactionTitleModel = new bi_transactionTitle_model();
    }

    public function testDefineTable()
    {
        $this->biTransactionTitleModel->define_table();
        
        // Assuming that fields_array and table_details are public for test purposes
        $fieldsArray = $this->biTransactionTitleModel->fields_array;
        $tableDetails = $this->biTransactionTitleModel->table_details;

        $this->assertNotEmpty($fieldsArray, 'Fields array should not be empty');
        $this->assertArrayHasKey('tablename', $tableDetails, 'Table details should have tablename');
        $this->assertArrayHasKey('primarykey', $tableDetails, 'Table details should have primarykey');
        $this->assertArrayHasKey('orderby', $tableDetails, 'Table details should have orderby');
    }

    public function testInsertTransaction()
    {
        // Mock insert_data method to test insert_transaction
        $biTransactionTitleModelMock = $this->getMockBuilder(bi_transactionTitle_model::class)
            ->onlyMethods(['insert_data'])
            ->getMock();

        $biTransactionTitleModelMock->expects($this->once())
            ->method('insert_data')
            ->with($this->isType('array'));

        $biTransactionTitleModelMock->insert_transaction();
    }
}
