<?php

declare(strict_types=1);

use Ksfraser\FaBankImport\Actions\AddCustomerAction;
use Ksfraser\FaBankImport\Actions\AddVendorAction;
use Ksfraser\FaBankImport\Actions\ToggleTransactionAction;
use Ksfraser\FaBankImport\Actions\UnsetTransactionAction;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Ksfraser/FaBankImport/Actions/UnsetTransactionAction.php';
require_once __DIR__ . '/../../src/Ksfraser/FaBankImport/Actions/AddCustomerAction.php';
require_once __DIR__ . '/../../src/Ksfraser/FaBankImport/Actions/AddVendorAction.php';
require_once __DIR__ . '/../../src/Ksfraser/FaBankImport/Actions/ToggleTransactionAction.php';

final class LegacyInlineActionsTest extends TestCase
{
    public function testUnsetActionSupportsAndExecutes(): void
    {
        $controller = new class {
            public $called = false;
            public function unsetTrans(): void { $this->called = true; }
        };

        $action = new UnsetTransactionAction();
        $this->assertTrue($action->supports(['UnsetTrans' => ['1' => 'x']]));
        $this->assertTrue($action->execute(['UnsetTrans' => ['1' => 'x']], $controller));
        $this->assertTrue($controller->called);
    }

    public function testAddCustomerActionSupportsAndExecutes(): void
    {
        $controller = new class {
            public $called = false;
            public function addCustomer(): void { $this->called = true; }
        };

        $action = new AddCustomerAction();
        $this->assertTrue($action->supports(['AddCustomer' => ['1' => 'x']]));
        $this->assertTrue($action->execute(['AddCustomer' => ['1' => 'x']], $controller));
        $this->assertTrue($controller->called);
    }

    public function testAddVendorActionSupportsAndExecutes(): void
    {
        $controller = new class {
            public $called = false;
            public function addVendor(): void { $this->called = true; }
        };

        $action = new AddVendorAction();
        $this->assertTrue($action->supports(['AddVendor' => ['1' => 'x']]));
        $this->assertTrue($action->execute(['AddVendor' => ['1' => 'x']], $controller));
        $this->assertTrue($controller->called);
    }

    public function testToggleActionSupportsAndExecutes(): void
    {
        $controller = new class {
            public $called = false;
            public function toggleDebitCredit(): void { $this->called = true; }
        };

        $action = new ToggleTransactionAction();
        $this->assertTrue($action->supports(['ToggleTransaction' => ['1' => 'x']]));
        $this->assertTrue($action->execute(['ToggleTransaction' => ['1' => 'x']], $controller));
        $this->assertTrue($controller->called);
    }
}
