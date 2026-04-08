<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlMeta extends HtmlElement {
    public function __construct(?HtmlString $content = null) {
        parent::__construct($content);
        $this->setTag('meta');
        // Meta tags are self-closing, so no content is needed
    }

    public function setAttribute(string $name, $value): \Ksfraser\HTML\HtmlElement {
        return parent::setAttribute($name, $value);
    }

    public function getHtml(): string {
        $attrs = trim($this->getAttributes());
        return '<meta' . ($attrs ? ' ' . $attrs : '') . ' />';
    }
}
