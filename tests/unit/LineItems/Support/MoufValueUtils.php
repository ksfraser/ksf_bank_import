<?php

declare(strict_types=1);

/**
 * Test double for the Mouf utility class the vendored HTML attribute trait
 * depends on.
 *
 * src/Ksfraser/HTML/HtmlAttributesTrait.php was copied from the Mouf HTML
 * library. It lives in the Mouf\Html\Tags namespace and calls
 * Mouf\Utils\Value\ValueUtils::val() to resolve lazy attribute values. The
 * Mouf package itself is not a dependency of this repository, so the single
 * method the trait uses is provided here.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Mouf\Utils\Value;

/**
 * Minimal stand-in for Mouf\Utils\Value\ValueUtils.
 */
class ValueUtils
{
    /**
     * Resolve a possibly lazy attribute value.
     *
     * A real ValueInterface implementation is asked for its value; anything
     * else is returned unchanged.
     *
     * @param mixed $value Attribute value.
     * @return mixed Resolved value.
     */
    public static function val($value)
    {
        if ($value instanceof \Mouf\Utils\Value\ValueInterface) {
            return $value->getValue();
        }

        return $value;
    }
}
