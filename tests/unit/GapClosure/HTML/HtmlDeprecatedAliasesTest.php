<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML;

use PHPUnit\Framework\TestCase;

/**
 * Coverage for the deprecated class aliases.
 *
 * The alias files under src/Ksfraser/HTML/Elements/ only declare an alias
 * for the class that took over their name. They were never loaded, because
 * nothing refers to the old names any more.
 */
final class HtmlDeprecatedAliasesTest extends TestCase
{
    /**
     * The deprecated names and the class each of them points at.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function aliasProvider(): array
    {
        return [
            'button' => [
                \Ksfraser\HTML\Elements\HtmlButton::class,
                \Ksfraser\HTML\Elements\Buttons\HtmlButton::class,
            ],
            'hidden' => [
                \Ksfraser\HTML\Elements\HtmlHidden::class,
                \Ksfraser\HTML\Elements\Form\Input\HtmlHidden::class,
            ],
            'input' => [
                \Ksfraser\HTML\Elements\HtmlInput::class,
                \Ksfraser\HTML\Elements\Form\Input\HtmlInput::class,
            ],
            'option' => [
                \Ksfraser\HTML\Elements\HtmlOption::class,
                \Ksfraser\HTML\Elements\Form\Input\HtmlOption::class,
            ],
            'select' => [
                \Ksfraser\HTML\Elements\HtmlSelect::class,
                \Ksfraser\HTML\Elements\Form\Input\HtmlSelect::class,
            ],
        ];
    }

    /**
     * @dataProvider aliasProvider
     * @param string $alias The deprecated class name.
     * @param string $target The class the alias points at.
     * @return void
     */
    public function testTheDeprecatedNameIsAnAliasOfItsReplacement(string $alias, string $target): void
    {
        $this->assertTrue(class_exists($alias), $alias . ' is expected to exist.');
        $this->assertTrue(
            is_a($alias, $target, true),
            $alias . ' is expected to be an alias of ' . $target . '.'
        );
    }
}
