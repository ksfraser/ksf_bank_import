<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use Ksfraser\PartnerSelectionPanel;
use Ksfraser\FormFieldNameGenerator;
use Ksfraser\PartnerTypes\PartnerTypeRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PartnerSelectionPanel
 *
 * Tests the partner type selection dropdown generation component.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Claude AI Assistant
 * @since      20251019
 * @version    1.0.0
 *
 * @covers     \Ksfraser\PartnerSelectionPanel
 */
class PartnerSelectionPanelTest extends TestCase
{
    /**
     * Test basic panel construction
     *
     * @since 20251019
     */
    public function testConstruction(): void
    {
        $panel = new PartnerSelectionPanel(123, 'SP');

        $this->assertInstanceOf(PartnerSelectionPanel::class, $panel);
    }

    /**
     * Test panel uses FormFieldNameGenerator internally
     *
     * @since 20251019
     */
    public function testUsesFieldNameGenerator(): void
    {
        $generator = new FormFieldNameGenerator();
        $panel = new PartnerSelectionPanel(123, 'SP', $generator);

        $this->assertInstanceOf(PartnerSelectionPanel::class, $panel);
    }

    /**
     * Test panel generates correct field name for partner type
     *
     * @since 20251019
     */
    public function testGeneratesCorrectFieldName(): void
    {
        $panel = new PartnerSelectionPanel(456, 'CU');

        $expectedFieldName = 'partnerType_456';
        $this->assertEquals($expectedFieldName, $panel->getFieldName());
    }

    /**
     * Test panel returns all available partner types
     *
     * @since 20251019
     */
    public function testReturnsAllPartnerTypes(): void
    {
        $panel = new PartnerSelectionPanel(100, 'SP');

        $types = $panel->getPartnerTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('SP', $types);
        $this->assertArrayHasKey('CU', $types);
        $this->assertArrayHasKey('BT', $types);
        $this->assertArrayHasKey('QE', $types);
        $this->assertArrayHasKey('MA', $types);
        $this->assertArrayHasKey('ZZ', $types);
    }

    /**
     * Test partner types are sorted by priority
     *
     * @since 20251019
     */
    public function testPartnerTypesAreSortedByPriority(): void
    {
        $panel = new PartnerSelectionPanel(100, 'SP');

        $types = $panel->getPartnerTypes();
        $codes = array_keys($types);

        // SP (10), CU (20), BT (30), QE (40), MA (50), ZZ (999)
        $this->assertEquals('SP', $codes[0]);
        $this->assertEquals('CU', $codes[1]);
        $this->assertEquals('BT', $codes[2]);
        $this->assertEquals('QE', $codes[3]);
        $this->assertEquals('MA', $codes[4]);
        $this->assertEquals('ZZ', $codes[5]);
    }

    /**
     * Test panel returns current selected partner type
     *
     * @since 20251019
     */
    public function testReturnsSelectedPartnerType(): void
    {
        $panel = new PartnerSelectionPanel(100, 'CU');

        $this->assertEquals('CU', $panel->getSelectedType());
    }

    /**
     * Test panel can change selected partner type
     *
     * @since 20251019
     */
    public function testCanChangeSelectedType(): void
    {
        $panel = new PartnerSelectionPanel(100, 'SP');
        $this->assertEquals('SP', $panel->getSelectedType());

        $panel->setSelectedType('BT');
        $this->assertEquals('BT', $panel->getSelectedType());
    }

    /**
     * Test panel validates selected partner type
     *
     * @since 20251019
     */
    public function testValidatesSelectedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type code');

