<?php

namespace Tests\Unit\Import\Services;

use Ksfraser\FaBankImport\Import\Services\TransactionDisplayRenderer;
use PHPUnit\Framework\TestCase;

class TransactionDisplayRendererTest extends TestCase
{
    private TransactionDisplayRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new TransactionDisplayRenderer();
    }

    /**
     * Test rendering empty transaction array.
     *
     * @test
     */
    public function testRenderingEmptyTransactions(): void
    {
        $html = $this->renderer->renderRows([], []);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
        $this->assertStringContainsString('<tbody>', $html);
    }

    /**
     * Test rendering single transaction.
     *
     * @test
     */
    public function testRenderingSingleTransaction(): void
    {
        $transactions = [
            ['id' => 1, 'date' => '2025-01-01', 'amount' => 100.00, 'description' => 'Test', 'status' => 'OK']
        ];

        $html = $this->renderer->renderRows($transactions, ['format' => 'html']);

        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('</tr>', $html);
    }

    /**
     * Test rendering multiple transactions.
     *
     * @test
     */
    public function testRenderingMultipleTransactions(): void
    {
        $transactions = [
            ['id' => 1, 'date' => '2025-01-01', 'amount' => 100.00, 'description' => 'Test 1', 'status' => 'OK'],
            ['id' => 2, 'date' => '2025-01-02', 'amount' => 200.00, 'description' => 'Test 2', 'status' => 'OK'],
            ['id' => 3, 'date' => '2025-01-03', 'amount' => 300.00, 'description' => 'Test 3', 'status' => 'FAIL'],
        ];

        $html = $this->renderer->renderRows($transactions, ['format' => 'html']);

        // Should contain 3 rows
        $this->assertStringContainsString('Test 1', $html);
        $this->assertStringContainsString('Test 2', $html);
        $this->assertStringContainsString('Test 3', $html);
    }

    /**
     * Test JSON rendering format.
     *
     * @test
     */
    public function testJsonRendering(): void
    {
        $transactions = [
            ['id' => 1, 'amount' => 100.00]
        ];

        $json = $this->renderer->renderRows($transactions, ['format' => 'json']);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
    }

    /**
     * Test HTML escaping for XSS protection.
     *
     * @test
     */
    public function testHtmlEscaping(): void
    {
        $transactions = [
            ['id' => 1, 'description' => '<script>alert("XSS")</script>']
        ];

        $html = $this->renderer->renderRows($transactions, ['format' => 'html']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * Test rendering detail view.
     *
     * @test
     */
    public function testRenderingDetail(): void
    {
        $transaction = ['id' => 1, 'amount' => 100.00, 'description' => 'Test'];

        $html = $this->renderer->renderDetail($transaction);

        $this->assertStringContainsString('detail', $html);
        $this->assertStringContainsString('id', $html);
        $this->assertStringContainsString('100', $html);
    }

    /**
     * Test rendering detail with custom CSS class.
     *
     * @test
     */
    public function testRenderingDetailWithCssClass(): void
    {
        $transaction = ['id' => 1, 'amount' => 100.00];

        $html = $this->renderer->renderDetail($transaction, ['css_class' => 'my-class']);

        $this->assertStringContainsString('class="my-class"', $html);
    }

    /**
     * Test rendering with custom columns.
     *
     * @test
     */
    public function testRenderingWithCustomColumns(): void
    {
        $transactions = [
            ['id' => 1, 'amount' => 100.00, 'description' => 'Test']
        ];

        $html = $this->renderer->renderRows($transactions, [
            'format' => 'html',
            'columns' => ['id', 'amount']
        ]);

        $this->assertStringContainsString('1', $html);
        $this->assertStringContainsString('100', $html);
    }
}
