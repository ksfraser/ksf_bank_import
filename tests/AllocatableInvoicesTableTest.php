<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../views/AllocatableInvoicesTable.php';

if (!defined('TABLESTYLE')) {
    define('TABLESTYLE', 'table');
}

class AllocatableInvoicesTableTest extends TestCase
{
    public function testRendersHeadersAndFormattedValues()
    {
        $rows = [
            [
                'type_no' => 1234,
                'trans_date' => '2026-04-01',
                'invoice_amount' => 1500.0,
                'payments' => 200.0,
                'unallocated' => 1300.0,
            ],
        ];

        $columns = [
            'type_no' => '#',
            'trans_date' => 'Date',
            'invoice_amount' => [
                'label' => 'Invoice Amount',
                'formatter' => function ($v) { return number_format((float)$v, 2); },
                'attrs' => ['align' => 'right', 'class' => 'currency'],
            ],
            'payments' => [
                'label' => 'Other Payments',
                'formatter' => function ($v) { return number_format((float)$v, 2); },
                'attrs' => ['align' => 'right'],
            ],
            'unallocated' => [
                'label' => 'Outstanding Balance',
                'formatter' => function ($v) { return number_format((float)$v, 2); },
                'attrs' => ['align' => 'right'],
            ],
        ];

        $table = new \KsfBankImport\Views\AllocatableInvoicesTable($rows, $columns);
        $html = $table->getHtml();

        $this->assertStringContainsString('Invoice Amount', $html);
        $this->assertStringContainsString('1,500.00', $html);
        $this->assertStringContainsString('1,300.00', $html);
    }
}
