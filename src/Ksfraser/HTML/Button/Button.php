<?php
namespace Ksfraser\HTML\Button;

use Ksfraser\HTML\Button\HtmlButton;
use Ksfraser\HTML\JS\HtmlJsEventTrait;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * Button - HTML <button> element abstraction
 *
 * Usage:
 * - (new Button('Click Me'))->addClass('btn-primary')->setType('submit')->render()
 *
 * @package Ksfraser\HTML\Button
 */
class Button extends HtmlButton {
        use \Ksfraser\HTML\JS\HtmlJsEventTrait;
    /**
     * Constructor
     * @param string|HtmlElementInterface|null $text Optional button text/content
     */
    public function __construct($text = null) {
        parent::__construct();
        $this->setTag('button');
        if ($text !== null) {
            if (is_string($text)) {
                $text = new HtmlString(htmlspecialchars($text));
            }
            $this->addNested($text);
        }
    }

    /**
     * Set the text/content of the button
     * @param string|HtmlElementInterface $text
     * @return self
     */
    public function setText($text): self {
        $this->nested = [];
        if (is_string($text)) {
            $text = new HtmlString(htmlspecialchars($text));
        }
        $this->addNested($text);
        return $this;
    }

    /**
     * Add a CSS class to the button
     * @param string $class
     * @return self
     */
    public function addClass(string $class): self {
        $this->addCSSClass($class);
        return $this;
    }

    /**
     * Set the button type attribute
     * @param string $type
     * @return self
     */
    public function setType(string $type): self {
        $this->setAttribute('type', $type);
        return $this;
    }

    /**
     * Add an HTML attribute
     * @param string $name
     * @param string $value
     * @return self
     */
    // setAttribute removed because of Trait

    /**
     * Get HTML representation as string
     * @return string
     */
    public function getHtml(): string {
        return parent::getHtml();
    }

    /**
     * Render the button to HTML string
     * @return string
     */
    public function render(): string {
        return $this->getHtml();
    }
        /**
     * Set the name attribute
     * @param string $name
     * @return self
     */
    public function setName(string $name): self {
        $this->setAttribute('name', $name);
        return $this;
    }
}
