<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

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
         */
        public function toHtml() {
                echo $this->getHtml();
        }
        public function getHtml()
        {
                //A HTML string doesn't have tags, attributes, styles, etc.
                //Escape the string to prevent XSS vulnerabilities.
                return htmlspecialchars($this->string, ENT_QUOTES, 'UTF-8');
        }
}
// This class is a simple wrapper for a string to implement the HtmlElementInterface.