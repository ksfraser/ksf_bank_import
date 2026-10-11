<?php
/**
 * PartnerTypeSelectorView Test
 * 
 * Tests for the PartnerTypeSelectorView component that renders a partner type
 * dropdown selector using HTML library classes.
 * 
 * @package Ksfraser
 * @subpackage Tests
 */

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\Views\PartnerTypeSelectorView;

class PartnerTypeSelectorViewTest extends TestCase
{
    /**
     * Test that render() returns an HtmlFragment
     */
    public function testRenderReturnsHtmlFragment()
    {
        $data = [
            'id' => 123,
            'selected_value' => 'vendor',
            'options' => [
                'vendor' => 'Vendor/Supplier',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => true
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $fragment = $view->render();
        
        $this->assertInstanceOf(\Ksfraser\HTML\HtmlFragment::class, $fragment);
    }
    
    /**
     * Test that display() outputs HTML
     */
    public function testDisplayOutputsHtml()
    {
        $data = [
            'id' => 456,
            'selected_value' => 'customer',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        
        ob_start();
        $view->display();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Partner:', $output);
    }
    
    /**
     * Test selector contains correct name attribute
     */
    public function testSelectorContainsCorrectName()
    {
        $data = [
            'id' => 789,
            'selected_value' => 'vendor',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // Should contain select element with correct name
        $this->assertStringContainsString('name="partnerType[789]"', $html);
        $this->assertStringContainsString('<select', $html);
    }
    
    /**
     * Test selector has correct selected value
     */
    public function testSelectorHasCorrectSelection()
    {
        $data = [
            'id' => 321,
            'selected_value' => 'customer',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer',
                'employee' => 'Employee'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // Customer option should be selected
        $this->assertMatchesRegularExpression('/<option[^>]*value="customer"[^>]*selected/', $html);
    }
    
    /**
     * Test all options are rendered
     */
    public function testAllOptionsRendered()
    {
        $data = [
            'id' => 111,
            'selected_value' => 'vendor',
            'options' => [
                'vendor' => 'Vendor/Supplier',
                'customer' => 'Customer/Debtor',
                'employee' => 'Employee',
                'other' => 'Other'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // All option values should be present
        $this->assertStringContainsString('value="vendor"', $html);
        $this->assertStringContainsString('value="customer"', $html);
        $this->assertStringContainsString('value="employee"', $html);
        $this->assertStringContainsString('value="other"', $html);
        
        // All option labels should be present
        $this->assertStringContainsString('Vendor/Supplier', $html);
        $this->assertStringContainsString('Customer/Debtor', $html);
        $this->assertStringContainsString('Employee', $html);
        $this->assertStringContainsString('Other', $html);
    }
    
    /**
     * Test select_submit adds onchange attribute
     */
    public function testSelectSubmitAddsOnchange()
    {
        $data = [
            'id' => 222,
            'selected_value' => 'vendor',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => true
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // Should have onchange to submit form
        $this->assertStringContainsString('onchange=', $html);
        $this->assertStringContainsString('submit()', $html);
    }
    
    /**
     * Test without select_submit has no onchange
     */
    public function testWithoutSelectSubmitNoOnchange()
    {
        $data = [
            'id' => 333,
            'selected_value' => 'customer',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // Should NOT have onchange
        $this->assertStringNotContainsString('onchange=', $html);
    }
    
    /**
     * Test HTML special characters are escaped in options
     */
    public function testHtmlEscapingInOptions()
    {
        $data = [
            'id' => 444,
            'selected_value' => 'vendor',
            'options' => [
                'vendor' => 'Vendor <Test>',
                'customer' => 'Customer & Co.'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // HTML should be escaped
        $this->assertStringContainsString('&lt;Test&gt;', $html);
        $this->assertStringContainsString('&amp; Co.', $html);
    }
    
    /**
     * Test label is rendered correctly
     */
    public function testLabelRendered()
    {
        $data = [
            'id' => 555,
            'selected_value' => 'vendor',
            'options' => [
                'vendor' => 'Vendor'
            ],
            'label' => 'Partner Type:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        $this->assertStringContainsString('Partner Type:', $html);
    }
    
    /**
     * Test composability - render() can be added to another fragment
     */
    public function testComposability()
    {
        $data = [
            'id' => 666,
            'selected_value' => 'customer',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $fragment = $view->render();
        
        // Should be able to add to another fragment
        $parentFragment = new \Ksfraser\HTML\HtmlFragment();
        $parentFragment->addChild($fragment);
        
        $html = $parentFragment->getHtml();
        $this->assertStringContainsString('partnerType[666]', $html);
        $this->assertStringContainsString('Partner:', $html);
    }
    
    /**
     * Test empty selected value
     */
    public function testEmptySelectedValue()
    {
        $data = [
            'id' => 777,
            'selected_value' => '',
            'options' => [
                'vendor' => 'Vendor',
                'customer' => 'Customer'
            ],
            'label' => 'Partner:',
            'select_submit' => false
        ];
        
        $view = new PartnerTypeSelectorView($data);
        $html = $view->render()->getHtml();
        
        // Should not have any selected option
        $this->assertStringNotContainsString('selected', $html);
    }
}
