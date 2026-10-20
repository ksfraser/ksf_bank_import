<?php

use PHPUnit\Framework\TestCase;

class bi_transactionTest extends TestCase
{
    protected $biTransaction;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        $this->biTransaction = new bi_transaction();
    }

    /** @BABOK NFR-016 / FR-018 — method: testConstructorInitializesProperties */
    public function testConstructorInitializesProperties()
    {
        $this->assertSame('bi_transaction', $this->biTransaction->iam);
        $this->assertSame(0, $this->biTransaction->matched);
        $this->assertSame(0, $this->biTransaction->created);
    }

    /** @BABOK NFR-016 / FR-018 — method: testSetMethodSetsField */
    public function testSetMethodSetsField()
    {
        $field = 'partnerId';
        $value = 12345;

        $this->biTransaction->set($field, $value);

        $this->assertSame($value, $this->biTransaction->partnerId);
    }

    /** @BABOK NFR-016 / FR-018 — method: testSetMethodThrowsExceptionForInvalidField */
    public function testSetMethodThrowsExceptionForInvalidField()
    {
        $this->expectException(Exception::class);

        $this->biTransaction->set('invalidField', 'value');
    }
}

?>
