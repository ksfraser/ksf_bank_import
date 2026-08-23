<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for View Component Classes
 *
 * Originally documented the PROD state of view component classes via
 * source-content pins. CONVERTED (refactor-psr): now verifies BEHAVIOR —
 *
 * - Component classes exist at runtime in src/Ksfraser/FaBankImport/views/
 * - Each implements Ksfraser\HTML\HtmlElementInterface
 * - toHTML() renders a label row containing the component's content
 * - getHTML() returns the same markup as toHTML() outputs
 *
 * Files under test:
 * - AddCustomerButton.php
 * - AddNoButton.php
 * - AddVendorButton.php
 * - DisplaySettledTransactions.php
 * - ToggleTransactionTypeButton.php
 * - TransType.php
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
     * Load one of the (mutually redeclaring) view component files.
     */
    private function requireComponent(string $file): void
    {
        $path = $this->viewsDir . $file;
        $this->assertFileExists($path);
        require_once $path;
    }

    /**
     * @test
     * AddNoButton renders a label row with its submit button.
     */
    public function testAddNoButtonRenders()
    {
        $this->requireComponent('AddNoButton.php');

        $this->assertTrue(class_exists('Ksfraser\FaBankImport\AddNoButton'));
        $this->assertContains(
            'Ksfraser\HTML\HtmlElementInterface',
            class_implements('Ksfraser\FaBankImport\AddNoButton'),
            'AddNoButton must implement HtmlElementInterface'
        );

        $button = new \Ksfraser\FaBankImport\AddNoButton(1);

        ob_start();
        $button->toHTML();
        $output = ob_get_clean();

        $this->assertStringContainsString('<tr>', $output, 'Must render a table row');
        $this->assertStringContainsString('Add Button', $output, 'Must render the label');
        $this->assertStringContainsString('There is nothing to add', $output, 'Must render the content');
        $this->assertSame($output, $button->getHTML(), 'getHTML() must match toHTML() output');
    }

    /**
     * @test
     * ToggleTransactionTypeButton renders a label row with its submit button.
     */
    public function testToggleTransactionTypeButtonRenders()
    {
        $this->requireComponent('ToggleTransactionTypeButton.php');

        $this->assertTrue(class_exists('Ksfraser\FaBankImport\ToggleTransactionTypeButton'));
        $this->assertContains(
            'Ksfraser\HTML\HtmlElementInterface',
            class_implements('Ksfraser\FaBankImport\ToggleTransactionTypeButton'),
            'ToggleTransactionTypeButton must implement HtmlElementInterface'
        );

        $button = new \Ksfraser\FaBankImport\ToggleTransactionTypeButton(7);

        ob_start();
        $button->toHTML();
        $output = ob_get_clean();

        $this->assertStringContainsString('<tr>', $output, 'Must render a table row');
        $this->assertStringContainsString('Toggle Transaction Type', $output, 'Must render the label');
        $this->assertStringContainsString('ToggleTransaction[7]', $output, 'Must render the named submit button');
        $this->assertSame($output, $button->getHTML(), 'getHTML() must match toHTML() output');
    }

    /**
     * @test
     * TransType maps transactionDC values to their display labels.
     */
    public function testTransTypeLabelMapping()
    {
        $this->requireComponent('TransType.php');

        $this->assertTrue(class_exists('Ksfraser\FaBankImport\TransType'));

        $expectations = [
            'C' => 'Credit',
            'B' => 'Bank Transfer',
            'D' => 'Debit',
        ];

        foreach ($expectations as $dc => $expectedLabel) {
            $lineitem = new \stdClass();
            $lineitem->transactionDC = $dc;

            $view = new \Ksfraser\FaBankImport\TransType($lineitem);
            $html = $view->getHtml();

            $this->assertStringContainsString($expectedLabel, $html,
                "transactionDC '$dc' should display as '$expectedLabel'");
            $this->assertStringContainsString('<tr>', $html, 'Must render a table row');
        }
    }
}
