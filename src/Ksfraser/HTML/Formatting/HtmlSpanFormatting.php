<?php
namespace Ksfraser\HTML\Formatting;

use Ksfraser\HTML\HtmlElement;

class HtmlSpanFormatting extends HtmlElement {
	public function __construct(\Ksfraser\HTML\HtmlElementInterface $content) {
		parent::__construct($content);
		$this->setTag('span');
	}
}
