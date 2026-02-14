<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionTableTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionTableTest.
 */
use PHPUnit\Framework\TestCase;

class TransactionTableTest extends TestCase
{
    public function testConstruct()
    {
        // Create a mock for the db_fetch function
        $dbMock = $this->getMockBuilder(stdClass::class)
                       ->setMethods(['db_fetch'])
                       ->getMock();
        
        $dbMock->expects($this->any())
               ->method('db_fetch')
               ->will($this->returnValue([
                   'transactionDC' => 'C',
                   'our_account' => '1234',
                   'valueTimestamp' => '2025-04-01 12:00:00',
                   'accountName' => 'Test Account',
                   'transactionTitle' => 'Test Transaction',
                   'currency' => 'USD',
                   'status' => 1,
                   'id' => 1,
                   'fa_trans_type' => 'ST_SUPPAYMENT',
                   'fa_trans_no' => 123,
                   'transactionAmount' => 100.00
               ]));

        // Inject the mock into your transaction_table
        $transactions_table = new transaction_table($dbMock);
        
        // Assert that the transaction_table rows are correctly populated
        $this->assertCount(1, $transactions_table->transaction_table_rows);
        $row = $transactions_table->transaction_table_rows[0];
        $this->assertEquals('C', $row->transactionDC);
        $this->assertEquals('1234', $row->our_account);
        $this->assertEquals('2025-04-01 12:00:00', $row->valueTimestamp);
        $this->assertEquals('Test Account', $row->bankAccount);
        $this->assertEquals('Test Transaction', $row->transactionTitle);
        $this->assertEquals('USD', $row->currency);
        $this->assertEquals(1, $row->status);
        $this->assertEquals(1, $row->tid);
        $this->assertEquals('ST_SUPPAYMENT', $row->fa_trans_type);
        $this->assertEquals(123, $row->fa_trans_no);
        $this->assertEquals(100.00, $row->amount);
    }
}