        new PartnerSelectionPanel(100, 'INVALID');
    }

    /**
     * Test panel generates HTML for array_selector
     *
     * @since 20251019
     */
    public function testGeneratesHtmlForArraySelector(): void
    {
        $panel = new PartnerSelectionPanel(123, 'SP');

        $html = $panel->getHtml();

        $this->assertIsString($html);
        $this->assertStringContainsString('partnerType_123', $html);
        $this->assertStringContainsString('Supplier', $html);
        $this->assertStringContainsString('Customer', $html);
    }

    /**
     * Test panel includes select_submit option
     *
     * @since 20251019
     */
    public function testIncludesSelectSubmitOption(): void
    {
        $panel = new PartnerSelectionPanel(123, 'SP');

        $options = $panel->getArraySelectorOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('select_submit', $options);
        $this->assertTrue($options['select_submit']);
    }

    /**
     * Test panel can disable select_submit
     *
     * @since 20251019
     */
    public function testCanDisableSelectSubmit(): void
    {
        $panel = new PartnerSelectionPanel(123, 'SP');
        $panel->setSelectSubmit(false);

        $options = $panel->getArraySelectorOptions();

        $this->assertFalse($options['select_submit']);
    }

    /**
     * Test panel generates label_row compatible output
     *
     * @since 20251019
     */
    public function testGeneratesLabelRowOutput(): void
    {
        $panel = new PartnerSelectionPanel(123, 'SP');

        $output = $panel->getLabelRowOutput();

        $this->assertIsArray($output);
        $this->assertArrayHasKey('label', $output);
        $this->assertArrayHasKey('content', $output);
        $this->assertEquals('Partner:', $output['label']);
    }

    /**
     * Test panel can customize label
     *
     * @since 20251019
     */
    public function testCanCustomizeLabel(): void
    {
        $panel = new PartnerSelectionPanel(123, 'SP');
        $panel->setLabel('Partner Type:');

        $output = $panel->getLabelRowOutput();

        $this->assertEquals('Partner Type:', $output['label']);
    }

    /**
     * Test panel with zero ID
     *
     * @since 20251019
     */
    public function testPanelWithZeroId(): void
    {
        $panel = new PartnerSelectionPanel(0, 'SP');

        $this->assertEquals('partnerType_0', $panel->getFieldName());
    }

    /**
     * Test panel returns registry instance
     *
     * @since 20251019
     */
    public function testReturnsRegistryInstance(): void
    {
        $panel = new PartnerSelectionPanel(100, 'SP');

        $registry = $panel->getRegistry();

        $this->assertInstanceOf(PartnerTypeRegistry::class, $registry);
    }

    /**
     * Test panel uses custom registry if provided
     *
     * @since 20251019
     */
    public function testUsesCustomRegistry(): void
    {
        $customRegistry = PartnerTypeRegistry::getInstance();
        $panel = new PartnerSelectionPanel(100, 'SP', null, $customRegistry);

        $registry = $panel->getRegistry();

        $this->assertSame($customRegistry, $registry);
    }

    /**
     * Test static method gets partner types once per page
     *
     * @since 20251019
     */
    public function testStaticGetPartnerTypesArray(): void
    {
        // Should return same array structure as instance method
        $staticTypes = PartnerSelectionPanel::getPartnerTypesArray();
        $panel = new PartnerSelectionPanel(100, 'SP');
        $instanceTypes = $panel->getPartnerTypes();

        $this->assertEquals($instanceTypes, $staticTypes);
    }

    /**
     * Test static method returns cached result
     *
     * @since 20251019
     */
    public function testStaticMethodReturnsCachedArray(): void
    {
        $first = PartnerSelectionPanel::getPartnerTypesArray();
        $second = PartnerSelectionPanel::getPartnerTypesArray();

        // Should return the exact same array (same reference)
        $this->assertSame($first, $second);
    }

    /**
     * Test performance optimization with shared types
     *
     * @since 20251019
     */
    public function testMultiplePanelsCanSharePartnerTypesArray(): void
    {
        // Get shared array once
        $sharedTypes = PartnerSelectionPanel::getPartnerTypesArray();

        // Create multiple panels with different IDs (simulating multiple line items)
        $panel1 = new PartnerSelectionPanel(100, 'SP');
        $panel2 = new PartnerSelectionPanel(200, 'CU');
        $panel3 = new PartnerSelectionPanel(300, 'BT');

        // All panels should return same partner types structure
        $this->assertEquals($sharedTypes, $panel1->getPartnerTypes());
        $this->assertEquals($sharedTypes, $panel2->getPartnerTypes());
        $this->assertEquals($sharedTypes, $panel3->getPartnerTypes());
    }

    /**
     * Test static method can be used in page-level initialization
     *
     * @since 20251019
     */
    public function testStaticMethodForPageLevelInit(): void
    {
        // Simulate page-level code
        $optypes = PartnerSelectionPanel::getPartnerTypesArray();

        // Verify it has all expected keys
        $this->assertArrayHasKey('SP', $optypes);
        $this->assertArrayHasKey('CU', $optypes);
        $this->assertArrayHasKey('BT', $optypes);
        $this->assertArrayHasKey('QE', $optypes);
        $this->assertArrayHasKey('MA', $optypes);
        $this->assertArrayHasKey('ZZ', $optypes);

        // Verify it has correct labels
        $this->assertEquals('Supplier', $optypes['SP']);
        $this->assertEquals('Customer', $optypes['CU']);
    }
}
