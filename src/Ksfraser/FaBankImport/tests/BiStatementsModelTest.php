<?php

use PHPUnit\Framework\TestCase;

class bi_statements_modelTest extends TestCase
{
    protected $bi_statements;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        $this->bi_statements = new bi_statements_model();
    }

    /** @BABOK NFR-016 / FR-018 — method: testDefineTable */
    public function testDefineTable()
    {
        $this->bi_statements->define_table();
        $this->assertArrayHasKey('tablename', $this->bi_statements->table_details);
        $this->assertArrayHasKey('primarykey', $this->bi_statements->table_details);
        $this->assertEquals('bi_statements', $this->bi_statements->iam);
    }

    /** @BABOK NFR-016 / FR-018 — method: testInsertTransaction */
    public function testInsertTransaction()
    {
        // Mock insert_data method
        $this->bi_statements = $this->getMockBuilder(bi_statements_model::class)
            ->setMethods(['insert_data'])
            ->getMock();

        $this->bi_statements->expects($this->once())
            ->method('insert_data')
            ->with($this->isType('array'));

        $this->bi_statements->insert_transaction();
    }

    /** @BABOK NFR-016 / FR-018 — method: testUpdateStatement */
    public function testUpdateStatement()
    {
        $this->bi_statements->id = 1;
        $this->bi_statements->startBalance = 100.00;
        $this->bi_statements->endBalance = 200.00;

        $this->bi_statements->update_statement();

        $this->expectOutputRegex('/Could not update trans/');
    }

    /** @BABOK NFR-016 / FR-018 — method: testGetStatement */
    public function testGetStatement()
    {
        $this->bi_statements->id = 1;
        $result = $this->bi_statements->get_statement();

        $this->assertIsArray($result);
    }

    /** @BABOK NFR-016 / FR-018 — method: testStatementExists */
    public function testStatementExists()
    {
        $this->bi_statements->bank = 'Bank1';
        $this->bi_statements->statementId = '12345';

        $result = $this->bi_statements->statement_exists();
        $this->assertIsBool($result);
    }
}
