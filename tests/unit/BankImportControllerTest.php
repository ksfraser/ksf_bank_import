<?php

use PHPUnit\Framework\TestCase;

class BankImportControllerTest extends TestCase
{
    public function testControllerCanBeInstantiated()
    {
        $controller = new bank_import_controller();
        $this->assertInstanceOf(bank_import_controller::class, $controller);
    }

    public function testControllerHasRepository()
    {
        $controller = new bank_import_controller();
        $this->assertNotNull($controller->repository);
    }

    public function testExtractPostReturnsErrorWhenPartnerIdMissing()
    {
        $controller = new bank_import_controller();
        $_POST = [];
        $controller->tid = 1;
        $result = $controller->extractPost();
        $this->assertTrue($result);
    }

    public function testSetWithDefaultField()
    {
        $controller = new bank_import_controller();
        $controller->set('testField', 'testValue');
        $this->assertEquals('testValue', $controller->get('testField'));
    }
}
