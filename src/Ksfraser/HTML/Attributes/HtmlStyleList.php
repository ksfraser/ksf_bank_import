<?php

namespace Ksfraser\HTML\Attributes;

use Ksfraser\HTML\Attributes\HtmlAttributeValueObject;

/**
* HtmlStyleList represents the value for a style attribute as a collection of CSS property-value pairs.
* It is not a standalone HTML element, but an attribute value object for use in other elements.
* 
* Examples:
*   background-color
*   color
*   font-family
*   font-size
*   text-align
*/
class HtmlStyleList extends HtmlAttributeValueObject
{
    /**
     * Returns the CSS string for the style attribute
     */
    public function getStyleString(): string
    {
        if (count($this->attributeArray) === 0) {
            return '';
        }
        $value = '';
        foreach ($this->attributeArray as $style) {
            $value .= $style->getHtml();
        }
        return $value;
    }
    /**
     * Constructor
    * @param \Ksfraser\HTML\Elements\HtmlStyle|null $style Optional style to add on creation
     */
    public function __construct(?\Ksfraser\HTML\Elements\HtmlStyle $style = null)
    {
        $this->attributeName = 'style';
        $this->attributeArray = array();
        if ($style !== null) {
            $this->addAttribute($style);
        }
    }

    /**
     * Add a style to the list (type safe)
     */
    public function addAttribute($style): void
    {
        parent::addAttributeValueObject($style);
    }

    /**
     * Set (add or replace) a style in the list by property name (type safe)
     */
    public function setAttribute($style): void
    {
        $this->addAttribute($style);
    }

    protected function getAttributeValueString(): string
    {
        if (count($this->attributeArray) === 0) {
            return '';
        }
        $value = '';
        foreach ($this->attributeArray as $style) {
            $value .= $style->getHtml();
        }
        return $value;
    }
}
