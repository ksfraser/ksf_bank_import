<?php

namespace Ksfraser\HTML\Traits;

use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Trait for unified attribute handling (setAttribute, addAttribute)
 */
trait HtmlAttributeTrait {
    /**
    * @var \Ksfraser\HTML\HtmlAttributeList
     */
    protected $attributeList;

    /**
     * Initialize attribute list
     */
    protected function initAttributeList(): void {
        $this->attributeList = new \Ksfraser\HTML\HtmlAttributeList();
    }
    /**
     * Add an HTML attribute object (HtmlAttribute or subclass)
     * @param HtmlAttribute $attribute
     * @return self
     */
    public function addAttributeObject(HtmlAttribute $attribute): self {
        if (!isset($this->attributeList)) {
            $this->initAttributeList();
        }
        $this->attributeList->addAttributeObject($attribute);
        return $this;
    }
    /**
     * Add an HTML attribute by name and value (wraps setAttribute)
     * @param string $name
     * @param string|HtmlElementInterface $value
     * @return self
     */
    public function addAttribute($name, $value): self {
        return $this->setAttribute($name, $value);
    }

    /**
     * Convenience setter for an attribute by name/value.
     * Accepts string or HtmlElementInterface for value.
     * Converts string to HtmlString for storage.
     * @param string $name
     * @param string|HtmlElementInterface $value
     * @return self
     */
    public function setAttribute(string $name, $value): self {
        if (!$value instanceof HtmlString) {
            $value = new HtmlString($value);
        }
        $attr = new HtmlAttribute($name, $value);
        if (method_exists($this->attributeList, 'setAttribute')) {
            $this->attributeList->setAttribute($attr);
        } else {
            $this->addAttributeObject($attr);
        }
        return $this;
    }
}
