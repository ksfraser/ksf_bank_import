<?php
use PHPUnit\Framework\TestCase;

class ImportStatementsTest extends TestCase
{
    public function testImportStatement()
    {
        // Sample data to be used in the test
        $sampleTransaction = new stdClass();
        $sampleTransaction->bank = 'Test Bank';
        $sampleTransaction->statementId = '12345';
        $sampleTransaction->transactions = [];

        // Mocking the bi_statements_model class
        $biStatementsMock = $this->createMock(bi_statements_model::class);
        $biStatementsMock->method('statement_exists')->willReturn(false);
        $biStatementsMock->method('hand_insert_sql')->willReturn('INSERT INTO statements ...');
        $biStatementsMock->method('get')->willReturn('12345');

        // Mocking the bi_transactions_model class
        $biTransactionsMock = $this->createMock(bi_transactions_model::class);
        $biTransactionsMock->method('trans_exists')->willReturn(false);
        $biTransactionsMock->method('hand_insert_sql')->willReturn('INSERT INTO transactions ...');

        // Replacing instantiated classes with mocks
        $this->replaceInstance(bi_statements_model::class, $biStatementsMock);
        $this->replaceInstance(bi_transactions_model::class, $biTransactionsMock);

        // Call the function under test
        $result = importStatement($sampleTransaction);

        // Assert the expected outcomes
        $this->assertStringContainsString('new, imported', $result);
    }

    // Helper function to replace class instances with mocks
    private function replaceInstance($class, $mock)
    {
        global $instanceMap;
        if (!isset($instanceMap)) {
            $instanceMap = [];
        }
        $instanceMap[$class] = $mock;
    }
}
?>
