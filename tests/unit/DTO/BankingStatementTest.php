<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\BankingStatement;

class BankingStatementTest extends TestCase
{
    public function test_addTransaction_and_dump()
    {
        $s = new BankingStatement();
        $s->bank = 'MyBank';
        $s->account = 'ACC';
        $s->startBalance = 0;
        $s->endBalance = 0;
        $s->currency = 'USD';
        $s->timestamp = time();
        $s->number = 'N';
        $s->sequence = 'S';
        $s->statementId = 'ID';

        $t = new stdClass();
        $s->addTransaction($t);

        $this->assertCount(1, $s->transactions);
    }
}
