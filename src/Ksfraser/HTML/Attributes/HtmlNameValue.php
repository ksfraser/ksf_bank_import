<?php

namespace Ksfraser\HTML\Attributes;

use Ksfraser\HTML\Elements\HtmlString;

/**
 * HtmlNameValue
 *
 * Represents a value object for HTML name attributes (e.g., <select name="...">).
 *
 * This class is functionally identical to HtmlString, but exists for semantic clarity and type safety.
 * It extends HtmlString to avoid code duplication. The only addition is getName(), which returns the raw string value.
 *
 * Use HtmlNameValue for attribute contexts (name="...") and HtmlString for element content.
 */
class HtmlNameValue extends HtmlString
{
    /**
     * Get the raw name value (unescaped)
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->string;
    }
}
