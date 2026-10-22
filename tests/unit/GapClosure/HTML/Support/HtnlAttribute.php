<?php

declare(strict_types=1);

namespace Ksfraser\HTML\Elements\Buttons;

/**
 * Stand-in for the misspelled attribute type the button helper asks for.
 *
 * Buttons\HtmlButton::setOnclick() declares its parameter as
 * "HtnlAttribute", a class that does not exist in the tree. The parameter
 * type is checked when the method is entered, so the method can only be
 * called at all when a class of that name is declared. The body of the
 * method then fails for a different reason (it builds the attribute with
 * one argument instead of two), which is why only the call itself is
 * covered here.
 */
class HtnlAttribute
{
    /** @var string */
    private $value;

    /**
     * @param string $value The attribute value.
     */
    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    /**
     * @return string The attribute value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string The attribute value.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
