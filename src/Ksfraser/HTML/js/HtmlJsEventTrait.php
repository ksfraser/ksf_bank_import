<?php
namespace Ksfraser\HTML\JS;

use Ksfraser\HTML\HtmlAttribute;

/**
 * Trait for adding JavaScript event attributes to HTML elements
 * @since 20260221
 */
trait HtmlJsEventTrait {
    /**
     * Set the onclick attribute
     * @param HtmlAttribute $onclick
     * @return self
     */
    public function setOnclick(HtmlAttribute $onclick): self {
        $this->addAttributeObject(new HtmlAttribute('onclick', $onclick));
        return $this;
    }

    // Add more JS event methods as needed (e.g., setOnchange, setOnblur, etc.)
}
