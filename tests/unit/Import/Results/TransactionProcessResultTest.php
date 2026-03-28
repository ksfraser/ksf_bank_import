<?php

namespace Tests\Unit\Import\Results;

use Ksfraser\FaBankImport\Import\Results\TransactionProcessResult;
use PHPUnit\Framework\TestCase;

class TransactionProcessResultTest extends TestCase
{
    /**
     * Test successful transaction processing result.
     *
     * @test
     */
    public function testSuccessfulResult(): void
    {
        $result = TransactionProcessResult::successful(123);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(123, $result->getTransactionId());
        $this->assertEmpty($result->getGlEntries());
    }

    /**
     * Test failed transaction processing result.
     *
     * @test
     */
    public function testFailedResult(): void
    {
        $result = TransactionProcessResult::failed(456, 'Database insert failed');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(456, $result->getTransactionId());
        $this->assertCount(1, $result->getErrors());
    }

    /**
     * Test recording GL entries.
     *
     * @test
     */
    public function testRecordGlEntries(): void
    {
        $result = TransactionProcessResult::successful(789);

        $result->recordGlEntry(1, '1000', 500.00, 'Debit')
            ->recordGlEntry(2, '2000', -500.00, 'Credit');

        $entries = $result->getGlEntries();
        $this->assertCount(2, $entries);
        $this->assertEquals('1000', $entries[0]['account']);
        $this->assertEquals(500.00, $entries[0]['amount']);
        $this->assertEquals(1000.00, $result->getAmountPosted()); // Absolute value sum
    }

    /**
     * Test setting contact information.
     *
     * @test
     */
    public function testContactInfo(): void
    {
        $result = TransactionProcessResult::successful(111);

        $result->setContact(999, 'CU');

        $this->assertEquals(999, $result->getContactId());
        $this->assertEquals('CU', $result->getContactType());
    }

    /**
     * Test chaining operations.
     *
     * @test
     */
    public function testChaining(): void
    {
        $result = TransactionProcessResult::successful(222)
            ->recordGlEntry(1, '1000', 100.00, 'Memo')
            ->setContact(555, 'SU')
            ->addWarning('Amount mismatch by 0.01');

        $this->assertEquals(555, $result->getContactId());
        $this->assertCount(1, $result->getGlEntries());
        $this->assertCount(1, $result->getWarnings());
    }
}
