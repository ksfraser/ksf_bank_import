<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlOrderedList extends HtmlElement
{
	// Can accept array of items or HtmlElementInterface
	function __construct($data)
	{
		parent::__construct();
		$this->tag = "ol";
		if (is_array($data)) {
			foreach ($data as $item) {
				if ($item instanceof HtmlElementInterface) {
					$this->addNested($item);
				} else {
					$this->addNested(new HtmlListItem(new HtmlString((string)$item)));
				}
			}
		} elseif ($data instanceof HtmlElementInterface) {
			$this->addNested($data);
		}
	}
}
