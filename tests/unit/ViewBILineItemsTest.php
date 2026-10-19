<?php

/**
 * Unit tests for the line item rendering.
 *
 * These tests used to drive the deprecated ViewBILineItems class. That class
 * is dead code: it was replaced by bi_lineitem's own display methods
 * (display(), getHtml(), getLeftTd(), getRightTd()) and it could never run -
 * it calls methods it does not have (matchedVendor(), isPaired(),
 * selectAndDisplayButton(), ...) and reads properties it does not have.
 * The tests therefore skipped themselves.
 *
 * They now exercise the implementation that replaced it: bi_lineitem's own
 * display methods. The bug they were written for - using an undefined
 * $bi_lineitem variable instead of $this->bi_lineitem - is exactly the kind
 * of mistake this rendering path can make, so the assertions check the
 * rendered markup.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      October 19, 2025
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for the line item rendering (bi_lineitem display methods).
 *
 * Focuses on critical bugs and display logic.
 */
class ViewBILineItemsTest extends TestCase
{
    /**
     * @var object The line item under test
     */
    private $lineItem;

    /**
     * Set up test fixtures before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../class.bi_lineitem.php';

        // The vendor list is indexed by position, with the account names in
        // the 'shortnames' list, which is what bi_lineitem::matchedVendor()
        // searches.
        $vendorList = [
            0 => [
                'supplier_id' => 5,
                'supp_name'   => 'Vendor Account',
            ],
            'shortnames' => ['Vendor Account'],
        ];

        $transaction = [
            'transactionDC'      => 'D',
            'memo'               => 'Test memo',
            'our_account'        => 'CHK001',
            'valueTimestamp'     => '2026-01-15',
            'entryTimestamp'     => '2026-01-15 09:05:00',
            'accountName'        => 'Vendor Account',
            'transactionTitle'   => 'Test Transaction',
            'transactionCode'    => 'X',
            'transactionCodeDesc' => 'Desc',
            'currency'           => 'USD',
            'status'             => 0,
            'id'                 => 123,
            'fa_trans_type'      => 0,
            'fa_trans_no'        => 0,
            'transactionAmount'  => 1000.00,
            'transactionType'    => 'Payment',
        ];

        $this->lineItem = new \bi_lineitem($transaction, $vendorList, []);
    }

    /**
     * The left cell of the line item renders without an undefined variable
     * error and contains the values of the line item.
     *
     * @test
     */
    public function testDisplayLeftUsesThisBiLineitemNotUndefinedVariable(): void
    {
        // This should NOT throw an undefined variable error.
        // If it does, the bug is not fixed.
        $output = $this->lineItem->getLeftTd()->getHtml();

        $this->assertNotEmpty($output, 'display_left() must produce HTML');
        $this->assertStringContainsString(
            '<td width="50%">',
            $output,
            'The left cell must be a 50% wide table cell'
        );
        $this->assertStringContainsString(
            '2026-01-15',
            $output,
            'The transaction date must be rendered'
        );
        $this->assertStringContainsString(
            'Test Transaction',
            $output,
            'The transaction title must be rendered'
        );
        $this->assertStringContainsString(
            'Vendor Account',
            $output,
            'The other bank account must be rendered'
        );
    }

    /**
     * The complete line item row is a table row with a left and a right cell.
     *
     * @test
     */
    public function testDisplayLeftGeneratesHtmlOutput(): void
    {
        $output = $this->lineItem->getHtml();

        $this->assertNotEmpty($output, 'getHtml() must produce HTML');

        // Should contain table opening
        $this->assertStringContainsString(
            '<td width="50%">',
            $output,
            'Output should contain table cell opening'
        );
        $this->assertStringContainsString(
            '<tr>',
            $output,
            'Output should contain the table row'
        );
    }
}
