<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlString;

/**
 * Stylesheet - Represents an external stylesheet link element
 * 
 * Generates: <link rel="stylesheet" href="path/to/style.css">
 * 
 * Single Responsibility: Render stylesheet link elements with proper formatting and security.
 * Extends HtmlElement to maintain consistency with HTML builder pattern.
 * 
 * @package Ksfraser\HTML\Elements
 */
class HtmlExternalCSS extends HtmlLink
{
    /**
     * Constructor
     * 
     * Initializes a stylesheet link element as an empty element (no closing tag).
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Set the stylesheet URL
     * 
     * Automatically applies security encoding and sets rel="stylesheet" attribute.
     * 
     * @param string $url The URL to the stylesheet file
     * @return self Fluent interface for chaining
     */
    public function setHref(string $url): self
    {
        // Encode URL for HTML attribute context
        $encodedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $this->addAttributeObject(new HtmlAttribute('href', $encodedUrl));
        return $this;
    }
    
    /**
     * Set rel attribute (should always be "stylesheet" for CSS)
     * 
     * @param string $rel The rel value (default: "stylesheet")
     * @return self Fluent interface for chaining
     */
    public function setRel(string $rel = 'stylesheet'): self
    {
        return parent::setRel($rel);
    }
}
