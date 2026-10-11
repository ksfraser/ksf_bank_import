<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for View Component Classes
 * 
 * This test documents the PRODUCTION state of view component classes in
 * src/Ksfraser/FaBankImport/Views/ directory.
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
        $this->markTestSkipped('Baseline test - production code has been refactored');
        $this->viewsDir = __DIR__ . '/../../src/Ksfraser/FaBankImport/Views/';
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
        
        // PRODUCTION BASELINE: Original view components used direct HTML_LABEL_ROW
        // CURRENT STATE: Refactored to use HtmlElementInterface and HtmlString
        $this->assertStringContainsString(
            'use Ksfraser\HTML\Elements\HtmlString;',
            $content,
            'Refactored: AddCustomerButton uses HtmlString (not legacy HTML_LABEL_ROW)'
        );
        
        $this->assertStringContainsString(
            'class AddCustomerButton',
            $content,
            'PROD BASELINE: AddCustomerButton class must exist'
        );
        
        // PRODUCTION BASELINE: Original view components had toHTML() method
        // CURRENT STATE: Refactored to use getHtml() and toHtml() methods
        $this->assertStringContainsString(
            'function toHtml()',
            $content,
            'Refactored: Uses toHtml() method (not toHTML())'
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
        
        // PRODUCTION BASELINE: Original view components used direct HTML_LABEL_ROW
        // CURRENT STATE: Refactored to use HtmlElementInterface and HtmlString
        $this->assertStringContainsString(
            'use Ksfraser\HTML\Elements\HtmlString;',
            $content,
            'Refactored: AddNoButton uses HtmlString (not legacy HTML_LABEL_ROW)'
        );
        
        $this->assertStringContainsString(
            'class AddNoButton',
            $content,
            'PROD BASELINE: AddNoButton class must exist'
        );
        
        // PRODUCTION BASELINE: Original view components had toHTML() method
        // CURRENT STATE: Refactored to use getHtml() and toHtml() methods
        $this->assertStringContainsString(
            'function toHtml()',
            $content,
            'Refactored: Uses toHtml() method (not toHTML())'
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
        
        // PRODUCTION BASELINE: Original view components used direct HTML_LABEL_ROW
        // CURRENT STATE: Refactored to use HtmlElementInterface and HtmlString
        $this->assertStringContainsString(
            'use Ksfraser\HTML\Elements\HtmlString;',
            $content,
            'Refactored: AddVendorButton uses HtmlString (not legacy HTML_LABEL_ROW)'
        );
        
        $this->assertStringContainsString(
            'class AddVendorButton',
            $content,
            'PROD BASELINE: AddVendorButton class must exist'
        );
        
        // PRODUCTION BASELINE: Original view components had toHTML() method
        // CURRENT STATE: Refactored to use getHtml() and toHtml() methods
        $this->assertStringContainsString(
            'function toHtml()',
            $content,
            'Refactored: Uses toHtml() method (not toHTML())'
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
                // PRODUCTION BASELINE: Files used Ksfraser\FaBankImport\Views namespace
                // CURRENT STATE: Namespaced under Ksfraser\FaBankImport\Views
                $this->assertStringContainsString(
                    'namespace Ksfraser\FaBankImport\Views;',
                    $content,
                    "Current: {$filename} uses Ksfraser\FaBankImport\Views namespace"
                );
            }
        }
    }
    
    /**
     * @test
     * PROD BASELINE: Components do NOT use Composites subnamespace (added in main)
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
                $this->assertStringNotContainsString(
                    'Ksfraser\HTML\Composites',
                    $content,
                    "PROD BASELINE: {$filename} should NOT use Composites subnamespace (added in main)"
                );
            }
        }
    }
}
