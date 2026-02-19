<?php
use PHPUnit\Framework\TestCase;

class BiModelSmokeTest extends TestCase
{
    /**
     * @dataProvider biModelProvider
     */
    public function testModelInstantiation($classFile, $className)
    {
        require_once $classFile;
        $obj = new $className();
        $this->assertIsObject($obj);
    }

    public static function biModelProvider()
    {
        return [
            [__DIR__ . '/../../class.bi_transactions.php', 'bi_transactions_model'],
            [__DIR__ . '/../../class.bi_transaction.php', 'bi_transaction_model'],
            [__DIR__ . '/../../class.bi_statements.php', 'bi_statements_model'],
            [__DIR__ . '/../../class.bi_partners_data.php', 'bi_partners_data'],
            [__DIR__ . '/../../class.bi_lineitem.php', 'bi_lineitem_model'],
            [__DIR__ . '/../../class.bi_counterparty_model.php', 'bi_counterparty_model'],
            [__DIR__ . '/../../class.bi_bank_accounts.php', 'bi_bank_accounts_model'],
            [__DIR__ . '/../../class.bi_transactionTitle_model.php', 'bi_transactionTitle_model'],
            [__DIR__ . '/../../class.bi_transfer_matches.php', 'bi_transfer_matches_model'],
        ];
    }
}
