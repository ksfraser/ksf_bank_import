<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :bi_statements_modelTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for bi_statements_modelTest.
 */
use PHPUnit\Framework\TestCase;

class bi_statements_modelTest extends TestCase
{
    protected $bi_statements;

    protected function setUp(): void
    {
        $this->bi_statements = new bi_statements_model();
    }

    public function testDefineTable()
    {
        $this->bi_statements->define_table();
        $this->assertArrayHasKey('tablename', $this->bi_statements->table_details);
        $this->assertArrayHasKey('primarykey', $this->bi_statements->table_details);
        $this->assertEquals('bi_statements', $this->bi_statements->iam);
    }

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

    public function testUpdateStatement()
    {
        $this->bi_statements->id = 1;
        $this->bi_statements->startBalance = 100.00;
        $this->bi_statements->endBalance = 200.00;

        $this->bi_statements->update_statement();

        $this->expectOutputRegex('/Could not update trans/');
    }

    public function testGetStatement()
    {
        $this->bi_statements->id = 1;
        $result = $this->bi_statements->get_statement();

        $this->assertIsArray($result);
    }

    public function testStatementExists()
    {
        $this->bi_statements->bank = 'Bank1';
        $this->bi_statements->statementId = '12345';

        $result = $this->bi_statements->statement_exists();
        $this->assertIsBool($result);
    }
}
