<?php

namespace Tests\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\Form\Input\HtmlTextInput;
use Ksfraser\HTML\Elements\Form\Input\HtmlRadioInput;
use Ksfraser\HTML\Elements\Form\Input\HtmlCheckboxInput;

/**
 * Unit tests for form input type elements
 *
 * @BABOK Related: ksf_bank_import HTML refactor (PEAR-style element grouping)
 * @author Kevin Fraser
 * @since 20260822
 */
class FormInputTypesTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_text_input_with_type_text(): void
    {
        $input = new HtmlTextInput();

        $this->assertInstanceOf(HtmlTextInput::class, $input);
        $this->assertStringContainsString('type="text"', $input->getHtml());
    }

    /**
     * @test
     */
    public function it_sets_name_and_value_on_text_input(): void
    {
        $input = (new HtmlTextInput())
            ->setName('stock_id')
            ->setValue('ITEM<1>');

        $html = $input->getHtml();

        $this->assertStringContainsString('name="stock_id"', $html);
        $this->assertStringContainsString('value="ITEM&lt;1&gt;"', $html);
    }

    /**
     * @test
     */
    public function it_creates_radio_input_with_type_radio(): void
    {
        $input = new HtmlRadioInput();

        $this->assertStringContainsString('type="radio"', $input->getHtml());
    }

    /**
     * @test
     */
    public function it_checks_radio_input(): void
    {
        $input = (new HtmlRadioInput())->setChecked(true);

        $this->assertStringContainsString('checked', $input->getHtml());

        $unchecked = (new HtmlRadioInput())->setChecked(false);
        $this->assertStringNotContainsString('checked', $unchecked->getHtml());
    }

    /**
     * @test
     */
    public function it_creates_checkbox_input_with_type_checkbox(): void
    {
        $input = new HtmlCheckboxInput();

        $this->assertStringContainsString('type="checkbox"', $input->getHtml());
    }

    /**
     * @test
     */
    public function it_defaults_checkbox_unchecked(): void
    {
        $this->assertStringNotContainsString('checked', (new HtmlCheckboxInput())->getHtml());
    }
}
