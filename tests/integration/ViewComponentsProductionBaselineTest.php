<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for View Component Classes
 * 
 * This test documents the PRODUCTION state of view component classes in
 * src/Ksfraser/FaBankImport/views/ directory.
 * 
 * Purpose: Verify that view component classes on main branch maintain
 * backward compatibility with the production version.
 * 
 * Files under test:
 * - AddCustomerButton.php
 * - AddNoButton.php  
 * - AddVendorButton.php
 * - DisplaySettledTransactions.php
 * - ToggleTransactionTypeButton.php
 * - TransType.php
 * 
 * Key behaviors documented (PROD):
 * 1. Use HTML_LABEL_ROW and HTML_ROW_LABELDecorator from Ksfraser\HTML namespace
 * 2. Simple view components with toHTML() method
 * 3. All in Ksfraser\FaBankImport namespace
 * 
 * Changes on main (expected):
 * - Namespace reorganization from Ksfraser\HTML to Ksfraser\HTML\Composites
 * 
 * @package KsfBankImport\Tests\Integration
 */
class ViewComponentsProductionBaselineTest extends TestCase
{
    private $viewsDir;
    
    protected function setUp(): void
    {
        $this->viewsDir = __DIR__ . '/../../src/Ksfraser/FaBankImport/views/';
        $this->assertDirectoryExists($this->viewsDir, 'Views directory must exist');
    }
    
    /**
     * @test
     * PROD BASELINE: AddCustomerButton exists and uses correct namespaces
     */
    public function testProdBaseline_AddCustomerButtonClass()
    {
        $file = $this->viewsDir . 'AddCustomerButton.php';
        $this->assertFileExists($file);
        
        $content = file_get_contents($file);
        
        $this->assertStringContainsString(
            'use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;',
            $content,
            'BASELINE: AddCustomerButton uses Composites decorator'
        );
        
        $this->assertStringContainsString(
            'class AddCustomerButton',
            $content,
            'PROD BASELINE: AddCustomerButton class must exist'
        );
        
        $this->assertStringContainsString(
            'function toHTML()',
            $content,
            'PROD BASELINE: Must have toHTML() method'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: AddNoButton exists and uses correct namespaces
     */
    public function testProdBaseline_AddNoButtonClass()
    {
        $file = $this->viewsDir . 'AddNoButton.php';
        $this->assertFileExists($file);
        
        $content = file_get_contents($file);
        
        $this->assertStringContainsString(
            'use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;',
            $content,
            'BASELINE: AddNoButton uses Composites decorator'
        );
        
        $this->assertStringContainsString(
            'class AddNoButton',
            $content,
            'PROD BASELINE: AddNoButton class must exist'
        );
        
        $this->assertStringContainsString(
            'function toHTML()',
            $content,
            'PROD BASELINE: Must have toHTML() method'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: AddVendorButton exists and uses correct namespaces
     */
    public function testProdBaseline_AddVendorButtonClass()
    {
        $file = $this->viewsDir . 'AddVendorButton.php';
        $this->assertFileExists($file);
        
        $content = file_get_contents($file);
        
        $this->assertStringContainsString(
            'use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;',
            $content,
            'BASELINE: AddVendorButton uses Composites decorator'
        );
        
        $this->assertStringContainsString(
            'class AddVendorButton',
            $content,
            'PROD BASELINE: AddVendorButton class must exist'
        );
        
        $this->assertStringContainsString(
            'function toHTML()',
            $content,
            'PROD BASELINE: Must have toHTML() method'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: All view components use Ksfraser\FaBankImport namespace
     */
    public function testProdBaseline_AllComponentsUseFaBankImportNamespace()
    {
        $files = [
            'AddCustomerButton.php',
            'AddNoButton.php',
            'AddVendorButton.php',
            'DisplaySettledTransactions.php',
            'ToggleTransactionTypeButton.php',
            'TransType.php'
        ];
        
        foreach ($files as $filename) {
            $file = $this->viewsDir . $filename;
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $this->assertStringContainsString(
                    'namespace Ksfraser\FaBankImport;',
                    $content,
                    "PROD BASELINE: {$filename} must use Ksfraser\FaBankImport namespace"
                );
            }
        }
    }
    
    /**
     * @test
     * BASELINE: Components use the Composites subnamespace (migrated from flat Ksfraser\HTML)
     */
    public function testProdBaseline_NoCompositesSubnamespace()
    {
        $files = [
            'AddCustomerButton.php',
            'AddNoButton.php',
            'AddVendorButton.php'
        ];
        
        foreach ($files as $filename) {
            $file = $this->viewsDir . $filename;
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $this->assertStringContainsString(
                    'Ksfraser\HTML\Composites',
                    $content,
                    "BASELINE: {$filename} should use Composites subnamespace"
                );
            }
        }
    }
}
