<?php
namespace Ksfraser\HTML;

use Ksfraser\HTML\Elements\HtmlHtml;

class HtmlDoc {
    /**
     * @var HtmlHtml
     */
    protected $html = null;

    public function __construct(?HtmlHtml $html = null) {
        if( null !== $html ) {
            $this->addHtml($html);
        }
    }

    public function addHtml(HtmlHtml $html) {
        $this->html = $html;
        return $this;
    }

        public function addNested(HtmlHtml $html) {
        $this->addHtml( $html );
        return $this;
    }

    public function getHtml(): string {
        return "<!DOCTYPE html>\n" . $this->html->getHtml();
    }

    /**
     * Echo the HTML document (for interface consistency)
     */
    public function toHtml(): void {
        echo $this->getHtml();
    }
}
