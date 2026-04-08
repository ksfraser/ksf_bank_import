<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlString
 *
 * This class is a simple wrapper for a string to implement the HtmlElementInterface.
 *
 * NOTE: HtmlNameValue extends this class because their implementations are currently identical.
 * If HtmlString is refactored or its implementation changes, HtmlNameValue should be re-examined for correctness.
 *
 * NOTE: For semantic clarity, use HtmlJSString when representing JavaScript content in HTML elements (e.g., <script> tags).
 */
class HtmlString implements HtmlElementInterface
{

        protected $string;

        public function __construct( $string )
        {
                $this->string = $string;
        }
        /**
         * Renders the object in HTML.
         * The Html is echoed directly into the output.
         * 
         * @return void
         */
        public function toHtml(): void {
                echo $this->getHtml();
        }
        
        /**
         * Get HTML representation as string
         * 
         * @return string HTML-escaped string
         */
        public function getHtml(): string
        {
                //A HTML string doesn't have tags, attributes, styles, etc.
                //Escape the string to prevent XSS vulnerabilities.
                return htmlspecialchars($this->string, ENT_QUOTES, 'UTF-8');
        }
}
