<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML\Support;

use Ksfraser\HTML\HTMLChildrenTrait;

/**
 * Consumer of the vendored HTML children trait.
 *
 * The trait in src/Ksfraser/HTML/HTMLChildrenTrait.php is not referenced by
 * any production class, so it can only be exercised through a class that
 * uses it. This consumer exposes its protected renderers.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML\Support
 * @license    MIT
 */
final class HtmlChildrenConsumer
{
    use HTMLChildrenTrait;

    /**
     * Render the children straight to the output.
     *
     * @return string The rendered markup.
     */
    public function renderChildrenOutput(): string
    {
        return $this->renderChildren();
    }

    /**
     * Render the children into a string.
     *
     * @return string The rendered markup.
     */
    public function renderChildrenMarkup(): string
    {
        return $this->renderChildrenHtml();
    }
}
