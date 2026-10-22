<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class HtmlString implements HtmlElementInterface
{

        protected $string;

        /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        public function __construct( $string )
        {
                $this->string = $string;
        }
        /**
         * Renders the object in HTML.
         * The Html is echoed directly into the output.
         * 
         * @return void
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        public function toHtml(): void {
                echo $this->getHtml();
        }
        
        /**
         * Get HTML representation as string
         * 
         * @return string HTML-escaped string
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        public function getHtml(): string
        {
                //A HTML string doesn't have tags, attributes, styles, etc.
                //Escape the string to prevent XSS vulnerabilities.
                return htmlspecialchars($this->string, ENT_QUOTES, 'UTF-8');
        }
}
// This class is a simple wrapper for a string to implement the HtmlElementInterface.