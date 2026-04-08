<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

class HtmlSection extends HtmlElement {
	public function __construct(\Ksfraser\HTML\HtmlElementInterface $content) {
		parent::__construct($content);
		$this->setTag('section');
	}
}
