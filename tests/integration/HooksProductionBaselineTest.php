<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for hooks.php
 * 
 * This test documents the PRODUCTION state of hooks.php.
 * 
 * Purpose: Verify that the hooks_bank_import class on main branch maintains
 * backward compatibility with the production version.
 * 
 * File under test: hooks.php (8 insertions on main)
 * 
 * Key behaviors documented:
 * 1. Defines MENU_IMPORT constant
 * 2. Extends hooks base class
 * 3. Installs menu options in GL app
 * 4. Provides activate_extension for database updates
 * 5. Provides db_prevoid for voiding bank transactions
 * 
 * Changes on main (expected):
 * - Added MENU_IMPORT constant definition (new functionality)
 * - Rearranged menu items to use MENU_IMPORT group
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
    }
    
    /**
     * @test
     * PROD BASELINE: hooks_bank_import class must exist
     */
    public function testProdBaseline_ClassExists()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            'class hooks_bank_import',
            $content,
            'PROD BASELINE: hooks_bank_import class must exist'
        );
        
        $this->assertStringContainsString(
            'extends hooks',
            $content,
            'PROD BASELINE: hooks_bank_import must extend hooks base class'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Should have module_name property
     */
    public function testProdBaseline_HasModuleNameProperty()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            "var \$module_name = 'bank_import'",
            $content,
            'PROD BASELINE: Must have module_name property set to "bank_import"'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Should have install_options method
     */
    public function testProdBaseline_HasInstallOptionsMethod()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            'function install_options($app)',
            $content,
            'PROD BASELINE: Must have install_options method'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Should have activate_extension method
     */
    public function testProdBaseline_HasActivateExtensionMethod()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            'function activate_extension($company, $check_only=true)',
            $content,
            'PROD BASELINE: Must have activate_extension method'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Should have db_prevoid method for voiding transactions
     */
    public function testProdBaseline_HasDbPrevoidMethod()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            'function db_prevoid($trans_type, $trans_no)',
            $content,
            'PROD BASELINE: Must have db_prevoid method for voiding bank transactions'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Should define MENU_IMPORT constant
     */
    public function testProdBaseline_DefinesMenuImportConstant()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            "define( 'MENU_IMPORT'",
            $content,
            'PROD BASELINE: Should define MENU_IMPORT constant'
        );
        
        $this->assertStringContainsString(
            "'menu_import'",
            $content,
            'PROD BASELINE: MENU_IMPORT should be set to "menu_import"'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Menu items use MENU_MAINTENANCE and MENU_INQUIRY
     */
    public function testProdBaseline_MenuItemsUseStandardMenus()
    {
        $content = file_get_contents($this->hooksFile);
        
        $this->assertStringContainsString(
            'MENU_MAINTENANCE',
            $content,
            'PROD BASELINE: Should use MENU_MAINTENANCE for menu organization'
        );
        
        $this->assertStringContainsString(
            'MENU_INQUIRY',
            $content,
            'PROD BASELINE: Should use MENU_INQUIRY for inquiry screens'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Documents menu structure for GL app (4 menu items on prod)
     * 
     * Main branch adds 6 additional menu items (not present on prod):
     * - Manage Uploaded Files
     * - Validate GL Entries  
     * - Module Configuration
     * - Bank Import Settings
     * - 2 more (to be documented)
     */
    public function testProdBaseline_InstallsGLAppMenuItems()
    {
        $content = file_get_contents($this->hooksFile);
        
        // Verify core 4 menu items are configured on prod
        $this->assertStringContainsString(
            'Manage Partners Bank Accounts',
            $content,
            'PROD BASELINE: Should install "Manage Partners Bank Accounts" menu'
        );
        
        $this->assertStringContainsString(
            'Import Bank Statements',
            $content,
            'PROD BASELINE: Should install "Import Bank Statements" menu'
        );
        
        $this->assertStringContainsString(
            'Process Bank Statements',
            $content,
            'PROD BASELINE: Should install "Process Bank Statements" menu'
        );
        
        $this->assertStringContainsString(
            'Bank Statements Inquiry',
            $content,
            'PROD BASELINE: Should install "Bank Statements Inquiry" menu'
        );
        
        // INVERTED (refactor-psr): the additional menu items were added as features.
        // Guard that they are present.
        $this->assertStringContainsString(
            'Manage Uploaded Files',
            $content,
            'BASELINE: Should have "Manage Uploaded Files" menu'
        );
        
        $this->assertStringContainsString(
            'Validate GL Entries',
            $content,
            'BASELINE: Should have "Validate GL Entries" menu'
        );
        
        $this->assertStringContainsString(
            'Module Configuration',
            $content,
            'BASELINE: Should have "Module Configuration" menu'
        );
        
        $this->assertStringContainsString(
            'Bank Import Settings',
            $content,
            'BASELINE: Should have "Bank Import Settings" menu'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: db_prevoid clears transaction linkages
     * 
     * This documents the SQL behavior for voiding bank transactions.
     * The method sets status=0 and clears FA linkages when a transaction is voided.
     */
    public function testProdBaseline_DbPrevoidClearsTransactionLinkages()
    {
        $content = file_get_contents($this->hooksFile);
        
        // Verify SQL updates expected fields
        $this->assertStringContainsString(
            'status=0',
            $content,
            'PROD BASELINE: db_prevoid must set status=0'
        );
        
        $this->assertStringContainsString(
            'fa_trans_no=0',
            $content,
            'PROD BASELINE: db_prevoid must clear fa_trans_no'
        );
        
        $this->assertStringContainsString(
            'fa_trans_type=0',
            $content,
            'PROD BASELINE: db_prevoid must clear fa_trans_type'
        );
        
        $this->assertStringContainsString(
            "created=0",
            $content,
            'PROD BASELINE: db_prevoid must reset created flag'
        );
        
        $this->assertStringContainsString(
            "matched=0",
            $content,
            'PROD BASELINE: db_prevoid must reset matched flag'
        );
    }
}
