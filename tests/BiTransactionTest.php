<?php

use PHPUnit\Framework\TestCase;

class bi_transactionTest extends TestCase
{
    protected $biTransaction;

    protected function setUp(): void
    {
        $this->biTransaction = new bi_transaction();
    }

    public function testConstructorInitializesProperties()
    {
        $this->assertSame('bi_transaction', $this->biTransaction->iam);
        $this->assertSame(0, $this->biTransaction->matched);
        $this->assertSame(0, $this->biTransaction->created);
    }

    public function testSetMethodSetsField()
    {
        $field = 'partnerId';
        $value = 12345;

        $this->biTransaction->set($field, $value);

        $this->assertSame($value, $this->biTransaction->partnerId);
    }

    public function testSetMethodThrowsExceptionForInvalidField()
    {
        $this->expectException(Exception::class);

        $this->biTransaction->set('invalidField', 'value');
    }
}

?>
