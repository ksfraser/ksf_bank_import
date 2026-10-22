<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controller;

use Ksfraser\FaBankImport\Controller\BiLineItemController;
use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\FaBankImport\View\BiLineItemView;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/CaseSensitiveClassStubs.php';

final class BiLineItemControllerTest extends TestCase
{
    public function testTheDisplayRendersTheLeftAndTheRightSideOfTheLineItem(): void
    {
        $model = new BiLineItemModel(['id' => 1]);
        $view = new BiLineItemView();

        $controller = new BiLineItemController($model, $view);
        $controller->display();

        $this->assertCount(2, $view->rendered);
        $this->assertSame('left', $view->rendered[0][0]);
        $this->assertSame($model, $view->rendered[0][1]);
        $this->assertSame('right', $view->rendered[1][0]);
        $this->assertSame($model, $view->rendered[1][1]);
    }
}
