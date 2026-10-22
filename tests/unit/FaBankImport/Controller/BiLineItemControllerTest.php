<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controller;

use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\FaBankImport\View\BiLineItemView;
use PHPUnit\Framework\TestCase;

/**
 * A line item view that records what it is asked to render.
 *
 * The production view renders through the FrontAccounting UI helpers
 * (label_row(), array_selector()), which only exist inside a FrontAccounting
 * installation. The controller test is about the delegation, so the view is
 * replaced by a double that records the calls.
 */
final class RecordingBiLineItemView extends BiLineItemView
{
    /** @var array<int, array{0: string, 1: object}> */
    public $rendered = [];

    /**
     * @param BiLineItemModel $model The line item to render.
     * @return void
     */
    public function display_left(BiLineItemModel $model)
    {
        $this->rendered[] = ['left', $model];
    }

    /**
     * @return void
     */
    public function display_right()
    {
        $this->rendered[] = ['right', $this->lineItemModel ?? null];
    }
}

/**
 * The controller renders the line item through the view.
 */
final class BiLineItemControllerTest extends TestCase
{
    public function testTheDisplayRendersTheLeftAndTheRightSideOfTheLineItem(): void
    {
        // The legacy model expects the full transaction row.
        $model = new BiLineItemModel([
            'id' => 1,
            'transactionDC' => 'C',
            'memo' => 'A memo',
            'our_account' => '000123',
            'valueTimestamp' => '2026-01-31',
            'entryTimestamp' => '2026-02-01',
            'accountName' => 'Counterparty',
            'transactionTitle' => 'A title',
            'transactionCode' => 'REF-1',
            'transactionCodeDesc' => 'Reference',
            'currency' => 'CAD',
            'status' => 0,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'transactionAmount' => 25.5,
            'transactionType' => 'TRN',
        ], ['shortnames' => ['COUNTERPARTY'], 'names' => ['Counterparty']]);

        $view = new RecordingBiLineItemView($model);

        $controller = new \Ksfraser\FaBankImport\Controller\BiLineItemController($model, $view);
        $controller->display();

        $this->assertCount(2, $view->rendered);
        $this->assertSame('left', $view->rendered[0][0]);
        $this->assertSame($model, $view->rendered[0][1]);
        $this->assertSame('right', $view->rendered[1][0]);
        $this->assertSame($model, $view->rendered[1][1]);
    }
}
