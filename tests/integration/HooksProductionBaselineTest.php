<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for hooks.php
 *
 * Originally documented the PROD state of hooks.php via source pins.
 * CONVERTED (refactor-psr): now verifies BEHAVIOR at runtime —
 *
 * - hooks_bank_import class loads and extends legacy hooks base
 * - MENU_IMPORT constant is defined (new architecture feature)
 * - install_options(), activate_extension(), and db_prevoid() exist
 * - Menu items are present in install_options() output
 *
 * @package KsfBankImport\Tests\Integration
 */
class HooksProductionBaselineTest extends TestCase
{
    private $hooksFile;

    protected function setUp(): void
    {
        $this->hooksFile = __DIR__ . '/../../hooks.php';
        $this->assertFileExists($this->hooksFile, 'hooks.php must exist');
        if (!class_exists('hooks_bank_import')) {
            require_once $this->hooksFile;
        }
    }

    /**
     * @test
     * BASELINE: hooks_bank_import class must exist and extend legacy hooks base.
     */
    public function testProdBaseline_ClassExists()
    {
        $this->assertTrue(class_exists('hooks_bank_import'), 'hooks_bank_import class must load');

        // Note: 'hooks' base class is provided by FrontAccounting or our stubs
        $this->assertContains(
            'hooks',
            class_parents('hooks_bank_import'),
            'hooks_bank_import must extend hooks base class'
        );
    }

    /**
     * @test
     * BASELINE: Must have module identifier property.
     */
    public function testProdBaseline_HasModuleNameProperty()
    {
        $ref = new \ReflectionClass('hooks_bank_import');
        $props = $ref->getDefaultProperties();
        
        $this->assertArrayHasKey('module_name', $props);
        $this->assertEquals('bank_import', $props['module_name']);
    }

    /**
     * @test
     * BASELINE: Core hooks methods must exist.
     */
    public function testProdBaseline_CoreMethodsExist()
    {
        $ref = new \ReflectionClass('hooks_bank_import');
        
        foreach (['install_options', 'activate_extension', 'db_prevoid'] as $method) {
            $this->assertTrue($ref->hasMethod($method), "Must have {$method} method");
        }
    }

    /**
     * @test
     * BASELINE: Should define MENU_IMPORT constant (Modern architecture).
     */
    public function testProdBaseline_DefinesMenuImportConstant()
    {
        $this->assertTrue(defined('MENU_IMPORT'), 'BASELINE: Should define MENU_IMPORT constant');
        $this->assertEquals('menu_import', MENU_IMPORT);
    }

    /**
     * @test
     * BASELINE: Menu structure contains all required bank import options.
     */
    public function testProdBaseline_InstallsGLAppMenuItems()
    {
        $hooks = new \hooks_bank_import();
        $app = new \MockApp();
        $app->id = 'GL';
        $app->menu_items = [];

        $hooks->install_options($app);
        
        $labels = array_map(fn($m) => $m['label'], $app->menu_items);

        $expected = [
            'Manage Partners Bank Accounts',
            'Import Bank Statements',
            'Process Bank Statements',
            'Bank Statements Inquiry',
            'Manage Uploaded Files',
            'Validate GL Entries',
            'Module Configuration',
            'Bank Import Settings'
        ];

        foreach ($expected as $label) {
            $this->assertContains($label, $labels, "Menu should contain: $label");
        }
    }
}
