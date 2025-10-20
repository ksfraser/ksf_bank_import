<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HtmlSelect;
use Ksfraser\HTML\HtmlOption;

/**
 * HtmlSelectTest
 *
 * Tests for HtmlSelect class.
 *
 * @package    Ksfraser\Tests\Unit\HTML
 * @author     Claude AI Assistant
 * @since      20251020
 */
class HtmlSelectTest extends TestCase
{
    public function testConstruction(): void
    {
        $select = new HtmlSelect('field1');
        $this->assertInstanceOf(HtmlSelect::class, $select);
    }

    public function testGetHtmlBasic(): void
    {
        $select = new HtmlSelect('field1');
        $html = $select->getHtml();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="field1"', $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function testAddOption(): void
    {
        $select = new HtmlSelect('field1');
        $option = new HtmlOption('value1', 'Label 1');
        $select->addOption($option);

        $html = $select->getHtml();
        $this->assertStringContainsString('<option', $html);
        $this->assertStringContainsString('value="value1"', $html);
        $this->assertStringContainsString('Label 1', $html);
    }

    public function testAddOptionFluentInterface(): void
    {
        $select = new HtmlSelect('field1');
        $option = new HtmlOption('value1', 'Label 1');
        $result = $select->addOption($option);

        $this->assertSame($select, $result);
    }

    public function testAddMultipleOptions(): void
    {
        $select = new HtmlSelect('field1');
        $select->addOption(new HtmlOption('value1', 'Label 1'));
        $select->addOption(new HtmlOption('value2', 'Label 2'));
        $select->addOption(new HtmlOption('value3', 'Label 3'));

        $html = $select->getHtml();
        $this->assertStringContainsString('value="value1"', $html);
        $this->assertStringContainsString('Label 1', $html);
        $this->assertStringContainsString('value="value2"', $html);
        $this->assertStringContainsString('Label 2', $html);
        $this->assertStringContainsString('value="value3"', $html);
        $this->assertStringContainsString('Label 3', $html);
    }

    public function testAddOptionsFromArray(): void
    {
        $select = new HtmlSelect('field1');
        $data = [
            'value1' => 'Label 1',
            'value2' => 'Label 2',
            'value3' => 'Label 3',
        ];
        $select->addOptionsFromArray($data);

        $html = $select->getHtml();
        $this->assertStringContainsString('value="value1"', $html);
        $this->assertStringContainsString('Label 1', $html);
        $this->assertStringContainsString('value="value2"', $html);
    }

    public function testAddOptionsFromArrayWithSelectedValue(): void
    {
        $select = new HtmlSelect('field1');
        $data = [
            'value1' => 'Label 1',
            'value2' => 'Label 2',
            'value3' => 'Label 3',
        ];
        $select->addOptionsFromArray($data, 'value2');

        $html = $select->getHtml();
        $this->assertStringContainsString('selected', $html);
        // Check that selected appears in the right place (with value2)
        $this->assertMatchesRegularExpression('/value="value2"[^>]*selected/', $html);
    }

    public function testGetName(): void
    {
        $select = new HtmlSelect('field1');
        $this->assertEquals('field1', $select->getName());
    }

    public function testSetId(): void
    {
        $select = new HtmlSelect('field1');
        $select->setId('my-select');

        $html = $select->getHtml();
        $this->assertStringContainsString('id="my-select"', $html);
    }

    public function testSetIdFluentInterface(): void
    {
        $select = new HtmlSelect('field1');
        $result = $select->setId('my-select');

        $this->assertSame($select, $result);
    }

    public function testSetClass(): void
    {
        $select = new HtmlSelect('field1');
        $select->setClass('form-control');

        $html = $select->getHtml();
        $this->assertStringContainsString('class="form-control"', $html);
    }

    public function testSetMultiple(): void
    {
        $select = new HtmlSelect('field1');
        $select->setMultiple(true);

        $html = $select->getHtml();
        $this->assertStringContainsString('multiple', $html);
    }

    public function testSetSize(): void
    {
        $select = new HtmlSelect('field1');
        $select->setSize(5);

        $html = $select->getHtml();
        $this->assertStringContainsString('size="5"', $html);
    }

    public function testSetDisabled(): void
    {
        $select = new HtmlSelect('field1');
        $select->setDisabled(true);

        $html = $select->getHtml();
        $this->assertStringContainsString('disabled', $html);
    }

    public function testSetRequired(): void
    {
        $select = new HtmlSelect('field1');
        $select->setRequired(true);

        $html = $select->getHtml();
        $this->assertStringContainsString('required', $html);
    }

    public function testSetAttribute(): void
    {
        $select = new HtmlSelect('field1');
        $select->setAttribute('data-test', 'value123');

        $html = $select->getHtml();
        $this->assertStringContainsString('data-test="value123"', $html);
    }

    public function testToHtmlOutputsSelect(): void
    {
        $select = new HtmlSelect('field1');
        $select->addOption(new HtmlOption('value1', 'Label 1'));

        ob_start();
        $select->toHtml();
        $output = ob_get_clean();

        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('name="field1"', $output);
    }

    public function testEmptySelect(): void
    {
        $select = new HtmlSelect('field1');
        $html = $select->getHtml();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('</select>', $html);
        $this->assertStringNotContainsString('<option', $html);
    }

    public function testChainedFluentInterface(): void
    {
        $select = new HtmlSelect('field1');
        $select->setId('my-select')
               ->setClass('form-control')
               ->setRequired(true)
               ->addOption(new HtmlOption('value1', 'Label 1'))
               ->addOption(new HtmlOption('value2', 'Label 2'));

        $html = $select->getHtml();
        $this->assertStringContainsString('id="my-select"', $html);
        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('value="value1"', $html);
        $this->assertStringContainsString('value="value2"', $html);
    }

    public function testGetOptions(): void
    {
        $select = new HtmlSelect('field1');
        $option1 = new HtmlOption('value1', 'Label 1');
        $option2 = new HtmlOption('value2', 'Label 2');
        
        $select->addOption($option1);
        $select->addOption($option2);

        $options = $select->getOptions();
        $this->assertCount(2, $options);
        $this->assertSame($option1, $options[0]);
        $this->assertSame($option2, $options[1]);
    }

    public function testGetOptionCount(): void
    {
        $select = new HtmlSelect('field1');
        $this->assertEquals(0, $select->getOptionCount());

        $select->addOption(new HtmlOption('value1', 'Label 1'));
        $this->assertEquals(1, $select->getOptionCount());

        $select->addOption(new HtmlOption('value2', 'Label 2'));
        $this->assertEquals(2, $select->getOptionCount());
    }

    public function testNameIsEscaped(): void
    {
        $select = new HtmlSelect('field"><script>alert("XSS")</script>');
        $html = $select->getHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }
}
