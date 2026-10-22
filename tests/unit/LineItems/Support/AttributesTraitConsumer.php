<?php

declare(strict_types=1);

/**
 * Consumer of the vendored Mouf HTML attribute trait.
 *
 * The trait in src/Ksfraser/HTML/HtmlAttributesTrait.php is not referenced by
 * any production class, so it can only be exercised through a class that
 * uses it. This consumer exposes the trait's protected renderer.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

use Mouf\Html\Tags\AttributesTrait;

/**
 * Exposes the protected members of the vendored attribute trait.
 */
class AttributesTraitConsumer
{
    use AttributesTrait;

    /**
     * Render the attributes collected so far.
     *
     * @return string Rendered attribute string.
     */
    public function renderAttributes(): string
    {
        return $this->getAttributes();
    }

    /**
     * Replace the whole attribute bag.
     *
     * @param array $attributes Attribute bag.
     * @return void
     */
    public function setAttributeBag(array $attributes): void
    {
        $this->attributes = $attributes;
    }
}
